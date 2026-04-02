<?php
require_once __DIR__ . '/BaseModel.php';

class OrderItem extends BaseModel
{
  protected $table = 'order_item';

  public function __construct($db)
  {
    $this->db = $db;
  }

  public function findById($id)
  {
    $result = parent::findById($id);
    if (!$result) {
      throw new Exception("Order item not found.", 404);
    }
    return $result;
  }

  public function save($data)
  {
    $this->validate($data);

    $activeOrder = [];
    $order_select_stmt = $this->db->query("SELECT * FROM orders WHERE status = 'open'");
    $productId = $data['product_code'];

    $categoryTax = $this->getCategoryTax($productId);
    $productAmount = $data['amount'];
    $productPrice = $this->getProductPrice($productId);
    $orderItemTotalTax = $this->calcOrderItemTotalTax($categoryTax, $productPrice, $productAmount);

    $orderItemTotalPrice = $orderItemTotalTax + ($productPrice * $productAmount);

    $order_items_stmt = $this->db->query("SELECT * FROM order_item");
    $orderItems = $order_items_stmt->fetch();

    $order_insert_stmt = $this->db->prepare("INSERT INTO orders (total, tax, business_code) VALUES (:total, :tax, :business_code)");
    $order_update_stmt = $this->db->prepare("UPDATE orders o
      SET total = :total, tax = :tax
      WHERE status = 'open'");

    $activeOrder = $order_select_stmt->fetch(PDO::FETCH_ASSOC);

    $insert_item_stmt = $this->db->prepare(
      "INSERT INTO order_item (order_code, product_code, amount, price, tax, business_code)
        VALUES (:order_code, :product_code, :amount, :price, :tax, :business_code)
        RETURNING *"
    );

    if (!$activeOrder) {
      $order_insert_stmt->execute([":total" => $orderItemTotalPrice,  ":tax" => $orderItemTotalTax, ":business_code" => parent::generateBusinessCode()]);
      $order_select_stmt = $this->db->query("SELECT * FROM orders o WHERE o.status = 'open'");
      $activeOrder = $order_select_stmt->fetch(PDO::FETCH_ASSOC);

      return $insert_item_stmt->execute([":order_code" => $activeOrder['code'], ":product_code" => $productId, "amount" => $productAmount, ":price" => $productPrice, ":tax" => $orderItemTotalTax, ":business_code" => $this->generateOrderItemBusinessCode()]);
    } else {
      $orderTotalPrice = $activeOrder['total'] + $orderItemTotalPrice;
      $orderTotalTax = $activeOrder['tax'] + $orderItemTotalTax;
      $order_update_stmt->execute([":total" => $orderTotalPrice, ":tax" => $orderTotalTax]);

      if ($orderItems && $this->isOrderItemRepeated($productId, $activeOrder['code'])) {
        $stmt = $this->db->prepare("SELECT * FROM order_item o
            WHERE o.product_code = :product_code AND o.order_code = :order_code");
        $stmt->execute([":product_code" => $data['product_code'], ":order_code" => $activeOrder['code']]);
        $existingOrderItem = $stmt->fetch(PDO::FETCH_ASSOC);
        $amountsAdded = $data['amount'] + $existingOrderItem['amount'];
        $newTotalTax = $this->calcOrderItemTotalTax($categoryTax, $productPrice, $data['amount']) + $existingOrderItem['tax'];

        $existing_item_stmt = $this->db->prepare(
          "UPDATE order_item o
            SET amount = :new_amount, tax = :new_total_tax
            WHERE product_code = :product_code"
        );

        return $existing_item_stmt->execute([":new_amount" => $amountsAdded, ":new_total_tax" => $newTotalTax, ":product_code" => $productId]);
      }
      return $insert_item_stmt->execute([":order_code" => $activeOrder['code'], ":product_code" => $productId, "amount" => $productAmount, ":price" => $productPrice, ":tax" => $orderItemTotalTax, ":business_code" => $this->generateOrderItemBusinessCode()]);
    }
  }

  public function delete($orderItemId)
  {
    $this->calculateOrderWhenItemDeleted($orderItemId);

    $check_existence_stmt = $this->db->prepare("SELECT code FROM order_item WHERE code = :id");
    $check_existence_stmt->execute([":id" => $orderItemId]);
    if (!$check_existence_stmt->fetchColumn()) {
      throw new Exception("Order item not found.", 404);
    }

    return parent::delete($orderItemId);
  }

  private function validate(array $data)
  {
    $productCode = $data['product_code'];
    $amount = $data['amount'];

    $product_stmt = $this->db->prepare("SELECT * FROM products WHERE code = :code");
    $product_stmt->execute([":code" => $productCode]);

    if ($product_stmt->rowCount() === 0) throw new Exception("Product doesn't exist.", 404);

    if ($amount < 1 || $amount > 10000 || !is_int($amount)) {
      throw new Exception("Amount must be an integer number between 1 and 10000 (ten thousand).", 400);
    }

    $verify_associated_product_existence = $this->db->prepare(
      "SELECT COUNT(*)
    FROM products
    WHERE code = :product_code"
    );

    $verify_associated_order_existence = $this->db->prepare(
      "SELECT COUNT(*)
    FROM orders
    WHERE code = :order_code"
    );

    $verify_associated_product_existence->execute([":product_code" => $productCode]);

    $this->verifyStockAvailability($data['product_code'], $data);

    if (!$verify_associated_product_existence->fetchColumn()) {
      throw new Exception("Product does not exist.", 404);
    }
  }

  public function generateOrderItemBusinessCode()
  {
    $stmt = $this->db->prepare("SELECT COALESCE(MAX(business_code) + 1, 1) FROM order_item");
    $stmt->execute();
    return $stmt->fetchColumn();
  }

  private function getCategoryTax(int $productId)
  {
    $search_category_tax = $this->db->prepare(
      "SELECT c.tax
      FROM categories c
      INNER JOIN products p
      ON c.code = p.category_code
      WHERE p.code = :product_code"
    );
    $search_category_tax->execute([":product_code" => $productId]);
    return $search_category_tax->fetchColumn();
  }

  private function getProductPrice(int $productId)
  {
    $search_product_price = $this->db->prepare(
      "SELECT p.price
        FROM products p
        WHERE p.code = :product_code"
    );
    $search_product_price->execute([":product_code" => $productId]);
    return $search_product_price->fetchColumn();
  }

  private function calcOrderItemTotalTax(float $taxPercent, float $unitPrice, int $amount)
  {
    return ($taxPercent / 100) * $unitPrice * $amount;
  }

  private function verifyStockAvailability(int $productId, array $orderItem)
  {
    $existing_item_amount_stmt = $this->db->prepare(
      "SELECT amount
      FROM order_item oi
      INNER JOIN orders o
      ON oi.order_code = o.code
      WHERE oi.product_code = :product_code
      AND o.status = 'open'"
    );
    $product_stmt = $this->db->prepare("SELECT * FROM products WHERE code = :code");
    $product_stmt->execute([":code" => $productId]);
    $product = $product_stmt->fetch(PDO::FETCH_ASSOC);

    $existing_item_amount_stmt->execute([":product_code" => $orderItem['product_code']]);
    $existingItemAmount = $existing_item_amount_stmt->fetchColumn();
    if ($product['amount'] < $orderItem['amount'] + $existingItemAmount) {
      if ($product['amount'] === 0) {
        throw new Exception("This product is out of stock.");
      }
      throw new Exception("This product has only " . (int)$product['amount'] . " itens in stock.");
    }
    return true;
  }

  private function isOrderItemRepeated($productId, $orderId)
  {
    $stmt = $this->db->prepare("SELECT * FROM order_item o WHERE o.product_code = :product_code AND o.order_code = :order_code");
    $stmt->execute([":product_code" => $productId, ":order_code" => $orderId]);
    if ($stmt->rowCount() > 0) return true;
    return false;
  }

  private function calculateOrderWhenItemDeleted(int $deletedItemId)
  {

    $item_stmt = $this->db->prepare("SELECT * FROM order_item o WHERE o.code = :code");
    $item_stmt->execute([":code" => $deletedItemId]);
    $itemToDelete = $item_stmt->fetch(PDO::FETCH_ASSOC);

    $itemTotalPrice = $itemToDelete['tax'] + ($itemToDelete['amount'] * $itemToDelete['price']);

    $order_stmt = $this->db->query("SELECT * FROM orders o WHERE o.status = 'open'");
    $activeOrder = $order_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$activeOrder) {
      throw new Exception("Error: No orders open.");
    }

    $orderTotalPrice = $activeOrder['total'] - $itemTotalPrice;
    $orderTotalTax = $activeOrder['tax'] - $itemToDelete['tax'];

    $order_update_stmt = $this->db->prepare("UPDATE orders o SET total = :total, tax = :tax WHERE o.code = :order_code");
    $order_update_stmt->execute([":total" => $orderTotalPrice, ":tax" => $orderTotalTax, ":order_code" => $itemToDelete['order_code']]);

    if ($order_update_stmt->rowCount() === 0) {
      throw new Exception("Error during total calculation, no rows affected.");
    }
  }
}

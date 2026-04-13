<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\BaseModel;
use App\Exceptions\ApiException;
use PDO;

class OrderItem extends BaseModel
{
  protected string $table = 'order_item';

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  public function list(): array
  {
    $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY code ASC");
    $order_stmt = $this->db->prepare(
      "SELECT o.status FROM orders o
    INNER JOIN order_item oi
    ON o.code = oi.order_code
    WHERE oi.code = :code
    "
    );
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($items as $index => $i) {
      $items[$index]['total'] = $this->getOrderItemTotalPrice(
        $i['tax'],
        $i['price'],
        $i['amount'],
      );
      $order_stmt->execute([":code" => $i['code']]);
      $order_status = $order_stmt->fetchColumn();
      $items[$index]['order_status'] = $order_status;
      $items[$index]['product_name'] = $this->getProductName($i['code']);
    }
    return $items;
  }

  /**
   * @param int $id
   * @return array<string, mixed>
   */
  public function findById(int $id): array
  {
    $result = parent::findById($id);
    if (!$result) {
      throw new ApiException("Order item not found.", 404);
    }
    return $result;
  }

  /**
   * @param array<string, mixed>
   * @return array<string, mixed>
   */
  public function save(array $data): array
  {
    $this->validate($data);

    $activeOrder = [];
    $order_select_stmt = $this->db->query(
      "SELECT * FROM orders
      WHERE status = 'open'"
    );
    $productId = $data['product_code'];

    $categoryTax = $this->getCategoryTax($productId);
    $productAmount = $data['amount'];
    $productPrice = $this->getProductPrice($productId);
    $orderItemTotalTax = $this->calcOrderItemTotalTax(
      $categoryTax,
      $productPrice,
      $productAmount
    );

    $orderItemTotalPrice = $this->getOrderItemTotalPrice(
      $orderItemTotalTax,
      $productPrice,
      $productAmount
    );

    $order_items_stmt = $this->db->query("SELECT * FROM order_item");
    $orderItems = $order_items_stmt->fetch();

    $order_insert_stmt = $this->db->prepare(
      "INSERT INTO orders (total, tax, business_code)
      VALUES (:total, :tax, :business_code)"
    );
    $order_update_stmt = $this->db->prepare(
      "UPDATE orders o
      SET total = :total, tax = :tax
      WHERE status = 'open'"
    );

    $activeOrder = $order_select_stmt->fetch(PDO::FETCH_ASSOC);

    $insert_item_stmt = $this->db->prepare(
      "INSERT INTO order_item
      (order_code, product_code, amount, price, tax, business_code)
        VALUES
        (:order_code, :product_code, :amount, :price, :tax, :business_code)
        RETURNING *"
    );

    if (!$activeOrder) {
      $order_insert_stmt->execute([
        ":total" => $orderItemTotalPrice,
        ":tax" => $orderItemTotalTax,
        ":business_code" => parent::generateBusinessCode()
      ]);
      $order_select_stmt = $this->db->query(
        "SELECT *
        FROM orders o
        WHERE o.status = 'open'"
      );
      $activeOrder = $order_select_stmt->fetch(PDO::FETCH_ASSOC);

      $insert_item_stmt->execute([
        ":order_code" => $activeOrder['code'],
        ":product_code" => $productId,
        "amount" => $productAmount,
        ":price" => $productPrice,
        ":tax" => $orderItemTotalTax,
        ":business_code" => $this->generateOrderItemBusinessCode()
      ]);

      $item = $insert_item_stmt->fetch(PDO::FETCH_ASSOC);
      $item['total'] = $this->getOrderItemTotalPrice(
        $item['tax'],
        $item['price'],
        $item['amount']
      );
      return $item;
    } else {
      $orderTotalPrice = $activeOrder['total'] + $orderItemTotalPrice;
      $orderTotalTax = $activeOrder['tax'] + $orderItemTotalTax;
      $order_update_stmt->execute([
        ":total" => $orderTotalPrice,
        ":tax" => $orderTotalTax
      ]);

      if ($orderItems && $this->isOrderItemRepeated($productId, $activeOrder['code'])) {
        $stmt = $this->db->prepare("SELECT *
        FROM order_item o
        WHERE o.product_code = :product_code
        AND o.order_code = :order_code");
        $stmt->execute([
          ":product_code" => $data['product_code'],
          ":order_code" => $activeOrder['code']
        ]);
        $existingOrderItem = $stmt->fetch(PDO::FETCH_ASSOC);
        $amountsAdded = $data['amount'] + $existingOrderItem['amount'];
        $newTotalTax = $this->calcOrderItemTotalTax(
          $categoryTax,
          $productPrice,
          $data['amount']
        ) + $existingOrderItem['tax'];

        $existing_item_stmt = $this->db->prepare(
          "UPDATE order_item o
            SET amount = :new_amount, tax = :new_total_tax
            WHERE product_code = :product_code
            RETURNING *"
        );

        $existing_item_stmt->execute([
          ":new_amount" => $amountsAdded,
          ":new_total_tax" => $newTotalTax,
          ":product_code" => $productId
        ]);
        $item = $existing_item_stmt->fetch(PDO::FETCH_ASSOC);
        $item['total'] = $this->getOrderItemTotalPrice(
          $item['tax'],
          $item['price'],
          $item['amount']
        );
        return $item;
      }
      $insert_item_stmt->execute([
        ":order_code" => $activeOrder['code'],
        ":product_code" => $productId,
        "amount" => $productAmount,
        ":price" => $productPrice,
        ":tax" => $orderItemTotalTax,
        ":business_code" => $this->generateOrderItemBusinessCode()
      ]);
      $item = $insert_item_stmt->fetch(PDO::FETCH_ASSOC);
      $item['total'] = $this->getOrderItemTotalPrice(
        $item['tax'],
        $item['price'],
        $item['amount']
      );
      return $item;
    }
  }

  /**
   * @param int $orderItemId
   * @return void
   */
  public function delete(int $orderItemId): void
  {
    $this->calculateOrderWhenItemDeleted($orderItemId);

    $check_existence_stmt = $this->db->prepare(
      "SELECT code
      FROM order_item
      WHERE code = :id"
    );
    $check_existence_stmt->execute([":id" => $orderItemId]);
    if (!$check_existence_stmt->fetchColumn()) {
      throw new ApiException("Order item not found.", 404);
    }

    parent::delete($orderItemId);
  }


  /**
   * @param array<string, mixed>
   * @return void
   */
  private function validate(array $data): void
  {
    $productCode = $data['product_code'];
    $amount = $data['amount'];

    $product_stmt = $this->db->prepare(
      "SELECT *
      FROM products
      WHERE code = :code
      AND status = 'active'"
    );
    $product_stmt->execute([":code" => $productCode]);

    if ($product_stmt->rowCount() === 0) {
      throw new ApiException("Product doesn't exist.", 404);
    }
    if ($amount < 1 || $amount > 10000 || !filter_var($amount, FILTER_VALIDATE_INT)) {
      throw new ApiException(
        "Amount must be an integer number between 1 and 10000 (ten thousand).",
        400
      );
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
      throw new ApiException("Product does not exist.", 404);
    }
  }

  /**
   * @return int
   */
  public function generateOrderItemBusinessCode(): int
  {
    $stmt = $this->db->prepare(
      "SELECT COALESCE(MAX(business_code) + 1, 1)
    FROM order_item"
    );
    $stmt->execute();
    return $stmt->fetchColumn();
  }

  /**
   * @param int $orderItemId
   * @return string
   */
  private function getProductName(int $orderItemId): string
  {
    $stmt = $this->db->prepare(
      "SELECT p.name
    FROM products p
    INNER JOIN order_item oi
    ON p.code = oi.product_code
    WHERE oi.code = :code"
    );

    $stmt->execute([":code" => $orderItemId]);
    return $stmt->fetchColumn();
  }

  /**
   * @param int $productId
   * @return float
   */
  private function getCategoryTax(int $productId): float
  {
    $search_category_tax = $this->db->prepare(
      "SELECT c.tax
      FROM categories c
      INNER JOIN products p
      ON c.code = p.category_code
      WHERE p.code = :product_code"
    );
    $search_category_tax->execute([":product_code" => $productId]);
    return (float)$search_category_tax->fetchColumn();
  }

  /**
   * @param int @productId
   * @return float
  */
  private function getProductPrice(int $productId): float
  {
    $search_product_price = $this->db->prepare(
      "SELECT p.price
        FROM products p
        WHERE p.code = :product_code"
    );
    $search_product_price->execute([":product_code" => $productId]);
    return (float)$search_product_price->fetchColumn();
  }

  /**
   * @param float $taxPercent
   * @param float $unitPrice
   * @param int $amount
   * @return float
   */
  private function calcOrderItemTotalTax(
    float $taxPercent,
    float $unitPrice,
    int $amount
  ): float {
    return $result = ($taxPercent / 100) * $unitPrice * $amount;
    return (float)$result;
  }

  /**
   * @param mixed $totalTax
   * @param mixed $price
   * @param int $amount
   * @return float
   */
  private function getOrderItemTotalPrice(mixed $totalTax, mixed $price, int $amount): float
  {
    $result =  $totalTax + ($price * $amount);
    return (float)$result;
  }

  /**
   * @param int $productId
   * @param array<string, mixed> $orderItem
   * @return bool
   */
  private function verifyStockAvailability(int $productId, array $orderItem): bool
  {
    $existing_item_amount_stmt = $this->db->prepare(
      "SELECT amount
      FROM order_item oi
      INNER JOIN orders o
      ON oi.order_code = o.code
      WHERE oi.product_code = :product_code
      AND o.status = 'open'"
    );
    $product_stmt = $this->db->prepare(
      "SELECT *
      FROM products
      WHERE code = :code"
    );
    $product_stmt->execute([":code" => $productId]);
    $product = $product_stmt->fetch(PDO::FETCH_ASSOC);

    $existing_item_amount_stmt->execute([
      ":product_code" => $orderItem['product_code']
    ]);
    $existingItemAmount = $existing_item_amount_stmt->fetchColumn();
    if ($product['amount'] < $orderItem['amount'] + $existingItemAmount) {
      if ($product['amount'] === 0) {
        throw new ApiException("This product is out of stock.");
      }
      throw new ApiException("This product has only " . (int)$product['amount'] . " items in stock.");
    }
    return true;
  }

  /**
   * @param int $productId
   * @param int $orderId
   * @return bool
   */
  private function isOrderItemRepeated(int $productId, int $orderId): bool
  {
    $stmt = $this->db->prepare(
      "SELECT *
      FROM order_item o
      WHERE o.product_code =
      :product_code
      AND o.order_code = :order_code"
    );
    $stmt->execute([":product_code" => $productId, ":order_code" => $orderId]);
    if ($stmt->rowCount() > 0) return true;
    return false;
  }

  /**
   * @param int $deleteItemId
   * @return void
   */
  private function calculateOrderWhenItemDeleted(int $deletedItemId): void
  {

    $item_stmt = $this->db->prepare("SELECT * FROM order_item o WHERE o.code = :code");
    $item_stmt->execute([":code" => $deletedItemId]);
    $itemToDelete = $item_stmt->fetch(PDO::FETCH_ASSOC);

    $itemTotalPrice = $itemToDelete['tax'] + ($itemToDelete['amount'] * $itemToDelete['price']);

    $order_stmt = $this->db->query("SELECT * FROM orders o WHERE o.status = 'open'");
    $activeOrder = $order_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$activeOrder) {
      throw new ApiException("Error: No orders open.");
    }

    $orderTotalPrice = $activeOrder['total'] - $itemTotalPrice;
    $orderTotalTax = $activeOrder['tax'] - $itemToDelete['tax'];

    $order_update_stmt = $this->db->prepare(
      "UPDATE orders o
      SET total = :total, tax = :tax
      WHERE o.code = :order_code"
    );
    $order_update_stmt->execute([
      ":total" => $orderTotalPrice,
      ":tax" => $orderTotalTax,
      ":order_code" => $itemToDelete['order_code']
    ]);

    if ($order_update_stmt->rowCount() === 0) {
      throw new ApiException("Error during total calculation, no rows affected.");
    }
  }
}

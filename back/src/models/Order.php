<?php
require_once __DIR__ . '/BaseModel.php';

class Order extends BaseModel
{
  protected $table = 'orders';

  public function __contruct(PDO $db)
  {
    $this->db = $db;
  }

  public function findById($id)
  {
    $result = parent::findById($id);
    if (!$result) {
      throw new Exception("Order not found.", 404);
    }
    return $result;
  }

  public function save($data)
  {
    $this->validate($data);

    $businessCode = parent::generateBusinessCode();
    $stmt = $this->db->prepare("INSERT INTO orders (total, tax, business_code) VALUES (:total, :tax, :business_code)");

    return $stmt->execute([":total" => (float)$data['total'], ":tax" => (float)$data['tax'], ":business_code" => $businessCode]);
  }

  public function finish($orderId)
  {
    $order_select_stmt = $this->db->prepare("SELECT * FROM orders o WHERE o.code = :code AND o.status = 'open'");
    $order_select_stmt->execute([":code" => $orderId]);
    $openOrder = $order_select_stmt->fetch(PDO::FETCH_ASSOC);
    if ($openOrder) {
      $openOrderId = $openOrder['code'];
      $order_item_select_stmt = $this->db->prepare("SELECT * FROM order_item oi WHERE oi.order_code = :order_code");
      $order_item_select_stmt->execute([":order_code" => $openOrderId]);
      $orderItems = $order_item_select_stmt->fetchAll(PDO::FETCH_ASSOC);

      if ($orderItems) {
        foreach ($orderItems as $item) {
          $this->discountStock($item);
        }
        $open_order_update_stmt = $this->db->prepare("UPDATE orders SET status = 'closed'");
        return $open_order_update_stmt->execute();
      }
    }
    throw new Exception("You dont have items in your order.");
  }

  public function delete($orderId)
  {
    $check_existence_stmt = $this->db->prepare("SELECT code FROM orders WHERE code = :id");
    $check_existence_stmt->execute([":id" => $orderId]);
    if (!$check_existence_stmt->fetchColumn()) {
      throw new Exception("Product not found.", 404);
    }

    $associated_registers_stmt = $this->db->prepare(
      "SELECT * FROM order_item oi
      WHERE oi.order_code = :code"
    );
    $associated_registers_stmt->execute([':code' => $orderId]);

    if ($associated_registers_stmt->fetch()) {
      throw new Exception("Can't delete, this item has associated registers.", 422);
    }

    return parent::softDelete($orderId);
  }

  private function validate(array $data)
  {
    $total = $data['total'];
    $tax = $data['tax'];

    if (!is_numeric($total)) {
      throw new Exception("Total must be a number.", 400);
    }

    if (!is_numeric($tax)) {
      throw new Exception("Tax must be a number.", 400);
    }
  }

  private function discountStock(array $orderItem)
  {
    $productId = $orderItem['product_code'];
    $orderItemAmount = $orderItem['amount'];

    $discount_statement = $this->db->prepare("UPDATE products p SET amount = amount - :item_amount WHERE p.code = :product_code");
    $discount_statement->execute([":item_amount" =>  $orderItemAmount, "product_code" => $productId]);
  }
}

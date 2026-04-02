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

    return parent::delete($orderId);
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
}

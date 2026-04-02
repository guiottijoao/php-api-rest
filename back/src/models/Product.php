<?php
require_once __DIR__ . '/BaseModel.php';

class Product extends BaseModel
{
  protected $table = 'products';

  public function __construct($db)
  {
    $this->db = $db;
  }

  public function findById($id)
  {
    $result = parent::findById($id);
    if (!$result) {
      throw new Exception("Product not found.", 404);
    }
    return $result;
  }

  public function save($data)
  {
    $this->validate($data);

    $stmt = $this->db->prepare("INSERT INTO products (name, amount, price, category_code, business_code) VALUES (:name, :amount, :price, :category_code, :business_code)");
    $stmt->bindValue(':name', $this->sanitize($data['name']), PDO::PARAM_STR);
    $stmt->bindValue(':amount', (int)$data['amount']);
    $stmt->bindValue(':price', (float)$data['price']);
    $stmt->bindValue(':category_code', (int)$data['category_code']);
    $stmt->bindValue(':business_code', parent::generateBusinessCode());

    return $stmt->execute();
  }

  public function delete($productId)
  {
    $check_existence_stmt = $this->db->prepare("SELECT code FROM products WHERE code = :id");
    $check_existence_stmt->execute([":id" => $productId]);
    if (!$check_existence_stmt->fetchColumn()) {
      throw new Exception("Product not found.", 404);
    }

    $associated_registers_stmt = $this->db->prepare(
      "SELECT * FROM order_item oi
      INNER JOIN orders o
      ON oi.order_code = o.code
      WHERE oi.product_code = :product_code
      AND o.status = 'open'"
    );
    $associated_registers_stmt->execute([':product_code' => $productId]);

    if ($associated_registers_stmt->fetch()) {
      throw new Exception("Can't delete, this item has associated registers.", 422);
    }

    return parent::softDelete($productId);
  }

  private function validate(array $data)
  {
    $name = $data['name'];
    $amount = $data['amount'];
    $price = $data['price'];
    $categoryCode = $data['category_code'];

    if (mb_strlen($name) > 20) {
      throw new Exception("Name cannot exceed 20 characters.");
    }

    if (!preg_match('/^[\p{L}\p{N}\s]+$/u', $name)) {
      throw new Exception("Name contains invalid characters.");
    }

    if ($this->nameExists($name)) {
      throw new Exception("Product with this name already exists.");
    }

    if ($amount < 1 || $amount > 10000) {
      throw new Exception("Amount must be a number between 1 and 10000 (ten thousand).");
    }

    if ($price < 0.1 || $price > 1000000000) {
      throw new Exception("Price must be a number between 0.1 and 1000000000 (one billion)");
    }

    $verify_fk_existence = $this->db->prepare("SELECT COUNT(*) FROM categories WHERE code = :category_code");
    $verify_fk_existence->execute([":category_code" => $categoryCode]);
    if (!$verify_fk_existence->fetchColumn()) {
      throw new Exception("Category does not exist.");
    }
  }
}

<?php
require_once __DIR__ . '/BaseModel.php';

class Category extends BaseModel
{
  protected $table = 'categories';

  public function __construct($db)
  {
    $this->db = $db;
  }

  // não precisa criar função list() porque não precisa tratar e ja tem na classe pai

  public function findById($id)
  {
    $result = parent::findById($id);
    if (!$result) {
      throw new Exception("Category not found.", 404);
    }
    return $result;
  }

  public function save($data)
  {
    $this->validate($data);
    $businessCode = parent::generateBusinessCode();

    $stmt = $this->db->prepare("INSERT INTO categories (name, tax, business_code) VALUES (:name, :tax, :business_code)");
    $stmt->bindValue(':name', parent::sanitize($data['name']), PDO::PARAM_STR);
    $stmt->bindValue(':tax', (float)$data['tax']);
    $stmt->bindValue(':business_code', $businessCode);

    return $stmt->execute();
  }

  public function delete($categoryId)
  {
    $check_existence_stmt = $this->db->prepare("SELECT code FROM categories WHERE code = :id");
    $check_existence_stmt->execute([":id" => $categoryId]);
    if (!$check_existence_stmt->fetchColumn()) {
      throw new Exception("Category not found.", 404);
    }

    $associated_registers_stmt = $this->db->query(
      "SELECT * FROM products p
      WHERE p.category_code = '$categoryId'
      AND p.status = 'active'"
    );
    if ($associated_registers_stmt->fetch()) {
      throw new Exception("Can't delete, this item has associated registers.", 422);
    }

    return parent::delete($categoryId);
  }

  private function validate(array $data)
  {
    $name = $data['name'];
    $tax = $data['tax'];

    if (mb_strlen($name) > 20) {
      throw new Exception("Category name cannot exceed 20 characters.", 400);
    }

    if (parent::nameExists($name)) {
      throw new Exception("A category with this name already exists.", 409);
    }

    if (!preg_match('/^[\p{L}\p{N}\s]+$/u', $name)) {
      throw new Exception("Name contains invalid characters.", 400);
    }

    if (!is_numeric($tax) || $tax < 0 || $tax > 100) {
      throw new Exception("Tax must be a number between 0 and 100", 400);
    }
  }
}

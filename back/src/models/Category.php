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

    $stmt = $this->db->prepare("INSERT INTO categories (name, tax, business_code) VALUES (:name, :tax, :business_code)");
    $stmt->bindValue(':name', $this->sanitize($data['name']), PDO::PARAM_STR);
    $stmt->bindValue(':tax', (float)$data['tax']);
    $stmt->bindValue(':business_code', $this->generateBusinessCode());

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

  private function generateBusinessCode()
  {
    $stmt = $this->db->query("SELECT COALESCE(MAX(business_code) + 1, 1) FROM categories WHERE status = 'active'");
    return $stmt->fetchColumn();
  }

  private function validate(array $data)
  {
    $name = $data['name'];

    $check_repeated_name_stmt = $this->db->prepare("SELECT name FROM categories where name = :name");
    $check_repeated_name_stmt->execute([":name" => $name]);
    if ($check_repeated_name_stmt->fetch()) throw new Exception("This category is already registered.", 422);

    if (mb_strlen($name) > 20) {
      throw new Exception("Category name cannot exceed 20 characters.", 400);
    }

    if ($this->nameExists($name)) {
      throw new Exception("A category with this name already exists.", 409);
    }
  }

  private function nameExists(string $name)
  {
    $trimmedName = trim($name);
    $normalizedName = str_replace(' ', '', $trimmedName);

    $query = "SELECT COUNT(*) FROM categories WHERE LOWER(REPLACE(name, ' ', '')) = LOWER(:normalizedName)";
    $stmt = $this->db->prepare($query);
    $stmt->bindValue(':normalizedName', $normalizedName);
    $stmt->execute();
    return $stmt->fetchColumn() > 0;
  }

  private function sanitize(string $string)
  {
    return htmlspecialchars(preg_replace('/\s+/', ' ', strip_tags(trim($string))));
  }
}

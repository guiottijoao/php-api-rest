<?php
class Category
{
  private $db;

  public function __construct($db)
  {
    $this->db = $db;
  }

  public function list()
  {
    $stmt = $this->db->prepare("SELECT * FROM categories");
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function save($data)
  {
    $this->validate($data);

    $stmt = $this->db->prepare("INSERT INTO categories (name, tax) VALUES (:name, :tax)");
    $stmt->bindValue(':name', $this->sanitize($data['name']), PDO::PARAM_STR);
    $stmt->bindValue(':tax', (float)$data['tax']);

    return $stmt->execute();
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

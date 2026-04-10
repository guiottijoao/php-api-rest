<?php

namespace App\models;

use App\exceptions\ApiException;
use App\models\BaseModel;
use PDO;

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
      throw new ApiException("Category not found.", 404);
    }
    return $result;
  }

  public function save($data)
  {
    $this->validate($data);
    $businessCode = parent::generateBusinessCode();

    $stmt = $this->db->prepare("INSERT INTO categories (name, tax, business_code) VALUES (:name, :tax, :business_code) RETURNING *");
    $stmt->bindValue(':name', parent::sanitize($data['name']), PDO::PARAM_STR);
    $stmt->bindValue(':tax', (float)$data['tax']);
    $stmt->bindValue(':business_code', $businessCode);

    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function delete($categoryId)
  {
    $check_existence_stmt = $this->db->prepare("SELECT code FROM categories WHERE code = :id");
    $check_existence_stmt->execute([":id" => $categoryId]);
    if (!$check_existence_stmt->fetchColumn()) {
      throw new ApiException("Category not found.", 404);
    }

    $associated_registers_stmt = $this->db->prepare(
      "SELECT * FROM products p
      WHERE p.category_code = :code
      AND p.status = 'active'"
    );
    $associated_registers_stmt->execute([":code" => $categoryId]);
    if ($associated_registers_stmt->fetch()) {
      throw new ApiException("Can't delete, this item has associated registers.", 422);
    }

    return parent::softDelete($categoryId);
  }

  private function validate(array $data)
  {
    $name = $data['name'];
    $tax = $data['tax'];

    if (mb_strlen($name) > 20) {
      throw new ApiException("Category name cannot exceed 20 characters.", 400);
    }

    if (parent::nameExists($name)) {
      throw new ApiException("A category with this name already exists.", 409);
    }

    if (!preg_match('/^[\p{L}\p{N}\s]+$/u', $name)  || !preg_match('/\p{L}/u', $name)) {
      throw new ApiException("Name can't contain special characters or be only numbers.", 400);
    }

    if (!is_numeric($tax) || $tax < 0 || $tax > 100) {
      throw new ApiException("Tax must be a number between 0 and 100", 400);
    }
  }
}

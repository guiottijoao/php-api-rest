<?php

declare(strict_types=1);

namespace App\Models;

use App\Exceptions\ApiException;
use App\Models\BaseModel;
use PDO;

class Product extends BaseModel
{
  protected string $table = 'products';

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
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($items as $index => $i) {
      $items[$index]['tax'] = $this->getTaxById($i['code']);
    }
    return $items;
  }

  /**
   * @param int $id
   * @return array<string, mixed>
   */
  public function findById(int $id): array
  {
    $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE code = :id");
    $stmt->execute([':id' => $id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$result) {
      throw new ApiException("Product not found.", 404);
    }
    $result['tax'] = $this->getTaxById($result['code']);
    return $result;
  }

  /**
   * @param array<string, mixed> $data
   * @return array<string, mixed>
   */
  public function save(array $data): array
  {
    $this->validate($data);

    $stmt = $this->db->prepare(
      "INSERT INTO products (name, amount, price, category_code, business_code)
      VALUES (:name, :amount, :price, :category_code, :business_code) RETURNING *"
    );
    $stmt->bindValue(':name', $this->sanitize($data['name']), PDO::PARAM_STR);
    $stmt->bindValue(':amount', (int)$data['amount']);
    $stmt->bindValue(':price', (float)$data['price']);
    $stmt->bindValue(':category_code', (int)$data['category_code']);
    $stmt->bindValue(':business_code', parent::generateBusinessCode());

    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function delete(int $productId): void
  {
    parent::verifyExistence($productId);

    parent::verifyAssociatedRegisters($productId, 'products');

    parent::softDelete($productId);
  }

  /**
   * @param array<string, mixed> $data
   * @return void
   */
  private function validate(array $data): void
  {
    $name = $data['name'];
    $amount = $data['amount'];
    $price = $data['price'];
    $categoryCode = $data['category_code'];

    if (mb_strlen($name) > 20) {
      throw new ApiException("Name cannot exceed 20 characters.");
    }

    if (!preg_match('/^[\p{L}\p{N}\s]+$/u', $name)  || !preg_match('/\p{L}/u', $name)) {
      throw new ApiException("Name can't contain special characters or be only numbers.", 400);
    }

    if ($this->nameExists($name)) {
      throw new ApiException("Product with this name already exists.");
    }

    if ($amount < 1 || $amount > 10000) {
      throw new ApiException(
        "Amount must be a number between 1 and 10000 (ten thousand)."
      );
    }

    if ($price < 0.1 || $price > 1000000000) {
      throw new ApiException(
        "Price must be a number between 0.1 and 1000000000 (one billion)"
      );
    }

    $verify_fk_existence = $this->db->prepare(
      "SELECT COUNT(*)
      FROM categories
      WHERE code = :category_code"
    );
    $verify_fk_existence->execute([":category_code" => $categoryCode]);
    if (!$verify_fk_existence->fetchColumn()) {
      throw new ApiException("Category does not exist.");
    }
  }

  private function getTaxById(int $id): float
  {
    $stmt = $this->db->prepare(
      "SELECT c.tax FROM categories c
    INNER JOIN products p ON
    c.code = p.category_code
    WHERE p.code = :code"
    );
    $stmt->execute([":code" => $id]);
    $result = $stmt->fetchColumn();
    return (float)$result;
  }
}

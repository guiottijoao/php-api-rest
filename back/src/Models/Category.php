<?php

declare(strict_types=1);

namespace App\Models;

use App\Exceptions\ApiException;
use App\Models\BaseModel;
use PDO;

class Category extends BaseModel
{
  protected string $table = 'categories';

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  /**
   * @param int $id
   * @return array<int, mixed>
   */
  public function findById(int $id): array
  {
    $result = parent::findById($id);
    if (!$result) {
      throw new ApiException("Category not found.", 404);
    }
    return $result;
  }

  /**
   * @param array<string, mixed>> $data
   * @return array<string, mixed>
   */
  public function save(array $data): array
  {
    $this->validate($data);
    $businessCode = parent::generateBusinessCode();

    $stmt = $this->db->prepare(
      "INSERT INTO categories (name, tax, business_code)
      VALUES (:name, :tax, :business_code) RETURNING *"
    );
    $stmt->bindValue(':name', parent::sanitize($data['name']), PDO::PARAM_STR);
    $stmt->bindValue(':tax', (float)$data['tax']);
    $stmt->bindValue(':business_code', $businessCode);

    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  /**
   * @param int $categoryId
   * @return void
   */
  public function delete(int $categoryId): void
  {
    parent::verifyExistence($categoryId);

    parent::verifyAssociatedRegisters($categoryId, 'categories');

    parent::softDelete($categoryId);
  }

  /**
   * @param array<string, mixed> $data
   * @return void
   */
  private function validate(array $data): void
  {
    $name = $data['name'];
    $tax = $data['tax'];

    if (mb_strlen($name) > $this->MAX_NAME_LEN) {
      throw new ApiException("Category name cannot exceed {$this->MAX_NAME_LEN} characters.", 400);
    }

    if (parent::nameExists($name)) {
      throw new ApiException("A category with this name already exists.", 409);
    }

    if (!preg_match($this->SPECIAL_CHAR_REGEX, $name)  || !preg_match($this->CONTAIN_LETTER_REGEX, $name)) {
      throw new ApiException("Name can't contain special characters or be only numbers.", 400);
    }

    if (!is_numeric($tax) || $tax < $this->TAX_MIN_VAL || $tax > $this->TAX_MAX_VAL) {
      throw new ApiException("Tax must be a number between 0 and 100", 400);
    }
  }
}
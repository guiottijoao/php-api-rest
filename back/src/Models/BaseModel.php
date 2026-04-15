<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Status;
use App\Exceptions\ApiException;
use PDO;

abstract class BaseModel
{
  protected PDO $db;
  protected string $table;
  protected int $MAX_NAME_LEN = 20;
  protected string $SPECIAL_CHAR_REGEX = '/^[\p{L}\p{N}\s]+$/u';
  protected string $CONTAIN_LETTER_REGEX = '/\p{L}/u';
  protected int $TAX_MIN_VAL = 0;
  protected int $TAX_MAX_VAL = 100;
  protected int $MIN_PRODUCT_AMOUNT = 1;
  protected int $MAX_PRODUCT_AMOUNT = 10000;
  protected float $MIN_PRICE = 0.1;
  protected int $MAX_PRICE = 1000000000;
  protected string $BUSINESS_CODE_SEQUENCE = 'COALESCE(MAX(business_code) + 1, 1';
  protected string $BLANK_SPACE_REGEX = '/\s+/';
  
  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  /**
   * @param int $id
   * @return array<string, mixed>|false
   */
  public function findById(int $id): array|false
  {
    $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE code = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  /**
   * @return<int, array<string, mixed>>
   */
  public function list(): array
  {
    $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY code ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function softDelete(int $id): void
  {
    $status = $this->table == 'orders' ? Status::CLOSED : Status::INACTIVE;
    $stmt = $this->db->prepare("UPDATE {$this->table} SET status = :status WHERE code = :code");
    $stmt->execute([':code' => $id, ':status' => $status]);
  }

  public function hardDelete(int $id): void
  {
    $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE code = :id");
    $stmt->execute([':id' => $id]);
  }

  public function generateBusinessCode(): int
  {
    $status = $this->table === 'orders' ? Status::OPEN : Status::ACTIVE;
    $table = $this->table === 'order_item' ? 'orders' : $this->table;
    $stmt = $this->db->prepare(
      "SELECT {$this->BUSINESS_CODE_SEQUENCE})
    FROM {$table}
    WHERE status = :status"
    );
    $stmt->execute([":status" => $status]);
    return $stmt->fetchColumn();
  }

  public function generateOrderItemBusinessCode(): int
  {
    $stmt = $this->db->prepare(
      "SELECT COALESCE(MAX(business_code) + 1, 1)
    FROM order_item"
    );
    $stmt->execute();
    return $stmt->fetchColumn();
  }

  public function sanitize(string $string): string
  {
    return htmlspecialchars(preg_replace($this->BLANK_SPACE_REGEX, ' ', strip_tags(trim($string))));
  }

  public function nameExists(string $name): bool
  {
    $trimmedName = trim($name);
    $normalizedName = str_replace(' ', '', $trimmedName);

    $stmt = $this->db->prepare(
      "SELECT COUNT(*)
    FROM {$this->table}
    WHERE status = :status
    AND LOWER(REPLACE(name, ' ', '')) = LOWER(:normalizedName)"
    );
    $stmt->execute([':normalizedName' => $normalizedName, ':status' => Status::ACTIVE]);
    return $stmt->fetchColumn() > 0;
  }

  public function verifyExistence(int $id): void
  {
    $check_existence_stmt = $this->db->prepare(
      "SELECT code
      FROM {$this->table}
      WHERE code = :id"
    );
    $check_existence_stmt->execute([":id" => $id]);
    if (!$check_existence_stmt->fetchColumn()) {
      throw new ApiException("{$this->table} not found.", 404);
    }
  }

  public function hasAssociatedRecord(int $id, string $table): void
  {
    if ($table === 'products') {
      $associated_registers_stmt = $this->db->prepare(
        "SELECT * FROM order_item oi
        INNER JOIN orders o
        ON oi.order_code = o.code
        WHERE oi.product_code = :product_code
        AND o.status = :status"
      );

      $associated_registers_stmt->execute([':product_code' => $id, ':status' => Status::OPEN]);
    } else if ($table === 'categories') {
      $associated_registers_stmt = $this->db->prepare(
        "SELECT * FROM products p
      WHERE p.category_code = :category_code
      AND p.status = :status"
      );

      $associated_registers_stmt->execute([":category_code" => $id, ':status' => Status::OPEN]);
    } else {
      $associated_registers_stmt = $this->db->prepare(
        "SELECT * FROM order_item oi
      WHERE oi.order_code = :code"
      );

      $associated_registers_stmt->execute([':code' => $id]);
    }

    if ($associated_registers_stmt->fetch()) {
      throw new ApiException("Can't delete, this item has associated registers.", 422);
    }
  }
}

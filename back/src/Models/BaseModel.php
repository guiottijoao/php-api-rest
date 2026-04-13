<?php

declare(strict_types=1);

namespace App\Models;

use App\Exceptions\ApiException;
use PDO;

abstract class BaseModel
{
  protected PDO $db;
  protected string $table;

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

  /**
   * @param int $id
   * @return void
   */
  public function softDelete(int $id): void
  {
    $status = $this->table == 'orders' ? 'closed' : 'inactive';
    $stmt = $this->db->prepare("UPDATE {$this->table} SET status = :status WHERE code = :code");
    $stmt->execute([':code' => $id, ':status' => $status]);
  }

  /**
   * @param int $id
   * @return void
   */
  public function hardDelete(int $id): void
  {
    $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE code = :id");
    $stmt->execute([':id' => $id]);
  }

  /**
   * @return int
   */
  public function generateBusinessCode(): int
  {
    $status = $this->table === 'orders' ? 'open' : 'active';
    $table = $this->table === 'order_item' ? 'orders' : $this->table;
    $stmt = $this->db->prepare(
      "SELECT COALESCE(MAX(business_code) + 1, 1)
    FROM {$table}
    WHERE status = :status"
    );
    $stmt->execute([":status" => $status]);
    return $stmt->fetchColumn();
  }

  /**
   * @param string $string
   * @return string
   */
  public function sanitize(string $string): string
  {
    return htmlspecialchars(preg_replace('/\s+/', ' ', strip_tags(trim($string))));
  }

  /**
   * @param string $name
   * @return bool
   */
  public function nameExists(string $name): bool
  {
    $trimmedName = trim($name);
    $normalizedName = str_replace(' ', '', $trimmedName);

    $stmt = $this->db->prepare(
      "SELECT COUNT(*)
    FROM {$this->table}
    WHERE status = 'active'
    AND LOWER(REPLACE(name, ' ', '')) = LOWER(:normalizedName)"
    );
    $stmt->execute([':normalizedName' => $normalizedName]);
    return $stmt->fetchColumn() > 0;
  }

  /**
   * @param int $id
   * @return void
   */
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

  /**
   * @param int $id
   * @param string $table
   * @return void
   */
  public function verifyAssociatedRegisters(int $id, string $table): void
  {
    if ($table === 'products') {
      $associated_registers_stmt = $this->db->prepare(
        "SELECT * FROM order_item oi
        INNER JOIN orders o
        ON oi.order_code = o.code
        WHERE oi.product_code = :product_code
        AND o.status = 'open'"
      );

      $associated_registers_stmt->execute([':product_code' => $id]);
    } else if ($table === 'categories') {
      $associated_registers_stmt = $this->db->prepare(
        "SELECT * FROM products p
      WHERE p.category_code = :category_code
      AND p.status = 'active'"
      );

      $associated_registers_stmt->execute([":category_code" => $id]);
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

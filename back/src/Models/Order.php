<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Status;
use App\Exceptions\ApiException;
use App\Models\BaseModel;
use PDO;

class Order extends BaseModel
{
  protected string $table = 'orders';

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  /**
   * @param int $id
   * @return array<string, mixed>
   */
  public function findById(int $id): array
  {
    $result = parent::findById($id);
    if (!$result) {
      throw new ApiException("Order not found.", 404);
    }
    return $result;
  }

  /**
   * @param array<string, mixed> $data
   * @return array<string, mixed>
   */
  public function save(array $data): array
  {
    $this->validate($data);

    $businessCode = parent::generateBusinessCode();
    $stmt = $this->db->prepare(
      "INSERT INTO orders (total, tax, business_code)
      VALUES (:total, :tax, :business_code) RETURNING *"
    );

    $stmt->execute([
      ":total" => (float)$data['total'],
      ":tax" => (float)$data['tax'],
      ":business_code" => $businessCode
    ]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
      throw new ApiException("Failed to create order.", 500);
    }
    return $result;
  }

  public function delete(int $orderId): void
  {
    parent::verifyExistence($orderId);

    parent::hasAssociatedRecord($orderId, 'orders');

    parent::softDelete($orderId);
  }

  /**
   * @param array<string, mixed> $data
   * @return void
   */
  private function validate(array $data): void
  {
    $total = $data['total'];
    $tax = $data['tax'];

    if (!is_numeric($total)) {
      throw new ApiException("Total must be a number.", 400);
    }

    if (!is_numeric($tax)) {
      throw new ApiException("Tax must be a number.", 400);
    }
  }

  /**
   * @return array<string, mixed>|false
   */
  public function findOpenOrder(): array|false
  {
    $orderSelectStmt = $this->db->prepare(
      "SELECT * FROM orders
      WHERE status = :status"
    );
    $orderSelectStmt->execute([":status" => Status::OPEN]);
    return $orderSelectStmt->fetch(PDO::FETCH_ASSOC);
  }

  /**
   * @return array<string, mixed>
   */
  public function insertNewOrder(float $itemTotalPrice, float $itemTotalTax): array
  {
    $orderInsertStmt = $this->db->prepare(
      "INSERT INTO orders (total, tax, business_code)
      VALUES (:total, :tax, :business_code)"
    );
    $orderInsertStmt->execute([
      ":total" => $itemTotalPrice,
      ":tax" => $itemTotalTax,
      ":business_code" => parent::generateBusinessCode()
    ]);
    return $orderInsertStmt->fetch(PDO::FETCH_ASSOC);
  }

  public function updateOrder(float $totalPrice, float $totalTax, string $status): void
  {
    $orderUpdateStmt = $this->db->prepare(
      "UPDATE orders o
        SET total = :total, tax = :tax
        WHERE status = :status"
    );

    $orderUpdateStmt->execute([
      ":total" => $totalPrice,
      ":tax" => $totalTax,
      ":status" => $status
    ]);
  }

  public function getOrderStatusFromItem(int $itemId): string
  {
    $orderStmt = $this->db->prepare(
      "SELECT o.status FROM orders o
    INNER JOIN order_item oi
    ON o.code = oi.order_code
    WHERE oi.code = :code
    "
    );
    $orderStmt->execute([":code" => $itemId]);
    return $orderStmt->fetchColumn();
  }

  public function resetOrder(int $orderId): void
  {
    $updateOrderTotalAndTaxStmt = $this->db->prepare(
      "UPDATE orders o
      SET total = 0, tax = 0 WHERE
      o.code = :order_code"
    );
    $updateOrderTotalAndTaxStmt->execute([":order_code" => $orderId]);
  }

  public function closeOrder(int $orderId): void
  {
    $openOrderUpdateStmt = $this->db->prepare(
      "UPDATE orders
        SET status = :status
        WHERE code = :code"
    );
    $openOrderUpdateStmt->execute([":status" => Status::CLOSED, ":code" => $orderId]);
  }
}

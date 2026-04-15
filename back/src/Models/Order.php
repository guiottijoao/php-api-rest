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

  public function finish(int $orderId): void
  {
    $order_select_stmt = $this->db->prepare(
      "SELECT * FROM orders o
      WHERE o.code = :code
      AND o.status = :status"
    );
    $order_select_stmt->execute([":code" => $orderId, ":status" => Status::OPEN]);
    $openOrder = $order_select_stmt->fetch(PDO::FETCH_ASSOC);
    if ($openOrder) {
      $openOrderId = $openOrder['code'];
      $order_item_select_stmt = $this->db->prepare(
        "SELECT *
        FROM order_item oi
        WHERE oi.order_code = :order_code"
      );
      $order_item_select_stmt->execute([":order_code" => $openOrderId]);
      $orderItems = $order_item_select_stmt->fetchAll(PDO::FETCH_ASSOC);

      if (!$orderItems) {
        throw new ApiException("You dont have items in your order.");
      }

      foreach ($orderItems as $item) {
        $this->discountStock($item);
      }

      $open_order_update_stmt = $this->db->prepare(
        "UPDATE orders
        SET status = :status
        WHERE code = :code"
      );
      $open_order_update_stmt->execute([":status" => Status::CLOSED, ":code" => $orderId]);
    }
  }

  public function cancel(int $orderId): void
  {
    $active_order_stmt = $this->db->prepare("SELECT *
    FROM orders o
    WHERE o.code = :code
    AND o.status = :status");
    $active_order_stmt->execute([":code" => $orderId, ":status" => Status::OPEN]);
    $order = $active_order_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) throw new ApiException("Order not found.", 404);

    $order_items_stmt = $this->db->prepare(
      "SELECT * FROM order_item
      WHERE order_code = :order_code"
    );
    $order_items_stmt->execute([":order_code" => $orderId]);
    $orderItems = $order_items_stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$orderItems) throw new ApiException("Order has no items.", 400);

    $delete_order_items_stmt = $this->db->prepare(
      "DELETE FROM order_item o
      WHERE o.order_code = :order_code"
    );
    $delete_order_items_stmt->execute([":order_code" => $order['code']]);

    $update_order_total_and_tax = $this->db->prepare(
      "UPDATE orders o
      SET total = 0, tax = 0 WHERE
      o.code = :order_code"
    );
    $update_order_total_and_tax->execute([":order_code" => $order['code']]);
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

  private function discountStock(array $orderItem): void
  {
    $productId = $orderItem['product_code'];
    $orderItemAmount = $orderItem['amount'];

    $discount_statement = $this->db->prepare(
      "UPDATE products p 
      SET amount = amount - :item_amount
      WHERE p.code = :product_code"
    );

    $discount_statement->execute([
      ":item_amount" =>  $orderItemAmount,
      ":product_code" => $productId
    ]);
  }

  /**
   * @return array<string, mixed>|false
   */
  public function findOpenOrder(): array|false
  {
    $order_select_stmt = $this->db->prepare(
      "SELECT * FROM orders
      WHERE status = :status"
    );
    $order_select_stmt->execute([":status" => Status::OPEN]);
    return $order_select_stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function insertNewOrder(float $itemTotalPrice, float $itemTotalTax): void
  {
    $order_insert_stmt = $this->db->prepare(
      "INSERT INTO orders (total, tax, business_code)
      VALUES (:total, :tax, :business_code)"
    );
    $order_insert_stmt->execute([
      ":total" => $itemTotalPrice,
      ":tax" => $itemTotalTax,
      ":business_code" => parent::generateBusinessCode()
    ]);
  }

  public function updateOrder(float $totalPrice, float $totalTax, string $status): void
  {
    $order_update_stmt = $this->db->prepare(
      "UPDATE orders o
        SET total = :total, tax = :tax
        WHERE status = :status"
    );

    $order_update_stmt->execute([
      ":total" => $totalPrice,
      ":tax" => $totalTax,
      ":status" => $status
    ]);
  }
}

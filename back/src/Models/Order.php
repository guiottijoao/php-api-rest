<?php

declare(strict_types=1);

namespace App\Models;

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

  /**
   * @param $orderId
   * @return void
   */
  public function finish(int $orderId): void
  {
    $order_select_stmt = $this->db->prepare(
      "SELECT * FROM orders o
      WHERE o.code = :code
      AND o.status = 'open'"
    );
    $order_select_stmt->execute([":code" => $orderId]);
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
        SET status = 'closed'"
      );
      $open_order_update_stmt->execute();
    }
  }

  /**
   * @param int $orderId
   * @return void
   */
  public function cancel(int $orderId): void
  {
    $active_order_stmt = $this->db->prepare("SELECT *
    FROM orders o
    WHERE o.code = :code
    AND o.status = 'open'");
    $active_order_stmt->execute([":code" => $orderId]);
    $order = $active_order_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) throw new ApiException("Order not found.", 404);

    $order_items_stmt = $this->db->query("SELECT * FROM order_item");
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

  /**
   * @param int $orderId
   * @return void
   */
  public function delete(int $orderId): void
  {
    $check_existence_stmt = $this->db->prepare(
      "SELECT code
      FROM orders
      WHERE code = :id"
    );
    $check_existence_stmt->execute([":id" => $orderId]);
    if (!$check_existence_stmt->fetchColumn()) {
      throw new ApiException("Product not found.", 404);
    }

    $associated_registers_stmt = $this->db->prepare(
      "SELECT * FROM order_item oi
      WHERE oi.order_code = :code"
    );
    $associated_registers_stmt->execute([':code' => $orderId]);

    if ($associated_registers_stmt->fetch()) {
      throw new ApiException("Can't delete, this item has associated registers.", 422);
    }

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
   * @param int $orderId
   * @return void
   */
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
}

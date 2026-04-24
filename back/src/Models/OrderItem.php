<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Status;
use App\Exceptions\ApiException;
use App\Models\BaseModel;
use App\Services\OrderItemService;
use App\Services\OrderService;
use PDO;

class OrderItem extends BaseModel
{
  protected string $table = 'order_item';
  private OrderService $orderService;
  private OrderItemService $orderItemService;

  public function __construct(PDO $db)
  {
    $this->db = $db;
    $this->orderService = new OrderService($db);
    $this->orderItemService = new OrderItemService($db);

    parent::__construct($db);
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  public function list(?string $status = null): array
  {
    $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY code ASC");

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($items as $index => $item) {
      $items[$index]['total'] = $this->appendTotal($item);
      $orderStatus = $this->getOrderStatusFromItem($item['code']);
      $items[$index]['order_status'] = $orderStatus;
      $items[$index]['product_name'] = $this->orderItemService->getProductName($item['code']);
    }
    return $items;
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

  /**
   * @param int $id
   * @return array<string, mixed>
   */
  public function findById(int $id): array
  {
    $result = parent::findById($id);
    if (!$result) {
      throw new ApiException("Order item not found.", 404);
    }
    return $result;
  }

  /**
   * @param array<string, mixed>
   * @return array<string, mixed>
   */
  public function insertNewItem(array $data): array
  {
    $insertItemStmt = $this->db->prepare(
      "INSERT INTO order_item
      (order_code, product_code, amount, price, tax, business_code)
        VALUES
        (:order_code, :product_code, :amount, :price, :tax, :business_code)
        RETURNING *"
    );

    $insertItemStmt->execute([
      ":order_code" => $data['order_code'],
      ":product_code" => $data['product_code'],
      ":amount" => $data['amount'],
      ":price" => $data['price'],
      ":tax" => $data['tax'],
      ":business_code" => $data['business_code']
    ]);

    $item = $insertItemStmt->fetch(PDO::FETCH_ASSOC);
    $item['total'] = $this->appendTotal($item);

    return $item;
  }

  /**
   * @return array<string, mixed>
   */
  public function updateExistingItemQuantity(int $orderId, float $amountsAdded, float $newTotalTax, int $productId)
  {
    $existingItemStmt = $this->db->prepare(
      "UPDATE order_item o
              SET amount = :new_amount, tax = :new_total_tax
              WHERE product_code = :product_code
              AND order_code = :order_code
              RETURNING *"
    );
    
    $existingItemStmt->execute([
      ":new_amount" => $amountsAdded,
      ":new_total_tax" => $newTotalTax,
      ":product_code" => $productId,
      ":order_code" => $orderId
    ]);

    $item = $existingItemStmt->fetch(PDO::FETCH_ASSOC);
    $item['total'] = $this->appendTotal($item);
    return $item;
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  public function findItemsByOrder(int $openOrderId): array
  {
    $orderItemSelectStmt = $this->db->prepare(
      "SELECT *
        FROM order_item oi
        WHERE oi.order_code = :order_code"
    );
    $orderItemSelectStmt->execute([":order_code" => $openOrderId]);
    return $orderItemSelectStmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * @return array<string, mixed>
   */
  public function findItemByOrderAndProduct(int $productId, int $orderId)
  {
    $stmt = $this->db->prepare("SELECT *
        FROM order_item o
        WHERE o.product_code = :product_code
        AND o.order_code = :order_code");
    $stmt->execute([
      ":product_code" => $productId,
      ":order_code" => $orderId
    ]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function deleteItemsByOrder(int $orderId): void
  {
    $deleteOrderItemsStmt = $this->db->prepare(
      "DELETE FROM order_item o
      WHERE o.order_code = :order_code"
    );
    $deleteOrderItemsStmt->execute([":order_code" => $orderId]);
  }

  public function delete(int $orderItemId): void
  {
    $this->orderService->calculateOrderWhenItemDeleted($orderItemId);

    parent::verifyExistence($orderItemId);

    parent::hardDelete($orderItemId);
  }

  /**
   * @param array<string, mixed> $data
   * @return void
   */
  public function validate(array $data): void
  {
    $productCode = $data['product_code'];
    $amount = $data['amount'];

    $productStmt = $this->db->prepare(
      "SELECT *
      FROM products
      WHERE code = :code
      AND status = :status"
    );
    $productStmt->execute([":code" => $productCode, ":status" => Status::ACTIVE]);

    if ($productStmt->rowCount() === 0) {
      throw new ApiException("Product doesn't exist.", 404);
    }
    if ($amount < $this->MIN_PRODUCT_AMOUNT || $amount > $this->MAX_PRODUCT_AMOUNT || !filter_var($amount, FILTER_VALIDATE_INT)) {
      throw new ApiException(
        "Amount must be an integer number between 1 and 10000 (ten thousand).",
        400
      );
    }

    $this->orderItemService->verifyStockAvailability($data['product_code'], $data);
  }

  private function appendTotal(array $item): float
  {
    $itemTotal = $this->orderItemService->getOrderItemTotalPrice(
      $item['tax'],
      $item['price'],
      $item['amount']
    );

    return $itemTotal;
  }
}

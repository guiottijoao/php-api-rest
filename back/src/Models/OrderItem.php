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
  public function list(): array
  {
    $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY code ASC");
    $order_stmt = $this->db->prepare(
      "SELECT o.status FROM orders o
    INNER JOIN order_item oi
    ON o.code = oi.order_code
    WHERE oi.code = :code
    "
    );
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($items as $index => $i) {
      $items[$index]['total'] = $this->appendTotal($i);
      $order_stmt->execute([":code" => $i['code']]);
      $order_status = $order_stmt->fetchColumn();
      $items[$index]['order_status'] = $order_status;
      $items[$index]['product_name'] = $this->orderItemService->getProductName($i['code']);
    }
    return $items;
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
    $insert_item_stmt = $this->db->prepare(
      "INSERT INTO order_item
      (order_code, product_code, amount, price, tax, business_code)
        VALUES
        (:order_code, :product_code, :amount, :price, :tax, :business_code)
        RETURNING *"
    );

    $insert_item_stmt->execute([
      ":order_code" => $data['order_code'],
      ":product_code" => $data['product_code'],
      ":amount" => $data['amount'],
      ":price" => $data['price'],
      ":tax" => $data['tax'],
      ":business_code" => $data['business_code']
    ]);

    $item = $insert_item_stmt->fetch(PDO::FETCH_ASSOC);
    $item['total'] = $this->appendTotal($item);

    return $item;
  }

  /**
   * @return array<string, mixed>
   */
  public function joinRepeatedItems(float $amountsAdded, float $newTotalTax, int $productId)
  {
    $existing_item_stmt = $this->db->prepare(
      "UPDATE order_item o
              SET amount = :new_amount, tax = :new_total_tax
              WHERE product_code = :product_code
              RETURNING *"
    );

    $existing_item_stmt->execute([
      ":new_amount" => $amountsAdded,
      ":new_total_tax" => $newTotalTax,
      ":product_code" => $productId
    ]);

    $item = $existing_item_stmt->fetch(PDO::FETCH_ASSOC);
    $item['total'] = $this->appendTotal($item);
    return $item;
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

    $product_stmt = $this->db->prepare(
      "SELECT *
      FROM products
      WHERE code = :code
      AND status = :status"
    );
    $product_stmt->execute([":code" => $productCode, ":status" => Status::ACTIVE]);

    if ($product_stmt->rowCount() === 0) {
      throw new ApiException("Product doesn't exist.", 404);
    }
    if ($amount < $this->MIN_PRODUCT_AMOUNT || $amount > $this->MAX_PRODUCT_AMOUNT || !filter_var($amount, FILTER_VALIDATE_INT)) {
      throw new ApiException(
        "Amount must be an integer number between 1 and 10000 (ten thousand).",
        400
      );
    }

    $verify_associated_product_existence = $this->db->prepare(
      "SELECT COUNT(*)
    FROM products
    WHERE code = :product_code"
    );

    $verify_associated_product_existence->execute([":product_code" => $productCode]);

    $this->orderItemService->verifyStockAvailability($data['product_code'], $data);

    if (!$verify_associated_product_existence->fetchColumn()) {
      throw new ApiException("Product does not exist.", 404);
    }
  }

  private function appendTotal(array $item): array
  {
    $item['total'] = $this->orderItemService->getOrderItemTotalPrice(
      $item['tax'],
      $item['price'],
      $item['amount']
    );

    return $item;
  }
}

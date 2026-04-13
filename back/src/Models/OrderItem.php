<?php

declare(strict_types=1);

namespace App\Models;

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
      $items[$index]['total'] = $this->orderItemService->getOrderItemTotalPrice(
        $i['tax'],
        $i['price'],
        $i['amount'],
      );
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
   * @param array<string, mixed> $data
   * @return array<string, mixed>
   */
  public function save(array $data): array
  {
    $this->validate($data);

    $activeOrder = [];
    $order_select_stmt = $this->db->query(
      "SELECT * FROM orders
      WHERE status = 'open'"
    );
    $productId = $data['product_code'];

    $categoryTax = $this->orderItemService->getCategoryTax($productId);
    $productAmount = $data['amount'];
    $productPrice = $this->orderItemService->getProductPrice($productId);
    $orderItemTotalTax = $this->orderItemService->calcOrderItemTotalTax(
      $categoryTax,
      $productPrice,
      $productAmount
    );

    $orderItemTotalPrice = $this->orderItemService->getOrderItemTotalPrice(
      $orderItemTotalTax,
      $productPrice,
      $productAmount
    );

    $order_items_stmt = $this->db->query("SELECT * FROM order_item");
    $orderItems = $order_items_stmt->fetch();

    $order_insert_stmt = $this->db->prepare(
      "INSERT INTO orders (total, tax, business_code)
      VALUES (:total, :tax, :business_code)"
    );
    $order_update_stmt = $this->db->prepare(
      "UPDATE orders o
      SET total = :total, tax = :tax
      WHERE status = 'open'"
    );

    $activeOrder = $order_select_stmt->fetch(PDO::FETCH_ASSOC);

    $insert_item_stmt = $this->db->prepare(
      "INSERT INTO order_item
      (order_code, product_code, amount, price, tax, business_code)
        VALUES
        (:order_code, :product_code, :amount, :price, :tax, :business_code)
        RETURNING *"
    );

    if (!$activeOrder) {
      $order_insert_stmt->execute([
        ":total" => $orderItemTotalPrice,
        ":tax" => $orderItemTotalTax,
        ":business_code" => parent::generateBusinessCode()
      ]);
      $order_select_stmt = $this->db->query(
        "SELECT *
        FROM orders o
        WHERE o.status = 'open'"
      );
      $activeOrder = $order_select_stmt->fetch(PDO::FETCH_ASSOC);

      $insert_item_stmt->execute([
        ":order_code" => $activeOrder['code'],
        ":product_code" => $productId,
        "amount" => $productAmount,
        ":price" => $productPrice,
        ":tax" => $orderItemTotalTax,
        ":business_code" => $this->generateOrderItemBusinessCode()
      ]);

      $item = $insert_item_stmt->fetch(PDO::FETCH_ASSOC);
      $item['total'] = $this->orderItemService->getOrderItemTotalPrice(
        $item['tax'],
        $item['price'],
        $item['amount']
      );
      return $item;
    } else {
      $orderTotalPrice = $activeOrder['total'] + $orderItemTotalPrice;
      $orderTotalTax = $activeOrder['tax'] + $orderItemTotalTax;
      $order_update_stmt->execute([
        ":total" => $orderTotalPrice,
        ":tax" => $orderTotalTax
      ]);

      if ($orderItems && $this->orderItemService->isOrderItemRepeated($productId, $activeOrder['code'])) {
        $stmt = $this->db->prepare("SELECT *
        FROM order_item o
        WHERE o.product_code = :product_code
        AND o.order_code = :order_code");
        $stmt->execute([
          ":product_code" => $data['product_code'],
          ":order_code" => $activeOrder['code']
        ]);
        $existingOrderItem = $stmt->fetch(PDO::FETCH_ASSOC);
        $amountsAdded = $data['amount'] + $existingOrderItem['amount'];
        $newTotalTax = $this->orderItemService->calcOrderItemTotalTax(
          $categoryTax,
          $productPrice,
          $data['amount']
        ) + $existingOrderItem['tax'];

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
        $item['total'] = $this->orderItemService->getOrderItemTotalPrice(
          $item['tax'],
          $item['price'],
          $item['amount']
        );
        return $item;
      }
      $insert_item_stmt->execute([
        ":order_code" => $activeOrder['code'],
        ":product_code" => $productId,
        "amount" => $productAmount,
        ":price" => $productPrice,
        ":tax" => $orderItemTotalTax,
        ":business_code" => $this->generateOrderItemBusinessCode()
      ]);
      $item = $insert_item_stmt->fetch(PDO::FETCH_ASSOC);
      $item['total'] = $this->orderItemService->getOrderItemTotalPrice(
        $item['tax'],
        $item['price'],
        $item['amount']
      );
      return $item;
    }
  }

  /**
   * @param int $orderItemId
   * @return void
   */
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
  private function validate(array $data): void
  {
    $productCode = $data['product_code'];
    $amount = $data['amount'];

    $product_stmt = $this->db->prepare(
      "SELECT *
      FROM products
      WHERE code = :code
      AND status = 'active'"
    );
    $product_stmt->execute([":code" => $productCode]);

    if ($product_stmt->rowCount() === 0) {
      throw new ApiException("Product doesn't exist.", 404);
    }
    if ($amount < 1 || $amount > 10000 || !filter_var($amount, FILTER_VALIDATE_INT)) {
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

  /**
   * @return int
   */
  public function generateOrderItemBusinessCode(): int
  {
    $stmt = $this->db->prepare(
      "SELECT COALESCE(MAX(business_code) + 1, 1)
    FROM order_item"
    );
    $stmt->execute();
    return $stmt->fetchColumn();
  }
}

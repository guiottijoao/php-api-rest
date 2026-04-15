<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Status;
use App\Exceptions\ApiException;
use PDO;

class OrderService
{

  private PDO $db;

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  /**
   * @param int $deleteItemId
   * @return void
   */
  public function calculateOrderWhenItemDeleted(int $deletedItemId): void
  {
    $item_stmt = $this->db->prepare("SELECT * FROM order_item o WHERE o.code = :code");
    $item_stmt->execute([":code" => $deletedItemId]);
    $itemToDelete = $item_stmt->fetch(PDO::FETCH_ASSOC);

    $itemTotalPrice = $itemToDelete['tax'] + ($itemToDelete['amount'] * $itemToDelete['price']);

    $order_stmt = $this->db->prepare("SELECT * FROM orders o WHERE o.status = :status");
    $order_stmt->execute([":status" => Status::OPEN]);
    $activeOrder = $order_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$activeOrder) {
      throw new ApiException("Error: No orders open.");
    }

    $orderTotalPrice = $activeOrder['total'] - $itemTotalPrice;
    $orderTotalTax = $activeOrder['tax'] - $itemToDelete['tax'];

    $order_update_stmt = $this->db->prepare(
      "UPDATE orders o
      SET total = :total, tax = :tax
      WHERE o.code = :order_code"
    );
    $order_update_stmt->execute([
      ":total" => $orderTotalPrice,
      ":tax" => $orderTotalTax,
      ":order_code" => $itemToDelete['order_code']
    ]);

    if ($order_update_stmt->rowCount() === 0) {
      throw new ApiException("Error during total calculation, no rows affected.");
    }
  }
}

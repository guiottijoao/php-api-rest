<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Order;
use App\Models\OrderItem;
use PDO;

class CancelOrderService
{
  private $db;
  private Order $order;
  private OrderItem $orderItem;

  public function __construct(PDO $db)
  {
    $this->order = new Order($db);
    $this->orderItem = new OrderItem($db);
    $this->db = $db;
  }

  public function cancel(int $orderId): void
  {
    $activeOrder = $this->order->findOpenOrder();
    if (!$activeOrder) throw new ApiException("Order not found.", 404);

    if ((int)$activeOrder['code'] !== $orderId) {
      throw new ApiException("Order is not open.", 400);
    }

    $orderItems = $this->orderItem->findItemsByOrder($orderId);
    if (!$orderItems) throw new ApiException("Order has no items.", 400);

    $this->orderItem->deleteItemsByOrder($orderId);
    $this->order->resetOrder($orderId);
  }
}

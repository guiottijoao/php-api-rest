<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use PDO;

class FinishOrderService
{
  private Order $order;
  private OrderItem $orderItem;
  private Product $product;

  public function __construct(PDO $db) {
    $this->order = new Order($db);
    $this->orderItem = new OrderItem($db);
    $this->product = new Product($db);
  }

  public function finish(int $orderId): void
  {
    $openOrder = $this->order->findOpenOrder();

    if ($openOrder) {
      $openOrderId = $openOrder['code'];
      
      $orderItems = $this->orderItem->findItemsByOrder($openOrderId);

      if (!$orderItems) {
        throw new ApiException("You dont have items in your order.");
      }

      foreach ($orderItems as $item) {
        $this->product->discountStock($item);
      }

      $this->order->closeOrder($orderId);
    }
  }
}

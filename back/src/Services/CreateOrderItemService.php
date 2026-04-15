<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderItemService;
use PDO;

class CreateOrderItemService
{

  private Category $category;
  private Order $order;
  private OrderItem $orderItem;
  private OrderItemService $orderItemService;
  private $db;

  public function __construct(PDO $db)
  {
    $this->db = $db;
    $this->category = new Category($db);
    $this->order = new Order($db);
    $this->orderItem = new OrderItem($db);
    $this->orderItemService = new OrderItemService($db);
  }

  public function addItemToOrder(array $data): array
  {
    $this->orderItem->validate($data);

    $activeOrder = $this->order->findOpenOrder();
    $productId = $data['product_code'];

    $categoryTax = $this->category->getCategoryTax($productId);
    $productAmount = $data['amount'];
    $productPrice = $this->orderItemService->getProductPrice($productId);
    $orderItemTotalTax = $this->orderItemService->calculateItemTotalTax(
      $categoryTax,
      $productPrice,
      $productAmount
    );

    $orderItemTotalPrice = $this->orderItemService->getOrderItemTotalPrice(
      $orderItemTotalTax,
      $productPrice,
      $productAmount
    );

    if (!$activeOrder) {
      $this->order->insertNewOrder($orderItemTotalPrice, $orderItemTotalTax);

      $activeOrder = $this->order->findOpenOrder();

      $item = $this->orderItem->insertNewItem([
        'order_code' => $activeOrder['code'],
        'product_code' => $productId,
        'amount'        => $productAmount,
        'price'         => $productPrice,
        'tax'           => $orderItemTotalTax,
        'business_code' => $this->orderItem->generateOrderItemBusinessCode(),
      ]);

      return $item;
    } else {
      $orderTotalPrice = $activeOrder['total'] + $orderItemTotalPrice;
      $orderTotalTax = $activeOrder['tax'] + $orderItemTotalTax;

      $this->order->updateOrder($orderTotalPrice, $orderTotalTax, 'open');

      if ($this->orderItemService->isOrderItemRepeated($productId, $activeOrder['code'])) {
        $existingOrderItem = $this->orderItem->findItemByOrderAndProduct($productId, $activeOrder['code']);
        $amountsAdded = $data['amount'] + $existingOrderItem['amount'];
        $newTotalTax = $this->orderItemService->calculateItemTotalTax(
          $categoryTax,
          $productPrice,
          $data['amount']
        ) + $existingOrderItem['tax'];

        $item = $this->orderItem->updateExistingItemQuantity($amountsAdded, $newTotalTax, $productId);
        return $item;
      }

      $item = $this->orderItem->insertNewItem([
        'order_code' => $activeOrder['code'],
        'product_code' => $productId,
        'amount'        => $productAmount,
        'price'         => $productPrice,
        'tax'           => $orderItemTotalTax,
        'business_code' => $this->orderItem->generateOrderItemBusinessCode(),
      ]);
      return $item;
    }
  }
}

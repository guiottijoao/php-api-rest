<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Status;
use App\Exceptions\ApiException;
use PDO;

class OrderItemService
{

  private PDO $db;

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  /**
   * @param int $productId
   * @return float
   */
  public function getCategoryTax(int $productId): float
  {
    $search_category_tax = $this->db->prepare(
      "SELECT c.tax
      FROM categories c
      INNER JOIN products p
      ON c.code = p.category_code
      WHERE p.code = :product_code"
    );
    $search_category_tax->execute([":product_code" => $productId]);
    return (float)$search_category_tax->fetchColumn();
  }

  /**
   * @param int @productId
   * @return float
   */
  public function getProductPrice(int $productId): float
  {
    $search_product_price = $this->db->prepare(
      "SELECT p.price
        FROM products p
        WHERE p.code = :product_code"
    );
    $search_product_price->execute([":product_code" => $productId]);
    return (float)$search_product_price->fetchColumn();
  }

  /**
   * @param float $taxPercent
   * @param float $unitPrice
   * @param int $amount
   * @return float
   */
  public function calculateItemTotalTax(
    float $taxPercent,
    float $unitPrice,
    int $amount
  ): float {
    return $result = ($taxPercent / 100) * $unitPrice * $amount;
  }

  /**
   * @param mixed $totalTax
   * @param mixed $price
   * @param int $amount
   * @return float
   */
  public function getOrderItemTotalPrice(mixed $totalTax, mixed $price, int $amount): float
  {
    $result =  $totalTax + ($price * $amount);
    return (float)$result;
  }

  /**
   * @param int $productId
   * @param int $orderId
   * @return bool
   */
  public function isOrderItemRepeated(int $productId, int $orderId): bool
  {
    $stmt = $this->db->prepare(
      "SELECT *
      FROM order_item o
      WHERE o.product_code =
      :product_code
      AND o.order_code = :order_code"
    );
    $stmt->execute([":product_code" => $productId, ":order_code" => $orderId]);
    if ($stmt->rowCount() > 0) return true;
    return false;
  }

  /**
   * @param int $orderItemId
   * @return string
   */
  public function getProductName(int $orderItemId): string
  {
    $stmt = $this->db->prepare(
      "SELECT p.name
    FROM products p
    INNER JOIN order_item oi
    ON p.code = oi.product_code
    WHERE oi.code = :code"
    );

    $stmt->execute([":code" => $orderItemId]);
    return $stmt->fetchColumn();
  }

  /**
   * @param int $productId
   * @param array<string, mixed> $orderItem
   * @return bool
   */
  public function verifyStockAvailability(int $productId, array $orderItem): bool
  {
    $existing_item_amount_stmt = $this->db->prepare(
      "SELECT amount
      FROM order_item oi
      INNER JOIN orders o
      ON oi.order_code = o.code
      WHERE oi.product_code = :product_code
      AND o.status = :status"
    );
    $product_stmt = $this->db->prepare(
      "SELECT *
      FROM products
      WHERE code = :code"
    );
    $product_stmt->execute([":code" => $productId]);
    $product = $product_stmt->fetch(PDO::FETCH_ASSOC);

    $existing_item_amount_stmt->execute([
      ":product_code" => $orderItem['product_code'],
      ":status" => Status::OPEN
    ]);
    $existingItemAmount = $existing_item_amount_stmt->fetchColumn();
    if ($product['amount'] < $orderItem['amount'] + $existingItemAmount) {
      if ($product['amount'] === 0) {
        throw new ApiException("This product is out of stock.");
      }
      throw new ApiException("This product has only " . (int)$product['amount'] . " items in stock.");
    }
    return true;
  }
}

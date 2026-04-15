<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Services\CancelOrderService;
use PDO;

class CancelOrderController
{

  private PDO $db;
  private CancelOrderService $cancelOrderService;

  public function __construct(PDO $db)
  {
    $this->cancelOrderService = new CancelOrderService($db);
    $this->db = $db;
  }

  /**
   * @param int $orderId
   * @return void
   */
  public function update(int $orderId): void
  {
    try {
      header('Content-Type: application/json');

      $this->cancelOrderService->cancel($orderId);
      echo json_encode(["message" => "Order cancelled successfully."]);
    } catch (ApiException $e) {
      $code = (int)$e->getCode() ?: 500;
      http_response_code($code);
      echo json_encode(["message" => $e->getPublicMessage()]);
    }
  }
}

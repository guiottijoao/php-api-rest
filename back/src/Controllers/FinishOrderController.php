<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Services\FinishOrderService;
use PDO;

class FinishOrderController
{

  private PDO $db;
  private FinishOrderService $finishOrderService;

  public function __construct(PDO $db)
  {
    $this->finishOrderService = new FinishOrderService($db);
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

      $this->finishOrderService->finish($orderId);
      echo json_encode(["message" => "Order finished successfully."]);
    } catch (ApiException $e) {
      $code = $e->getCode() ?: 500;
      http_response_code($code);
      echo json_encode(["message" => $e->getPublicMessage()]);
    }
  }
}

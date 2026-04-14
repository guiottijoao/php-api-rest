<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Order;
use App\Exceptions\ApiException;
use PDO;

class FinishOrderController
{

  private PDO $db;

  public function __construct(PDO $db)
  {
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

      $model = new Order($this->db);
      $model->finish($orderId);
      echo json_encode(["message" => "Order finished successfully."]);
    } catch (ApiException $e) {
      $code = $e->getCode() ?: 500;
      http_response_code($code);
      echo json_encode(["message" => $e->getPublicMessage()]);
    }
  }
}

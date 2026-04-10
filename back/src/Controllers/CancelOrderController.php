<?php

namespace App\Controllers;

use App\Models\Order;
use App\Exceptions\ApiException;
use PDO;

class CancelOrderController
{
  
  private $db;

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  public function update($orderId)
  {
    try {
      header('Content-Type: application/json');

      $model = new Order($this->db);
      $model->cancel($orderId);
      echo json_encode(["message" => "Order cancelled successfully."]);
    } catch (ApiException $e) {
      throw $e;
    }
  }
}

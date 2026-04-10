<?php

namespace App\Controllers;

use App\Models\Order;
use App\Exceptions\ApiException;
use PDO;

class FinishOrderController
{

  private $db;

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  public function update($orderId)
  {
    try {
      header('Content-type: application/json');

      $model = new Order($this->db);
      $model->finish($orderId);
      echo json_encode(["message" => "Order finished successfully."]);
    } catch (ApiException $e) {
      throw $e;
    }
  }
}

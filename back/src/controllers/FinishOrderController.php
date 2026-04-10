<?php

namespace App\controllers;

use App\models\Order;
use App\exceptions\ApiException;
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

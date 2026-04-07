<?php
require_once __DIR__ . '/../models/Order.php';

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
      header('Content-type: application/json');

      $model = new Order($this->db);
      $model->cancel($orderId);
      echo json_encode(["message" => "Order cancelled successfully."]);
    } catch (Exception $e) {
      throw $e;
    }
  }
}

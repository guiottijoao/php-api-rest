<?php
require_once __DIR__ . '/../models/Order.php';

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
      echo json_encode(["msg" => "Order finished successfully."]);
    } catch (Exception $e) {
      throw $e;
    }
  }
}

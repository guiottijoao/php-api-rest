<?php

namespace App\Controllers;

use App\Models\OrderItem;
use App\Exceptions\ApiException;
use PDO;

class OrderItemController
{

  private $db;

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  public function index($id = null)
  {
    try {
      $model = new OrderItem($this->db);
      if ($id) {
        $data = $model->findById($id);
      } else {
        $data = $model->list();
      }

      header('Content-Type: application/json');
      echo json_encode($data);
    } catch (ApiException $e) {
      $code = $e->getCode();
      http_response_code($code);
      echo json_encode(["message" => $e->getPublicMessage()]);
    }
  }

  public function store()
  {
    try {
      header('Content-Type: application/json');

      $input = json_decode(file_get_contents('php://input'), true);

      if (!$input) throw new ApiException("Required fields not filled.", 400);
      if (!isset($input['product_code'], $input['amount'])) { // os outros campos  são calculados
        throw new ApiException("Expected fields: 'order', 'product', 'amount', 'price', 'tax'.");
      }

      foreach ($input as $field => $value) {
        if (empty($value)) {
          throw new ApiException("Field $field is required", 400);
        }
      }

      $model = new OrderItem($this->db);
      $result = $model->save($input);

      if ($result) {
        http_response_code(201);
        echo json_encode(["message" => "Order item created successfully.", "data" => $result]);
      } else {
        throw new ApiException("Cannot process order item data.", 400);
      }
    } catch (ApiException $e) {
      $code = (int)$e->getCode();
      http_response_code($code ?: 500);
      echo json_encode(["message" => $e->getPublicMessage()]);
    }
  }

  public function delete($orderItemId)
  {
    try {
      $model = new OrderItem($this->db);
      $model->delete($orderItemId);
      http_response_code(204);
    } catch (ApiException $e) {
      $code = (int)$e->getCode();
      http_response_code($code ?: 500);
      echo json_encode(["message" => $e->getPublicMessage()]);
    }
  }
}

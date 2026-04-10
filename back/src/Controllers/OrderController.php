<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use App\Exceptions\ApiException;
use App\Models\Order;
use Exception;

class OrderController
{

  private PDO $db;

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  public function index(?int $id = null): void
  {
    try {
      $model = new Order($this->db);
      $data = $id ? $model->findById($id) : $model->list();

      header('Content-Type: application/json');
      echo json_encode($data);
    } catch (ApiException $e) {
      $code = (int)$e->getCode();
      http_response_code($code);
      echo json_encode(["message" => $e->getPublicMessage()]);
    } catch (Exception $e) {
      error_log("Unexpected error: " . $e->getMessage() . " | " . $e->getTraceAsString());

      http_response_code(500);
      echo json_encode(["message" => "Internal server error"]);
    }
  }

  public function store(): void
  {
    try {
      header('Content-Type: application/json');

      $input = json_decode(file_get_contents('php://input'), associative: true);

      if (!$input) throw new ApiException("Required fields not filled.", 400);
      if (!isset($input['total'], $input['tax'])) {
        throw new ApiException("Expected fields: 'total', 'tax'.");
      }

      foreach ($input as $field => $value) {
        if (($value == "" || $value == null)) { // Não da pra usar epmty() porque 0 não passa
          throw new ApiException("Field $field is required", 400);
        }
      }

      $model = new Order($this->db);
      $result = $model->save($input);

      if ($result) {
        http_response_code(201);
        echo json_encode(["message" => "Order created successfully.", "data" => $result]);
      } else {
        throw new ApiException("Cannot process order data.", 400);
      }
    } catch (ApiException $e) {
      $code = (int)$e->getCode();
      http_response_code($code);
      echo json_encode(["message" => $e->getPublicMessage()]);
    } catch (Exception $e) {
      error_log("Unexpected error: " . $e->getMessage() . " | " . $e->getTraceAsString());

      http_response_code(500);
      echo json_encode(["message" => "Internal server error"]);
    }
  }

  public function delete(int $orderId): void
  {
    try {
      $model = new Order($this->db);
      if (!$orderId) {
        throw new ApiException("Id not provided.", 400);
      }

      $model->delete($orderId);
      http_response_code(204);
    } catch (ApiException $e) {
      $code = (int)$e->getCode();
      http_response_code($code);
      echo json_encode(["message" => $e->getPublicMessage()]);
    }
  }
}

<?php
require_once __DIR__ . '/../models/Order.php';

class OrderController
{

  private $db;

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  public function index($id = null)
  {
    try {
      $model = new Order($this->db);
      if ($id) {
        $data = $model->findById($id);
      } else {
        $data = $model->list();
      }

      header('Content-type: application/json');
      echo json_encode($data);
    } catch (Exception $e) {
      $code = (int)$e->getCode();
      http_response_code($code);
      echo json_encode(["message: " => $e->getMessage()]);
    }
  }

  public function store()
  {
    try {
      header('Content-type: application/json');

      $input = json_decode(file_get_contents('php://input'), true);

      if (!$input) throw new Error("Required fields not filled.", 400);
      if (!isset($input['total'], $input['tax'])) {
        throw new Exception("Expected fields: 'total', 'tax'.");
      }

      foreach ($input as $field => $value) {
        if (($value == "" || $value == null)) { // Não da pra usar epmty() porque 0 não passa
          throw new Exception("Field $field is required", 400);
        }
      }

      $model = new Order($this->db);
      $result = $model->save($input);

      if ($result) {
        http_response_code(200);
        echo json_encode(["message" => "Order created successfully."]);
      } else {
        throw new Exception("Cannot process order data.", 400);
      }
    } catch (Exception $e) {
      $code = (int)$e->getCode();
      http_response_code($code);
      echo json_encode(["message:" => $e->getMessage()]);
    }
  }

  public function delete($orderId)
  {
    try {
      $model = new Order($this->db);
      if (!$orderId) {
        throw new Exception("Id not provided.", 400);
      }

      $model->delete($orderId);
      http_response_code(200);
    } catch (\Throwable $e) {
      $code = (int)$e->getCode();
      http_response_code($code);
      echo json_encode(["message" => $e->getMessage()]);
    }
  }
}

<?php
require_once __DIR__ . '/../models/Product.php';

class ProductsController
{

  private $db;

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  public function index($id = null)
  {
    try {
      $model = new Product($this->db);
      if ($id) {
        $data = $model->findById($id);
      } else {
        $data = $model->list();
      }

      header('Content-type: application/json');
      echo json_encode($data);
    } catch (Exception $e) {
      $code = $e->getCode();
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
      if (!isset($input['name'], $input['amount'], $input['price'], $input['category_code'])) {
        throw new Exception("Expected fields: 'name', 'amount', 'price', 'category'.");
      }

      foreach ($input as $field => $value) {
        if (empty($value)) {
          throw new Exception("Field $field is required", 400);
        }
      }

      $model = new Product($this->db);
      $result = $model->save($input);

      if ($result) {
        http_response_code(201);
        echo json_encode(["message" => "Product created successfully."]);
      } else {
        throw new Exception("Cannot process product data.", 400);
      }
    } catch (Exception $e) {
      $code = (int)$e->getCode();
      http_response_code($code ?: 500);
      echo json_encode(["message" => $e->getMessage()]);
    }
  }

  public function delete($productId)
  {
    try {
      $model = new Product($this->db);
      if (!$productId) {
        throw new Exception("Id not provided.", 400);
      }

      $model->delete($productId);
      http_response_code(204);
    } catch (Exception $e) {
      $code = (int)$e->getCode();
      http_response_code($code ?: 500);
      echo json_encode(["message" => $e->getMessage()]);
    }
  }
}

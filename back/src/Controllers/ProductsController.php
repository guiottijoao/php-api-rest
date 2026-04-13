<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Models\Product;
use PDO;

class ProductsController
{

  private PDO $db;

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  /**
   * @param int $id
   * @return void
   */
  public function index(?int $id = null): void
  {
    try {
      $model = new Product($this->db);
      $data = $id ? $model->findById($id) : $model->list();

      header('Content-Type: application/json');
      echo json_encode($data);
    } catch (ApiException $e) {
      $code = (int)$e->getCode() ?: 500;
      http_response_code($code);
      echo json_encode(["message" => $e->getPublicMessage()]);
    }
  }

  /**
   * @return void
   */
  public function store(): void
  {
    try {
      header('Content-Type: application/json');

      $input = json_decode(file_get_contents('php://input'), associative: true);

      if (!$input) throw new ApiException("Required fields not filled.", 400);
      if (!isset($input['name'], $input['amount'], $input['price'], $input['category_code'])) {
        throw new ApiException("Expected fields: 'name', 'amount', 'price', 'category'.");
      }

      foreach ($input as $field => $value) {
        if (empty($value)) {
          throw new ApiException("Field $field is required", 400);
        }
      }

      $model = new Product($this->db);
      $result = $model->save($input);

      if ($result) {
        http_response_code(201);
        echo json_encode(["message" => "Product created successfully.", "data" => $result]);
      } else {
        throw new ApiException("Cannot process product data.", 400);
      }
    } catch (ApiException $e) {
      $code = (int)$e->getCode() ?: 500;
      http_response_code($code);
      echo json_encode(["message" => $e->getPublicMessage()]);
    }
  }

  /**
   * @param int $productId
   * @return void
   */
  public function delete(int $productId): void
  {
    try {
      $model = new Product($this->db);
      $model->delete($productId);
      http_response_code(204);
    } catch (ApiException $e) {
      $code = (int)$e->getCode() ?: 500;
      http_response_code($code);
      echo json_encode(["message" => $e->getPublicMessage()]);
    }
  }
}

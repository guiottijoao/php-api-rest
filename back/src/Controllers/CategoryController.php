<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use App\Exceptions\ApiException;
use PDO;

class CategoryController
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
      $model = new Category($this->db);
      $data = $id ? $model->findById($id) : $model->list();

      header('Content-Type: application/json');
      echo json_encode($data);
    } catch (ApiException $e) {
      $code = (int)$e->getCode();
      http_response_code($code ?: 500);
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

      $input = json_decode(file_get_contents("php://input"), associative: true);

      if (!$input) throw new ApiException("Required fields not filled.");

      foreach ($input as $field => $value) {
        if ($value === null || $value === '') {
          throw new ApiException("Field $field is required.", 400);
        }
      }

      $model = new Category($this->db);
      $result = $model->save($input);

      if ($result) {
        http_response_code(201);
        echo json_encode(["message" => "Category created successfully.", "data" => $result]);
      } else {
        throw new ApiException("Cannot process category data.", 400);
      }
    } catch (ApiException $e) {
      $code = (int)$e->getCode();
      http_response_code($code ?: 500);

      echo json_encode(["message" => $e->getPublicMessage()]);
    }
  }

  /**
   * @param int $categoryId
   * @return void
   */
  public function delete(int $categoryId): void
  {
    try {
      $model = new Category($this->db);
      $model->delete($categoryId);
      http_response_code(204);
    } catch (ApiException $e) {
      $code = (int)$e->getCode() ?: 500;
      http_response_code($code);
      echo json_encode(["message" => $e->getPublicMessage()]);
    }
  }
}

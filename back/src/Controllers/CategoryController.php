<?php

namespace App\Controllers;

use App\Models\Category;
use App\Exceptions\ApiException;
use PDO;

class CategoryController
{

  private $db;

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  public function index($id = null)
  {
    try {
      $model = new Category($this->db);
      if ($id) {
        $data = $model->findById($id);
      } else {
        $data = $model->list();
      }
      header('Content-Type: application/json');
      echo json_encode($data);
    } catch (ApiException $e) {
      $code = (int)$e->getCode();
      http_response_code($code ?: 500);
      echo json_encode(["message: " => $e->getMessage()]);
    }
  }

  public function store()
  {
    try {
      header('Content-Type: application/json');

      $input = json_decode(file_get_contents("php://input"), true);

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

      echo json_encode(["message" => $e->getMessage()]);
    }
  }

  public function delete($categoryId)
  {
    try {
      $model = new Category($this->db);
      if (!$categoryId) {
        throw new ApiException("Id not provided.", 400);
      }
      $model->delete($categoryId);
      http_response_code(204);
    } catch (ApiException $e) {
      $code = (int)$e->getCode() ?: 500;
      http_response_code($code);
      echo json_encode(["message" => $e->getMessage()]);
    }
  }
}

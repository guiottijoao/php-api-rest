<?php
require_once __DIR__ . '/../models/Category.php';

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
    } catch (Exception $e) {
      $code = (int)$e->getCode();
      http_response_code($code ?: 500);
      echo json_encode(["message: " => $e->getMessage()]);
    }
  }

  public function store()
  {
    try {
      header('Content-Type: applicatio/json');

      $input = json_decode(file_get_contents("php://input"), true);

      if (!$input) throw new Exception("Required fields not filled.");

      foreach ($input as $field => $value) {
        if ($value === null || $value === '') {
          throw new Exception("Field $field is required.", 400);
        }
      }

      $model = new Category($this->db);
      $result = $model->save($input);

      if ($result) {
        http_response_code(201);
        echo json_encode(["message" => "Category created successfully.", "data" => $result]);
      } else {
        throw new Exception("Cannot process category data.", 400);
      }
    } catch (Exception $e) {
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
        throw new Exception("Id not provided.", 400);
      }
      $model->delete($categoryId);
      http_response_code(204);
    } catch (Exception $e) {
      $code = (int)$e->getCode() ?: 500;
      http_response_code($code);
      echo json_encode(["message" => $e->getMessage()]);
    }
  }
}

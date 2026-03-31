<?php
require_once __DIR__ . '/../models/Category.php';

class CategoryController
{

  private $db;

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  public function index()
  {
    try {
      $model = new Category($this->db);
      $data = $model->list();

      header('Content-Type: application/json');
      echo json_encode($data);
    } catch (Exception $e) {
      throw $e;
    }
  }

  public function store()
  {
    try {
      header('Content-Type: applicatio/json');

      $input = json_decode(file_get_contents("php://input"), true);

      if (empty($input['name'])) {
        throw new Exception("Name is required.", 400);
      }

      if (!preg_match('/^[\p{L}\p{N}\s]+$/u', $input['name'])) {
        throw new Exception("Name contains invalid characters.", 400);
      }

      if (!isset($input['tax']) || !is_numeric($input['tax'])) {
        throw new Exception("Tax must be a number between 0 and 100.", 400);
      }

      if ($input['tax'] < 0 || $input['tax'] > 100) {
        throw new Exception("Tax must be a number between 0 and 100", 400);
      }

      $model = new Category($this->db);
      $result = $model->save($input);

      if ($result === true) {
        http_response_code(201);
        echo json_encode(["msg" => "Category created successfully."]);
      } else {
        throw new Exception("Cannot process category data.", 400);
      }
    } catch (Exception $e) {
      $code = (int)$e->getCode();
      http_response_code($code ?: 500);

      echo json_encode(["error" => $e->getMessage()]);
    }
  }

  public function delete($categoryId)
  {
    try {
      $model = new Category($this->db);
      $model->delete($categoryId);
      http_response_code(204);
    } catch (Exception $e) {
      $code = (int)$e->getCode() ?: 500;
      http_response_code($code);
      echo json_encode(["error" => $e->getMessage()])
    }
  }
}

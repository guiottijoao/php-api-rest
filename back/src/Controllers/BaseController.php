<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\ApiException;
use PDO;
use Exception;

abstract class BaseController
{

  protected PDO $db;
  protected string $model;
  protected string $service = '';

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  protected function getRequiredFields(): array
  {
    return [];
  }

  /**
   * @param int $id
   * @return void
   */
  public function index(?int $id = null): void
  {
    try {
      $modelClass = "App\\Models\\{$this->model}";
      $model = new $modelClass($this->db);
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

  /**
   * @return void
   */
  public function store(): void
  {
    try {
      header('Content-Type: application/json');
      $data = json_decode(file_get_contents("php://input"), associative: true);

      $this->validateRequiredFields($data, $this->getRequiredFields());

      foreach ($data as $field => $value) {
        if ($value === null || $value === '') {
          throw new ApiException("Field $field is required.", 400);
        }
      }

      $modelClass = "App\\Models\\{$this->model}";
      $modelName = explode("\\", $modelClass)[2];

      if ($this->service === 'CreateOrderItemService') {
        $serviceClass = "App\\Services\\{$this->service}";
        $service = new $serviceClass($this->db);

        $result = $service->addItemToOrder($data);
      } else {
        $model = new $modelClass($this->db);
        $result = $model->save($data);
      }


      if ($result) {
        http_response_code(201);
        echo json_encode(["message" => "$modelName created successfully.", "data" => $result]);
      } else {
        throw new ApiException("Cannot process $modelName data.", 400);
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

  /**
   * @param int $id
   * @return void
   */
  public function delete(int $id): void
  {
    try {
      header('Content-Type: application/json');
      $modelClass = "App\\Models\\{$this->model}";
      $model = new $modelClass($this->db);
      $model->delete($id);
      http_response_code(204);
    } catch (ApiException $e) {
      $code = (int)$e->getCode() ?: 500;
      http_response_code($code);
      echo json_encode(["message" => $e->getPublicMessage()]);
    }
  }

  protected function validateRequiredFields(array $data, array $required): void
  {
    foreach ($required as $field) {
      if (!isset($data[$field])) {
        throw new ApiException("Field '$field' is required", 400);
      }
    }
  }
}

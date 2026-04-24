<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use App\Controllers\BaseController;
use App\Models\Order;
use App\Exceptions\ApiException;
use Exception;

class OrderController extends BaseController
{

  protected string $model = 'Order';

  public function __construct(PDO $db)
  {
    parent::__construct($db);
  }

  protected function getRequiredFields(): array
  {
    return ['total', 'tax'];
  }

  public function history(): void
  {
    try {
      $model = new Order($this->db);
      header('Content-Type: application/json');
      echo json_encode($model->listHistory());
    } catch (ApiException $e) {
      http_response_code((int)$e->getCode());
      echo json_encode(["message" => $e->getPublicMessage()]);
    } catch (Exception $e) {
      error_log("Unexpected error: " . $e->getMessage() . " | " . $e->getTraceAsString());
      http_response_code(500);
      echo json_encode(["message" => "Internal server error"]);
    }
  }
}

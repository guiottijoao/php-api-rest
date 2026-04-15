<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use PDO;

class OrderItemController extends BaseController
{

  protected string $model = 'OrderItem';
  protected string $service = 'CreateOrderItemService';

  public function __construct(PDO $db)
  {
    parent::__construct($db);
  }

  protected function getRequiredFields(): array
  {
    return ['product_code', 'amount'];
  }
}

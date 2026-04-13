<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use App\Controllers\BaseController;

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
}

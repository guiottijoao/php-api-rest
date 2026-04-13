<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use PDO;

class ProductsController extends BaseController
{

  protected string $model = 'Product';

  public function __construct(PDO $db)
  {
    parent::__construct($db);
  }

  protected function getRequiredFields(): array
  {
    return ['name', 'price', 'amount', 'category_code'];
  }
}

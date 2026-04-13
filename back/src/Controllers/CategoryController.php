<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use PDO;

class CategoryController extends BaseController
{

  protected string $model = 'Category';

  public function __construct(PDO $db)
  {
    parent::__construct($db);
  }

  protected function getRequiredFields(): array {
    return ['name', 'tax'];
  }
}

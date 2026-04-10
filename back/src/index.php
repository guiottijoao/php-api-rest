<?php

use App\config\Database;
use App\controllers\CategoryController;
use App\controllers\ProductsController;
use App\controllers\OrderItemController;
use App\controllers\CancelOrderController;
use App\controllers\FinishOrderController;
use App\controllers\OrderController;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

spl_autoload_register(function ($class) {
  $baseDir = __DIR__ . '/';

  $class = str_replace('App\\', '', $class);
  $file = $baseDir . str_replace('\\', '/', $class) . '.php';

  if (file_exists($file)) {
    require $file;
  }
});

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

$db = Database::getConnection();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', trim($uri, '/'));
$route = $uri[0];
$id = $uri[1] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

// http method => controller method
$actions = [
  'GET' => 'index',
  'POST' => 'store',
  'PUT' => 'update',
  'DELETE' => 'delete'
];

// route => controller
$controllers = [
  'categories' => CategoryController::class,
  'products' => ProductsController::class,
  'orders' => OrderController::class,
  'finish-order' => FinishOrderController::class,
  'cancel-order' => CancelOrderController::class,
  'order-items' => OrderItemController::class,
];

if (isset($controllers[$route])) {
  $controllerName = $controllers[$route];
  $controller = new $controllerName($db);

  if (!isset($controller)) {
    http_response_code(404);
    echo json_encode(["message" => "Controller not found"]);
  }

  $action = $actions[$method];

  if (method_exists($controller, $action)) {
    if ($id !== null) {
      $controller->$action($id);
    } else {
      $controller->$action();
    }
  } else {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed."]);
  }
} else {
  http_response_code(404);
  echo json_encode(["message" => "Route not found."]);
}

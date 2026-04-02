<?php

require_once 'config/Database.php';
require_once 'controllers/CategoryController.php';
require_once 'controllers/ProductsController.php';
require_once 'controllers/OrderController.php';
require_once 'controllers/OrderItemController.php';
require_once 'controllers/FinishOrderController.php';
require_once 'controllers/CancelOrderController.php';

$database =  new Database();
$db = $database::getConnection();

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
  'categories' => 'CategoryController',
  'products' => 'ProductsController',
  'orders' => 'OrderController',
  'finish-order' => 'FinishOrderController',
  'cancel-order' => 'CancelOrderController',
  'order-items' => 'OrderItemController',
];

if (isset($controllers[$route])) {
  $controllerName = $controllers[$route];
  $controller = new $controllerName($db);
  
  if (!isset($controller)) {
    http_response_code(404);
    echo json_encode(["error" => "Controller not found"]);
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
    echo json_encode(["error" => "Method not allowed."]);
  }
} else {
  http_response_code(404);
  echo json_encode(["error" => "Route not found."]);
}

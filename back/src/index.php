<?php

use App\Config\Database;

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

$controllers = require __DIR__ . '/Config/routes.php';

if (isset($controllers[$route])) {
  $controllerName = $controllers[$route];

  if (!class_exists($controllerName)) {
    http_response_code(404);
    echo json_encode(["message" => "Controller not found"]);
  }
  $controller = new $controllerName($db);

  if ($route === 'order-history') {
    $action = 'history';
  } else {
    $action = $actions[$method];
  }

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

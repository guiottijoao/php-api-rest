<?php

use App\Controllers\CategoryController;
use App\Controllers\ProductsController;
use App\Controllers\OrderItemController;
use App\Controllers\CancelOrderController;
use App\Controllers\FinishOrderController;
use App\Controllers\OrderController;

return [
    'categories'   => CategoryController::class,
    'products'     => ProductsController::class,
    'orders'       => OrderController::class,
    'finish-order' => FinishOrderController::class,
    'cancel-order' => CancelOrderController::class,
    'order-items'  => OrderItemController::class,
];
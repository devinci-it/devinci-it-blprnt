<?php
// Example API route with middleware
global $router;

use DevinciIT\Blprnt\Middleware\AuthTokenMiddleware;

$router->group([
    'middleware' => [AuthTokenMiddleware::class]
], function ($router) {
    $router->get('/api/users', [App\Controllers\UserController::class, 'index']);
});

<?php
/**
 * --------------------------------------------------------------------------
 * ROUTES
 * --------------------------------------------------------------------------
 *
 * Define all application routes here.
 *
 * Each route maps a URI + HTTP method to:
 *  - a Closure (quick / simple logic)
 *  - or a Controller action (array: [ControllerClass::class, 'methodName'])
 *
 * Supported HTTP methods: GET, POST, PUT, PATCH, DELETE
 *
 * --------------------------------------------------------------------------
 * CLOSURE-BASED ROUTE
 * --------------------------------------------------------------------------
 *
 * $router->get('/', function () {
 *     return 'Hello, world!';
 * });
 *
 * Use closures for simple logic, testing, prototyping, or quick responses.
 *
 * --------------------------------------------------------------------------
 * CONTROLLER ROUTE (RECOMMENDED)
 * --------------------------------------------------------------------------
 *
 * $router->get('/users', [App\Controllers\UserController::class, 'index']);
 *
 * This will call:
 *
 *     App\Controllers\UserController::index()
 *
 * Prefer controllers for production applications to keep code organized.
 *
 * --------------------------------------------------------------------------
 * HTTP METHODS
 * --------------------------------------------------------------------------
 *
 * $router->get('/resource', [...]);      // Retrieve data
 * $router->post('/resource', [...]);     // Create data
 * $router->put('/resource/{id}', [...]);   // Replace entire resource
 * $router->patch('/resource/{id}', [...]);  // Partial update
 * $router->delete('/resource/{id}', [...]);  // Delete resource
 *
 * --------------------------------------------------------------------------
 * ROUTE GROUPING WITH MIDDLEWARE
 * --------------------------------------------------------------------------
 *
 * $router->group(['middleware' => [AuthMiddleware::class]], function($r) {
 *     $r->get('/admin', [AdminController::class, 'dashboard']);
 *     $r->post('/admin/settings', [AdminController::class, 'updateSettings']);
 * });
 *
 * Group related routes and apply shared middleware to all of them.
 *
 * --------------------------------------------------------------------------
 * BEST PRACTICES
 * --------------------------------------------------------------------------
 *
 * - Prefer controllers over closures for real applications
 * - Keep controllers small and focused
 * - Validate all incoming parameters
 * - Organize routes by feature or module
 * - Use route grouping to apply middleware to related routes
 * - Use closures for simple responses or when prototyping
 *
 * --------------------------------------------------------------------------
 * EXAMPLE ROUTES
 * --------------------------------------------------------------------------
 *
 * $router->get('/', [HomeController::class, 'index']);
 * $router->get('/users', [UserController::class, 'index']);
 * $router->get('/users/{id}', [UserController::class, 'show']);
 * $router->post('/users', [UserController::class, 'store']);
 * $router->put('/users/{id}', [UserController::class, 'update']);
 * $router->patch('/users/{id}', [UserController::class, 'patch']);
 * $router->delete('/users/{id}', [UserController::class, 'destroy']);
 *
 * --------------------------------------------------------------------------
 */

// Make router available from parent scope
global $router;




$router->get('/', [App\Controllers\SplashController::class, 'index']);

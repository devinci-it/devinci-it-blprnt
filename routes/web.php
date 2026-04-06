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
 *  - or a Controller action (recommended)
 *
 * --------------------------------------------------------------------------
 * BASIC ROUTE (Closure)
 * --------------------------------------------------------------------------
 *
 * $router->get('/', function () {
 *     return 'Hello, world!';
 * });
 *
 * Use closures for simple logic, testing, or quick responses.
 *
 * --------------------------------------------------------------------------
 * CONTROLLER ROUTE (Recommended)
 * --------------------------------------------------------------------------
 *
 * $router->get('/users', [App\Controllers\UserController::class, 'index']);
 *
 * This will call:
 *
 *     App\Controllers\UserController::index()
 *
 * Keep your application structured by using controllers.
 *
 * --------------------------------------------------------------------------
 * DEFAULT ROUTE
 * --------------------------------------------------------------------------
 *
 * $router->get('/', [App\Controllers\HomeController::class, 'index']);
 *
 * This is your application's homepage.
 *
 * --------------------------------------------------------------------------
 * DYNAMIC ROUTES (PARAMETERS)
 * --------------------------------------------------------------------------
 *
 * $router->get('/users/{id}', [UserController::class, 'show']);
 *
 * URL example:
 *     /users/42
 *
 * The parameter will be passed to your controller method:
 *
 *     public function show($id)
 *
 * You can define multiple parameters:
 *
 * $router->get('/posts/{postId}/comments/{commentId}', [CommentController::class, 'show']);
 *
 * --------------------------------------------------------------------------
 * BEST PRACTICES
 * --------------------------------------------------------------------------
 *
 * - Prefer controllers over closures for real applications
 * - Keep controllers small and focused
 * - Validate all incoming parameters
 * - Organize routes by feature or module
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
 * $router->delete('/users/{id}', [UserController::class, 'destroy']);
 *
 * --------------------------------------------------------------------------
 */




$router->get('/', [App\Controllers\SplashController::class, 'index']);
$router->get('/demo/blog-post', [App\Controllers\Demo\BlogPostController::class, 'index']);
// $router->post('/demo/blog-post', [App\Controllers\Demo\BlogPostController::class, 'store']);

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

// Demo endpoint: /demo/http-error?code=404&message=Not%20Found
$router->get('/demo/http-error', function ($request) {
	$code = (int) $request->query('code', 500);
	$message = (string) $request->query('message', 'Demo HTTP error');

	if ($code < 400 || $code > 599) {
		$code = 500;
	}

	$throwFile = __DIR__ . '/../test/demo/http-error/throw.php';
	if (!is_file($throwFile)) {
		throw new \RuntimeException('Error demo file not found', 500);
	}

	require $throwFile;
});


// AUTH ROUTES
$router->post('/login', [App\Controllers\AuthController::class, 'login']);
$router->post('/logout', [App\Controllers\AuthController::class, 'logout']);
$router->get('/login', [App\Controllers\AuthController::class, 'index']);

// API endpoint for login
$router->post('/api/login', [App\Controllers\AuthController::class, 'apiLogin']);

/*
 Protected admin route example (uncomment to use)

$router->group(['middleware' => [AuthMiddleware::class]], function($r) {
	$r->get('/admin', [AdminController::class, 'index']);
});

non closure gated route example:
*/

use DevinciIT\Blprnt\Middleware\AuthMiddleware;
use App\Controllers\AdminController;
use DevinciIT\Blprnt\Auth\Auth;
use DevinciIT\Blprnt\Middleware\AuthGuard;
use DevinciIT\Blprnt\Middleware\GuestGuard;

$router->get('/admin', [AdminController::class, 'index'], [AuthGuard::class]);
$router->get('/login', [App\Controllers\AuthController::class, 'index'], [GuestGuard::class]);
$router->get('/logout', [App\Controllers\AuthController::class, 'logout'], [AuthGuard::class]);
// Example: pipeline usage for /admin
use DevinciIT\Blprnt\Core\MiddlewarePipeline;

$router->get('/admin', function ($request) {
    $pipeline = new MiddlewarePipeline([
        \DevinciIT\Blprnt\Middleware\AuthGuard::class
    ]);
    return $pipeline->then(function ($request) {
        return (new AdminController())->index($request);
    })($request);
});
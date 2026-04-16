<?php

declare(strict_types=1);

/**
 * --------------------------------------------------------------------------
 * APPLICATION BOOTSTRAP
 * --------------------------------------------------------------------------
 *
 * Entry point for handling all HTTP requests.
 *
 * Responsibilities:
 * - Load Composer dependencies
 * - Configure and build the application (via HttpBootstrapBuilder)
 * - Handle the incoming request and return a response
 *
 * Tip:
 * Use the fluent builder below to customize behavior (env, views, routes, etc.)
 */

require __DIR__ . '/../vendor/autoload.php';

use DevinciIT\Blprnt\Core\App;
use DevinciIT\Blprnt\Core\HttpBootstrap;
use DevinciIT\Blprnt\Core\Request;
use DevinciIT\Blprnt\Core\Router;
use DevinciIT\Blprnt\Http\Kernel;
use DevinciIT\Blprnt\Support\Session;
/*
|--------------------------------------------------------------------------
| Build Application
|--------------------------------------------------------------------------
|
| The HttpBootstrap builder wires up:
| - Environment (.env)
| - Error handling
| - Service container
| - Router binding
| - View system
| - Helpers
| - Route files
|
| Customize behavior using the fluent API:
|
|   ->withoutEnv()
|   ->withEnvPath('/custom/path')
|   ->withoutErrorHandler()
|   ->withView('/views', 'layouts/custom.php')
|   ->withoutHelpers()
|   ->withRoutes([...])
|
*/

Session::start();
$bootstrap = HttpBootstrap::builder(__DIR__ . '/..')
    // ->withView(__DIR__ . '/../resources/views')
    // ->withoutHelpers()
    // ->withRoutes([__DIR__ . '/../routes/web.php'])
    ->build();

/** @var App $app */
$app = $bootstrap->app;

/** @var Router $router */
$router = $bootstrap->router;

/** @var Kernel $kernel */
$kernel = $bootstrap->kernel;


/*
|--------------------------------------------------------------------------
| Global Assets (Optional)
|--------------------------------------------------------------------------
|
| Register default CSS/JS for all views.
| These load automatically on every render.
|
| Files must exist in /public before registering.
|
| Example:
|
| set_default_assets(
|     ['assets/css/reset.css', 'assets/css/theme.css'],
|     [
|         'assets/js/common.js',
|         ['path' => 'assets/js/utils.js', 'defer' => true],
|     ]
| );
|
*/


/*
|--------------------------------------------------------------------------
| Handle Request
|--------------------------------------------------------------------------
*/

echo $kernel->handle(new Request());
<?php

/**
 * --------------------------------------------------------------------------
 * APPLICATION BOOTSTRAP
 * --------------------------------------------------------------------------
 *
 * This file boots the application and handles the incoming request lifecycle.
 *
 * Flow:
 * 1. Load dependencies (Composer)
 * 2. Load environment variables
 * 3. Register error handling
 * 4. Initialize service container
 * 5. Register core services (Router, View)
 * 6. Load application helpers
 * 7. Load application routes
 * 8. Pass request through the Kernel
 * 9. Output the response
 */

require __DIR__ . '/../vendor/autoload.php';

use DevinciIT\Blprnt\Core\App;
use DevinciIT\Blprnt\Core\Router;
use DevinciIT\Blprnt\Core\View;
use DevinciIT\Blprnt\Core\ErrorHandler;
use DevinciIT\Blprnt\Http\Kernel;
use DevinciIT\Blprnt\Core\Request;

/* --------------------------------------------------------------------------
 ENVIRONMENT
 --------------------------------------------------------------------------

 Load environment variables from .env file

 Access via:
     $_ENV['KEY']
     getenv('KEY')
-------------------------------------------------------------------------- */

Dotenv\Dotenv::createImmutable(__DIR__ . '/../')->load();

/* --------------------------------------------------------------------------
 ERROR HANDLING
 --------------------------------------------------------------------------

 Registers global exception & error handling
 Provides useful output in development and safe output in production
-------------------------------------------------------------------------- */
ErrorHandler::register();

/* --------------------------------------------------------------------------
 SERVICE CONTAINER
 --------------------------------------------------------------------------

 Initialize the application service container (singleton pattern)
 This is the central hub for all service registration and resolution
-------------------------------------------------------------------------- */
$container = App::getInstance();

/* --------------------------------------------------------------------------
 CORE SERVICES
 --------------------------------------------------------------------------

 Register core services in the container:
 - Router    → Handles HTTP routing (singleton - same instance always)

 Services are resolved lazily (when requested) via closures.
 Router is cached to ensure routes persist across multiple container calls.
-------------------------------------------------------------------------- */
// Cache router instance to ensure same instance on repeated calls
$router = null;
$container->bind('router', function () use (&$router) {
    if ($router === null) {
        $router = new Router();
    }
    return $router;
});

/* --------------------------------------------------------------------------
 VIEW 
 --------------------------------------------------------------------------
 Initialize the static View with base path for view files.
 View uses static methods and is not bound to the container since it's
 a utility class with static state management.
-------------------------------------------------------------------------- */
View::init(__DIR__ . '/../app/Views');

/* --------------------------------------------------------------------------
 HELPERS
 --------------------------------------------------------------------------

 Load all global helper functions available to the application:
 - app()                 Get the service container
 - router()              Get the router instance  
 - view()                Render a view with data and optional assets
 - set_default_assets()  Configure global CSS/JS for all views

 This makes all helpers available globally for routes and controllers.
-------------------------------------------------------------------------- */
require __DIR__ . '/helpers.php';

/* --------------------------------------------------------------------------
 GLOBAL/DEFAULT ASSETS
 --------------------------------------------------------------------------
 Register CSS and JavaScript files to be included in all views automatically.
 These are added before per-render assets, so page-specific assets can override them.
 
 FILE VALIDATION: All paths are checked relative to public/ directory.
 If a file is not found, a warning is logged but rendering continues.
 
 Best practice structure:
 - public/assets/css/reset.css
 - public/assets/css/theme.css
 - public/assets/js/common.js
 
 Use set_default_assets() helper (available after loading helpers above).
 Call this once, and all views automatically get these assets—no need to pass them!
 
 Files must exist before being registered. Create them first, then uncomment below.
-------------------------------------------------------------------------- */

// Example: Uncomment after creating asset files
/*
set_default_assets(
    [
        'assets/css/reset.css',
        'assets/css/theme.css',
    ],
    [
        'assets/js/common.js',
        ['path' => 'assets/js/utils.js', 'defer' => true]
    ]
);
// Then in your controllers, just use:
//   view('dashboard', $data);  // CSS & JS already included automatically!
// Or with page-specific assets:
//   view('dashboard', $data, ['assets/css/dashboard.css']);
*/
require __DIR__ . '/helpers.php';

/* --------------------------------------------------------------------------
 ROUTES
 --------------------------------------------------------------------------

 Load application routes (web + API)
 
 The Router load() method accepts optional paths:
 - router()->load();                   // Defaults to routes/web.php
 - router()->load($customPath);        // Use custom route file
-------------------------------------------------------------------------- */
router()
    ->load(__DIR__ . '/../routes/web.php')
    ->load(__DIR__ . '/../routes/api.php');

/* --------------------------------------------------------------------------
 REQUEST LIFECYCLE
 --------------------------------------------------------------------------

 The Kernel processes the request:
 - Retrieves router from container
 - Matches route
 - Resolves controller
 - Runs middleware
 - Returns response
-------------------------------------------------------------------------- */
$kernel = new Kernel($container->make('router'));

/* --------------------------------------------------------------------------
 RESPONSE
 --------------------------------------------------------------------------

 Execute request and output final response (HTML, JSON, etc.)
-------------------------------------------------------------------------- */
echo $kernel->handle(new Request());

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
 * 4. Initialize core services (Router, View)
 * 5. Load application routes
 * 6. Pass request through the Kernel
 * 7. Output the response
 */

require __DIR__ . '/../vendor/autoload.php';

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
 CORE SERVICES
 --------------------------------------------------------------------------

 Router → Handles HTTP routing
 View   → Handles template rendering
-------------------------------------------------------------------------- */
$router = new Router();
View::init(__DIR__ . '/../app/Views');

/* --------------------------------------------------------------------------
 ROUTES
 --------------------------------------------------------------------------

 Register application routes (web + API)
-------------------------------------------------------------------------- */
require __DIR__ . '/../routes/web.php';
require __DIR__ . '/../routes/api.php';

/* --------------------------------------------------------------------------
 REQUEST LIFECYCLE
 --------------------------------------------------------------------------

 The Kernel processes the request:
 - Matches route
 - Resolves controller
 - Runs middleware
 - Returns response
-------------------------------------------------------------------------- */
$kernel = new Kernel($router);

/* --------------------------------------------------------------------------
 RESPONSE
 --------------------------------------------------------------------------

 Execute request and output final response (HTML, JSON, etc.)
-------------------------------------------------------------------------- */
echo $kernel->handle(new Request());

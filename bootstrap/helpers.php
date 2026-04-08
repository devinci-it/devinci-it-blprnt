<?php

use DevinciIT\Blprnt\Core\View;

/**
 * --------------------------------------------------------------------------
 * GLOBAL APPLICATION HELPERS
 * --------------------------------------------------------------------------
 *
 * This file contains all global helper functions available throughout
 * the application. These functions provide convenient access to core
 * services and utilities.
 *
 * Available helpers:
 * - app() - Get the service container
 * - router() - Get the router instance
 * - view() - Render a view with data and assets
 * - render_view() - Render a view without asset registration
 */

// ===========================================================================
// Application Container
// ===========================================================================

if (!function_exists('app')) {
    /**
     * Get the application instance
     *
     * Provides access to the global application container for service resolution
     * and dependency injection throughout the application.
     *
     * @return \DevinciIT\Blprnt\Core\App The application service container
     *
     * @example
     * // Register a service
     * app()->bind('database', fn($app) => new Database());
     *
     * // Retrieve a service
     * $router = app()->make('router');
     * $view = app()->make('view');
     *
     * // Check if service exists
     * if (app()->has('router')) {
     *     $router = app()->make('router');
     * }
     */
    function app()
    {
        return \DevinciIT\Blprnt\Core\App::getInstance();
    }
}

// ===========================================================================
// Router
// ===========================================================================

if (!function_exists('router')) {
    /**
     * Get the router instance
     *
     * Provides convenient access to the application router for route registration
     * and loading.
     *
     * @return \DevinciIT\Blprnt\Core\Router The router instance
     *
     * @example
     * // Load route files
     * router()->load(__DIR__ . '/../routes/web.php');
     *
     * // Register routes
     * router()->get('/posts', [PostController::class, 'index']);
     * router()->post('/posts', [PostController::class, 'store']);
     *
     * // Route grouping with middleware
     * router()->group(['middleware' => ['auth']], function ($router) {
     *     $router->get('/dashboard', [DashboardController::class, 'index']);
     * });
     */
    function router()
    {
        return app()->make('router');
    }
}

// ===========================================================================
// View Rendering
// ===========================================================================

if (!function_exists('view')) {
    /**
     * Render a view with data and optional assets
     *
     * Automatically registers CSS/JS files with the View so they're included
     * in the layout. Developers don't need to worry about duplicates—View
     * handles deduplication automatically.
     *
     * @param string $view View file path (without .php extension)
     * @param array $data Data to pass to the view
     * @param array $css Additional CSS file paths to load (automatically registered)
     * @param array $js Additional JS file paths to load (automatically registered)
     *            Can be strings or arrays with 'path' and optional 'defer' keys
     * @return void
     *
     * @example
     * view('dashboard', [
     *     'title' => 'Dashboard',
     *     'data' => $data
     * ], [
     *     'assets/css/dashboard.css'
     * ], [
     *     'assets/js/dashboard.js',
     *     ['path' => 'assets/js/analytics.js', 'defer' => true]
     * ]);
     */
    function view(string $view, array $data = [], array $css = [], array $js = []): void
    {
        // Automatically register CSS files
        foreach ($css as $cssFile) {
            View::addCss($cssFile);
        }

        // Automatically register JS files
        foreach ($js as $jsFile) {
            if (is_array($jsFile)) {
                // Format: ['path' => '...', 'defer' => true/false]
                $path = $jsFile['path'] ?? null;
                $defer = $jsFile['defer'] ?? true;
                if ($path !== null) {
                    View::addJs($path, $defer);
                }
            } else {
                // Simple string path, default defer=true
                View::addJs($jsFile, true);
            }
        }

        // Render view with empty CSS/JS arrays (already registered via View::addCss/addJs)
        View::render($view, $data, [], []);
    }
}

if (!function_exists('render_view')) {
    /**
     * Render a view without automatic asset registration
     *
     * Use this for simple views that don't need CSS/JS assets.
     * For most cases, use view() helper instead.
     *
     * @param string $view View file path (without .php extension)
     * @param array $data Data to pass to the view
     * @param array $css CSS files to include in this render only
     * @param array $js JS files to include in this render only
     * @return void
     *
     * @example
     * render_view('simple-page', ['title' => 'Welcome']);
     */
    function render_view(string $view, array $data = [], array $css = [], array $js = []): void
    {
        View::render($view, $data, $css, $js);
    }
}

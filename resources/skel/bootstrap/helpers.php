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
     * Render a view with data and optional assets (RECOMMENDED)
     *
     * This is the primary helper for rendering views. It automatically includes
     * all default CSS/JS registered via set_default_assets() in bootstrap/app.php.
     *
     * KEY POINT: Any DEFAULT assets registered via set_default_assets() in bootstrap
     * are AUTOMATICALLY included in every view render—you don't need to pass them!
     *
     * Variable Flow:
     * - view($view, $data, $css, $js)
     *   └─ View::render()
     *      ├─ Merges defaults + per-render assets
     *      └─ Passes $defaultCss, $defaultJs to layout
     *
     * Use this helper to:
     * - Pass data to the view
     * - Add page-specific CSS/JS on top of defaults
     *
     * @param string $view View file path (without .php extension)
     * @param array $data Data to pass to the view
     * @param array $css Additional CSS file paths for THIS PAGE ONLY
     *                   (defaults are added automatically)
     * @param array $js Additional JS file paths for THIS PAGE ONLY
     *                  (defaults are added automatically)
     *                  Can be strings or arrays with 'path' and optional 'defer' keys
     * @return void
     *
     * @example
     * // Scenario 1: View with just data (defaults automatically included)
     * view('dashboard', ['title' => 'Dashboard', 'user' => $user]);
     *
     * // Scenario 2: View with page-specific CSS/JS (on top of defaults)
     * view('dashboard', [
     *     'title' => 'Dashboard',
     *     'articles' => $articles
     * ], [
     *     'assets/css/dashboard.css'  // Added to defaults automatically
     * ], [
     *     'assets/js/dashboard.js',
     *     ['path' => 'assets/js/article-filter.js', 'defer' => true]
     * ]);
     *
     * HTML OUTPUT will include:
     * - All default CSS (from set_default_assets)
     * - Additional CSS passed here
     * - All default JS (from set_default_assets)
     * - Additional JS passed here
     */
    function view(string $view, array $data = [], array $css = [], array $js = []): void
    {
        // Pass directly to View::render() which handles:
        // 1. Merging defaults + per-render assets
        // 2. Preparing $defaultCss/$defaultJs variables for layout
        // 3. Rendering view and layout
        View::render($view, $data, $css, $js);
    }
}

if (!function_exists('render_view')) {
    /**
     * Render a view directly (ADVANCED - use view() instead in most cases)
     *
     * This helper passes through to View::render() without any special handling.
     * It's semantically identical to view() but more explicit about direct rendering.
     *
     * Variable Flow:
     * - render_view($view, $data, $css, $js)
     *   └─ View::render()  [same as view()]
     *      ├─ Merges defaults + per-render assets
     *      └─ Passes $defaultCss, $defaultJs to layout
     *
     * Use cases:
     * - When you explicitly want to call View::render() (preference)
     * - Less common - prefer view() helper for clarity
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
        // Direct pass-through to View::render()
        // Both view() and render_view() do the same thing—just different naming
        View::render($view, $data, $css, $js);
    }
}

// ===========================================================================
// Default Assets Configuration
// ===========================================================================

if (!function_exists('set_default_assets')) {
    /**
     * Define global/default CSS and JS files for all views
     *
     * This function should be called ONCE in bootstrap/app.php to configure
     * assets that should be included in every page automatically.
     *
     * These default assets are registered ONE TIME and then automatically
     * injected into all view renders without needing to pass them in every
     * view() call.
     *
     * @param array $css CSS file paths (relative to public/, e.g., 'assets/css/reset.css')
     * @param array $js JS file paths (relative to public/, e.g., 'assets/js/common.js')
     *                  Can be strings or arrays with 'path', 'defer', 'checkExists' keys
     * @param bool $validateFiles Whether to check that files exist (default: true)
     * @return void
     *
     * @example
     * // In bootstrap/app.php after View::init():
     * set_default_assets(
     *     // CSS files
     *     [
     *         'assets/css/reset.css',
     *         'assets/css/variables.css',
     *         'assets/css/theme.css',
     *     ],
     *     // JS files
     *     [
     *         'assets/js/common.js',                    // String format
     *         ['path' => 'assets/js/utils.js', 'defer' => true],  // Array format
     *         ['path' => 'assets/js/theme-switcher.js', 'defer' => false],
     *     ],
     *     true  // Validate that files exist
     * );
     *
     * IMPORTANT: After calling set_default_assets(), developers simply use:
     *
     *     view('dashboard', $data);  // CSS & JS already included automatically!
     *
     * Or with additional page-specific assets:
     *
     *     view('dashboard', $data, ['assets/css/dashboard.css']);
     *
     * The default CSS/JS are merged automatically. No duplicates.
     */
    function set_default_assets(
        array $css = [],
        array $js = [],
        bool $validateFiles = true
    ): void {
        if (!empty($css) || !empty($js)) {
            View::registerDefaults($css, $js, $validateFiles);
        }
    }
}

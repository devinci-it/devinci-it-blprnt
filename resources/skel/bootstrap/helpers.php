<?php

use DevinciIT\Blprnt\Core\View;

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
     */
    function render_view(string $view, array $data = [], array $css = [], array $js = []): void
    {
        View::render($view, $data, $css, $js);
    }
}

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

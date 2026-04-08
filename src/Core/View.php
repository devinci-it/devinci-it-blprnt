<?php
namespace DevinciIT\Blprnt\Core;

/**
 * View class for rendering PHP views and layouts
 *
 * Handles view rendering with support for static assets (CSS/JS),
 * data injection, and layout management.
 */
class View
{
    /**
     * @var string Base path for view files
     */
    protected static string $basePath;

    /**
     * @var string Default layout template file
     */
    protected static string $layout;

    /**
     * @var array<string> Registered CSS file paths
     */
    protected static array $cssFiles = [];

    /**
     * @var array<string> Registered JS file paths with defer flag
     */
    protected static array $jsFiles = [];

    /**
     * Initialize the View with base path and layout template
     *
     * @param string $basePath Base directory path for view files
     * @param string $layout Layout template file (default: 'layouts/main.php')
     * @return void
     */
    public static function init($basePath, $layout = 'layouts/main.php')
    {
        self::$basePath = rtrim($basePath, '/') . '/';
        self::$layout = $layout;
    }

    /**
     * Render a view with optional data and merged static assets
     *
     * This method handles view rendering with data injection and optional
     * per-render CSS/JS files. CSS/JS files passed here are automatically
     * registered with the View so they appear in the layout.
     *
     * @param string $view View file name (without extension)
     * @param array<string, mixed> $data Data to inject into view
     * @param array<string> $css Additional CSS file paths to register for this render
     * @param array<string|array> $js Additional JS file paths to register for this render
     *                               Can be strings or arrays with 'path' and 'defer' keys
     * @return void
     *
     * @example
     * View::render('dashboard', [
     *     'title' => 'Dashboard',
     *     'items' => $items
     * ], [
     *     'assets/css/dashboard.css'
     * ], [
     *     'assets/js/dashboard.js',
     *     ['path' => 'assets/js/analytics.js', 'defer' => true]
     * ]);
     */
    public static function render($view, $data = [], $css = [], $js = [])
    {
        // Register CSS files
        if (!empty($css)) {
            foreach ($css as $cssFile) {
                self::addCss($cssFile);
            }
        }

        // Register JS files
        if (!empty($js)) {
            foreach ($js as $jsFile) {
                if (is_array($jsFile)) {
                    // Format: ['path' => '...', 'defer' => true/false]
                    $path = $jsFile['path'] ?? null;
                    $defer = $jsFile['defer'] ?? true;
                    if ($path !== null) {
                        self::addJs($path, $defer);
                    }
                } else {
                    // Simple string path, default defer=true
                    self::addJs($jsFile, true);
                }
            }
        }

        // Render the view
        extract($data);
        ob_start();
        require self::$basePath . $view . '.php';
        $content = ob_get_clean();
        require self::$basePath . self::$layout;
    }

    /**
     * Add a CSS file to the view/layout
     *
     * Registers a static CSS file path to be included in the rendered layout.
     *
     * @param string $filePath Relative path to CSS file
     * @return void
     */
    public static function addCss(string $filePath): void
    {
        if (!in_array($filePath, self::$cssFiles)) {
            self::$cssFiles[] = $filePath;
        }
    }

    /**
     * Add a JavaScript file to the view/layout with optional defer attribute
     *
     * Registers a static JS file path to be included in the rendered layout.
     *
     * @param string $filePath Relative path to JavaScript file
     * @param bool $defer Whether to add defer attribute (default: true)
     * @return void
     */
    public static function addJs(string $filePath, bool $defer = true): void
    {
        $jsEntry = [
            'path' => $filePath,
            'defer' => $defer
        ];

        if (!in_array($jsEntry, self::$jsFiles)) {
            self::$jsFiles[] = $jsEntry;
        }
    }

    /**
     * Get all registered CSS files
     *
     * @return array<string> Array of CSS file paths
     */
    public static function getCssFiles(): array
    {
        return self::$cssFiles;
    }

    /**
     * Get all registered JavaScript files
     *
     * @return array<string|array> Array of JS entries with path and defer status
     */
    public static function getJsFiles(): array
    {
        return self::$jsFiles;
    }

    /**
     * Clear all registered CSS files
     *
     * @return void
     */
    public static function clearCss(): void
    {
        self::$cssFiles = [];
    }

    /**
     * Clear all registered JavaScript files
     *
     * @return void
     */
    public static function clearJs(): void
    {
        self::$jsFiles = [];
    }

    /**
     * Clear all registered assets (CSS and JS)
     *
     * @return void
     */
    public static function clearAssets(): void
    {
        self::clearCss();
        self::clearJs();
    }
}

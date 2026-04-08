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
     * @var array<string> Default/global CSS files applied to all views
     */
    protected static array $defaultCssFiles = [];

    /**
     * @var array<string> Default/global JS files applied to all views
     */
    protected static array $defaultJsFiles = [];

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
    /**
     * Render a view with layout
     *
     * This method:
     * 1. Clears per-render assets from last render
     * 2. Registers CSS/JS files (per-render only)
     * 3. Prepares $defaultCss and $defaultJs variables
     * 4. Renders the view file
     * 5. Passes $defaultCss/$defaultJs/content to layout
     *
     * Variable Flow to Layout:
     * - $defaultCss   ← array of all CSS files (defaults + per-render)
     * - $defaultJs    ← array of all JS files (defaults + per-render)
     * - $content      ← rendered view HTML
     * - $title        ← from $data (if provided)
     * - Any $data     ← extracted as variables
     *
     * The layout receives these via extract(compact(...)) so they're available
     * in the layout.php file without needing View class imports or method calls.
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
        // Clear per-render assets from previous render
        self::clearCss();
        self::clearJs();
        
        // Register CSS files (per-render only - defaults are applied via getCssFiles() merge)
        if (!empty($css)) {
            foreach ($css as $cssFile) {
                self::addCss($cssFile);
            }
        }

        // Register JS files (per-render only - defaults are applied via getJsFiles() merge)
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

        // Prepare CSS and JS arrays for the layout (reduces PHP logic in markup)
        $defaultCss = self::getCssFiles();
        $defaultJs = self::getJsFiles();

        // Render the view
        extract($data);
        ob_start();
        require self::$basePath . $view . '.php';
        $content = ob_get_clean();

        // Make CSS/JS available to layout without needing View imports
        extract(compact('defaultCss', 'defaultJs', 'content'));
        require self::$basePath . self::$layout;
    }

    /**
     * Register a global/default CSS file applied to all views
     *
     * Global CSS files are included in every view/layout automatically.
     * Added before per-render CSS files.
     *
     * Validates that the file exists relative to the public directory before registering.
     * If file doesn't exist, a warning is logged but registration continues
     * (files may be generated at runtime).
     *
     * @param string $filePath Relative path to CSS file (relative to public/)
     * @param bool $checkExists Whether to validate file existence (default: true)
     * @return bool True if registered, false if validation failed
     *
     * @example
     * View::addDefaultCss('assets/css/reset.css');      // Must exist in public/assets/css/
     * View::addDefaultCss('assets/css/theme.css', true);
     */
    public static function addDefaultCss(string $filePath, bool $checkExists = true): bool
    {
        // Check if file exists relative to public directory
        if ($checkExists) {
            $publicPath = self::findPublicPath();
            $fullPath = $publicPath . '/' . ltrim($filePath, '/');

            if (!file_exists($fullPath)) {
                trigger_error(
                    "Default CSS file not found: {$filePath} (checked at: {$fullPath})",
                    E_USER_WARNING
                );
                return false;
            }
        }

        if (!in_array($filePath, self::$defaultCssFiles)) {
            self::$defaultCssFiles[] = $filePath;
        }
        return true;
    }

    /**
     * Register a global/default JavaScript file applied to all views
     *
     * Global JS files are included in every view/layout automatically.
     * Added before per-render JS files.
     *
     * Validates that the file exists relative to the public directory before registering.
     * If file doesn't exist, a warning is logged but registration continues
     * (files may be generated at runtime).
     *
     * @param string $filePath Relative path to JavaScript file (relative to public/)
     * @param bool $defer Whether to add defer attribute (default: true)
     * @param bool $checkExists Whether to validate file existence (default: true)
     * @return bool True if registered, false if validation failed
     *
     * @example
     * View::addDefaultJs('assets/js/common.js');                        // Must exist
     * View::addDefaultJs('assets/js/analytics.js', false);              // Analytics (no defer)
     * View::addDefaultJs('assets/js/generated.js', true, false);        // Skip check
     */
    public static function addDefaultJs(string $filePath, bool $defer = true, bool $checkExists = true): bool
    {
        // Check if file exists relative to public directory
        if ($checkExists) {
            $publicPath = self::findPublicPath();
            $fullPath = $publicPath . '/' . ltrim($filePath, '/');

            if (!file_exists($fullPath)) {
                trigger_error(
                    "Default JS file not found: {$filePath} (checked at: {$fullPath})",
                    E_USER_WARNING
                );
                return false;
            }
        }

        $jsEntry = [
            'path' => $filePath,
            'defer' => $defer
        ];

        if (!in_array($jsEntry, self::$defaultJsFiles)) {
            self::$defaultJsFiles[] = $jsEntry;
        }
        return true;
    }

    /**
     * Get all default/global CSS files
     *
     * @return array<string> Array of CSS file paths
     */
    public static function getDefaultCssFiles(): array
    {
        return self::$defaultCssFiles;
    }

    /**
     * Get all default/global JavaScript files
     *
     * @return array<string|array> Array of JS entries with path and defer status
     */
    public static function getDefaultJsFiles(): array
    {
        return self::$defaultJsFiles;
    }

    /**
     * Clear all default/global CSS files
     *
     * @return void
     */
    public static function clearDefaultCss(): void
    {
        self::$defaultCssFiles = [];
    }

    /**
     * Clear all default/global JavaScript files
     *
     * @return void
     */
    public static function clearDefaultJs(): void
    {
        self::$defaultJsFiles = [];
    }

    /**
     * Register default/global CSS and JS files from configuration
     *
     * Convenience method to register multiple default assets at once.
     * Perfect for use in bootstrap/app.php after View::init()
     *
     * All files are validated to exist relative to public directory.
     * Skips files that don't exist and logs warnings.
     *
     * @param array<string> $css Array of CSS file paths (relative to public/)
     * @param array<string|array> $js Array of JS file paths (strings or ['path' => '...', 'defer' => true/false])
     * @param bool $checkExists Whether to validate file existence (default: true)
     * @return void
     *
     * @example
     * View::registerDefaults([
     *     'assets/css/reset.css',
     *     'assets/css/typography.css',
     *     'assets/css/theme.css'
     * ], [
     *     'assets/js/common.js',
     *     ['path' => 'assets/js/analytics.js', 'defer' => false]
     * ]);
     */
    public static function registerDefaults(array $css = [], array $js = [], bool $checkExists = true): void
    {
        foreach ($css as $cssFile) {
            self::addDefaultCss($cssFile, $checkExists);
        }

        foreach ($js as $jsFile) {
            if (is_array($jsFile)) {
                $path = $jsFile['path'] ?? null;
                $defer = $jsFile['defer'] ?? true;
                $skipCheck = $jsFile['checkExists'] ?? $checkExists;
                if ($path !== null) {
                    self::addDefaultJs($path, $defer, $skipCheck);
                }
            } else {
                self::addDefaultJs($jsFile, true, $checkExists);
            }
        }
    }

    /**
     * Add a CSS file to the view/layout
     *
     * Registers a static CSS file path to be included in the rendered layout.
     * Prevents duplicates by checking if the file is already registered (either as default or per-render).
     *
     * @param string $filePath Relative path to CSS file
     * @return void
     */
    public static function addCss(string $filePath): void
    {
        // Don't add if already in defaults or per-render
        if (!in_array($filePath, self::$defaultCssFiles) && !in_array($filePath, self::$cssFiles)) {
            self::$cssFiles[] = $filePath;
        }
    }

    /**
     * Add a JavaScript file to the view/layout with optional defer attribute
     *
     * Registers a static JS file path to be included in the rendered layout.
     * Prevents duplicates by checking if the file is already registered (either as default or per-render).
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

        // Check if already in defaults
        $inDefaults = false;
        foreach (self::$defaultJsFiles as $defaultJs) {
            if (is_array($defaultJs) && $defaultJs['path'] === $filePath) {
                $inDefaults = true;
                break;
            } elseif (is_string($defaultJs) && $defaultJs === $filePath) {
                $inDefaults = true;
                break;
            }
        }

        // Check if already in per-render
        $inRender = false;
        foreach (self::$jsFiles as $js) {
            if ($js['path'] === $filePath) {
                $inRender = true;
                break;
            }
        }

        // Only add if not already registered in defaults or per-render
        if (!$inDefaults && !$inRender) {
            self::$jsFiles[] = $jsEntry;
        }
    }

    /**
     * Get all registered CSS files (default + per-render)
     *
     * Returns combined array of default/global CSS files followed by per-render CSS files.
     *
     * @return array<string> Array of CSS file paths
     */
    public static function getCssFiles(): array
    {
        return array_merge(self::$defaultCssFiles, self::$cssFiles);
    }

    /**
     * Get all registered JavaScript files (default + per-render)
     *
     * Returns combined array of default/global JS files followed by per-render JS files.
     *
     * @return array<string|array> Array of JS entries with path and defer status
     */
    public static function getJsFiles(): array
    {
        return array_merge(self::$defaultJsFiles, self::$jsFiles);
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

    /**
     * Find the public directory path
     *
     * Searches for 'public' directory by traversing up from current location.
     * Used for validating asset file paths.
     *
     * @return string Absolute path to public directory
     */
    private static function findPublicPath(): string
    {
        // Start from current working directory
        $dir = getcwd();
        
        // Check current directory and up to 5 levels
        for ($i = 0; $i < 5; $i++) {
            if (is_dir($dir . '/public')) {
                return $dir . '/public';
            }
            $dir = dirname($dir);
            if ($dir === '/') {
                break;
            }
        }
        
        // Fallback: assume public is in project root
        return getcwd() . '/public';
    }
}

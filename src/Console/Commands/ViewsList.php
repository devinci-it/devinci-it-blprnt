<?php

namespace DevinciIT\Blprnt\Console\Commands;

use DevinciIT\Blprnt\Console\Command;
use DevinciIT\Blprnt\Core\View;

/**
 * List Views configuration including default assets and layout path
 *
 * Shows:
 * - Base Views directory path
 * - Default layout template
 * - All registered global/default CSS files
 * - All registered global/default JavaScript files
 *
 * Usage:
 *   php blprnt views:list
 */
class ViewsList extends Command
{
    protected string $signature = 'views:list';
    protected string $description = 'List Views configuration, assets, and layout path';

    public function handle(array $args = []): void
    {
        echo "\n";
        echo str_repeat('=', 100) . "\n";
        echo "Views Configuration\n";
        echo str_repeat('=', 100) . "\n\n";

        // Load the consumer application to get Views configuration from bootstrap/app.php
        $this->loadApplication();

        // Reflection to access protected static properties
        $reflection = new \ReflectionClass(View::class);
        
        // Get basePath
        $basePathProperty = $reflection->getProperty('basePath');
        $basePathProperty->setAccessible(true);
        $basePath = 'Not initialized';
        try {
            $basePath = $basePathProperty->getValue();
        } catch (\Throwable $e) {
            // basePath not initialized
        }

        // Get layout
        $layoutProperty = $reflection->getProperty('layout');
        $layoutProperty->setAccessible(true);
        $layout = 'Not set';
        try {
            $layout = $layoutProperty->getValue();
        } catch (\Throwable $e) {
            // layout not initialized
        }

        // Display configuration
        echo "📁 Views Base Directory\n";
        echo str_repeat('-', 100) . "\n";
        if ($basePath === 'Not initialized') {
            echo "   ⚠️  " . $basePath . "\n";
        } else {
            echo "   " . $basePath . "\n";
        }
        echo "\n";

        echo "📄 Default Layout Template\n";
        echo str_repeat('-', 100) . "\n";
        if ($layout === 'Not set') {
            echo "   ⚠️  " . $layout . "\n";
        } else {
            echo "   " . $layout . "\n";
        }
        echo "\n";

        // Display default CSS files
        $defaultCss = View::getDefaultCssFiles();
        echo "🎨 Global CSS Files (" . count($defaultCss) . ")\n";
        echo str_repeat('-', 100) . "\n";
        if (!empty($defaultCss)) {
            foreach ($defaultCss as $index => $css) {
                echo "   " . str_pad(($index + 1) . ".", 4) . $css . "\n";
            }
        } else {
            echo "   None configured\n";
        }
        echo "\n";

        // Display default JS files
        $defaultJs = View::getDefaultJsFiles();
        echo "⚙️  Global JavaScript Files (" . count($defaultJs) . ")\n";
        echo str_repeat('-', 100) . "\n";
        if (!empty($defaultJs)) {
            foreach ($defaultJs as $index => $js) {
                $path = is_array($js) ? $js['path'] : $js;
                $defer = is_array($js) ? $js['defer'] : true;
                $deferLabel = $defer ? '(defer)' : '(blocking)';
                echo "   " . str_pad(($index + 1) . ".", 4) . $path . " " . $deferLabel . "\n";
            }
        } else {
            echo "   None configured\n";
        }
        echo "\n";

        // Summary
        echo str_repeat('=', 100) . "\n";
        echo "Summary: " . count($defaultCss) . " CSS file(s) + " . count($defaultJs) . " JS file(s) globally configured\n";
        echo str_repeat('=', 100) . "\n\n";

        // Help text
        echo "📝 To configure defaults, add to bootstrap/app.php:\n\n";
        echo "   View::registerDefaults(\n";
        echo "       ['assets/css/reset.css', 'assets/css/theme.css'],\n";
        echo "       ['assets/js/common.js']\n";
        echo "   );\n\n";
    }

    /**
     * Load the consumer application context (bootstrap/app.php)
     * This initializes the View with the app's configuration
     */
    private function loadApplication(): void
    {
        $projectRoot = getcwd();
        $bootstrapFile = $projectRoot . '/bootstrap/app.php';

        // Check if running from framework directory
        if (!file_exists($bootstrapFile)) {
            $bootstrapFile = __DIR__ . '/../../../bootstrap/app.php';
        }

        if (file_exists($bootstrapFile)) {
            require_once $bootstrapFile;
        }
    }
}

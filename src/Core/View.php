<?php
namespace DevinciIT\Blprnt\Core;

class View
{
    protected static string $basePath;
    protected static string $layout;

    public static function init($basePath, $layout = 'layouts/main.php')
    {
        self::$basePath = rtrim($basePath, '/') . '/';
        self::$layout = $layout;
    }

    public static function render($view, $data = [], $css = [], $js = [])
    {
        extract($data);
        ob_start();
        require self::$basePath . $view . '.php';
        $content = ob_get_clean();
        require self::$basePath . self::$layout;
    }
}

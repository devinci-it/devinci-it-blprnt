<?php
namespace DevinciIT\Blprnt\Core;

use Throwable;

class ErrorHandler
{
    public static function register(): void
    {
        set_exception_handler([self::class, 'handle']);
    }

    public static function handle(Throwable $e): void
    {
        self::storeThrowable($e);

        if (self::isLocalDevelopment()) {
            self::renderLocalError();
            return;
        }

        http_response_code(500);
        echo 'Server Error';
    }

    public static function storeThrowable(Throwable $throwable): void
    {
        $GLOBALS['blprnt_error'] = $throwable;
        $GLOBALS['blprnt_stack_trace'] = $throwable->getTraceAsString();
        $GLOBALS['blprnt_stack_trace_array'] = $throwable->getTrace();
    }

    public static function isLocalDevelopment(): bool
    {
        $appEnv = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'production';

        return in_array(strtolower((string) $appEnv), ['local', 'development', 'dev'], true);
    }

    public static function renderLocalError(): void
    {
        $path=__DIR__ . '/../../app/Views/errors/debug.php' ?? __DIR__ . '/../../vendor/devinci-it/blprnt/app/Views/errors/debug.php';
        // Render from verndor or app views if exists
        if (file_exists(__DIR__ . '/../../app/Views/errors/debug.php')) {
            require __DIR__ . '/../../app/Views/errors/debug.php';
            return;
        }elseif (file_exists(__DIR__ . '/../../vendor/devinci-it/blprnt/app/Views/errors/debug.php')) {
            require __DIR__ . '/../../vendor/devinci-it/blprnt/app/Views/errors/debug.php';
            return;
        }
    }
}

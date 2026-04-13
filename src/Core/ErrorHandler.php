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

        self::logThrowable($e);

        if (self::isCli()) {
            self::renderCliError($e);
            return;
        }

        if (self::isLocalDevelopment()) {
            self::renderLocalError();
            return;
        }

        $statusCode = (int) $e->getCode();
        if ($statusCode < 400 || $statusCode > 599) {
            $statusCode = 500;
        }

        self::renderHttpErrorPage($statusCode);
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
        $debugViewPath = self::resolveDebugViewPath();

        if ($debugViewPath !== null) {
            View::render('errors/debug', [], [], []);
            // require $debugViewPath;
            return;
        }

        // Fallback if debug template cannot be located.
        http_response_code(500);
        echo 'Unhandled Exception (debug view not found).';
    }

    public static function renderHttpErrorPage(int $statusCode = 500, ?string $message = null): void
    {
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=utf-8');

        $httpStatusCode = $statusCode;
        $httpTitle = self::getHttpStatusText($statusCode);
        $httpMessage = $message ?? self::getDefaultHttpMessage($statusCode);

        if (View::isInitialized() && View::viewExists('errors/http')) {
            try {
                View::render('errors/http', [
                    'title' => $httpStatusCode . ' ' . $httpTitle,
                    'httpStatusCode' => $httpStatusCode,
                    'httpTitle' => $httpTitle,
                    'httpMessage' => $httpMessage,
                ], [
                    'assets/css/http-error.css',
                ]);
                return;
            } catch (Throwable $renderError) {
                self::logThrowable($renderError);
            }
        }

        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>'
            . htmlspecialchars("{$httpStatusCode} {$httpTitle}", ENT_QUOTES, 'UTF-8')
            . '</title></head><body>'
            . htmlspecialchars($httpMessage, ENT_QUOTES, 'UTF-8')
            . '</body></html>';
    }

    public static function isCli(): bool
    {
        return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
    }

    public static function renderCliError(Throwable $e): void
    {
        $output = [];
        $output[] = '';
        $output[] = '=== Blprnt Exception ===';
        $output[] = 'Type: ' . get_class($e);
        $output[] = 'Message: ' . $e->getMessage();
        $output[] = 'File: ' . $e->getFile() . ':' . $e->getLine();

        if (self::isLocalDevelopment()) {
            $output[] = 'Trace:';
            $output[] = $e->getTraceAsString();
        }

        $output[] = '';
        fwrite(STDERR, implode(PHP_EOL, $output));
    }

    private static function logThrowable(Throwable $e): void
    {
        error_log(sprintf(
            '[blprnt] %s: %s in %s:%d',
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ));
    }

    private static function resolveDebugViewPath(): ?string
    {
        $paths = [
            __DIR__ . '/../../app/Views/errors/debug.php',
            __DIR__ . '/../../vendor/devinci-it/blprnt/resources/views/debug.php',
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    public static function getHttpStatusText(int $statusCode): string
    {
        $statusMap = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            422 => 'Unprocessable Entity',
            500 => 'Internal Server Error',
            503 => 'Service Unavailable',
        ];

        return $statusMap[$statusCode] ?? 'Error';
    }

    private static function getDefaultHttpMessage(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'Your request could not be processed.',
            401 => 'Authentication is required to access this resource.',
            403 => 'You do not have permission to access this resource.',
            404 => 'The page you requested could not be found.',
            405 => 'This HTTP method is not allowed for the requested route.',
            422 => 'The request contains invalid data.',
            503 => 'The service is temporarily unavailable.',
            default => 'An unexpected error occurred. Please try again later.',
        };
    }
}

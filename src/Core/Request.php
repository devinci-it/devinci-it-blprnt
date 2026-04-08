<?php
namespace DevinciIT\Blprnt\Core;

class Request
{
    protected $jsonData = [];
    protected $type = 'web'; // 'api', 'web', or 'console'

    public function __construct()
    {
        // Parse JSON body from request
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = file_get_contents('php://input');
            $this->jsonData = json_decode($rawBody, true) ?? [];
        }

        // Auto-detect request type
        $this->detectRequestType();
    }

    /**
     * Auto-detect whether this is an API, Web, or Console request
     */
    private function detectRequestType(): void
    {
        // Console request
        if (php_sapi_name() === 'cli') {
            $this->type = 'console';
            return;
        }

        // Check if this is an API request
        $uri = $this->uri();
        $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        // API request indicators
        $isApiRoute = strpos($uri, '/api') === 0 || strpos($uri, '/api/') === 0;
        $acceptsJson = strpos($acceptHeader, 'application/json') !== false;
        $isJsonContent = strpos($contentType, 'application/json') !== false;

        if ($isApiRoute || $acceptsJson || $isJsonContent) {
            $this->type = 'api';
        } else {
            $this->type = 'web';
        }
    }

    /**
     * Get the type of request: 'api', 'web', or 'console'
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * Set the request type explicitly
     */
    public function setType(string $type): void
    {
        if (in_array($type, ['api', 'web', 'console'])) {
            $this->type = $type;
        }
    }

    /**
     * Check if this is an API request
     */
    public function isApi(): bool
    {
        return $this->type === 'api';
    }

    /**
     * Check if this is a web request
     */
    public function isWeb(): bool
    {
        return $this->type === 'web';
    }

    /**
     * Check if this is a console request
     */
    public function isConsole(): bool
    {
        return $this->type === 'console';
    }

    public function uri()
    {
        return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }

    public function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Get input from JSON body or POST data
     */
    public function input($key, $default = null)
    {
        return $this->jsonData[$key] ?? $_POST[$key] ?? $default;
    }

    /**
     * Get query string parameters
     */
    public function query($key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Get all input data (JSON or POST)
     */
    public function all()
    {
        return !empty($this->jsonData) ? $this->jsonData : $_POST;
    }
}


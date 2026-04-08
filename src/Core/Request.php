<?php
namespace DevinciIT\Blprnt\Core;

class Request
{
    protected $jsonData = [];

    public function __construct()
    {
        // Parse JSON body from request
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = file_get_contents('php://input');
            $this->jsonData = json_decode($rawBody, true) ?? [];
        }
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

    /**
     * Get Content-Type header
     */
    public function contentType(): string
    {
        return $_SERVER['CONTENT_TYPE'] ?? '';
    }

    /**
     * Get Accept header
     */
    public function accept(): string
    {
        return $_SERVER['HTTP_ACCEPT'] ?? '';
    }

    /**
     * Check if running in CLI
     */
    public static function isCliRequest(): bool
    {
        return php_sapi_name() === 'cli';
    }

    /**
     * Check if this is an API request
     * API indicators: /api route prefix OR application/json Accept/Content-Type
     */
    public function isApi(): bool
    {
        $uri = $this->uri();
        $isApiRoute = strpos($uri, '/api') === 0 || strpos($uri, '/api/') === 0;
        $acceptsJson = strpos($this->accept(), 'application/json') !== false;
        $isJsonContent = strpos($this->contentType(), 'application/json') !== false;

        return $isApiRoute || $acceptsJson || $isJsonContent;
    }

    /**
     * Check if this is a web request (traditional HTTP from browser)
     */
    public function isWeb(): bool
    {
        return !$this->isApi() && !self::isCliRequest();
    }

    /**
     * Check if this is a CLI request
     */
    public function isCli(): bool
    {
        return self::isCliRequest();
    }
}



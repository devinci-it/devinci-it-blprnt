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

    /**
     * Check if a file was uploaded in this request
     */
    public function hasFile(string $field): bool
    {
        return isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Get uploaded file info
     *
     * @return array|null File info array with keys: name, type, size, tmp_name, error
     */
    public function file(string $field): ?array
    {
        if (!$this->hasFile($field)) {
            return null;
        }

        return [
            'name' => $_FILES[$field]['name'],
            'type' => $_FILES[$field]['type'],
            'size' => $_FILES[$field]['size'],
            'tmp_name' => $_FILES[$field]['tmp_name'],
            'error' => $_FILES[$field]['error'],
        ];
    }

    /**
     * Get all uploaded files
     *
     * @return array Associative array of all uploaded files
     */
    public function files(): array
    {
        $files = [];

        if (!empty($_FILES)) {
            foreach ($_FILES as $field => $file_info) {
                if ($file_info['error'] === UPLOAD_ERR_OK) {
                    $files[$field] = [
                        'name' => $file_info['name'],
                        'type' => $file_info['type'],
                        'size' => $file_info['size'],
                        'tmp_name' => $file_info['tmp_name'],
                        'error' => $file_info['error'],
                    ];
                }
            }
        }

        return $files;
    }
}

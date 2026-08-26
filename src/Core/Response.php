<?php
namespace DevinciIT\Blprnt\Core;

/**
 * A finished HTTP response — status, headers, and body — that doesn't touch
 * the actual output stream until send() is called.
 *
 * This used to be a static-only class where Response::json() echoed
 * immediately as a side effect and returned void. That made
 * `return Response::json($data);` from a controller silently do the wrong
 * thing: the JSON already left the process during the call (bypassing
 * Http\Kernel::handleApi()'s success/data envelope entirely), while the
 * controller's actual return value was null — so Kernel and the bootstrap
 * layer had nothing meaningful to work with. See
 * docs/wiki/Core/Request-Response.wiki.md for the full writeup.
 *
 * Now: Response::json()/text()/html() build and return an instance: nothing
 * is written to the output stream until send() runs. Http\Kernel and the
 * HttpBootstrap(Builder) entry points call send() when a controller/route
 * returns a Response instance, and fall back to echoing the value directly
 * otherwise (unchanged behavior for closures/controllers that return a
 * plain array/string, or that call view() and return nothing).
 */
class Response
{
    protected string $body;
    protected int $statusCode;

    /** @var array<string, string> */
    protected array $headers;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(string $body = '', int $statusCode = 200, array $headers = [])
    {
        $this->body = $body;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * @param mixed $data Anything json_encode() accepts.
     */
    public static function json($data, int $statusCode = 200): self
    {
        return new self(json_encode($data), $statusCode, [
            'Content-Type' => 'application/json',
            // Security headers — same defaults this class has always shipped.
            'Permissions-Policy' => 'interest-cohort=()',
            'Referrer-Policy' => 'no-referrer',
            'Strict-Transport-Security' => 'max-age=63072000; includeSubDomains; preload',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self'; object-src 'none';",
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public static function text(string $body, int $statusCode = 200): self
    {
        return new self($body, $statusCode, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    public static function html(string $body, int $statusCode = 200): self
    {
        return new self($body, $statusCode, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function status(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Write this response to the actual output stream. The one place in the
     * whole request lifecycle that's allowed to do that for a Response
     * instance — everything upstream (controllers, Router, Kernel) only
     * ever builds or passes one along.
     */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);

            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }

        echo $this->body;
    }

    /**
     * Finalize whatever Http\Kernel::handle() returned: send() it if it's a
     * Response instance, otherwise echo it as-is — unchanged behavior for a
     * closure/controller that returned a plain array/string, or that called
     * view() (which echoes directly and returns null) rather than building
     * a Response. The one place HttpBootstrap/HttpBootstrapBuilder hand off
     * to actual output.
     *
     * @param mixed $value
     */
    public static function emit($value): void
    {
        if ($value instanceof self) {
            $value->send();
            return;
        }

        echo $value;
    }
}

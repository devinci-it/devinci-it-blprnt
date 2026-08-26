<?php
namespace DevinciIT\Blprnt\Core;

/**
 * Centralized HTTP bootstrap so application entry files stay minimal.
 *
 * Now exposes a fluent builder API for strict 1:1 parity with legacy setup().
 */
class HttpBootstrap
{
    /**
     * Get a fluent builder for HTTP bootstrap (strictly matches legacy setup()).
     *
     * @param string $basePath Absolute project root path.
     * @return HttpBootstrapBuilder
     */
    public static function builder(string $basePath): HttpBootstrapBuilder
    {
        return new HttpBootstrapBuilder($basePath);
    }

    /**
     * Run the full HTTP kernel lifecycle with default options (parity with setup()).
     *
     * @param string $basePath
     * @param Request|null $request
     * @return void
     */
    public static function run(string $basePath, ?Request $request = null): void
    {
        $result = self::builder($basePath)
            ->build()
            ->kernel
            ->handle($request ?? new Request());

        Response::emit($result);
    }
}
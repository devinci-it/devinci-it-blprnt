<?php

namespace DevinciIT\Blprnt\Core;

use DevinciIT\Blprnt\Http\Kernel;

class HttpBootstrapBuilder
{
    private string $basePath;
    private bool $preferSourceRoot = false;
    private bool $preferSourceRootConfigured = false;
    private ?string $sourceRoot = null;
    private bool $sourceRootConfigured = false;

    private bool $loadEnv = true;
    private ?string $envPath = null;

    private bool $registerErrorHandler = true;

    private bool $initView = true;
    private ?string $viewPath = null;
    private string $viewLayout = 'layouts/main.php';

    private bool $loadHelpers = true;
    private ?string $helpersPath = null;

    private string $routerBindingKey = 'router';

    /** @var string[] */
    private array $routeFiles = [];
    private bool $routeFilesCustomized = false;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');

        $this->preferSourceRoot = $this->readBoolEnv('BLPRNT_DEV_SOURCE', false);

        $sourceRoot = $this->readStringEnv('BLPRNT_DEV_ROOT');
        if (is_string($sourceRoot) && $sourceRoot !== '') {
            $this->sourceRoot = trim($sourceRoot, '/');
        }

        $this->routeFiles = [
            $this->resolveProjectPath('routes/web.php'),
            $this->resolveProjectPath('routes/api.php'),
        ];
    }

    /* ------------------------------------------------------------------
     | Fluent Configuration
     ------------------------------------------------------------------*/

    public function withoutEnv(): self
    {
        $this->loadEnv = false;
        return $this;
    }

    public function withEnvPath(string $path): self
    {
        $this->envPath = $path;
        return $this;
    }

    public function withoutErrorHandler(): self
    {
        $this->registerErrorHandler = false;
        return $this;
    }

    public function withView(string $path, string $layout = 'layouts/main.php'): self
    {
        $this->initView = true;
        $this->viewPath = $path;
        $this->viewLayout = $layout;
        return $this;
    }

    public function withoutView(): self
    {
        $this->initView = false;
        return $this;
    }

    public function withHelpers(string $path): self
    {
        $this->helpersPath = $path;
        return $this;
    }

    public function withoutHelpers(): self
    {
        $this->loadHelpers = false;
        return $this;
    }

    public function withRouterKey(string $key): self
    {
        $this->routerBindingKey = $key;
        return $this;
    }

    public function withRoutes(array $files): self
    {
        $this->routeFiles = $files;
        $this->routeFilesCustomized = true;
        return $this;
    }

    /**
     * Prefer looking for app/bootstrap/routes/public under source root (for framework development).
     */
    public function preferSourceRoot(bool $enabled = true): self
    {
        $this->preferSourceRoot = $enabled;
        $this->preferSourceRootConfigured = true;

        return $this;
    }

    /**
     * Set source root used when source-root mode is enabled (e.g. "src" or "resources/skel").
     */
    public function withSourceRoot(string $root): self
    {
        $this->sourceRoot = trim($root, '/');
        $this->sourceRootConfigured = true;

        return $this;
    }

    /* ------------------------------------------------------------------
     | Build
     ------------------------------------------------------------------*/

    public function build(): BootstrapResult
    {
        $this->loadEnvironment();
        $this->hydrateSourceRootSettingsFromEnv();
        $this->publishDebugContext();

        if ($this->registerErrorHandler) {
            ErrorHandler::register();
        }

        $app = App::getInstance();

        $this->bindRouter($app);

        if ($this->initView) {
            View::init(
                $this->viewPath ?? $this->resolveProjectPath('app/Views'),
                $this->viewLayout
            );
        }

        if ($this->loadHelpers) {
            $helpers = $this->helpersPath ?? $this->resolveProjectPath('bootstrap/helpers.php');

            if (is_file($helpers)) {
                require_once $helpers;
            }
        }

        $router = $app->make($this->routerBindingKey);

        $this->loadRoutes($router);

        $kernel = new Kernel($router);

        return new BootstrapResult($app, $router, $kernel);
    }

    public function run(?Request $request = null): void
    {
        $result = $this->build();
        echo $result->kernel->handle($request ?? new Request());
    }

    /* ------------------------------------------------------------------
     | Internals
     ------------------------------------------------------------------*/

    private function loadEnvironment(): void
    {
        if (!$this->loadEnv || !class_exists('\\Dotenv\\Dotenv')) {
            return;
        }

        $path = $this->envPath ?? $this->basePath;
        $file = $path . '/.env';

        if (!is_file($file)) {
            return;
        }

        \Dotenv\Dotenv::createImmutable($path)->safeLoad();
    }

    private function bindRouter(App $app): void
    {
        if ($app->has($this->routerBindingKey)) {
            return;
        }

        $router = null;

        $app->bind($this->routerBindingKey, function () use (&$router) {
            return $router ??= new Router();
        });
    }

    private function loadRoutes(Router $router): void
    {
        foreach ($this->routeFiles as $file) {
            if (is_string($file) && is_file($file)) {
                $router->load($file);
            }
        }
    }

    private function hydrateSourceRootSettingsFromEnv(): void
    {
        if (!$this->preferSourceRootConfigured) {
            $this->preferSourceRoot = $this->readBoolEnv('BLPRNT_DEV_SOURCE', $this->preferSourceRoot);
        }

        if (!$this->sourceRootConfigured) {
            $sourceRoot = $this->readStringEnv('BLPRNT_DEV_ROOT');
            if (is_string($sourceRoot) && $sourceRoot !== '') {
                $this->sourceRoot = trim($sourceRoot, '/');
            }
        }

        if (!$this->routeFilesCustomized) {
            $this->routeFiles = [
                $this->resolveProjectPath('routes/web.php'),
                $this->resolveProjectPath('routes/api.php'),
            ];
        }
    }

    private function resolveProjectPath(string $relativePath): string
    {
        $relativePath = ltrim($relativePath, '/');
        $projectPath = $this->basePath . '/' . $relativePath;

        if (!$this->preferSourceRoot) {
            return $projectPath;
        }

        foreach ($this->sourceRootCandidates() as $root) {
            $candidate = $root . '/' . $relativePath;
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return $projectPath;
    }

    /**
     * @return string[]
     */
    private function sourceRootCandidates(): array
    {
        $roots = [];

        if (is_string($this->sourceRoot) && $this->sourceRoot !== '') {
            $roots[] = $this->basePath . '/' . $this->sourceRoot;
        }

        $roots[] = $this->basePath . '/src';

        return array_values(array_unique($roots));
    }

    private function readStringEnv(string $key): ?string
    {
        if (isset($_ENV[$key]) && is_string($_ENV[$key]) && trim($_ENV[$key]) !== '') {
            return trim($_ENV[$key]);
        }

        $value = getenv($key);
        if ($value !== false && trim((string)$value) !== '') {
            return trim((string)$value);
        }

        return null;
    }

    private function readBoolEnv(string $key, bool $default = false): bool
    {
        $value = $this->readStringEnv($key);

        if ($value === null) {
            return $default;
        }

        $normalized = strtolower($value);

        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    private function publishDebugContext(): void
    {
        ErrorHandler::setContext('bootstrap_base_path', $this->basePath);
        ErrorHandler::setContext('route_files', $this->routeFiles);
        ErrorHandler::setContext('route_directory', $this->resolveProjectPath('routes'));
        ErrorHandler::setContext('helpers_path', $this->helpersPath ?? $this->resolveProjectPath('bootstrap/helpers.php'));
        ErrorHandler::setContext('view_path', $this->viewPath ?? $this->resolveProjectPath('app/Views'));
        ErrorHandler::setContext('source_root', $this->sourceRoot ?? 'src');
        ErrorHandler::setContext('source_root_enabled', $this->preferSourceRoot);
    }
}

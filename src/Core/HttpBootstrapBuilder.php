<?php
namespace DevinciIT\Blprnt\Core;

use DevinciIT\Blprnt\Http\Kernel;

class HttpBootstrapBuilder
{
    private string $basePath;

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

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');

        $this->routeFiles = [
            $this->basePath . '/routes/web.php',
            $this->basePath . '/routes/api.php',
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
        return $this;
    }

    /* ------------------------------------------------------------------
     | Build
     ------------------------------------------------------------------*/

    public function build(): BootstrapResult
    {
        $this->loadEnvironment();

        if ($this->registerErrorHandler) {
            ErrorHandler::register();
        }

        $app = App::getInstance();

        $this->bindRouter($app);

        if ($this->initView) {
            View::init(
                $this->viewPath ?? ($this->basePath . '/app/Views'),
                $this->viewLayout
            );
        }

        if ($this->loadHelpers) {
            $helpers = $this->helpersPath ?? ($this->basePath . '/bootstrap/helpers.php');

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
}

<?php

declare(strict_types=1);

namespace DevinciIT\Blprnt\Core\Exceptions;

use RuntimeException;

class RouteNotFoundException extends RuntimeException
{
    /** @var string[] */
    private array $registeredMethods;

    public function __construct(
        public readonly string $httpMethod,
        public readonly string $uri,
        array $registeredMethods = []
    ) {
        $this->registeredMethods = array_values(array_unique(array_map('strtoupper', $registeredMethods)));

        $message = sprintf('Route not found: %s %s', strtoupper($httpMethod), $uri);
        parent::__construct($message, 404);
    }

    /**
     * @return string[]
     */
    public function getRegisteredMethods(): array
    {
        return $this->registeredMethods;
    }
}

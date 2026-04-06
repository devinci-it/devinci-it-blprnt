<?php
namespace DevinciIT\Blprnt\Console;

use RuntimeException;

abstract class Command
{
    // Command signature (e.g. 'make:controller')
    protected string $signature;
    // Command description
    protected string $description = '';
    // Optional handler class for complex command flows
    protected ?string $handlerClass = null;
    protected array $optionDefinitions = [];
    protected array $parsedOptions = [];
    protected array $parsedArguments = [];
    protected array $unknownOptions = [];

    public function getSignature(): string
    {
        return $this->signature;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getHandlerClass(): ?string
    {
        return $this->handlerClass;
    }

    protected function configureOptions(): void
    {
        // Override in command classes to define options via addOption().
    }

    protected function addOption(
        string $name,
        ?string $short = null,
        bool $expectsValue = false,
        bool $valueOptional = false,
        $default = null
    ): self {
        $this->optionDefinitions[$name] = [
            'short' => $short,
            'expectsValue' => $expectsValue,
            'valueOptional' => $valueOptional,
            'default' => $default,
        ];

        return $this;
    }

    protected function parseInput(array $args): void
    {
        $this->parsedOptions = [];
        $this->parsedArguments = [];
        $this->unknownOptions = [];

        foreach ($this->optionDefinitions as $name => $definition) {
            $this->parsedOptions[$name] = $definition['default'];
        }

        $parsingOptions = true;

        for ($i = 0; $i < count($args); $i++) {
            $arg = $args[$i];

            if ($parsingOptions && $arg === '--') {
                $parsingOptions = false;
                continue;
            }

            if ($parsingOptions && str_starts_with($arg, '--')) {
                $this->parseLongOption($arg, $args, $i);
                continue;
            }

            if ($parsingOptions && str_starts_with($arg, '-') && $arg !== '-') {
                $this->parseShortOption($arg, $args, $i);
                continue;
            }

            $this->parsedArguments[] = $arg;
        }
    }

    private function parseLongOption(string $arg, array $args, int &$index): void
    {
        $optionBody = substr($arg, 2);
        [$name, $inlineValue] = array_pad(explode('=', $optionBody, 2), 2, null);
        $definition = $this->optionDefinitions[$name] ?? null;

        if ($definition === null) {
            $this->unknownOptions[] = '--' . $name;
            return;
        }

        if ($definition['expectsValue']) {
            if ($inlineValue !== null) {
                $this->parsedOptions[$name] = $inlineValue;
                return;
            }

            if (($definition['valueOptional'] ?? false) === true) {
                $this->parsedOptions[$name] = $definition['default'];
                return;
            }

            if (isset($args[$index + 1])) {
                $this->parsedOptions[$name] = $args[++$index];
                return;
            }

            $this->unknownOptions[] = '--' . $name;
            return;
        }

        $this->parsedOptions[$name] = true;
    }

    private function parseShortOption(string $arg, array $args, int &$index): void
    {
        $optionKey = ltrim($arg, '-');
        $targetName = null;
        $targetDefinition = null;

        foreach ($this->optionDefinitions as $name => $definition) {
            if (($definition['short'] ?? null) === $optionKey) {
                $targetName = $name;
                $targetDefinition = $definition;
                break;
            }
        }

        if ($targetName === null || $targetDefinition === null) {
            $this->unknownOptions[] = '-' . $optionKey;
            return;
        }

        if ($targetDefinition['expectsValue']) {
            if (($targetDefinition['valueOptional'] ?? false) === true) {
                $this->parsedOptions[$targetName] = $targetDefinition['default'];
                return;
            }

            if (isset($args[$index + 1])) {
                $this->parsedOptions[$targetName] = $args[++$index];
                return;
            }

            $this->unknownOptions[] = '-' . $optionKey;
            return;
        }

        $this->parsedOptions[$targetName] = true;
    }

    protected function getOption(string $name, $fallback = null)
    {
        return $this->parsedOptions[$name] ?? $fallback;
    }

    protected function getParsedOptions(): array
    {
        return $this->parsedOptions;
    }

    protected function getParsedArguments(): array
    {
        return $this->parsedArguments;
    }

    protected function getUnknownOptions(): array
    {
        return $this->unknownOptions;
    }

    public function run(array $args = [])
    {
        $this->optionDefinitions = [];
        $this->configureOptions();
        $this->parseInput($args);

        if ($this->handlerClass !== null) {
            $handler = new $this->handlerClass();

            if ($handler instanceof CommandHandler) {
                return $handler->handle($this->getParsedArguments(), $this);
            }

            if (is_callable($handler)) {
                return $handler($this->getParsedArguments(), $this);
            }

            throw new RuntimeException(
                sprintf(
                    'Invalid command handler "%s" for "%s". Handler must implement %s or be invokable.',
                    $this->handlerClass,
                    static::class,
                    CommandHandler::class
                )
            );
        }

        return $this->handle($this->getParsedArguments());
    }

    // Override for simple commands that do not use handlerClass.
    public function handle(array $args = [])
    {
        throw new RuntimeException(
            sprintf(
                'Command "%s" must implement handle() or define handlerClass.',
                static::class
            )
        );
    }
}

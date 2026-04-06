# Blprnt

Blprnt is a minimal, extensible PHP micro-framework 

## Philosophy

> Blueprint your app, do not over-engineer it.

- Convention over configuration (while staying configurable)
- Clear MVC boundaries
- Lightweight core with explicit flow
- Middleware-first HTTP pipeline
- Practical developer experience without hidden magic

## Core Features

- Minimal application kernel and request lifecycle
- PSR-4 autoloading
- Router with middleware support
- Simple controller and view foundation
- Environment-aware error handling
- CLI command registry and command kernel
- SCSS build support via bundled style commands

## Requirements

- PHP 8.1+
- Composer 2+

## Installation

1. Allow the Composer plugin:

   ```bash
   composer config --no-plugins allow-plugins.devinci-it/blprnt true
   ```

2. Require the package:

   ```bash
   composer require devinci-it/blprnt
   ```

## Bootstrap Publishing Flow

On package install/update, Blprnt publishes missing project scaffolding and starter assets.

### Published directories

- `app`
- `bootstrap`
- `routes`
- `config`

### Published files

- `.env`
- `blprnt`
- `public/index.php`

### Resource-driven setup

- `resources/views` -> `app/Views`
- `resources/scss` -> `resources/scss` (project-local editable SCSS)
- `resources/logo.svg` -> `public/logo.svg`
- `resources/favicon.svg` -> `public/favicon.svg`

### Automatic style build

During publishing, Blprnt compiles SCSS from `resources/scss` into:

- `public/vendor/devinci-it/blprnt/css`

This keeps generated CSS in `public` while preserving source files in `resources`.

## Style Commands

Use the CLI script to compile styles manually:

```bash
./blprnt build:styles --source=resources/scss --output=public/vendor/devinci-it/blprnt/css --style=compressed --force
```

Common options:

- `--source`: source SCSS directory
- `--output`: output CSS directory
- `--style`: `compressed` or `expanded`
- `--force`: overwrite existing CSS files
- `--clean`: remove output directory before compiling

## Project Layout

```text
app/
  Controllers/
  Middleware/
  Views/
bootstrap/
config/
public/
resources/
  scss/
  views/
routes/
src/
```

## PHP File Documentation

This section documents key PHP components and responsibility boundaries.

### Composer integration

- `src/Composer/BlprntPlugin.php`
  - Composer plugin entry point.
  - Subscribes to package install/update events.
  - Triggers publish flow when Blprnt is installed or updated.

- `src/Composer/Installer.php`
  - Publishes skeleton directories and bootstrap files.
  - Seeds project `app/Views` and `public` assets from `resources`.
  - Invokes style compilation into public CSS output.

### Console subsystem

- `src/Console/Kernel.php`
  - Dispatches command signatures to registered command instances.

- `src/Console/CommandRegistry.php`
  - Central registry for command class instances.

- `src/Console/Command.php`
  - Base command abstraction with option parsing and handler support.

- `src/Console/Commands/Styles/BuildStylesCommand.php`
  - Recursively compiles SCSS files into CSS output structure.

- `src/Console/Commands/Styles/ScssCompileCommand.php`
  - Compiles a single SCSS entry file to one CSS output.

### HTTP and MVC core

- `src/Core/App.php` and `src/Http/Kernel.php`
  - Application bootstrap and request handling integration.

- `src/Core/Router.php`
  - Route registration and dispatch.

- `src/Core/Pipeline.php`
  - Middleware pipeline execution.

- `src/Core/Controller.php` and `src/Core/View.php`
  - Base controller helpers and view rendering.

- `src/Core/ErrorHandler.php`
  - Development/production error reporting strategy.

## License

MIT

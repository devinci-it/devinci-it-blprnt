<?php

declare(strict_types=1);

namespace DevinciIT\Blprnt\Console\Commands\Generator;

use DevinciIT\Blprnt\Console\Command;

/**
 * Scaffolds a new migration file with the next numeric prefix, so the
 * migration subsystem has the same make:* generator every other scaffold
 * type (controller/view/service/route) already gets.
 */
class MakeMigrationCommand extends Command
{
    protected string $signature = 'make:migration';
    protected string $description = 'Generate a numbered migration file from the migration stub';

    protected function configureOptions(): void
    {
        $this->addOption('help', 'h', false, false)
            ->addOption('force', 'f', false, false)
            ->addOption('table', 't', true, false, null)
            ->addOption('path', null, true, false, 'database/migrations');
    }

    public function handle(array $args = [])
    {
        if ((bool) $this->getOption('help', false)) {
            $this->printHelp();
            return;
        }

        $unknown = $this->getUnknownOptions();
        if (!empty($unknown)) {
            fwrite(STDERR, 'Unknown option(s): ' . implode(', ', $unknown) . "\n");
            $this->printHelp();
            return;
        }

        $name = trim((string) ($args[0] ?? ''));
        if ($name === '') {
            fwrite(STDERR, "Missing migration name.\n\n");
            $this->printHelp();
            return;
        }

        $slug = $this->toSnakeCase($name);
        if ($slug === '') {
            fwrite(STDERR, "Invalid migration name. Use letters, numbers, spaces, - or _.\n");
            return;
        }

        $outputDir = $this->resolvePath(
            (string) $this->getOption('path', 'database/migrations')
        );

        $prefix = $this->nextPrefix($outputDir);
        $targetFile = rtrim($outputDir, '/') . '/' . $prefix . '_' . $slug . '.php';
        $force = (bool) $this->getOption('force', false);

        if (is_file($targetFile) && !$force) {
            fwrite(STDERR, "Migration already exists: {$targetFile}\nUse --force to overwrite.\n");
            return;
        }

        $table = trim((string) ($this->getOption('table') ?? ''));
        if ($table === '') {
            $table = $this->guessTableName($slug);
        }

        $stub = $this->loadStub('Migration.php.tmp');
        if ($stub === null) {
            fwrite(STDERR, "Unable to locate Migration.php.tmp stub.\n");
            return;
        }

        $contents = str_replace(
            ['{{ name }}', '{{ table }}'],
            [$name, $table],
            $stub
        );

        if (!is_dir($outputDir) && !@mkdir($outputDir, 0755, true) && !is_dir($outputDir)) {
            fwrite(STDERR, "Unable to create output directory: {$outputDir}\n");
            return;
        }

        if (file_put_contents($targetFile, $contents) === false) {
            fwrite(STDERR, "Unable to write migration file: {$targetFile}\n");
            return;
        }

        echo "Created migration: {$targetFile}\n";
        echo "Run it with: php blprnt migrate:run\n";
    }

    /**
     * "Create Posts Table" / "create-posts-table" / "create_posts_table" -> "create_posts_table"
     */
    private function toSnakeCase(string $value): string
    {
        $value = preg_replace('/[^A-Za-z0-9]+/', '_', trim($value)) ?? '';
        $value = strtolower(trim($value, '_'));

        return $value;
    }

    /**
     * "create_posts_table" -> "posts". Falls back to the full slug when the
     * name doesn't follow that convention — --table is always there to be
     * explicit instead.
     */
    private function guessTableName(string $slug): string
    {
        if (preg_match('/^create_(.+)_table$/', $slug, $matches) === 1) {
            return $matches[1];
        }

        return $slug;
    }

    /**
     * Look at existing NNNN_*.php files in the target directory and return
     * the next 4-digit prefix. Starts at 0001 if the directory is empty or
     * missing.
     */
    private function nextPrefix(string $dir): string
    {
        $max = 0;

        foreach (glob(rtrim($dir, '/') . '/*.php') ?: [] as $file) {
            if (preg_match('/^(\d+)_/', basename($file), $matches) === 1) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }

    private function loadStub(string $stubName): ?string
    {
        $packageRoot = dirname(__DIR__, 4);
        $candidates = [
            getcwd() . '/resources/stub/' . $stubName,
            $packageRoot . '/resources/stub/' . $stubName,
        ];

        foreach ($candidates as $path) {
            if (!is_file($path)) {
                continue;
            }
            $contents = file_get_contents($path);
            if ($contents !== false) {
                return $contents;
            }
        }

        return null;
    }

    private function resolvePath(string $path): string
    {
        if ($path === '') {
            return getcwd();
        }

        if (str_starts_with($path, '/')) {
            return rtrim($path, '/');
        }

        return rtrim(getcwd() . '/' . ltrim($path, '/'), '/');
    }

    private function printHelp(): void
    {
        echo "\n";
        echo "Description:\n";
        echo "  Generate a numbered migration file (NNNN_name.php) from the migration stub.\n";
        echo "\n";
        echo "Usage:\n";
        echo "  php blprnt make:migration create_posts_table\n";
        echo "  php blprnt make:migration create_posts_table --table=posts\n\n";
        echo "Options:\n";
        echo "  -h, --help          Show this help message\n";
        echo "  -f, --force         Overwrite an existing file with the same name\n";
        echo "  -t, --table=<name>  Table name used in the generated Schema::create() call\n";
        echo "                      (guessed from a \"create_x_table\"-style name otherwise)\n";
        echo "      --path=<dir>    Migrations directory, relative to cwd (default: database/migrations)\n";
        echo "\n";
    }
}

<?php
namespace DevinciIT\Blprnt\Console\Commands\Styles;

use DevinciIT\Blprnt\Console\Command;
use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\OutputStyle;
use Throwable;

class ScssCompileCommand extends Command
{
    protected string $signature = 'scss:compile';
    protected string $description = 'Compile SCSS to CSS using a standalone PHP compiler';

    protected function configureOptions(): void
    {
        $this->addOption('help', 'h', false, false)
            ->addOption('input', 'i', true, true, 'resources/scss/app.scss')
            ->addOption('output', 'o', true, true, 'public/css/app.css')
            ->addOption('style', 's', true, true, 'compressed');
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

        $input = (string) ($this->getOption('input') ?: 'resources/scss/app.scss');
        $output = (string) ($this->getOption('output') ?: 'public/css/app.css');
        $style = strtolower((string) ($this->getOption('style') ?: 'compressed'));

        if (!in_array($style, ['compressed', 'expanded'], true)) {
            fwrite(STDERR, "Invalid style. Use 'compressed' or 'expanded'.\n");
            return;
        }

        $inputPath = $this->resolvePath($input);
        $outputPath = $this->resolvePath($output);

        if (!is_file($inputPath)) {
            fwrite(STDERR, "SCSS input not found: {$inputPath}\n");
            return;
        }

        $source = file_get_contents($inputPath);
        if ($source === false) {
            fwrite(STDERR, "Unable to read input file: {$inputPath}\n");
            return;
        }

        try {
            $compiler = new Compiler();
            $compiler->setOutputStyle(OutputStyle::fromString($style));
            $compiler->setImportPaths(dirname($inputPath));

            $result = $compiler->compileString($source, $inputPath);
            $css = $result->getCss();
        } catch (Throwable $e) {
            fwrite(STDERR, "SCSS compile failed: " . $e->getMessage() . "\n");
            return;
        }

        $outputDir = dirname($outputPath);
        if (!is_dir($outputDir)) {
            @mkdir($outputDir, 0755, true);
        }

        if (file_put_contents($outputPath, $css) === false) {
            fwrite(STDERR, "Unable to write CSS file: {$outputPath}\n");
            return;
        }

        echo "Compiled SCSS: {$inputPath} -> {$outputPath}\n";
    }

    private function resolvePath(string $path): string
    {
        if ($path === '') {
            return getcwd();
        }

        if (str_starts_with($path, '/')) {
            return $path;
        }

        return getcwd() . '/' . ltrim($path, '/');
    }

    private function printHelp(): void
    {
        echo "Usage:\n";
        echo "  blprnt scss:compile [--input=resources/scss/app.scss] [--output=public/css/app.css] [--style=compressed]\n\n";
        echo "Options:\n";
        echo "  --input    SCSS input path (default: resources/scss/app.scss)\n";
        echo "  --output   CSS output path (default: public/css/app.css)\n";
        echo "  --style    Output style: compressed|expanded (default: compressed)\n";
    }
}

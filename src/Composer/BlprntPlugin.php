<?php

namespace DevinciIT\Blprnt\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use DevinciIT\Blprnt\Support\IOHelper;

class BlprntPlugin implements PluginInterface, EventSubscriberInterface
{
    private const PACKAGE_NAME = 'devinci-it/blprnt';

    public function activate(Composer $composer, IOInterface $io): void {}
    public function deactivate(Composer $composer, IOInterface $io): void {}
    public function uninstall(Composer $composer, IOInterface $io): void {}

    /*
    |--------------------------------------------------------------------------
    | EVENT SUBSCRIPTION
    |--------------------------------------------------------------------------
    */

    public static function getSubscribedEvents(): array
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL => 'onPackageEvent',
            PackageEvents::POST_PACKAGE_UPDATE  => 'onPackageEvent',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | MAIN EVENT HANDLER
    |--------------------------------------------------------------------------
    */

    public function onPackageEvent(PackageEvent $event): void
    {
        $io = $event->getIO();
        $helper = new IOHelper($io);

        $this->debug($io, 'Blprnt plugin triggered');

        $op = $event->getOperation();
        $this->debug($io, 'Operation: ' . get_class($op));

        $package = match (true) {
            $op instanceof InstallOperation => $op->getPackage(),
            $op instanceof UpdateOperation  => $op->getTargetPackage(),
            default => null,
        };

        if (!$package) {
            $this->debug($io, 'No package resolved from operation');
            return;
        }

        $this->debug($io, 'Package detected: ' . $package->getName());

        if ($package->getName() !== self::PACKAGE_NAME) {
            $this->debug($io, 'Skipping (not blprnt package)');
            return;
        }

        $this->debug($io, 'Starting installer...');

        $this->runInstaller($event->getComposer(), $io);
    }

    /*
    |--------------------------------------------------------------------------
    | INSTALLER ENTRY POINT
    |--------------------------------------------------------------------------
    */

    private function runInstaller(Composer $composer, IOInterface $io): void
    {
        $projectRoot = dirname($composer->getConfig()->get('vendor-dir'));
        $packageRoot = $projectRoot . '/vendor/devinci-it/blprnt';

        $this->debug($io, "Project root: {$projectRoot}");
        $this->debug($io, "Package root: {$packageRoot}");

        $installer = new BlprntInstaller(
            $projectRoot,
            $packageRoot,
            new IOHelper($io)
        );

        $installer->runInstall(
            new \Composer\Script\Event(
                'blprnt-install',
                $composer,
                $io
            )
        );

        $this->debug($io, 'Installer finished');
    }

    /*
    |--------------------------------------------------------------------------
    | DEBUG HELPER
    |--------------------------------------------------------------------------
    */

    private function debug(IOInterface $io, string $message): void
    {
        $io->write('<comment>[blprnt:debug]</comment> ' . $message);
    }
}
<?php
namespace DevinciIT\Blprnt\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\Installer\Package\InstallOperation;
use Composer\Installer\Package\UpdateOperation;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

/**
 * Composer plugin that publishes Blprnt scaffolding when the package is installed or updated.
 */
class BlprntPlugin implements PluginInterface, EventSubscriberInterface
{
    private const PACKAGE_NAME = 'devinci-it/blprnt';
    private ?Composer $composer = null;
    private ?IOInterface $io = null;

    /**
     * Activates the plugin and runs a missing-skeleton check.
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;

        $this->publishIfNeeded($composer, $io);
    }

    /**
     * Composer plugin deactivation callback.
     */
    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // No-op.
    }

    /**
     * Composer plugin uninstall callback.
     */
    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // No-op.
    }

    /**
     * Registers package lifecycle events consumed by this plugin.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL => 'onPostPackageInstall',
            PackageEvents::POST_PACKAGE_UPDATE => 'onPostPackageUpdate',
        ];
    }

    /**
     * Handles post-install for the target package.
     */
    public function onPostPackageInstall(PackageEvent $event): void
    {
        $operation = $event->getOperation();

        if (!($operation instanceof InstallOperation)) {
            return;
        }

        $package = $operation->getPackage();
        if ($package->getName() !== self::PACKAGE_NAME) {
            return;
        }

        $this->publish($event->getComposer(), $event->getIO());
    }

    /**
     * Handles post-update for the target package.
     */
    public function onPostPackageUpdate(PackageEvent $event): void
    {
        $operation = $event->getOperation();

        if (!($operation instanceof UpdateOperation)) {
            return;
        }

        $package = $operation->getTargetPackage();
        if ($package->getName() !== self::PACKAGE_NAME) {
            return;
        }

        $this->publish($event->getComposer(), $event->getIO());
    }

    /**
     * Executes the installer publishing flow.
     */
    private function publish(Composer $composer, IOInterface $io): void
    {
        $projectRoot = dirname($composer->getConfig()->get('vendor-dir'));
        $packageRoot = Installer::packageRootFrom(__DIR__);

        Installer::publishForProject($projectRoot, $packageRoot, $io);
    }

    /**
     * Publishes bootstrap files when required project skeleton pieces are missing.
     */
    private function publishIfNeeded(Composer $composer, IOInterface $io): void
    {
        $projectRoot = dirname($composer->getConfig()->get('vendor-dir'));
        $missingSkeleton = false;

        foreach (['app', 'bootstrap', 'routes', 'config', '.env', 'blprnt'] as $entry) {
            if (!file_exists($projectRoot . '/' . $entry)) {
                $missingSkeleton = true;
                break;
            }
        }

        if (!file_exists($projectRoot . '/app/Views') || !file_exists($projectRoot . '/public/index.php')) {
            $missingSkeleton = true;
        }

        if ($missingSkeleton) {
            $this->publish($composer, $io);
        }
    }
}

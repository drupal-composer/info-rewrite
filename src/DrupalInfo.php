<?php

namespace DrupalComposer\Composer;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventDispatcher;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use DrupalComposer\Composer\Writer\Factory;

class DrupalInfo implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var Composer $composer
     */
    protected $composer;

    /**
     * @var IOInterface $io
     */
    protected $io;

    /**
     * @var EventDispatcher $eventDispatcher
     */
    protected $eventDispatcher;

    /**
     * Package types to process.
     */
    protected static $packageTypes = [
        'drupal-core',
        'drupal-module',
        'drupal-profile',
        'drupal-theme',
    ];

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        $events[PackageEvents::POST_PACKAGE_INSTALL] = ['writeInfoFiles', 50];
        $events[PackageEvents::POST_PACKAGE_UPDATE] = ['writeInfoFiles', 50];
        return $events;
    }

    /**
     * Writes out version information to .info files.
     *
     * @param \Composer\Installer\PackageEvent $event
     *   The package event.
     */
    public function writeInfoFiles(PackageEvent $event)
    {
        $operation = $event->getOperation();
        $package = $this->getPackageFromOperation($operation);
        if (!$this->processPackage($package)) {
            if ($this->io->isVerbose()) {
                $this->io->write(
                    '<info>Not writing info files for ' . $package->getPrettyName() . ' as it is of type '
                    . $package->getType() . '</info>'
                );
            }
            return;
        }
        $this->doWriteInfoFiles($package);
    }

    /**
     * Do the info file re-writing.
     *
     * @param PackageInterface $package
     */
    protected function doWriteInfoFiles(PackageInterface $package)
    {
        // Get the install path from the package object.
        $manager = $this->composer->getInstallationManager();
        $install_path = $manager->getInstaller($package->getType())->getInstallPath($package);
        $factory = new Factory($install_path);
        $writer = $factory->get();
        $writer->rewrite($this->findVersion($package), $this->findTimestamp($package));
    }

    /**
     * Find specific version info for a given package.
     *
     * @param  PackageInterface $package
     * @return string
     */
    protected function findVersion(PackageInterface $package)
    {
        // Check for more specific version info from drupal.org.
        $extra = $package->getExtra();
        if (isset($extra['drupal']['version'])) {
            return $extra['drupal']['version'];
        }

        // Default to package version.
        return $package->getVersion();
    }

    /**
     * Find a timestamp that the release is from in the package.
     *
     * @param  PackageInterface $package
     * @return string
     *   Unix timestamp.
     */
    protected function findTimestamp(PackageInterface $package)
    {
        // Check for a timestamp from drupal.org.
        $extra = $package->getExtra();
        if (isset($extra['drupal']['datestamp'])) {
            return $extra['drupal']['datestamp'];
        }

        // Fall back to package release if available.
        if ($date = $package->getReleaseDate()) {
            return $date->format('U');
        }

        // Last resort, use current time.
        return time();
    }

    /**
     * Determine if this package should be processed.
     *
     * @param  PackageInterface $package
     * @return bool
     */
    protected function processPackage(PackageInterface $package)
    {
        return in_array($package->getType(), static::$packageTypes);
    }

    /**
     * Gather the package from the given operation.
     *
     * @param  OperationInterface $operation
     * @return \Composer\Package\PackageInterface
     * @throws \Exception
     */
    protected function getPackageFromOperation(OperationInterface $operation)
    {
        if ($operation instanceof InstallOperation) {
            $package = $operation->getPackage();
        } elseif ($operation instanceof UpdateOperation) {
            $package = $operation->getTargetPackage();
        } else {
            throw new \Exception('Unknown operation: ' . get_class($operation));
        }

        if (!isset($package)) {
            throw new \Exception('No package found: ' . get_class($operation));
        }
        return $package;
    }
}

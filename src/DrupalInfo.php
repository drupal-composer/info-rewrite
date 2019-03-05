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
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
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
    protected $packageTypes = [
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

        $config = $this->composer->getConfig();
        if ($config) {
            $additionalPackageTypes = $config->get('drupal-info-rewrite--additional-packageTypes');
            if (is_array($additionalPackageTypes)) {
                $this->packageTypes = array_merge($this->packageTypes, $additionalPackageTypes);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        // Pre-install/update events for rolling back the rewrite to avoid prompts for changed files.
        $events[ScriptEvents::PRE_INSTALL_CMD] = 'rollbackRewrite';
        $events[ScriptEvents::PRE_UPDATE_CMD] = 'rollbackRewrite';

        // Events for performing the re-writing of info files.
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
     * Remove the info file rewriting.
     */
    public function rollbackRewrite(Event $event)
    {
        $packages = $this->composer->getRepositoryManager()->getLocalRepository()->getPackages();
        foreach ($packages as $package) {
            if (!$this->processPackage($package)) {
                if ($this->io->isVerbose()) {
                    $this->io->write(
                        '<info>Not rollinback info files for ' . $package->getPrettyName() . ' as it is of type '
                        . $package->getType() . '</info>'
                    );
                }
                continue;
            }

            $this->doRollback($package);
        }
    }

    /**
     * Do the info file re-writing.
     *
     * @param PackageInterface $package
     */
    protected function doWriteInfoFiles(PackageInterface $package)
    {
        if ($writer = $this->getWriter($package)) {
            $writer->rewrite($this->findVersion($package), $this->findTimestamp($package));
        } elseif ($this->io->isVerbose()) {
            $this->io->write(
                '<info>No info files found for ' .$package->getPrettyName() . '</info>'
            );
        }
    }

    /**
     * Process an info file rollback for a given package.
     * @param PackageInterface $package
     */
    protected function doRollback(PackageInterface $package)
    {
        $writer = $this->getWriter($package);
        $writer->rollback();
    }

    /**
     * Get the writer service.
     * @param PackageInterface $package
     * @return Writer\WriterInterface
     */
    protected function getWriter(PackageInterface $package)
    {
        // Get the install path from the package object.
        $manager = $this->composer->getInstallationManager();
        $install_path = $manager->getInstaller($package->getType())->getInstallPath($package);
        $factory = new Factory($install_path);
        return $factory->get();
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

        // Default to package pretty version.
        // The normal version has 4 digits for some reason.
        return $package->getPrettyVersion();
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
        return in_array($package->getType(), $this->packageTypes);
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

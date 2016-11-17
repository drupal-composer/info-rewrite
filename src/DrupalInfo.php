<?php

namespace jhedstrom\Composer;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventDispatcher;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Util\ProcessExecutor;

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
     * @var ProcessExecutor $executor
     */
    protected $executor;

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
    }

    /**
     * Gather the package from the given operation.
     *
     * @param OperationInterface $operation
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
        return $package;
    }
}

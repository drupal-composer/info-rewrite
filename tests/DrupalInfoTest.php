<?php

namespace jhedstrom\Composer\Tests;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\SolverOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use jhedstrom\Composer\DrupalInfo;

/**
 * @coversDefaultClass \jhedstrom\Composer\DrupalInfo
 */
class DrupalInfoTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var DrupalInfo
     */
    protected $fixture;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->composer = $this->prophesize(Composer::class);
        $this->io = $this->prophesize(IOInterface::class);

        $this->fixture = new DrupalInfo();
        $this->fixture->activate(
            $this->composer->reveal(),
            $this->io->reveal()
        );
    }

    /**
     * @covers ::getSubscribedEvents
     */
    public function testGetSubscribedEvents()
    {
        $events = DrupalInfo::getSubscribedEvents();
        $this->assertArrayHasKey(PackageEvents::POST_PACKAGE_INSTALL, $events);
        $this->assertArrayHasKey(PackageEvents::POST_PACKAGE_UPDATE, $events);
    }

    /**
     * @covers ::writeInfoFiles
     */
    public function testInstallOperationWriteInfoFiles()
    {
        $event = $this->prophesize(PackageEvent::class);
        $operation = $this->prophesize(InstallOperation::class);
        $package = $this->prophesize(PackageInterface::class);
        $package->getType()->willReturn('drupal-fo');
        $operation->getPackage()->willReturn($package->reveal());
        $event->getOperation()->willReturn($operation->reveal());
        $this->fixture->writeInfoFiles($event->reveal());
    }

    /**
     * @covers ::writeInfoFiles
     */
    public function testUpdateOperationWriteInfoFiles()
    {
        $event = $this->prophesize(PackageEvent::class);
        $operation = $this->prophesize(UpdateOperation::class);
        $package = $this->prophesize(PackageInterface::class);
        $package->getType()->willReturn('drupal-fo');
        $operation->getTargetPackage()->willReturn($package->reveal());
        $event->getOperation()->willReturn($operation->reveal());
        $this->fixture->writeInfoFiles($event->reveal());
    }

    /**
     * @covers ::writeInfoFiles
     */
    public function testInvalidOperationWriteInfoFiles()
    {
        $event = $this->prophesize(PackageEvent::class);
        $operation = $this->prophesize(SolverOperation::class);
        $operation = $operation->reveal();
        $event->getOperation()->willReturn($operation);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unknown operation: ' . get_class($operation));
        $this->fixture->writeInfoFiles($event->reveal());
    }

    /**
     * @covers ::writeInfoFiles
     */
    public function testIgnoredPackageType()
    {
        $event = $this->prophesize(PackageEvent::class);
        $operation = $this->prophesize(InstallOperation::class);
        $package = $this->prophesize(PackageInterface::class);
        $package->getType()->willReturn('drupal-fo');
        $operation->getPackage()->willReturn($package->reveal());
        $event->getOperation()->willReturn($operation->reveal());
        $this->fixture->writeInfoFiles($event->reveal());
    }
}

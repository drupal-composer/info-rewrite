<?php

namespace DrupalComposer\Composer\Tests;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\SolverOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\InstallationManager;
use Composer\Installer\InstallerInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use DrupalComposer\Composer\DrupalInfo;

/**
 * @coversDefaultClass \DrupalComposer\Composer\DrupalInfo
 */
class DrupalInfoTest extends \PHPUnit_Framework_TestCase
{
    use InfoFileTrait;

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
        // Generate test files.
        $this->generateDirectories();

        // Setup installer.
        $extra = [
            'drupal' => [
                'version' => 'foo-x+5',
                'datestamp' => '1234567890',
            ],
        ];
        $package = $this->prophesize(PackageInterface::class);
        $package->getType()->willReturn('drupal-module');
        $package->getExtra()->willReturn($extra);
        $package = $package->reveal();
        $installer = $this->prophesize(InstallerInterface::class);
        $installer->getInstallPath($package)->willReturn($this->getDirectory());
        $manager = $this->prophesize(InstallationManager::class);
        $manager->getInstaller('drupal-module')->willReturn($installer->reveal());
        $this->composer = $this->prophesize(Composer::class);
        $this->composer->getInstallationManager()->willReturn($manager->reveal());

        $this->fixture->activate(
            $this->composer->reveal(),
            $this->io->reveal()
        );

        $event = $this->prophesize(PackageEvent::class);
        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package);
        $event->getOperation()->willReturn($operation->reveal());
        $this->fixture->writeInfoFiles($event->reveal());

        // Verify that 3 .info files are updated.
        $files = [
            $this->getDirectory() . '/module_a/module_a.info.yml',
            $this->getDirectory() . '/nested_module/nested_module.info.yml',
            $this->getDirectory() . '/nested_module/modules/module_b/module_b.info.yml',
        ];
        $info = <<<EOL
# Information added by drupal-composer/info-rewrite on 2009-02-13T23:31:30+00:00.
version: 'foo-x+5'
timestamp: 123
EOL;

        foreach ($files as $file) {
            $this->assertFileExists($file);
            $this->assertContains($info, file_get_contents($file));
        }
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

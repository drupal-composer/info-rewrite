<?php

namespace DrupalComposer\Composer\Tests;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\SolverOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\InstallationManager;
use Composer\Installer\InstallerInterface;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Repository\RepositoryManager;
use Composer\Repository\WritableRepositoryInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use DrupalComposer\Composer\DrupalInfo;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @coversDefaultClass \DrupalComposer\Composer\DrupalInfo
 */
class DrupalInfoTest extends TestCase
{
    use InfoFileTrait;
    use ProphecyTrait;

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
    protected function setUp():void
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
        $this->assertArrayHasKey(ScriptEvents::PRE_INSTALL_CMD, $events);
        $this->assertArrayHasKey(ScriptEvents::PRE_UPDATE_CMD, $events);
        $this->assertArrayHasKey(ScriptEvents::POST_INSTALL_CMD, $events);
        $this->assertArrayHasKey(ScriptEvents::POST_UPDATE_CMD, $events);
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
        $this->composer->getConfig()->willReturn(null);

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
# Information added by drupal-composer/info-rewrite; date of revision: 2009-02-13T23:31:30+00:00.
version: 'foo-x+5'
datestamp: 123
EOL;

        foreach ($files as $file) {
            $this->assertFileExists($file);
            $this->assertStringContainsString($info, file_get_contents($file));
        }

        // Verify that module with existing version information is not updated.
        $file = $this->getDirectory() . '/module_with_version/module_with_version.info.yml';
        $this->assertFileExists($file);
        $this->assertStringNotContainsString($info, file_get_contents($file));
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
    public function testNoInfoFile()
    {
        $package = $this->prophesize(PackageInterface::class);
        $package->getType()->willReturn('drupal-module');
        $package->getPrettyName()->willReturn('foo');
        $package = $package->reveal();
        $installer = $this->prophesize(InstallerInterface::class);
        $installer->getInstallPath($package)->willReturn($this->getDirectory('drush'));
        $manager = $this->prophesize(InstallationManager::class);
        $manager->getInstaller('drupal-module')->willReturn($installer->reveal());
        $this->composer = $this->prophesize(Composer::class);
        $this->composer->getInstallationManager()->willReturn($manager->reveal());
        $this->composer->getConfig()->willReturn(null);
        $this->io->write('<info>No info files found for foo</info>')->shouldBeCalled();
        $this->io->isVerbose()->willReturn(true);
        $this->fixture->activate(
            $this->composer->reveal(),
            $this->io->reveal()
        );
        $event = $this->prophesize(PackageEvent::class);
        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package);
        $event->getOperation()->willReturn($operation->reveal());
        $this->fixture->writeInfoFiles($event->reveal());
    }

    /**
     * @covers ::rollbackRewrite
     */
    public function testRollbackRewrite()
    {
        // Generate test files.
        $this->generateDirectories();

        // Add the .info file that will be removed.
        $files = [
            $this->getDirectory() . '/module_a/module_a.info.yml',
            $this->getDirectory() . '/nested_module/nested_module.info.yml',
            $this->getDirectory() . '/nested_module/modules/module_b/module_b.info.yml',
        ];
        $info_pattern = <<<EOL

# Information added by drupal-composer/info-rewrite; date of revision: 2001-01-02.
version: 'foo-version'
timestamp: 1234
EOL;
        foreach ($files as $file) {
            $handle = fopen($file, 'a');
            fwrite($handle, $info_pattern);
            fclose($handle);
            $this->assertStringContainsString($info_pattern, file_get_contents($file));
        }

        $package = $this->prophesize(PackageInterface::class);
        $package->getType()->willReturn('drupal-module');
        $package = $package->reveal();
        $packages = [$package];

        $local_repository = $this->prophesize(WritableRepositoryInterface::class);
        $local_repository->getPackages()->willReturn($packages);

        $manager = $this->prophesize(RepositoryManager::class);
        $manager->getLocalRepository()->willReturn($local_repository->reveal());

        $installer = $this->prophesize(InstallerInterface::class);
        $installer->getInstallPath($package)->willReturn($this->getDirectory());
        $location_manager = $this->prophesize(InstallationManager::class);
        $location_manager->getInstaller('drupal-module')->willReturn($installer->reveal());

        $this->composer = $this->prophesize(Composer::class);
        $this->composer->getRepositoryManager()->willReturn($manager->reveal());
        $this->composer->getInstallationManager()->willReturn($location_manager->reveal());
        $this->composer->getConfig()->willReturn(null);

        $this->fixture->activate(
            $this->composer->reveal(),
            $this->io->reveal()
        );

        $event = $this->prophesize(Event::class);
        $this->fixture->rollbackRewrite($event->reveal());

        // Verify that 3 .info files are updated.
        foreach ($files as $file) {
            $contents = file_get_contents($file);
            $this->assertStringNotContainsString($info_pattern, $contents);
        }
    }

    /**
     * Verifies a warning if rollback is attempted without .info file.
     *
     * @covers ::rollbackRewrite
     */
    public function testRollbackNoInfo()
    {
        // Generate test files.
        $this->generateDirectories();

        // Add the .info file that will be removed.
        $files = [
            $this->getDirectory() . '/module_missing_info',
        ];

        $package = $this->prophesize(PackageInterface::class);
        $package->getType()->willReturn('drupal-module');
        $package->getPrettyName()->willReturn('My Module');
        $package = $package->reveal();
        $packages = [$package];

        $local_repository = $this->prophesize(WritableRepositoryInterface::class);
        $local_repository->getPackages()->willReturn($packages);

        $manager = $this->prophesize(RepositoryManager::class);
        $manager->getLocalRepository()->willReturn($local_repository->reveal());

        $installer = $this->prophesize(InstallerInterface::class);
        $installer->getInstallPath($package)->willReturn($this->getDirectory() . '/module_missing_info');
        $location_manager = $this->prophesize(InstallationManager::class);
        $location_manager->getInstaller('drupal-module')->willReturn($installer->reveal());

        $this->composer = $this->prophesize(Composer::class);
        $this->composer->getRepositoryManager()->willReturn($manager->reveal());
        $this->composer->getInstallationManager()->willReturn($location_manager->reveal());
        $this->composer->getConfig()->willReturn(null);

        // Ensure an error is logged.
        $this->io->isVerbose()->willReturn(true);
        $this->io->write('<info>No info files found for My Module</info>')->shouldBeCalledOnce();

        $this->fixture->activate(
            $this->composer->reveal(),
            $this->io->reveal()
        );

        $event = $this->prophesize(Event::class);
        $this->fixture->rollbackRewrite($event->reveal());
    }
}

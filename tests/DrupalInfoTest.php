<?php

namespace jhedstrom\Composer\Tests;

use Composer\Installer\PackageEvents;
use jhedstrom\Composer\DrupalInfo;

/**
 * @coversDefaultClass \jhedstrom\Composer\DrupalInfo
 */
class DrupalInfoTest extends \PHPUnit_Framework_TestCase {

    /**
     * @covers ::getSubscribedEvents
     */
    public function testGetSubscribedEvents() {
        $events = DrupalInfo::getSubscribedEvents();
        $this->assertArrayHasKey(PackageEvents::POST_PACKAGE_INSTALL, $events);
        $this->assertArrayHasKey(PackageEvents::POST_PACKAGE_UPDATE, $events);
    }

}

<?php

namespace jhedstrom\Composer\Tests\Writer;

use jhedstrom\Composer\Tests\InfoFileTrait;
use jhedstrom\Composer\Writer\Drupal;
use jhedstrom\Composer\Writer\Drupal7;
use jhedstrom\Composer\Writer\Factory;

/**
 * @coversDefaultClass \jhedstrom\Composer\Writer\Factory
 */
class FactoryTest extends \PHPUnit_Framework_TestCase
{
    use InfoFileTrait;

    /**
     * Tests the factory.
     *
     * @dataProvider providerTestGet
     *
     * @param string $path
     * @param string $expectedWriter
     */
    public function testGet($path, $expectedWriter)
    {
        $factory = new Factory($path);
        $this->assertInstanceOf($expectedWriter, $factory->get());
    }

    /**
     * Data provider for testGet.
     */
    public function providerTestGet()
    {
        $cases = [];
        $this->generateDirectories();

        $cases[] = [$this->getDirectory('drupal'), Drupal::class];
        $cases[] = [$this->getDirectory('drupal7'), Drupal7::class];

        return $cases;
    }
}

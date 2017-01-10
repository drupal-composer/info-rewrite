<?php

namespace DrupalComposer\Composer\Tests;

use org\bovigo\vfs\vfsStream;

/**
 * Trait for defining .info files for testing.
 */
trait InfoFileTrait
{
    /**
     * Generates the filesystem.
     */
    public function generateDirectories()
    {
        vfsStream::setup('modules', null, [
            // Drupal 8+.
            'drupal' => [
                'module_a' => [
                    'module_a.module' => '',
                    'module_a.info.yml' => 'name: module_a',
                ],
                'nested_module' => [
                    'nested_module.module' => '',
                    'nested_module.info.yml' => 'name: nested_module',
                    'modules' => [
                        'module_b' => [
                            'module_b.module' => '',
                            'module_b.info.yml' => 'name: module_b',
                        ],
                    ],
                ],
            ],
            // Drupal 7.
            'drupal7' => [
                'module_a' => [
                    'module_a.module' => '',
                    'module_a.info' => 'name = module_a',
                ],
                'nested_module' => [
                    'nested_module.module' => '',
                    'nested_module.info' => 'name = nested_module',
                    'modules' => [
                        'module_b' => [
                            'module_b.module' => '',
                            'module_b.info' => 'name = module_b',
                        ],
                    ],
                ],
            ],

        ]);
    }

    /**
     * Get directory names for a given version.
     */
    public function getDirectory($version = 'drupal')
    {
        return vfsStream::url('modules/' . $version);
    }
}

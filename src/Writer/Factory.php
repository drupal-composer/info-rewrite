<?php

namespace DrupalComposer\Composer\Writer;

use RegexIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * Defines a writer factory.
 */
class Factory
{
    /**
     * A mapping of patterns to the appropriate write.
     */
    protected static $infoMapping = [
        '/^.+\.info\.yml$/i' => Drupal::class,
        '/^.+\.info$/i' => Drupal7::class,
    ];

    /**
     * Given a package install path, return the appropriate .info file writer.
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Return the writer plugin.
     *
     * @return \DrupalComposer\Composer\Writer\WriterInterface
     */
    public function get()
    {
        return $this->scan();
    }

    /**
     * Scans the directory for .info or .info.yml files and returns the appropriate writer.
     */
    protected function scan()
    {
        foreach (static::$infoMapping as $pattern => $class) {
            $fileIterator = new RegexIterator(
                new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($this->path)
                ),
                $pattern,
                RegexIterator::MATCH
            );
            $infoFiles = [];
            foreach ($fileIterator as $found) {
                $infoFiles[] = $found->getPathname();
            }
            if (!empty($infoFiles)) {
                $writer = new $class();
                $writer->set($infoFiles);
                return $writer;
            }
        }
    }
}

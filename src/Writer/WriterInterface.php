<?php

namespace jhedstrom\Composer\Writer;

/**
 * Defines an interface for writing .info files.
 */
interface WriterInterface
{
    /**
     * Set the .info file information.
     *
     * @param array $paths
     */
    public function set(array $paths);

    /**
     * Rewrites the .info files with the provided version.
     *
     * @param string $version
     *   Version information to write out.
     */
    public function rewrite($version);
}

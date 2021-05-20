<?php

namespace DrupalComposer\Composer\Writer;

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
     * Rewrites the .info files with the provided version and timestamp.
     *
     * @param string $version
     *   Version information to write out.
     * @param string $timestamp
     * @return
     */
    public function rewrite($version, $timestamp, $core = null, $project = null);

    /**
     * Rollback the info files to their download/unprocessed state.
     */
    public function rollback();
}

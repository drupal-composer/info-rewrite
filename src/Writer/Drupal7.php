<?php

namespace DrupalComposer\Composer\Writer;

/**
 * Drupal 7 .info file writer.
 */
class Drupal7 extends Drupal
{
    /**
     * {@inheritdoc}
     */
    const VERSION_EXISTS_PATTERN = '#version=.*[\d+].*#';

    /**
     * Format version and timestamp into INI format.
     */
    protected function formatInfo($version, $timestamp, $core = null, $project = null)
    {
        $date = gmdate('c', $timestamp);
        $info = array();
        // Always start with EOL character.
        $info[] = '';
        $info[] = "; Information added by drupal-composer/info-rewrite; date of revision: $date.";
        $info[] = "version = \"$version\"";
        if ($core) {
            $info[] = "core = \"$core\"";
        }
        if ($project) {
            $info[] = "project = \"$project\"";
        }
        $info[] = "datestamp = \"$timestamp\"";
        // Always end with EOL character.
        $info[] = '';

        return implode("\n", $info);
    }
}

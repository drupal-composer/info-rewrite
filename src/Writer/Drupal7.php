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
    protected function formatInfo($version, $timestamp)
    {
        $date = gmdate('c', $timestamp);
        $info = array();
        // Always start with EOL character.
        $info[] = '';
        $info[] = "; Information added by drupal-composer/info-rewrite on $date.";
        $info[] = "version = \"$version\"";
        $info[] = "datestamp = \"$timestamp\"";
        // Always end with EOL character.
        $info[] = '';

        return implode("\n", $info);
    }
}

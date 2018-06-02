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
        $info = <<<EOL
; Information added by drupal-composer/info-rewrite on $date.
version = "$version"
datestamp = "$timestamp"
EOL;
        return $info;
    }
}

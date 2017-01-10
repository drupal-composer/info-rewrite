<?php

namespace DrupalComposer\Composer\Writer;

/**
 * Drupal 8+ .info file writer.
 */
class Drupal implements WriterInterface
{
    /**
     * File paths to rewrite.
     *
     * @var string[]
     */
    protected $paths;

    /**
     * {@inheritdoc}
     */
    public function set(array $paths)
    {
        $this->paths = $paths;
    }

    /**
     * {@inheritdoc}
     */
    public function rewrite($version, $timestamp)
    {
        foreach ($this->paths as $info_file) {
            $file = fopen($info_file, 'a+');
            fwrite($file, $this->formatInfo($version, $timestamp));
            fclose($file);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rollback()
    {
        $pattern = '# Information added by drupal-composer/info-rewrite on';
        foreach ($this->paths as $info_file) {
            $contents = file_get_contents($info_file);
            $parts = explode($pattern, $contents);
            file_put_contents($info_file, trim($parts[0]) . "\n");
        }
    }

    /**
     * Format version and timestamp into YAML.
     */
    protected function formatInfo($version, $timestamp)
    {
        $date = gmdate('c', $timestamp);
        $info = <<<EOL

# Information added by drupal-composer/info-rewrite on $date.
version: '$version'
timestamp: $timestamp
EOL;
        return $info;
    }
}

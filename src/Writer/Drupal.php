<?php

namespace DrupalComposer\Composer\Writer;

/**
 * Drupal 8+ .info file writer.
 */
class Drupal implements WriterInterface
{
    /**
     * Pattern to indicate a file already has version info.
     */
    const VERSION_EXISTS_PATTERN = '#version:.*[\d+].*#';

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
            // Don't write to files that already contain version information.
            if (!$this->hasVersionInfo($info_file)) {
                $file = fopen($info_file, 'a+');
                fwrite($file, $this->formatInfo($version, $timestamp));
                fclose($file);
            }
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
datestamp: $timestamp

EOL;
        return $info;
    }

    /**
     * Determine if a given file already contains version info.
     *
     * @param string $file_path
     *   Path to the info file.
     *
     * @return bool
     *   Returns true if file already has version info.
     */
    protected function hasVersionInfo($file_path)
    {
        $contents = file_get_contents($file_path);
        return preg_match(static::VERSION_EXISTS_PATTERN, $contents);
    }
}

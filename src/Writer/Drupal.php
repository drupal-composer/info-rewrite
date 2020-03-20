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
     * Pattern to indicate a file has core_version_requirement.
     */
    const CORE_VERSION_REQUIREMENT_EXISTS_PATTERN = '#core_version_requirement:.*#';

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
    public function rewrite($version, $timestamp, $core = null, $project = null)
    {
        foreach ($this->paths as $info_file) {
            // Don't write to files that already contain version information.
            if (!$this->hasVersionInfo($info_file)) {
                $file = fopen($info_file, 'a+');
                $coreToWrite = $this->hasCoreVersionRequirement($info_file) ? $core : null;
                fwrite($file, $this->formatInfo($version, $timestamp, $coreToWrite, $project));
                fclose($file);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rollback()
    {
        $pattern = '# Information added by drupal-composer/info-rewrite; date of revision:';
        foreach ($this->paths as $info_file) {
            $contents = file_get_contents($info_file);
            $parts = explode($pattern, $contents);
            file_put_contents($info_file, trim($parts[0]) . "\n");
        }
    }

    /**
     * Format version and timestamp into YAML.
     */
    protected function formatInfo($version, $timestamp, $core = null, $project = null)
    {
        $date = gmdate('c', $timestamp);
        $info = array();
        // Always start with EOL character.
        $info[] = '';
        $info[] = "# Information added by drupal-composer/info-rewrite; date of revision: $date.";
        $info[] = "version: '$version'";
        if ($core) {
            $info[] = "core: '$core'";
        }
        if ($project) {
            $info[] = "project: '$project'";
        }
        $info[] = "datestamp: $timestamp";
        // Always end with EOL character.
        $info[] = '';

        return implode("\n", $info);
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

    /**
     * Determine if a given file contains core_version_requirement.
     *
     * @param string $file_path
     *   Path to the info file.
     *
     * @return bool
     *   Returns true if file already has core_version_requirement.
     */
    protected function hasCoreVersionRequirement($file_path)
    {
        $contents = file_get_contents($file_path);
        return preg_match(static::CORE_VERSION_REQUIREMENT_EXISTS_PATTERN, $contents);
    }
}

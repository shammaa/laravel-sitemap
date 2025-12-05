<?php

declare(strict_types=1);

namespace Shammaa\LaravelSitemap\Services;

use Shammaa\LaravelSitemap\Exceptions\InvalidSitemapConfigException;

class SitemapValidator
{
    /**
     * Validate sitemap configuration.
     *
     * @param string $name The sitemap name
     * @param array $config The configuration array
     * @param bool $throwException Whether to throw exception on invalid config
     * @return bool
     * @throws InvalidSitemapConfigException
     */
    public static function validateConfig(string $name, array $config, bool $throwException = true): bool
    {
        // Validate model is required
        if (empty($config['model'])) {
            if ($throwException) {
                throw new InvalidSitemapConfigException("'model' is required", $name);
            }
            return false;
        }

        // Validate model class exists
        if (!class_exists($config['model'])) {
            if ($throwException) {
                throw new InvalidSitemapConfigException("Model class '{$config['model']}' does not exist", $name);
            }
            return false;
        }

        // Validate changefreq is valid
        $validChangefreqs = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];
        if (isset($config['changefreq']) && !in_array($config['changefreq'], $validChangefreqs, true)) {
            if ($throwException) {
                throw new InvalidSitemapConfigException("Invalid changefreq '{$config['changefreq']}'. Valid values: " . implode(', ', $validChangefreqs), $name);
            }
            return false;
        }

        // Validate priority is between 0 and 1
        if (isset($config['priority'])) {
            $priority = (float) $config['priority'];
            if ($priority < 0 || $priority > 1) {
                if ($throwException) {
                    throw new InvalidSitemapConfigException("Priority must be between 0 and 1, got {$priority}", $name);
                }
                return false;
            }
        }

        // Validate range_size is positive
        if (isset($config['range_size']) && $config['range_size'] <= 0) {
            if ($throwException) {
                throw new InvalidSitemapConfigException("range_size must be greater than 0", $name);
            }
            return false;
        }

        // Validate cache times are positive
        if (isset($config['cache_time']) && $config['cache_time'] < 0) {
            if ($throwException) {
                throw new InvalidSitemapConfigException("cache_time must be greater than or equal to 0", $name);
            }
            return false;
        }

        return true;
    }

    /**
     * Validate year parameter.
     *
     * @param int $year The year to validate
     * @param bool $throwException Whether to throw exception on invalid year
     * @return bool
     * @throws InvalidSitemapConfigException
     */
    public static function validateYear(int $year, bool $throwException = true): bool
    {
        $currentYear = (int) date('Y');
        $minYear = 2000; // Reasonable minimum
        
        if ($year < $minYear || $year > $currentYear + 1) {
            if ($throwException) {
                throw new InvalidSitemapConfigException("Invalid year: {$year}. Must be between {$minYear} and " . ($currentYear + 1));
            }
            return false;
        }

        return true;
    }

    /**
     * Validate chunk parameter.
     *
     * @param int $chunk The chunk number
     * @param bool $throwException Whether to throw exception on invalid chunk
     * @return bool
     * @throws InvalidSitemapConfigException
     */
    public static function validateChunk(int $chunk, bool $throwException = true): bool
    {
        if ($chunk < 1) {
            if ($throwException) {
                throw new InvalidSitemapConfigException("Invalid chunk: {$chunk}. Must be greater than 0");
            }
            return false;
        }

        return true;
    }
}


<?php

declare(strict_types=1);

namespace Shammaa\LaravelSitemap\Console;

use Illuminate\Console\Command;
use Shammaa\LaravelSitemap\Services\SitemapManager;
use Illuminate\Support\Facades\Cache;

class ClearSitemapCache extends Command
{
    protected $signature = 'sitemap:clear {type? : Specific sitemap type to clear}';
    protected $description = 'Clear sitemap cache';

    public function handle(SitemapManager $manager): int
    {
        $type = $this->argument('type');

        if ($type) {
            $config = $manager->getConfig($type);
            if (!$config) {
                $this->error("Sitemap type '{$type}' not found.");
                return 1;
            }

            $this->clearConfigCache($manager, $config, $type);
            $this->info("Cache cleared for sitemap type: {$type}");
        } else {
            $manager->clearCache();
            Cache::forget('sitemap.main.index.urls');
            $this->info('All sitemap cache cleared.');
        }

        return 0;
    }

    protected function clearConfigCache(SitemapManager $manager, $config, string $type): void
    {
        // Clear main caches
        Cache::forget("sitemap.{$type}.latest");
        Cache::forget("sitemap.{$type}.total_count");
        Cache::forget("sitemap.{$type}.years");

        // Clear year-based caches
        if ($config->splitByYear) {
            $years = $manager->getYears($config);
            foreach ($years as $year) {
                Cache::forget("sitemap.{$type}.year.{$year}");
            }
        }

        // Clear range-based caches
        if ($config->splitByRange) {
            $total = $manager->getTotalCount($config);
            $chunks = ceil($total / $config->rangeSize);
            for ($i = 1; $i <= $chunks; $i++) {
                $offset = ($i - 1) * $config->rangeSize;
                Cache::forget("sitemap.{$type}.range.{$offset}");
            }
        }

        // Clear general cache patterns
        $patterns = [
            "sitemap.{$type}.*",
        ];

        // Note: Cache::forget doesn't support patterns in all drivers
        // This is a simplified approach
    }
}


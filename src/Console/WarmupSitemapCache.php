<?php

declare(strict_types=1);

namespace Shammaa\LaravelSitemap\Console;

use Illuminate\Console\Command;
use Shammaa\LaravelSitemap\Services\SitemapManager;

class WarmupSitemapCache extends Command
{
    protected $signature = 'sitemap:warmup {type? : Specific sitemap type to warmup}';
    protected $description = 'Warm up sitemap cache';

    public function handle(SitemapManager $manager): int
    {
        $type = $this->argument('type');

        if ($type) {
            $config = $manager->getConfig($type);
            if (!$config) {
                $this->error("Sitemap type '{$type}' not found.");
                return 1;
            }

            $this->warmupConfig($manager, $config, $type);
            $this->info("Cache warmed up for sitemap type: {$type}");
        } else {
            $this->info('Warming up all sitemap caches...');
            
            $configs = $manager->getAllConfigs();
            foreach ($configs as $name => $config) {
                $this->warmupConfig($manager, $config, $name);
            }

            // Clear main index cache to force regeneration
            \Illuminate\Support\Facades\Cache::forget('sitemap.main.index.urls');
            
            $this->info('All sitemap caches warmed up.');
        }

        return 0;
    }

    protected function warmupConfig(SitemapManager $manager, $config, string $type): void
    {
        $this->line("Warming up: {$type}");

        // Warm up latest
        $manager->getLatestItems($config);
        $this->line("  ✓ Latest items cached");

        // Warm up years if applicable
        if ($config->splitByYear) {
            $years = $manager->getYears($config);
            foreach ($years as $year) {
                $manager->getItemsByYear($config, $year);
            }
            $this->line("  ✓ Year-based sitemaps cached (" . count($years) . " years)");
        }

        // Warm up ranges if applicable
        if ($config->splitByRange) {
            $total = $manager->getTotalCount($config);
            $chunks = ceil($total / $config->rangeSize);
            
            $bar = $this->output->createProgressBar($chunks);
            $bar->start();

            for ($i = 1; $i <= $chunks; $i++) {
                $offset = ($i - 1) * $config->rangeSize;
                $manager->getItemsByRange($config, $offset, $config->rangeSize);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->line("  ✓ Range-based sitemaps cached (" . $chunks . " chunks)");
        }

        // Warm up full sitemap if not split
        if (!$config->splitByYear && !$config->splitByRange) {
            $manager->getItems($config);
            $this->line("  ✓ Full sitemap cached");
        }
    }
}


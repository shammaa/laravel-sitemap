<?php

declare(strict_types=1);

namespace Shammaa\LaravelSitemap\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void register(string $name, array $config)
 * @method static \Shammaa\LaravelSitemap\Config\SitemapConfig|null getConfig(string $name)
 * @method static array getAllConfigs()
 * @method static array getItems(\Shammaa\LaravelSitemap\Config\SitemapConfig $config, ?int $limit = null, ?int $year = null, ?int $offset = null)
 * @method static array getLatestItems(\Shammaa\LaravelSitemap\Config\SitemapConfig $config)
 * @method static array getItemsByYear(\Shammaa\LaravelSitemap\Config\SitemapConfig $config, int $year)
 * @method static array getItemsByRange(\Shammaa\LaravelSitemap\Config\SitemapConfig $config, int $offset, int $limit)
 * @method static int getTotalCount(\Shammaa\LaravelSitemap\Config\SitemapConfig $config)
 * @method static array getYears(\Shammaa\LaravelSitemap\Config\SitemapConfig $config)
 * @method static string generateUrl(\Shammaa\LaravelSitemap\Config\SitemapConfig $config, $item)
 * @method static void clearCache(?string $name = null)
 *
 * @see \Shammaa\LaravelSitemap\Services\SitemapManager
 */
class Sitemap extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'sitemap';
    }
}


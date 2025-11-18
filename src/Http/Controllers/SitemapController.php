<?php

declare(strict_types=1);

namespace Shammaa\LaravelSitemap\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Shammaa\LaravelSitemap\Services\SitemapManager;
use Shammaa\LaravelSitemap\Config\SitemapConfig;
use Carbon\Carbon;

class SitemapController extends Controller
{
    public function __construct(
        protected SitemapManager $sitemapManager
    ) {}

    /**
     * Main sitemap index
     */
    public function index()
    {
        $cacheKey = 'sitemap.main.index.urls';
        $baseUrl = config('sitemap.base_url', url('/'));

        $urls = cache()->remember($cacheKey, 3600, function () use ($baseUrl) {
            $urls = [];
            $now = Carbon::now()->format('Y-m-d\TH:i:sP');

            foreach ($this->sitemapManager->getAllConfigs() as $name => $config) {
                // Latest sitemap
                $urls[] = [
                    'loc' => rtrim($baseUrl, '/') . "/sitemap-{$name}-latest.xml",
                    'lastmod' => $now,
                ];

                // Year-based sitemaps
                if ($config->splitByYear) {
                    $years = $this->sitemapManager->getYears($config);
                    foreach ($years as $year) {
                        $urls[] = [
                            'loc' => rtrim($baseUrl, '/') . "/sitemap-{$name}-{$year}.xml",
                            'lastmod' => $now,
                        ];
                    }
                }

                // Range-based sitemaps
                if ($config->splitByRange) {
                    $total = $this->sitemapManager->getTotalCount($config);
                    $chunks = ceil($total / $config->rangeSize);
                    
                    for ($i = 1; $i <= $chunks; $i++) {
                        $urls[] = [
                            'loc' => rtrim($baseUrl, '/') . "/sitemap-{$name}-part-{$i}.xml",
                            'lastmod' => $now,
                        ];
                    }
                }

                // Full sitemap (if not split)
                if (!$config->splitByYear && !$config->splitByRange) {
                    $urls[] = [
                        'loc' => rtrim($baseUrl, '/') . "/sitemap-{$name}.xml",
                        'lastmod' => $now,
                    ];
                }
            }

            return $urls;
        });

        return response()->view('sitemap::index', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }

    /**
     * Get sitemap by type
     */
    public function type(string $type)
    {
        $config = $this->sitemapManager->getConfig($type);
        
        if (!$config) {
            abort(404);
        }

        // Redirect to chunk if range-based and large
        if ($config->splitByRange) {
            $total = $this->sitemapManager->getTotalCount($config);
            if ($total > ($config->chunkSize ?? 50000)) {
                return redirect()->route('sitemap.range', [
                    'type' => $type,
                    'chunk' => 1
                ]);
            }
        }

        $items = $this->sitemapManager->getItems($config);
        
        return $this->renderSitemap($items, $config);
    }

    /**
     * Get latest items sitemap
     */
    public function latest(string $type)
    {
        $config = $this->sitemapManager->getConfig($type);
        
        if (!$config) {
            abort(404);
        }

        $items = $this->sitemapManager->getLatestItems($config);
        
        return $this->renderSitemap($items, $config, $config->latestPriority);
    }

    /**
     * Get yearly sitemap
     */
    public function yearly(string $type, int $year)
    {
        $config = $this->sitemapManager->getConfig($type);
        
        if (!$config || !$config->splitByYear) {
            abort(404);
        }

        $items = $this->sitemapManager->getItemsByYear($config, $year);
        
        return $this->renderSitemap($items, $config);
    }

    /**
     * Get range-based sitemap (chunks)
     */
    public function range(string $type, int $chunk)
    {
        $config = $this->sitemapManager->getConfig($type);
        
        if (!$config || !$config->splitByRange) {
            abort(404);
        }

        $offset = ($chunk - 1) * $config->rangeSize;
        $items = $this->sitemapManager->getItemsByRange($config, $offset, $config->rangeSize);
        
        return $this->renderSitemap($items, $config);
    }

    /**
     * Render sitemap XML
     */
    protected function renderSitemap(array $items, SitemapConfig $config, ?float $priority = null): \Illuminate\Http\Response
    {
        // Convert SitemapItem objects to arrays
        $itemsArray = array_map(function ($item) {
            if ($item instanceof \Shammaa\LaravelSitemap\Data\SitemapItem) {
                return $item->toArray();
            }
            return $item;
        }, $items);
        
        return response()->view('sitemap::sitemap', [
            'items' => $itemsArray,
            'changefreq' => $config->changefreq,
            'priority' => $priority ?? $config->priority,
        ])->header('Content-Type', 'application/xml');
    }
}


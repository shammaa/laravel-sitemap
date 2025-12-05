<?php

declare(strict_types=1);

namespace Shammaa\LaravelSitemap\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Shammaa\LaravelSitemap\Services\SitemapManager;
use Shammaa\LaravelSitemap\Services\SitemapValidator;
use Shammaa\LaravelSitemap\Config\SitemapConfig;
use Shammaa\LaravelSitemap\Exceptions\SitemapNotFoundException;
use Carbon\Carbon;

class SitemapController extends Controller
{
    public function __construct(
        protected SitemapManager $sitemapManager
    ) {}

    /**
     * Main sitemap index.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $cacheKey = 'sitemap.main.index.urls';
            // Use current request URL instead of config to support dynamic domains
            $baseUrl = config('sitemap.base_url');
            $defaultAppUrl = config('app.url', 'https://example.com');
            if (empty($baseUrl) || $baseUrl === $defaultAppUrl) {
                $baseUrl = request()->getSchemeAndHttpHost();
            }

            $urls = cache()->remember($cacheKey, 3600, function () use ($baseUrl) {
                $urls = [];
                $now = Carbon::now()->format('Y-m-d\TH:i:sP');

                foreach ($this->sitemapManager->getAllConfigs() as $name => $config) {
                    // Latest sitemap - only include if splitByYear is disabled to avoid duplication
                    // When splitByYear is enabled, all items are already covered by year-based sitemaps
                    if (!$config->splitByYear) {
                        $urls[] = [
                            'loc' => rtrim($baseUrl, '/') . "/sitemap-{$name}-latest.xml",
                            'lastmod' => $now,
                        ];
                    }

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
        } catch (\Exception $e) {
            Log::error('Sitemap index generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Return empty sitemap on error
            return response()->view('sitemap::index', ['urls' => []])
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Get sitemap by type.
     *
     * @param string $type The sitemap type
     * @return \Illuminate\Http\Response
     * @throws SitemapNotFoundException
     */
    public function type(string $type)
    {
        try {
            $config = $this->sitemapManager->getConfig($type);
            
            if (!$config) {
                throw new SitemapNotFoundException($type);
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
        } catch (SitemapNotFoundException $e) {
            abort(404, $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Sitemap type generation failed', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            
            abort(500, 'Sitemap generation failed');
        }
    }

    /**
     * Get latest items sitemap.
     *
     * @param string $type The sitemap type
     * @return \Illuminate\Http\Response
     * @throws SitemapNotFoundException
     */
    public function latest(string $type)
    {
        try {
            $config = $this->sitemapManager->getConfig($type);
            
            if (!$config) {
                throw new SitemapNotFoundException($type);
            }

            $items = $this->sitemapManager->getLatestItems($config);
            
            return $this->renderSitemap($items, $config, $config->latestPriority);
        } catch (SitemapNotFoundException $e) {
            abort(404, $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Sitemap latest generation failed', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            
            abort(500, 'Sitemap generation failed');
        }
    }

    /**
     * Get yearly sitemap.
     *
     * @param string $type The sitemap type
     * @param int $year The year
     * @return \Illuminate\Http\Response
     * @throws SitemapNotFoundException
     */
    public function yearly(string $type, int $year)
    {
        try {
            $config = $this->sitemapManager->getConfig($type);
            
            if (!$config || !$config->splitByYear) {
                throw new SitemapNotFoundException($type);
            }

            $items = $this->sitemapManager->getItemsByYear($config, $year);
            
            return $this->renderSitemap($items, $config);
        } catch (SitemapNotFoundException $e) {
            abort(404, $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Sitemap yearly generation failed', [
                'type' => $type,
                'year' => $year,
                'error' => $e->getMessage(),
            ]);
            
            abort(500, 'Sitemap generation failed');
        }
    }

    /**
     * Get range-based sitemap (chunks).
     *
     * @param string $type The sitemap type
     * @param int $chunk The chunk number
     * @return \Illuminate\Http\Response
     * @throws SitemapNotFoundException
     */
    public function range(string $type, int $chunk)
    {
        try {
            $config = $this->sitemapManager->getConfig($type);
            
            if (!$config || !$config->splitByRange) {
                throw new SitemapNotFoundException($type);
            }
            
            // Validate chunk
            SitemapValidator::validateChunk($chunk);

            $offset = ($chunk - 1) * $config->rangeSize;
            $items = $this->sitemapManager->getItemsByRange($config, $offset, $config->rangeSize);
            
            return $this->renderSitemap($items, $config);
        } catch (SitemapNotFoundException $e) {
            abort(404, $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Sitemap range generation failed', [
                'type' => $type,
                'chunk' => $chunk,
                'error' => $e->getMessage(),
            ]);
            
            abort(500, 'Sitemap generation failed');
        }
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


<?php

declare(strict_types=1);

namespace Shammaa\LaravelSitemap\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Shammaa\LaravelSitemap\Config\SitemapConfig;
use Shammaa\LaravelSitemap\Data\SitemapItem;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SitemapManager
{
    protected array $configs = [];

    public function __construct(array $configs = [])
    {
        $this->configs = $configs;
    }

    public function register(string $name, array $config): void
    {
        $this->configs[$name] = SitemapConfig::fromArray($name, $config);
    }

    public function getConfig(string $name): ?SitemapConfig
    {
        return $this->configs[$name] ?? null;
    }

    public function getAllConfigs(): array
    {
        return $this->configs; // Returns associative array: ['name' => SitemapConfig, ...]
    }

    /**
     * Get all items for a sitemap type
     */
    public function getItems(SitemapConfig $config, ?int $limit = null, ?int $year = null, ?int $offset = null): array
    {
        $cacheKey = $this->getCacheKey($config, $limit, $year, $offset);
        
        return Cache::remember($cacheKey, $config->cacheTime, function () use ($config, $limit, $year, $offset) {
            return $this->fetchItems($config, $limit, $year, $offset);
        });
    }

    /**
     * Get latest items for a sitemap type
     */
    public function getLatestItems(SitemapConfig $config): array
    {
        $cacheKey = "sitemap.{$config->name}.latest";
        
        return Cache::remember($cacheKey, $config->latestCacheTime, function () use ($config) {
            return $this->fetchItems($config, $config->latestLimit);
        });
    }

    /**
     * Get items by year
     */
    public function getItemsByYear(SitemapConfig $config, int $year): array
    {
        $cacheKey = "sitemap.{$config->name}.year.{$year}";
        
        return Cache::remember($cacheKey, $config->cacheTime, function () use ($config, $year) {
            return $this->fetchItems($config, null, $year);
        });
    }

    /**
     * Get items by range (for chunking)
     */
    public function getItemsByRange(SitemapConfig $config, int $offset, int $limit): array
    {
        $cacheKey = "sitemap.{$config->name}.range.{$offset}.{$limit}";
        
        return Cache::remember($cacheKey, $config->cacheTime, function () use ($config, $offset, $limit) {
            return $this->fetchItems($config, $limit, null, $offset);
        });
    }

    /**
     * Get total count of items
     */
    public function getTotalCount(SitemapConfig $config): int
    {
        $cacheKey = "sitemap.{$config->name}.total_count";
        
        return Cache::remember($cacheKey, 86400, function () use ($config) {
            return $this->getCount($config);
        });
    }

    /**
     * Get available years for a config
     */
    public function getYears(SitemapConfig $config): array
    {
        $cacheKey = "sitemap.{$config->name}.years";
        
        return Cache::remember($cacheKey, 7200, function () use ($config) {
            $table = $config->table ?? $this->getTableFromModel($config->model);
            $connection = DB::connection();
            $driver = $connection->getDriverName();
            
            $query = DB::table($table);
            
            // Use database-specific year extraction
            $dateField = $connection->getQueryGrammar()->wrap($config->dateField);
            if ($driver === 'sqlite') {
                $query->selectRaw("CAST(strftime('%Y', {$dateField}) AS INTEGER) as year");
            } elseif ($driver === 'pgsql') {
                $query->selectRaw("EXTRACT(YEAR FROM {$dateField}) as year");
            } else {
                // MySQL, MariaDB, etc.
                $query->selectRaw("YEAR({$dateField}) as year");
            }
            
            if ($config->statusField) {
                $query->where($config->statusField, $config->statusValue);
            }
            
            return $query->groupBy('year')
                ->orderBy('year', 'desc')
                ->pluck('year')
                ->toArray();
        });
    }

    /**
     * Generate URL for an item
     */
    public function generateUrl(SitemapConfig $config, $item): string
    {
        // Use custom URL callback if provided
        if ($config->urlCallback) {
            return call_user_func($config->urlCallback, $item, $config);
        }

        // Use route name from config
        if ($config->routeName) {
            $params = $this->buildRouteParams($config, $item);
            if (Route::has($config->routeName)) {
                return route($config->routeName, $params);
            }
        }

        // Use route prefix
        if ($config->routePrefix) {
            return $this->buildUrlFromPrefix($config, $item);
        }

        // Fallback
        $id = $item->id ?? (is_object($item) && method_exists($item, 'getKey') ? $item->getKey() : null);
        return url('/' . ($config->table ?? 'items') . '/' . ($id ?? ''));
    }

    /**
     * Build route parameters
     */
    protected function buildRouteParams(SitemapConfig $config, $item): array
    {
        $params = $config->routeParams;
        
        // Auto-fill common parameters
        if (isset($item->id)) {
            $params['id'] = $item->id;
        }
        
        if (isset($item->slug)) {
            $slugValue = $item->slug;
            
            // Handle JSON slugs (Spatie Translatable)
            if (is_string($slugValue) && strpos($slugValue, '{"') === 0) {
                $data = json_decode($slugValue, true);
                $slugValue = $data[app()->getLocale()] ?? $data['ar'] ?? '';
            }
            
            if (!empty($slugValue) && $slugValue != ($item->id ?? '')) {
                $params['slug'] = $slugValue;
            }
        }

        // Handle date-based routes (like posts)
        if ($config->dateField && isset($item->{$config->dateField})) {
            try {
                $date = Carbon::parse($item->{$config->dateField});
                $params['year'] = $date->format('Y');
                $params['month'] = $date->format('m');
                $params['day'] = $date->format('d');
            } catch (\Exception $e) {
                // Skip if date parsing fails
            }
        }

        // Remove null values to avoid route errors
        return array_filter($params, function ($value) {
            return $value !== null;
        });
    }

    /**
     * Build URL from route prefix
     */
    protected function buildUrlFromPrefix(SitemapConfig $config, $item): string
    {
        $slug = $item->{$config->slugField} ?? null;
        
        if ($slug) {
            // Handle JSON slugs
            if (is_string($slug) && strpos($slug, '{"') === 0) {
                $data = json_decode($slug, true);
                $slug = $data[app()->getLocale()] ?? $data['ar'] ?? '';
            }
        }

        $id = $item->id ?? (is_object($item) && method_exists($item, 'getKey') ? $item->getKey() : null);
        
        if (!$id) {
            return url('/');
        }
        
        if ($slug && $slug != $id && !empty($slug)) {
            return url("/ar/{$config->routePrefix}/{$id}/" . urlencode($slug));
        }
        
        return url("/ar/{$config->routePrefix}/{$id}");
    }

    /**
     * Fetch items from database
     */
    protected function fetchItems(SitemapConfig $config, ?int $limit = null, ?int $year = null, ?int $offset = null): array
    {
        if ($config->isSpatie) {
            return $this->fetchSpatieItems($config, $limit, $year, $offset);
        }

        return $this->fetchTranslatedItems($config, $limit, $year, $offset);
    }

    /**
     * Fetch items with translations
     */
    protected function fetchTranslatedItems(SitemapConfig $config, ?int $limit = null, ?int $year = null, ?int $offset = null): array
    {
        $table = $config->table ?? $this->getTableFromModel($config->model);
        $translationTable = $config->translationTable;
        $foreignKey = $config->foreignKey ?? str_replace('_translations', '', $translationTable ?? '') . '_id';
        
        $query = DB::table($table);
        
        if ($translationTable) {
            $query->join("{$translationTable} as t", "{$table}.id", '=', "t.{$foreignKey}")
                ->where('t.locale', app()->getLocale());
        }

        // Apply status filter
        if ($config->statusField) {
            $query->where("{$table}.{$config->statusField}", $config->statusValue);
        }

        // Apply slug filter
        if ($translationTable) {
            $query->whereNotNull("t.{$config->slugField}")
                ->where("t.{$config->slugField}", '!=', '');
        } else {
            $query->whereNotNull("{$table}.{$config->slugField}")
                ->where("{$table}.{$config->slugField}", '!=', '');
        }

        // Apply year filter
        if ($year) {
            $query->whereYear("{$table}.{$config->dateField}", $year);
        }

        // Apply custom query callback
        if ($config->queryCallback) {
            call_user_func($config->queryCallback, $query);
        }

        // Order by
        $query->orderBy("{$table}.{$config->dateField}", 'desc');

        // Apply offset
        if ($offset !== null) {
            $query->offset($offset);
        }

        // Apply limit
        if ($limit) {
            $query->limit($limit);
        }

        // Select fields
        $selectFields = [
            $translationTable ? "t.{$config->slugField} as slug" : "{$table}.{$config->slugField} as slug",
            "{$table}.updated_at",
            "{$table}.created_at",
            "{$table}.id",
        ];

        if ($translationTable) {
            $titleField = $config->titleField ?? 'title';
            $selectFields[] = "t.{$titleField} as title";
        } else {
            $nameField = $config->nameField ?? 'name';
            $selectFields[] = "{$table}.{$nameField} as name";
        }

        $items = $query->get($selectFields);

        return $items->map(function ($item) use ($config) {
            $url = $this->generateUrl($config, $item);
            
            $lastmod = null;
            if ($item->updated_at) {
                $lastmod = Carbon::parse($item->updated_at);
            } elseif ($item->created_at) {
                $lastmod = Carbon::parse($item->created_at);
            }

            return SitemapItem::fromModel(
                (object) $item,
                $url,
                $config->changefreq,
                $config->priority
            );
        })->toArray();
    }

    /**
     * Fetch Spatie translatable items
     */
    protected function fetchSpatieItems(SitemapConfig $config, ?int $limit = null, ?int $year = null, ?int $offset = null): array
    {
        $table = $config->table ?? $this->getTableFromModel($config->model);
        
        $query = DB::table($table)
            ->whereNotNull($config->slugField)
            ->where($config->slugField, '!=', '');

        // Apply status filter
        if ($config->statusField) {
            $query->where($config->statusField, $config->statusValue);
        }

        // Apply year filter
        if ($year) {
            $query->whereYear($config->dateField, $year);
        }

        // Apply custom query callback
        if ($config->queryCallback) {
            call_user_func($config->queryCallback, $query);
        }

        // Order by
        $query->orderBy($config->dateField, 'desc');

        // Apply offset
        if ($offset !== null) {
            $query->offset($offset);
        }

        // Apply limit
        if ($limit) {
            $query->limit($limit);
        }

        $items = $query->get([
            "{$config->slugField} as slug",
            'updated_at',
            'created_at',
            'id',
            "{$config->nameField} as name"
        ]);

        return $items->map(function ($item) use ($config) {
            $slugValue = $item->slug;

            // Handle JSON slugs
            if (is_string($slugValue) && strpos($slugValue, '{"') === 0) {
                $data = json_decode($slugValue, true);
                $slugValue = $data[app()->getLocale()] ?? $data['ar'] ?? '';
            }

            // Clean slug
            if (empty($slugValue) || $slugValue == $item->id) {
                $slugValue = '';
            }

            $url = $this->generateUrl($config, $item);

            $lastmod = null;
            if ($item->updated_at) {
                $lastmod = Carbon::parse($item->updated_at);
            } elseif ($item->created_at) {
                $lastmod = Carbon::parse($item->created_at);
            }

            return new SitemapItem(
                url: $url,
                lastmod: $lastmod,
                changefreq: $config->changefreq,
                priority: $config->priority,
                title: $item->name ?? null,
                id: $item->id
            );
        })->toArray();
    }

    /**
     * Get count of items
     */
    protected function getCount(SitemapConfig $config): int
    {
        $table = $config->table ?? $this->getTableFromModel($config->model);
        
        $query = DB::table($table);

        if ($config->isSpatie) {
            $query->whereNotNull($config->slugField)
                ->where($config->slugField, '!=', '');
        } else {
            $translationTable = $config->translationTable;
            if ($translationTable) {
                $foreignKey = $config->foreignKey ?? str_replace('_translations', '', $translationTable) . '_id';
                $query->join("{$translationTable} as t", "{$table}.id", '=', "t.{$foreignKey}")
                    ->where('t.locale', app()->getLocale())
                    ->whereNotNull("t.{$config->slugField}")
                    ->where("t.{$config->slugField}", '!=', '');
            } else {
                $query->whereNotNull("{$table}.{$config->slugField}")
                    ->where("{$table}.{$config->slugField}", '!=', '');
            }
        }

        if ($config->statusField) {
            $query->where("{$table}.{$config->statusField}", $config->statusValue);
        }

        if ($config->queryCallback) {
            call_user_func($config->queryCallback, $query);
        }

        return $query->count();
    }

    /**
     * Get table name from model
     */
    protected function getTableFromModel(string $model): string
    {
        if (class_exists($model) && is_subclass_of($model, \Illuminate\Database\Eloquent\Model::class)) {
            try {
                // Use reflection to get table without instantiating
                $reflection = new \ReflectionClass($model);
                if ($reflection->hasMethod('getTable')) {
                    // Try to get table name statically if possible
                    $instance = $reflection->newInstanceWithoutConstructor();
                    return $instance->getTable();
                }
            } catch (\Exception $e) {
                // Fallback to default
            }
        }
        
        return Str::snake(Str::plural(class_basename($model)));
    }

    /**
     * Generate cache key
     */
    protected function getCacheKey(SitemapConfig $config, ?int $limit = null, ?int $year = null, ?int $offset = null): string
    {
        $parts = ["sitemap", $config->name];
        
        if ($year) {
            $parts[] = "year.{$year}";
        }
        
        if ($offset !== null) {
            $parts[] = "range.{$offset}";
        }
        
        if ($limit) {
            $parts[] = "limit.{$limit}";
        }
        
        return implode('.', $parts);
    }

    /**
     * Clear cache for a config
     */
    public function clearCache(?string $name = null): void
    {
        if ($name && isset($this->configs[$name])) {
            $this->clearConfigCache($this->configs[$name]);
        } else {
            foreach ($this->configs as $config) {
                $this->clearConfigCache($config);
            }
        }
    }

    /**
     * Clear cache for a specific config
     */
    protected function clearConfigCache(SitemapConfig $config): void
    {
        $patterns = [
            "sitemap.{$config->name}.*",
            "sitemap.{$config->name}.latest",
            "sitemap.{$config->name}.total_count",
            "sitemap.{$config->name}.years",
        ];

        foreach ($patterns as $pattern) {
            // Clear cache by pattern (implementation depends on cache driver)
            Cache::forget($pattern);
        }

        // Clear specific caches
        if ($config->splitByYear) {
            $years = $this->getYears($config);
            foreach ($years as $year) {
                Cache::forget("sitemap.{$config->name}.year.{$year}");
            }
        }

        if ($config->splitByRange) {
            $total = $this->getTotalCount($config);
            $chunks = ceil($total / $config->rangeSize);
            for ($i = 0; $i < $chunks; $i++) {
                $offset = $i * $config->rangeSize;
                Cache::forget("sitemap.{$config->name}.range.{$offset}");
            }
        }
    }
}


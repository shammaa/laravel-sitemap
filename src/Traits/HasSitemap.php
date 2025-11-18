<?php

declare(strict_types=1);

namespace Shammaa\LaravelSitemap\Traits;

use Carbon\Carbon;

trait HasSitemap
{
    /**
     * Get the sitemap URL for this model instance
     * This method should be overridden in your model or handled by the SitemapManager
     */
    public function getSitemapUrl(): string
    {
        // This will be handled by SitemapManager using config
        return url('/' . $this->getTable() . '/' . $this->getKey());
    }

    /**
     * Get the last modification date for sitemap
     */
    public function getSitemapLastmod(): ?\DateTimeInterface
    {
        if ($this->usesTimestamps() && isset($this->updated_at)) {
            return $this->updated_at instanceof \DateTimeInterface 
                ? $this->updated_at 
                : Carbon::parse($this->updated_at);
        }

        if (isset($this->created_at)) {
            return $this->created_at instanceof \DateTimeInterface 
                ? $this->created_at 
                : Carbon::parse($this->created_at);
        }

        return null;
    }

    /**
     * Get the change frequency for sitemap
     */
    public function getSitemapChangefreq(): string
    {
        return 'weekly';
    }

    /**
     * Get the priority for sitemap
     */
    public function getSitemapPriority(): float
    {
        return 0.5;
    }

    /**
     * Check if this model should be included in sitemap
     */
    public function shouldBeInSitemap(): bool
    {
        // Check if model has status field and it's active
        if (isset($this->status)) {
            return $this->status == 1;
        }

        // Check if model has published_at and it's in the past
        if (isset($this->published_at)) {
            return $this->published_at <= now();
        }

        // Default: include all models
        return true;
    }

    /**
     * Get the sitemap route name
     * Route name is defined in config file, not here
     * Override only if you need custom logic per model instance
     */
    public function getSitemapRouteName(): ?string
    {
        return null; // Will use config
    }

    /**
     * Get the sitemap route parameters
     * Override this method in your model to specify custom parameters
     */
    public function getSitemapRouteParams(): array
    {
        // Default: use model key
        return [$this->getKeyName() => $this->getKey()];
    }

    /**
     * Get sitemap configuration for this model
     * Override this static method in your model to define sitemap settings
     * 
     * @return array
     */
    public static function getSitemapConfig(): array
    {
        return [
            // Database settings
            'table' => (new static)->getTable(),
            'translation_table' => null, // e.g., 'article_translations'
            'foreign_key' => null, // e.g., 'article_id'
            'slug_field' => 'slug',
            'title_field' => 'title',
            'name_field' => 'name',
            
            // Route settings
            'route_name' => null, // e.g., 'post'
            'route_prefix' => null, // Alternative to route_name
            'route_params' => [], // Default route parameters
            
            // Filter settings
            'status_field' => 'status',
            'status_value' => 1,
            'date_field' => 'created_at',
            
            // Cache settings (in seconds)
            'cache_time' => config('sitemap.cache.default_time', 3600),
            'latest_cache_time' => config('sitemap.cache.latest_time', 600),
            'latest_limit' => config('sitemap.defaults.latest_limit', 1000),
            
            // Splitting strategy
            // Options: 'year' (split by year), 'range' (split by chunks), 'none' (single file)
            'split_strategy' => 'none', // 'year', 'range', or 'none'
            'range_size' => config('sitemap.defaults.range_size', 10000),
            
            // SEO settings
            'changefreq' => config('sitemap.defaults.changefreq', 'weekly'),
            'priority' => config('sitemap.defaults.priority', 0.5),
            'latest_priority' => config('sitemap.defaults.latest_priority', 0.8),
            
            // Special settings
            'is_spatie' => false, // Use Spatie translatable format
            
            // Callbacks (optional)
            'query_callback' => null, // Custom query modification
            'url_callback' => null, // Custom URL generation
        ];
    }

    /**
     * Get sitemap name/key for this model
     * Used in sitemap URLs (e.g., sitemap-articles.xml)
     * Override to customize
     */
    public static function getSitemapName(): string
    {
        return \Illuminate\Support\Str::snake(\Illuminate\Support\Str::plural(class_basename(static::class)));
    }
}


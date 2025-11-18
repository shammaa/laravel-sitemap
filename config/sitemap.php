<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sitemap Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for your sitemap files. This will be used to generate
    | absolute URLs in the sitemap index.
    |
    */
    'base_url' => env('APP_URL', 'https://example.com'),

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware to apply to sitemap routes.
    |
    */
    'route_middleware' => [],

    /*
    |--------------------------------------------------------------------------
    | Default Cache Settings
    |--------------------------------------------------------------------------
    |
    | Default cache times for sitemap data. These can be overridden per
    | model using getSitemapCacheTime() method.
    |
    */
    'cache' => [
        'default_time' => 3600,      // 1 hour - for full sitemaps
        'latest_time' => 600,        // 10 minutes - for latest sitemaps
        'years_cache_time' => 7200,  // 2 hours - for years list
        'count_cache_time' => 86400, // 24 hours - for total count
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Sitemap Settings
    |--------------------------------------------------------------------------
    |
    | Default settings that can be overridden per model.
    | Each model can define its own settings using getSitemapConfig() method.
    |
    */
    'defaults' => [
        'latest_limit' => 1000,      // Number of latest items
        'range_size' => 10000,       // Size of each range chunk
        'changefreq' => 'weekly',    // Change frequency
        'priority' => 0.5,           // Default priority
        'latest_priority' => 0.8,    // Priority for latest items
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Discovery
    |--------------------------------------------------------------------------
    |
    | Automatically discover models that use HasSitemap trait.
    | You can also manually register models in the ServiceProvider.
    |
    */
    'auto_discover' => true,

    /*
    |--------------------------------------------------------------------------
    | Model Paths
    |--------------------------------------------------------------------------
    |
    | Paths to scan for models that use HasSitemap trait.
    |
    */
    'model_paths' => [
        app_path('Models'),
    ],
];


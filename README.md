# Laravel Sitemap Package

A professional, scalable Sitemap generator for Laravel with automatic model discovery, flexible chunking strategies, and advanced caching. This package automatically discovers models that use the `HasSitemap` trait and generates sitemaps dynamically based on your configuration.

## Features

- ✅ **Automatic Model Discovery** - Simply add the `HasSitemap` trait to your model and it's automatically registered
- ✅ **Flexible Chunking Strategies** - Split sitemaps by year, by range (chunks), or keep as a single file
- ✅ **Multilingual Support** - Full support for translation tables and Spatie Translatable
- ✅ **Smart Caching** - Separate cache settings for different sitemap types
- ✅ **Zero Configuration Required** - Everything is defined in your model, config file only for general settings
- ✅ **Scalable** - Handles millions of records efficiently with chunking
- ✅ **SEO Optimized** - Proper XML structure, lastmod dates, changefreq, and priority settings

## Installation

### Step 1: Install via Composer

```bash
composer require shammaa/laravel-sitemap
```

### Step 2: Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=sitemap-config
```

This will create `config/sitemap.php` in your project. You can customize general settings here, but most configuration is done in your models.

## Quick Start

### 1. Add Trait to Your Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Shammaa\LaravelSitemap\Traits\HasSitemap;

class Article extends Model
{
    use HasSitemap;

    /**
     * Define sitemap configuration for this model
     */
    public static function getSitemapConfig(): array
    {
        return [
            'table' => 'articles',
            'translation_table' => 'article_translations',
            'foreign_key' => 'article_id',
            'slug_field' => 'slug',
            'title_field' => 'title',
            'route_name' => 'post',
            'route_params' => [
                'year' => null,
                'month' => null,
                'day' => null,
                'post' => null,
            ],
            'status_field' => 'status',
            'status_value' => 1,
            'date_field' => 'created_at',
            'cache_time' => 3600,
            'latest_cache_time' => 600,
            'latest_limit' => 1000,
            'split_strategy' => 'year', // Split by year
            'changefreq' => 'daily',
            'priority' => 0.8,
            'latest_priority' => 1.0,
        ];
    }
}
```

**That's it!** The package will automatically:
- Discover your model
- Register it in the sitemap system
- Generate routes for sitemap access
- Create sitemap XML files on demand

### ⚡ Zero Additional Steps Required

**No manual activation needed!** Once you:
1. ✅ Install the package
2. ✅ Add `HasSitemap` trait to your model
3. ✅ Define `getSitemapConfig()` method

The sitemap routes are **automatically active** and ready to use:
- `/sitemap.xml` - Works immediately
- `/sitemap-{type}-latest.xml` - Works immediately
- All other routes - Work immediately

**No need to:**
- ❌ Run any commands
- ❌ Clear any cache (unless you have route cache)
- ❌ Register routes manually
- ❌ Activate anything

**Note:** If you have route caching enabled, you may need to run `php artisan route:clear` once after installation. Otherwise, everything works automatically!

## How It Works

### Automatic Discovery Process

1. **Service Provider Registration**: When Laravel boots, the `LaravelSitemapServiceProvider` is automatically registered (via `composer.json`)

2. **Model Scanning**: The provider scans your `app/Models` directory (configurable) for models that use the `HasSitemap` trait

3. **Configuration Extraction**: For each model found, it calls `getSitemapConfig()` to get the sitemap settings

4. **Registration**: Each model is registered in the `SitemapManager` with its configuration

5. **Route Generation**: Routes are automatically registered for accessing sitemaps

### Sitemap Generation Flow

```
Request: /sitemap.xml
    ↓
SitemapController::index()
    ↓
SitemapManager::getAllConfigs()
    ↓
For each registered model:
    - Check split_strategy
    - Generate appropriate sitemap URLs
    - Return sitemap index XML
```

```
Request: /sitemap-articles-2024.xml
    ↓
SitemapController::yearly('articles', 2024)
    ↓
SitemapManager::getItemsByYear(config, 2024)
    ↓
Fetch from database (with caching)
    ↓
Generate URLs for each item
    ↓
Return sitemap XML
```

## Configuration

### Model Configuration

Each model defines its own sitemap configuration via the `getSitemapConfig()` method:

```php
public static function getSitemapConfig(): array
{
    return [
        // Database Configuration
        'table' => 'articles',                    // Main table name
        'translation_table' => 'article_translations',  // Translation table (if multilingual)
        'foreign_key' => 'article_id',           // Foreign key in translation table
        'slug_field' => 'slug',                  // Field name for slug
        'title_field' => 'title',                // Field name for title (in translations)
        'name_field' => 'name',                  // Field name for name (for non-translated)
        
        // Route Configuration
        'route_name' => 'post',                  // Laravel route name
        'route_prefix' => null,                  // Alternative: route prefix
        'route_params' => [                      // Default route parameters
            'year' => null,
            'month' => null,
            'day' => null,
            'post' => null,
        ],
        
        // Filtering
        'status_field' => 'status',              // Field to check for active status
        'status_value' => 1,                     // Value for active status
        'date_field' => 'created_at',            // Field for date filtering
        
        // Cache Settings (in seconds)
        'cache_time' => 3600,                    // Cache time for full sitemaps
        'latest_cache_time' => 600,              // Cache time for latest items
        'latest_limit' => 1000,                  // Number of latest items
        
        // Splitting Strategy
        'split_strategy' => 'year',              // 'year', 'range', or 'none'
        'range_size' => 10000,                   // Size of each chunk (for 'range')
        
        // SEO Settings
        'changefreq' => 'daily',                // Change frequency
        'priority' => 0.5,                       // Default priority
        'latest_priority' => 0.8,                // Priority for latest items
        
        // Special Settings
        'is_spatie' => false,                    // Use Spatie Translatable format
        
        // Callbacks (Optional)
        'query_callback' => null,                // Custom query modification
        'url_callback' => null,                 // Custom URL generation
    ];
}
```

### Global Configuration

The `config/sitemap.php` file contains general settings:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Sitemap Base URL
    |--------------------------------------------------------------------------
    */
    'base_url' => env('APP_URL', 'https://example.com'),

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    */
    'route_middleware' => [],

    /*
    |--------------------------------------------------------------------------
    | Default Cache Settings
    |--------------------------------------------------------------------------
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
    */
    'auto_discover' => true,

    /*
    |--------------------------------------------------------------------------
    | Model Paths
    |--------------------------------------------------------------------------
    */
    'model_paths' => [
        app_path('Models'),
    ],
];
```

## Splitting Strategies

### Strategy: `'year'` - Split by Year

Splits sitemap into separate files for each year. Perfect for content that grows over time.

```php
'split_strategy' => 'year',
```

**Generated URLs:**
- `/sitemap-articles-latest.xml` - Latest 1000 articles
- `/sitemap-articles-2024.xml` - All articles from 2024
- `/sitemap-articles-2023.xml` - All articles from 2023
- `/sitemap-articles-2022.xml` - All articles from 2022
- ... and so on

**Use Case:** Blog posts, news articles, events

### Strategy: `'range'` - Split by Count (Chunks)

Splits sitemap into chunks of a specified size. Perfect for large datasets.

```php
'split_strategy' => 'range',
'range_size' => 10000,  // Each file contains 10,000 items
```

**Generated URLs:**
- `/sitemap-tags-latest.xml` - Latest 1000 tags
- `/sitemap-tags-part-1.xml` - Tags 1-10,000
- `/sitemap-tags-part-2.xml` - Tags 10,001-20,000
- `/sitemap-tags-part-3.xml` - Tags 20,001-30,000
- ... and so on

**Use Case:** Tags, categories, products (large catalogs)

### Strategy: `'none'` - Single File

Keeps all items in a single sitemap file. Use for smaller datasets.

```php
'split_strategy' => 'none',
```

**Generated URLs:**
- `/sitemap-categories-latest.xml` - Latest 1000 categories
- `/sitemap-categories.xml` - All categories

**Use Case:** Categories, small lists, static content

## Available Routes

**All routes are automatically registered and active immediately.** No manual route definition needed. No activation required. Just add the trait to your model and the routes work instantly!

### Main Sitemap Index
```
GET /sitemap.xml
```
Returns the main sitemap index containing links to all sitemap files.

### Latest Items
```
GET /sitemap-{type}-latest.xml
```
Returns the latest items for a specific type.

**Example:** `/sitemap-articles-latest.xml`

### Yearly Sitemaps (if `split_strategy = 'year'`)
```
GET /sitemap-{type}-{year}.xml
```
Returns items for a specific year.

**Example:** `/sitemap-articles-2024.xml`

### Range-based Sitemaps (if `split_strategy = 'range'`)
```
GET /sitemap-{type}-part-{chunk}.xml
```
Returns a specific chunk of items.

**Example:** `/sitemap-tags-part-1.xml`

### Full Sitemap (if `split_strategy = 'none'`)
```
GET /sitemap-{type}.xml
```
Returns all items for a specific type.

**Example:** `/sitemap-categories.xml`

## Usage Examples

### Example 1: Blog Articles (Split by Year)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Shammaa\LaravelSitemap\Traits\HasSitemap;

class Article extends Model
{
    use HasSitemap;

    public static function getSitemapConfig(): array
    {
        return [
            'table' => 'articles',
            'translation_table' => 'article_translations',
            'foreign_key' => 'article_id',
            'slug_field' => 'slug',
            'title_field' => 'title',
            'route_name' => 'post',
            'route_params' => [
                'year' => null,
                'month' => null,
                'day' => null,
                'post' => null,
            ],
            'status_field' => 'status',
            'status_value' => 1,
            'date_field' => 'created_at',
            'split_strategy' => 'year',
            'changefreq' => 'daily',
            'priority' => 0.8,
            'latest_priority' => 1.0,
        ];
    }
}
```

### Example 2: Tags (Split by Range - Spatie Translatable)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Shammaa\LaravelSitemap\Traits\HasSitemap;

class Tag extends Model
{
    use HasSitemap;

    public static function getSitemapConfig(): array
    {
        return [
            'table' => 'tags',
            'slug_field' => 'slug',
            'name_field' => 'name',
            'route_name' => 'tag',
            'route_params' => [
                'id' => null,
                'slug' => null,
            ],
            'status_field' => null,  // No status field
            'date_field' => 'created_at',
            'split_strategy' => 'range',
            'range_size' => 10000,
            'is_spatie' => true,  // Spatie Translatable
            'changefreq' => 'weekly',
            'priority' => 0.5,
        ];
    }
}
```

### Example 3: Categories (Single File)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Shammaa\LaravelSitemap\Traits\HasSitemap;

class Category extends Model
{
    use HasSitemap;

    public static function getSitemapConfig(): array
    {
        return [
            'table' => 'categories',
            'translation_table' => 'category_translations',
            'foreign_key' => 'category_id',
            'slug_field' => 'slug',
            'title_field' => 'name',
            'route_name' => 'category',
            'route_params' => ['slug' => null],
            'status_field' => 'status',
            'status_value' => 1,
            'split_strategy' => 'none',
            'changefreq' => 'monthly',
            'priority' => 0.6,
        ];
    }
}
```

## Advanced Usage

### Custom Query Callback

Modify the database query before execution:

```php
'query_callback' => function ($query) {
    $query->where('featured', true)
          ->where('published_at', '<=', now());
},
```

### Custom URL Generation

Generate custom URLs for items:

```php
'url_callback' => function ($item, $config) {
    // Custom URL logic
    return url("/custom-path/{$item->id}/" . Str::slug($item->title));
},
```

### Custom Sitemap Name

Override the default sitemap name:

```php
public static function getSitemapName(): string
{
    return 'my-custom-name';  // Default: snake_case plural of class name
}
```

## Artisan Commands

### Clear Cache

```bash
# Clear all sitemap cache
php artisan sitemap:clear

# Clear cache for specific type
php artisan sitemap:clear articles
```

### Warmup Cache

Pre-generate and cache all sitemaps:

```bash
# Warmup all sitemaps
php artisan sitemap:warmup

# Warmup specific type
php artisan sitemap:warmup articles
```

## Caching Strategy

The package uses intelligent caching:

1. **Full Sitemaps**: Cached for `cache_time` (default: 1 hour)
2. **Latest Items**: Cached for `latest_cache_time` (default: 10 minutes)
3. **Year Lists**: Cached for 2 hours
4. **Total Counts**: Cached for 24 hours

Cache keys follow this pattern:
- `sitemap.{name}.latest` - Latest items
- `sitemap.{name}.year.{year}` - Year-based sitemaps
- `sitemap.{name}.range.{offset}` - Range-based sitemaps
- `sitemap.{name}.total_count` - Total count
- `sitemap.{name}.years` - Available years

## URL Generation

The package automatically generates URLs based on your route configuration:

### Using Route Name

```php
'route_name' => 'post',
'route_params' => [
    'year' => null,
    'month' => null,
    'day' => null,
    'post' => null,
],
```

The package will automatically fill these parameters from the item data.

### Using Route Prefix

```php
'route_prefix' => 'tag',
```

Generates URLs like: `/ar/tag/{id}/{slug}`

### Date-based Routes

If your route requires date parameters (year, month, day), they are automatically extracted from the `date_field`:

```php
'date_field' => 'created_at',
```

The package extracts year, month, and day from this field automatically.

## Multilingual Support

### Translation Tables

For models using translation tables:

```php
'translation_table' => 'article_translations',
'foreign_key' => 'article_id',
```

The package automatically:
- Joins the translation table
- Filters by current locale
- Uses translated slug and title fields

### Spatie Translatable

For models using Spatie Translatable:

```php
'is_spatie' => true,
```

The package automatically:
- Handles JSON-encoded slug fields
- Extracts the correct locale from JSON
- Falls back to 'ar' if current locale not found

## Configuration Reference

### Complete Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `table` | string | `getTable()` | Main database table name |
| `translation_table` | string\|null | `null` | Translation table name |
| `foreign_key` | string\|null | `null` | Foreign key in translation table |
| `slug_field` | string | `'slug'` | Field name for slug |
| `title_field` | string | `'title'` | Field name for title (in translations) |
| `name_field` | string | `'name'` | Field name for name (non-translated) |
| `route_name` | string\|null | `null` | Laravel route name |
| `route_prefix` | string\|null | `null` | Route prefix (alternative to route_name) |
| `route_params` | array | `[]` | Default route parameters |
| `status_field` | string\|null | `'status'` | Field to check for active status |
| `status_value` | mixed | `1` | Value for active status |
| `date_field` | string | `'created_at'` | Field for date filtering |
| `cache_time` | int | `3600` | Cache time for full sitemaps (seconds) |
| `latest_cache_time` | int | `600` | Cache time for latest items (seconds) |
| `latest_limit` | int | `1000` | Number of latest items |
| `split_strategy` | string | `'none'` | `'year'`, `'range'`, or `'none'` |
| `range_size` | int | `10000` | Size of each chunk (for 'range') |
| `changefreq` | string | `'weekly'` | Change frequency |
| `priority` | float | `0.5` | Default priority (0.0 - 1.0) |
| `latest_priority` | float | `0.8` | Priority for latest items |
| `is_spatie` | bool | `false` | Use Spatie Translatable format |
| `query_callback` | Closure\|null | `null` | Custom query modification |
| `url_callback` | Closure\|null | `null` | Custom URL generation |

## Best Practices

1. **Use Year Strategy for Time-based Content**: Articles, news, events
2. **Use Range Strategy for Large Datasets**: Tags, products (10k+ items)
3. **Use None Strategy for Small Lists**: Categories, small collections
4. **Set Appropriate Cache Times**: Longer for stable content, shorter for frequently updated
5. **Warmup Cache After Deployment**: Run `sitemap:warmup` after major updates
6. **Monitor Cache Performance**: Adjust cache times based on your traffic

## Troubleshooting

### Models Not Appearing in Sitemap

1. Check that the model uses `HasSitemap` trait
2. Verify `getSitemapConfig()` method exists and returns array
3. Check `config('sitemap.auto_discover')` is `true`
4. Verify model path in `config('sitemap.model_paths')`

### Routes Not Working

1. Clear route cache: `php artisan route:clear`
2. Check middleware configuration
3. Verify ServiceProvider is registered

### Cache Issues

1. Clear sitemap cache: `php artisan sitemap:clear`
2. Check cache driver configuration
3. Verify cache keys are not conflicting

## Requirements

- PHP >= 8.1
- Laravel >= 9.0
- Illuminate packages (support, http, view, cache, database, routing)

## License

MIT License - feel free to use in commercial and personal projects.

## Support

For issues, questions, or contributions, please open an issue on GitHub.

---

**Made with ❤️ for the Laravel community**

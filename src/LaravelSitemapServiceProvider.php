<?php

declare(strict_types=1);

namespace Shammaa\LaravelSitemap;

use Shammaa\LaravelSitemap\Services\SitemapManager;
use Shammaa\LaravelSitemap\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class LaravelSitemapServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/sitemap.php', 'sitemap');

        $this->app->singleton(SitemapManager::class, function ($app) {
            $manager = new SitemapManager();
            
            // Auto-discover models with HasSitemap trait
            if (config('sitemap.auto_discover', true)) {
                $this->discoverModels($manager);
            }
            
            return $manager;
        });

        $this->app->alias(SitemapManager::class, 'sitemap');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/sitemap.php' => config_path('sitemap.php'),
        ], 'sitemap-config');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'sitemap');

        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Shammaa\LaravelSitemap\Console\ClearSitemapCache::class,
                \Shammaa\LaravelSitemap\Console\WarmupSitemapCache::class,
            ]);
        }
    }

    protected function registerRoutes(): void
    {
        Route::middleware(config('sitemap.route_middleware', []))
            ->group(function () {
                // Main sitemap index
                Route::get('/sitemap.xml', [SitemapController::class, 'index'])
                    ->name('sitemap.index');

                // Latest sitemaps
                Route::get('/sitemap-{type}-latest.xml', [SitemapController::class, 'latest'])
                    ->name('sitemap.latest');

                // Yearly sitemaps
                Route::get('/sitemap-{type}-{year}.xml', [SitemapController::class, 'yearly'])
                    ->where('year', '[0-9]{4}')
                    ->name('sitemap.yearly');

                // Range-based sitemaps (chunks)
                Route::get('/sitemap-{type}-part-{chunk}.xml', [SitemapController::class, 'range'])
                    ->where('chunk', '[0-9]+')
                    ->name('sitemap.range');

                // Type-based sitemaps
                Route::get('/sitemap-{type}.xml', [SitemapController::class, 'type'])
                    ->name('sitemap.type');
            });
    }

    /**
     * Discover models that use HasSitemap trait and register them
     */
    protected function discoverModels(SitemapManager $manager): void
    {
        $paths = config('sitemap.model_paths', [app_path('Models')]);
        $traitName = 'Shammaa\\LaravelSitemap\\Traits\\HasSitemap';

        foreach ($paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $files = glob($path . '/*.php');
            
            foreach ($files as $file) {
                $className = $this->getClassNameFromFile($file);
                
                if (!$className || !class_exists($className)) {
                    continue;
                }

                // Check if class uses HasSitemap trait
                $traits = class_uses_recursive($className);
                
                if (in_array($traitName, $traits)) {
                    try {
                        // Check if class has getSitemapConfig method
                        if (!method_exists($className, 'getSitemapConfig')) {
                            continue;
                        }
                        
                        $name = $className::getSitemapName();
                        $config = $className::getSitemapConfig();
                        
                        // Add model class to config
                        $config['model'] = $className;
                        
                        // Convert split_strategy to old format for compatibility
                        if (isset($config['split_strategy'])) {
                            $strategy = $config['split_strategy'];
                            $config['split_by_year'] = ($strategy === 'year');
                            $config['split_by_range'] = ($strategy === 'range');
                            unset($config['split_strategy']);
                        }
                        
                        $manager->register($name, $config);
                    } catch (\Exception $e) {
                        // Skip models that can't be registered
                        continue;
                    }
                }
            }
        }
    }

    /**
     * Get class name from file path
     */
    protected function getClassNameFromFile(string $file): ?string
    {
        $content = file_get_contents($file);
        
        if (!preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch)) {
            return null;
        }
        
        if (!preg_match('/class\s+(\w+)/', $content, $classMatch)) {
            return null;
        }
        
        return $namespaceMatch[1] . '\\' . $classMatch[1];
    }
}


<?php

declare(strict_types=1);

namespace Shammaa\LaravelSitemap\Config;

class SitemapConfig
{
    public function __construct(
        public readonly string $name,
        public readonly string $model,
        public readonly ?string $table = null,
        public readonly ?string $translationTable = null,
        public readonly ?string $foreignKey = null,
        public readonly string $slugField = 'slug',
        public readonly string $titleField = 'title',
        public readonly string $nameField = 'name',
        public readonly string $routePrefix = '',
        public readonly string $routeName = '',
        public readonly array $routeParams = [],
        public readonly ?string $statusField = 'status',
        public readonly mixed $statusValue = 1,
        public readonly ?string $dateField = 'created_at',
        public readonly int $cacheTime = 3600,
        public readonly int $latestCacheTime = 600,
        public readonly int $latestLimit = 1000,
        public readonly ?int $chunkSize = null,
        public readonly bool $splitByYear = false,
        public readonly bool $splitByRange = false,
        public readonly int $rangeSize = 10000,
        public readonly string $changefreq = 'weekly',
        public readonly float $priority = 0.5,
        public readonly float $latestPriority = 0.8,
        public readonly bool $isSpatie = false,
        public readonly ?\Closure $queryCallback = null,
        public readonly ?\Closure $urlCallback = null,
    ) {}

    public static function fromArray(string $name, array $config): self
    {
        return new self(
            name: $name,
            model: $config['model'] ?? '',
            table: $config['table'] ?? null,
            translationTable: $config['translation_table'] ?? null,
            foreignKey: $config['foreign_key'] ?? null,
            slugField: $config['slug_field'] ?? 'slug',
            titleField: $config['title_field'] ?? 'title',
            nameField: $config['name_field'] ?? 'name',
            routePrefix: $config['route_prefix'] ?? '',
            routeName: $config['route_name'] ?? '',
            routeParams: $config['route_params'] ?? [],
            statusField: $config['status_field'] ?? 'status',
            statusValue: $config['status_value'] ?? 1,
            dateField: $config['date_field'] ?? 'created_at',
            cacheTime: $config['cache_time'] ?? 3600,
            latestCacheTime: $config['latest_cache_time'] ?? 600,
            latestLimit: $config['latest_limit'] ?? 1000,
            chunkSize: $config['chunk_size'] ?? null,
            splitByYear: $config['split_by_year'] ?? false,
            splitByRange: $config['split_by_range'] ?? false,
            rangeSize: $config['range_size'] ?? 10000,
            changefreq: $config['changefreq'] ?? 'weekly',
            priority: $config['priority'] ?? 0.5,
            latestPriority: $config['latest_priority'] ?? 0.8,
            isSpatie: $config['is_spatie'] ?? false,
            queryCallback: $config['query_callback'] ?? null,
            urlCallback: $config['url_callback'] ?? null,
        );
    }
}


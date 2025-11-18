<?php

declare(strict_types=1);

namespace Shammaa\LaravelSitemap\Data;

use Carbon\Carbon;

class SitemapItem
{
    public function __construct(
        public readonly string $url,
        public readonly ?\DateTimeInterface $lastmod = null,
        public readonly string $changefreq = 'weekly',
        public readonly float $priority = 0.5,
        public readonly ?string $title = null,
        public readonly ?int $id = null,
    ) {}

    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'lastmod' => $this->lastmod?->format('Y-m-d\TH:i:sP'),
            'changefreq' => $this->changefreq,
            'priority' => (string) $this->priority,
            'title' => $this->title,
            'id' => $this->id,
        ];
    }

    public static function fromModel($model, string $url, ?string $changefreq = null, ?float $priority = null): self
    {
        $lastmod = null;
        
        if (method_exists($model, 'getSitemapLastmod')) {
            $lastmod = $model->getSitemapLastmod();
        } elseif (isset($model->updated_at)) {
            $lastmod = $model->updated_at instanceof \DateTimeInterface 
                ? $model->updated_at 
                : Carbon::parse($model->updated_at);
        } elseif (isset($model->created_at)) {
            $lastmod = $model->created_at instanceof \DateTimeInterface 
                ? $model->created_at 
                : Carbon::parse($model->created_at);
        }

        $changefreq = $changefreq 
            ?? (method_exists($model, 'getSitemapChangefreq') ? $model->getSitemapChangefreq() : 'weekly');
        
        $priority = $priority 
            ?? (method_exists($model, 'getSitemapPriority') ? $model->getSitemapPriority() : 0.5);

        $title = null;
        if (isset($model->title)) {
            $title = $model->title;
        } elseif (isset($model->name)) {
            $title = $model->name;
        }

        $id = $model->id ?? (is_object($model) && method_exists($model, 'getKey') ? $model->getKey() : null);

        return new self(
            url: $url,
            lastmod: $lastmod,
            changefreq: $changefreq,
            priority: $priority,
            title: $title,
            id: $id,
        );
    }
}


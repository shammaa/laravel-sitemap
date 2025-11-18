<?php

declare(strict_types=1);

namespace Shammaa\LaravelSitemap\Contracts;

use Illuminate\Database\Eloquent\Model;

interface HasSitemapData
{
    /**
     * Get the sitemap URL for this model instance
     */
    public function getSitemapUrl(): string;

    /**
     * Get the last modification date for sitemap
     */
    public function getSitemapLastmod(): ?\DateTimeInterface;

    /**
     * Get the change frequency for sitemap
     */
    public function getSitemapChangefreq(): string;

    /**
     * Get the priority for sitemap
     */
    public function getSitemapPriority(): float;

    /**
     * Check if this model should be included in sitemap
     */
    public function shouldBeInSitemap(): bool;

    /**
     * Get the sitemap route name
     */
    public function getSitemapRouteName(): string;

    /**
     * Get the sitemap route parameters
     */
    public function getSitemapRouteParams(): array;
}


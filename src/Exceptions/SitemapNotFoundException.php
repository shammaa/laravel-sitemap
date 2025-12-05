<?php

declare(strict_types=1);

namespace Shammaa\LaravelSitemap\Exceptions;

use RuntimeException;

class SitemapNotFoundException extends RuntimeException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(string $type)
    {
        parent::__construct("Sitemap type '{$type}' not found.");
    }
}


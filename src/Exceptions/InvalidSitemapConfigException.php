<?php

declare(strict_types=1);

namespace Shammaa\LaravelSitemap\Exceptions;

use InvalidArgumentException;

class InvalidSitemapConfigException extends InvalidArgumentException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(string $message, ?string $configName = null)
    {
        if ($configName) {
            $message = "Invalid sitemap config for '{$configName}': {$message}";
        }
        
        parent::__construct($message);
    }
}


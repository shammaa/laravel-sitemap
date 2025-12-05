<?php

declare(strict_types=1);

namespace Shammaa\LaravelSitemap\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Shammaa\LaravelSitemap\Exceptions\InvalidSitemapConfigException;

class InvalidSitemapConfigExceptionTest extends TestCase
{
    public function test_exception_message_without_config_name(): void
    {
        $exception = new InvalidSitemapConfigException('Test error');
        $this->assertEquals('Test error', $exception->getMessage());
    }

    public function test_exception_message_with_config_name(): void
    {
        $exception = new InvalidSitemapConfigException('Test error', 'test-config');
        $this->assertStringContainsString('test-config', $exception->getMessage());
        $this->assertStringContainsString('Test error', $exception->getMessage());
    }
}


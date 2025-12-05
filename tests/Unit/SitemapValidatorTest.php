<?php

declare(strict_types=1);

namespace Shammaa\LaravelSitemap\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Shammaa\LaravelSitemap\Exceptions\InvalidSitemapConfigException;
use Shammaa\LaravelSitemap\Services\SitemapValidator;

class SitemapValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_validate_config_success(): void
    {
        $config = [
            'model' => \Illuminate\Database\Eloquent\Model::class,
            'changefreq' => 'weekly',
            'priority' => 0.5,
        ];

        $this->assertTrue(SitemapValidator::validateConfig('test', $config));
    }

    public function test_validate_config_throws_exception_when_model_missing(): void
    {
        $this->expectException(InvalidSitemapConfigException::class);
        SitemapValidator::validateConfig('test', []);
    }

    public function test_validate_config_throws_exception_when_model_not_exists(): void
    {
        $this->expectException(InvalidSitemapConfigException::class);
        SitemapValidator::validateConfig('test', ['model' => 'NonExistentClass']);
    }

    public function test_validate_config_throws_exception_when_invalid_changefreq(): void
    {
        $this->expectException(InvalidSitemapConfigException::class);
        SitemapValidator::validateConfig('test', [
            'model' => \Illuminate\Database\Eloquent\Model::class,
            'changefreq' => 'invalid',
        ]);
    }

    public function test_validate_config_throws_exception_when_invalid_priority(): void
    {
        $this->expectException(InvalidSitemapConfigException::class);
        SitemapValidator::validateConfig('test', [
            'model' => \Illuminate\Database\Eloquent\Model::class,
            'priority' => 1.5,
        ]);
    }

    public function test_validate_year_success(): void
    {
        $currentYear = (int) date('Y');
        $this->assertTrue(SitemapValidator::validateYear($currentYear));
    }

    public function test_validate_year_throws_exception_when_invalid(): void
    {
        $this->expectException(InvalidSitemapConfigException::class);
        SitemapValidator::validateYear(1999);
    }

    public function test_validate_chunk_success(): void
    {
        $this->assertTrue(SitemapValidator::validateChunk(1));
    }

    public function test_validate_chunk_throws_exception_when_invalid(): void
    {
        $this->expectException(InvalidSitemapConfigException::class);
        SitemapValidator::validateChunk(0);
    }
}


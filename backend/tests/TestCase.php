<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected const CDN = 'https://fatherstock-cache.b-cdn.net';

    protected function setUp(): void
    {
        parent::setUp();

        // No test may ever reach the real CDN.
        Http::preventStrayRequests();

        config([
            'fatherstock.base_url' => self::CDN,
            'fatherstock.http.retries' => 0,
            'fatherstock.http.retry_delay_ms' => 0,
        ]);
    }

    /**
     * Decoded contents of a JSON file in tests/Fixtures.
     *
     * @return array<string, mixed>
     */
    protected function fixture(string $name): array
    {
        return json_decode(file_get_contents(__DIR__."/Fixtures/{$name}.json"), true, flags: JSON_THROW_ON_ERROR);
    }
}

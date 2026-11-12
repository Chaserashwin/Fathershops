<?php

namespace App\Services\FatherStock;

use App\Exceptions\UpstreamUnavailableException;
use Illuminate\Container\Attributes\Config;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Throwable;

/**
 * Thin HTTP client for the FatherStock catalog CDN.
 *
 * It only knows how to fetch a JSON file and classify the outcome:
 *  - array  → decoded JSON document
 *  - null   → the file does not exist upstream (404)
 *  - throws → anything else (timeouts, 5xx, non-JSON bodies)
 */
class FatherStockClient
{
    public function __construct(
        private readonly HttpFactory $http,
        #[Config('fatherstock.base_url')] private readonly string $baseUrl,
        #[Config('fatherstock.http')] private readonly array $options,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function hotCategories(): ?array
    {
        return $this->get('category/hot-category.json');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function category(int $categoryId): ?array
    {
        return $this->get("category/{$categoryId}.json");
    }

    /**
     * @return array<string, mixed>|null
     */
    public function product(int $productId): ?array
    {
        return $this->get("cache/{$productId}-f1.json");
    }

    /**
     * @return array<string, mixed>|null
     *
     * @throws UpstreamUnavailableException
     */
    private function get(string $path): ?array
    {
        try {
            $response = $this->http
                ->baseUrl($this->baseUrl)
                ->acceptJson()
                ->timeout($this->options['timeout'])
                ->connectTimeout($this->options['connect_timeout'])
                ->retry(
                    times: 1 + $this->options['retries'],
                    sleepMilliseconds: $this->options['retry_delay_ms'],
                    when: static fn (Throwable $e): bool => $e instanceof ConnectionException
                        || ($e instanceof RequestException && $e->response->serverError()),
                    throw: false,
                )
                ->get($path);
        } catch (ConnectionException $e) {
            throw new UpstreamUnavailableException($path, $e->getMessage(), $e);
        }

        if ($response->notFound()) {
            return null;
        }

        if ($response->failed()) {
            throw new UpstreamUnavailableException($path, "unexpected HTTP status {$response->status()}");
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new UpstreamUnavailableException($path, 'response body is not a JSON document');
        }

        return $payload;
    }
}

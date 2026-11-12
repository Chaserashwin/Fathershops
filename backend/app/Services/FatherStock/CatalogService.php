<?php

namespace App\Services\FatherStock;

use App\Data\Category;
use App\Data\ProductDetail;
use App\Data\ProductSummary;
use App\Exceptions\ApiException;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\UpstreamUnavailableException;
use Closure;
use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Read-only catalog backed by the FatherStock CDN.
 *
 * Each lookup is normalised into a small DTO and cached, so the CDN is hit
 * at most once per resource per TTL. "Not found" answers are cached too
 * (for a much shorter time) to protect the CDN from repeated misses.
 */
class CatalogService
{
    /** Cache marker for "we asked upstream and it does not exist". */
    private const MISSING = '__missing__';

    public function __construct(
        private readonly FatherStockClient $client,
        private readonly Cache $cache,
        #[Config('fatherstock.cache')] private readonly array $cacheOptions,
        #[Config('fatherstock.languages')] private readonly array $languages,
        #[Config('fatherstock.default_language')] private readonly string $defaultLanguage,
    ) {}

    /**
     * All featured categories.
     *
     * The brief asks for a 404 both when no categories exist and when the
     * upstream request fails, so an outage is reported to the log and then
     * surfaced to the client as "not found".
     *
     * @return list<Category>
     *
     * @throws ResourceNotFoundException
     */
    public function categories(): array
    {
        return $this->remember('categories', function (): array {
            try {
                $payload = $this->client->hotCategories();
            } catch (UpstreamUnavailableException $e) {
                report($e);

                throw ResourceNotFoundException::categories();
            }

            $entries = is_array($payload['response'] ?? null) ? $payload['response'] : [];

            $categories = collect($entries)
                ->filter(fn ($entry) => is_array($entry))
                ->map(fn (array $entry) => Category::fromUpstream($entry))
                ->filter()
                ->unique('id')
                ->values()
                ->all();

            if ($categories === []) {
                throw ResourceNotFoundException::categories();
            }

            return $categories;
        });
    }

    /**
     * A single featured category, or null when it is not in the featured
     * list (or the list itself is unavailable).
     */
    public function findCategory(int $categoryId): ?Category
    {
        try {
            return collect($this->categories())->firstWhere('id', $categoryId);
        } catch (ApiException) {
            return null;
        }
    }

    /**
     * Every product in a category, de-duplicated by product id.
     *
     * @return list<ProductSummary>
     *
     * @throws ResourceNotFoundException
     * @throws UpstreamUnavailableException
     */
    public function categoryProducts(int $categoryId, ?string $language = null): array
    {
        $languageId = $this->languageId($language);

        return $this->remember("category:{$categoryId}:products:{$languageId}", function () use ($categoryId, $languageId): array {
            $payload = $this->client->category($categoryId)
                ?? throw ResourceNotFoundException::category($categoryId);

            $entries = $payload['data']['products'] ?? null;

            if (! is_array($entries)) {
                throw new UpstreamUnavailableException("category/{$categoryId}.json", 'payload has no product list');
            }

            // The CDN repeats the same product several times per listing.
            return collect($entries)
                ->filter(fn ($entry) => is_array($entry))
                ->map(fn (array $entry) => ProductSummary::fromUpstream($entry, $languageId))
                ->filter()
                ->unique('id')
                ->values()
                ->all();
        });
    }

    /**
     * @throws ResourceNotFoundException
     * @throws UpstreamUnavailableException
     */
    public function product(int $productId, ?string $language = null): ProductDetail
    {
        $languageId = $this->languageId($language);

        return $this->remember("product:{$productId}:{$languageId}", function () use ($productId, $languageId): ProductDetail {
            $payload = $this->client->product($productId)
                ?? throw ResourceNotFoundException::product($productId);

            return ProductDetail::fromUpstream($payload, $languageId)
                ?? throw ResourceNotFoundException::product($productId);
        });
    }

    /**
     * Cache-aside with negative caching.
     *
     * Only "not found" is cached as a miss; upstream outages always bubble
     * up uncached so the next request retries.
     *
     * @template T
     *
     * @param  Closure(): T  $resolve
     * @return T
     */
    private function remember(string $key, Closure $resolve): mixed
    {
        $key = "{$this->cacheOptions['prefix']}:{$key}";
        $cached = $this->cache->get($key);

        if (is_array($cached) && ($cached['state'] ?? null) === self::MISSING) {
            throw new ResourceNotFoundException($cached['message'], $cached['code']);
        }

        if ($cached !== null) {
            return $cached;
        }

        try {
            $value = $resolve();
        } catch (ResourceNotFoundException $e) {
            if ($this->cacheOptions['not_found_ttl'] > 0) {
                $this->cache->put($key, [
                    'state' => self::MISSING,
                    'message' => $e->getMessage(),
                    'code' => $e->errorCode(),
                ], $this->cacheOptions['not_found_ttl']);
            }

            throw $e;
        }

        $this->cache->put($key, $value, $this->cacheOptions['ttl']);

        return $value;
    }

    private function languageId(?string $language): string
    {
        return $this->languages[$language ?? $this->defaultLanguage]
            ?? $this->languages[$this->defaultLanguage];
    }
}

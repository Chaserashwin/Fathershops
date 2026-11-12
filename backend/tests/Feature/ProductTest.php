<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            self::CDN.'/cache/358120-f1.json' => Http::response($this->fixture('product-358120')),
            self::CDN.'/cache/500-f1.json' => Http::response('Server error', 500),
            self::CDN.'/cache/7-f1.json' => Http::response(['unexpected' => 'shape']),
            // Fails twice (connection error, then 503) before succeeding.
            self::CDN.'/cache/42-f1.json' => Http::sequence()
                ->pushFailedConnection()
                ->pushStatus(503)
                ->push(['product_id' => '42'] + $this->fixture('product-358120')),
            self::CDN.'/cache/*' => Http::response('<html>404</html>', 404),
        ]);
    }

    public function test_it_shows_the_full_product_details(): void
    {
        $this->getJson('/api/v1/products/358120')
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'id', 'name', 'description_html', 'image', 'images', 'price', 'currency', 'stock', 'in_stock',
                'sku', 'model', 'type', 'source', 'rating', 'reviews',
                'options' => [['id', 'name', 'type', 'required', 'values' => [['id', 'name', 'image', 'stock', 'price_modifier']]]],
                'dimensions' => ['weight', 'length', 'width', 'height'],
                'seo' => ['title', 'description'],
            ]])
            ->assertJsonPath('data.id', 358120)
            ->assertJsonPath('data.price', '£16.18')
            ->assertJsonPath('data.currency', 'GBP')
            ->assertJsonPath('data.stock', 261)
            ->assertJsonPath('data.in_stock', true)
            ->assertJsonPath('data.sku', '1005003694420248')
            ->assertJsonPath('data.type', 'physical')
            ->assertJsonPath('data.options.0.name', 'Color')
            ->assertJsonPath('data.options.0.required', true)
            ->assertJsonPath('data.options.0.values.0.name', 'USB Charging')
            ->assertJsonPath('data.options.0.values.0.price_modifier', null)
            ->assertJsonPath('data.dimensions.weight', null);
    }

    public function test_the_gallery_is_unique_and_starts_with_the_main_image(): void
    {
        $data = $this->getJson('/api/v1/products/358120')->json('data');

        $this->assertCount(6, $data['images']);
        $this->assertSame($data['image'], $data['images'][0]);
        $this->assertSame($data['images'], array_values(array_unique($data['images'])));
    }

    public function test_the_description_html_is_sanitized(): void
    {
        $html = $this->getJson('/api/v1/products/358120')->json('data.description_html');

        $this->assertStringContainsString('Rechargeable trimmer', $html);
        $this->assertStringContainsString('<img', $html);

        foreach (['<script', 'alert(1)', 'onclick', 'onerror', 'javascript:', 'style='] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $html);
        }
    }

    public function test_it_serves_product_text_in_the_requested_language(): void
    {
        $this->getJson('/api/v1/products/358120?lang=ar')
            ->assertOk()
            ->assertJsonPath('data.id', 358120)
            ->assertJsonPath('data.name', fn (string $name) => str_starts_with($name, 'مشذب'));
    }

    public function test_it_returns_404_for_an_unknown_product(): void
    {
        $this->getJson('/api/v1/products/1')
            ->assertNotFound()
            ->assertExactJson(['error' => [
                'status' => 404,
                'code' => 'product_not_found',
                'message' => 'Product 1 was not found.',
            ]]);
    }

    public function test_it_returns_404_when_the_payload_is_not_a_product(): void
    {
        $this->getJson('/api/v1/products/7')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'product_not_found');
    }

    public function test_it_returns_404_for_a_non_numeric_product(): void
    {
        $this->getJson('/api/v1/products/not-a-number')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');

        Http::assertNothingSent();
    }

    public function test_it_returns_502_when_the_upstream_fails(): void
    {
        $this->getJson('/api/v1/products/500')
            ->assertStatus(502)
            ->assertExactJson(['error' => [
                'status' => 502,
                'code' => 'upstream_unavailable',
                'message' => 'The catalog service is temporarily unavailable. Please try again shortly.',
            ]]);
    }

    public function test_it_retries_transient_upstream_failures(): void
    {
        config(['fatherstock.http.retries' => 2]);

        $this->getJson('/api/v1/products/42')->assertOk()->assertJsonPath('data.id', 42);

        Http::assertSentCount(3);
    }

    public function test_it_returns_502_when_the_upstream_is_unreachable(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection timed out'));

        $this->getJson('/api/v1/products/99')
            ->assertStatus(502)
            ->assertJsonPath('error.code', 'upstream_unavailable');
    }

    public function test_products_are_cached_per_language(): void
    {
        $this->getJson('/api/v1/products/358120')->assertOk();
        $this->getJson('/api/v1/products/358120')->assertOk();
        Http::assertSentCount(1);

        $this->getJson('/api/v1/products/358120?lang=ar')->assertOk();
        Http::assertSentCount(2);
    }

    public function test_unsupported_methods_are_rejected(): void
    {
        $this->postJson('/api/v1/products/358120')
            ->assertStatus(405)
            ->assertJsonPath('error.code', 'method_not_allowed');
    }
}

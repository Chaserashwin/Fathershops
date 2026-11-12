<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CategoryProductsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            self::CDN.'/category/hot-category.json' => Http::response($this->fixture('hot-category')),
            self::CDN.'/category/20.json' => Http::response($this->fixture('category-20')),
            self::CDN.'/category/59.json' => Http::response(['message' => 'Product Fetched', 'data' => ['products' => []]]),
            self::CDN.'/category/500.json' => Http::response('Bad gateway', 502),
            self::CDN.'/category/501.json' => Http::response(['message' => 'Unexpected shape']),
            self::CDN.'/category/*' => Http::response('<html>404</html>', 404),
        ]);
    }

    public function test_it_lists_the_products_of_a_category(): void
    {
        $this->getJson('/api/v1/categories/20/products')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'name', 'image', 'price', 'stock', 'in_stock', 'rating', 'reviews', 'sku']],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'category' => ['id', 'name', 'image', 'parent_id'],
            ])
            ->assertJsonPath('data.0.id', 358120)
            ->assertJsonPath('data.0.price', 'CN¥ 148.69')
            ->assertJsonPath('data.0.stock', 261)
            ->assertJsonPath('data.0.in_stock', true)
            ->assertJsonPath('category.id', 20)
            ->assertJsonPath('category.name', 'Women');
    }

    public function test_it_removes_duplicate_products_and_heavy_fields(): void
    {
        // The fixture holds 6 entries but only 3 distinct products.
        $response = $this->getJson('/api/v1/categories/20/products')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3);

        $this->assertSame([358120, 357802, 357631], $response->json('data.*.id'));
        $this->assertArrayNotHasKey('descriptions', $response->json('data.0'));
    }

    public function test_it_paginates_products(): void
    {
        $this->getJson('/api/v1/categories/20/products?per_page=2&page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 357631)
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('links.next', null);
    }

    public function test_it_serves_product_names_in_the_requested_language(): void
    {
        $english = $this->getJson('/api/v1/categories/20/products')->json('data.0.name');
        $arabic = $this->getJson('/api/v1/categories/20/products?lang=ar')->json('data.0.name');

        $this->assertStringStartsWith('Trimmer for Intimate Areas', $english);
        $this->assertStringStartsWith('مشذب', $arabic);
    }

    public function test_an_existing_but_empty_category_returns_an_empty_list(): void
    {
        $this->getJson('/api/v1/categories/59/products')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('category.name', 'Sportswear');
    }

    public function test_it_returns_404_for_an_unknown_category(): void
    {
        $this->getJson('/api/v1/categories/999999/products')
            ->assertNotFound()
            ->assertExactJson(['error' => [
                'status' => 404,
                'code' => 'category_not_found',
                'message' => 'Category 999999 was not found.',
            ]]);
    }

    public function test_it_returns_404_for_a_non_numeric_category(): void
    {
        $this->getJson('/api/v1/categories/abc/products')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');

        Http::assertNothingSent();
    }

    public function test_it_returns_502_when_the_upstream_fails(): void
    {
        $this->getJson('/api/v1/categories/500/products')
            ->assertStatus(502)
            ->assertJsonPath('error.code', 'upstream_unavailable');
    }

    public function test_it_returns_502_when_the_upstream_payload_is_malformed(): void
    {
        $this->getJson('/api/v1/categories/501/products')
            ->assertStatus(502)
            ->assertJsonPath('error.code', 'upstream_unavailable');
    }

    public function test_it_validates_query_parameters(): void
    {
        $this->getJson('/api/v1/categories/20/products?page=0&per_page=1000&lang=xx')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['page', 'per_page', 'lang']);

        Http::assertNothingSent();
    }

    public function test_products_are_cached_between_requests(): void
    {
        $this->getJson('/api/v1/categories/20/products')->assertOk();
        $this->getJson('/api/v1/categories/20/products?page=2&per_page=1')->assertOk();

        // One call for the product list, one for the category names.
        Http::assertSentCount(2);
    }

    public function test_missing_categories_are_cached_but_outages_are_not(): void
    {
        $this->getJson('/api/v1/categories/999999/products')->assertNotFound();
        $this->getJson('/api/v1/categories/999999/products')->assertNotFound();
        Http::assertSentCount(1);

        $this->getJson('/api/v1/categories/500/products')->assertStatus(502);
        $this->getJson('/api/v1/categories/500/products')->assertStatus(502);
        Http::assertSentCount(3);
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CategoriesTest extends TestCase
{
    private const URL = self::CDN.'/category/hot-category.json';

    public function test_it_lists_categories_with_their_images(): void
    {
        Http::fake([self::URL => Http::response($this->fixture('hot-category'))]);

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.0', [
                'id' => 20,
                'name' => 'Women',
                'image' => 'https://sgp1.digitaloceanspaces.com/hc999/image/cache/ceddropshipper/no_image-250x250.png',
                'parent_id' => null,
            ])
            ->assertJsonPath('data.3.name', 'Sportswear')
            ->assertJsonPath('data.3.parent_id', 20);
    }

    public function test_it_skips_malformed_and_duplicate_entries(): void
    {
        Http::fake([self::URL => Http::response(['status' => 200, 'response' => [
            ['category_id' => '20', 'name' => 'Women &amp; Girls', 'image' => 'not-a-url', 'parent_id' => '0'],
            ['category_id' => '20', 'name' => 'Duplicate', 'image' => null, 'parent_id' => '0'],
            ['category_id' => '', 'name' => 'No id'],
            ['category_id' => '31', 'name' => ''],
            'garbage',
        ]])]);

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertExactJson(['data' => [
                ['id' => 20, 'name' => 'Women & Girls', 'image' => null, 'parent_id' => null],
            ]]);
    }

    public function test_it_returns_404_when_there_are_no_categories(): void
    {
        Http::fake([self::URL => Http::response(['status' => 200, 'response' => []])]);

        $this->getJson('/api/v1/categories')
            ->assertNotFound()
            ->assertExactJson(['error' => [
                'status' => 404,
                'code' => 'categories_not_found',
                'message' => 'No categories are available right now.',
            ]]);
    }

    public function test_it_returns_404_when_the_upstream_file_is_missing(): void
    {
        Http::fake([self::URL => Http::response('<html>404</html>', 404)]);

        $this->getJson('/api/v1/categories')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'categories_not_found');
    }

    public function test_it_returns_404_when_the_upstream_request_fails(): void
    {
        Http::fake([self::URL => Http::response('Server error', 500)]);

        $this->getJson('/api/v1/categories')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'categories_not_found');
    }

    public function test_it_returns_404_when_the_upstream_is_unreachable(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection timed out'));

        $this->getJson('/api/v1/categories')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'categories_not_found');
    }

    public function test_it_returns_404_when_the_upstream_body_is_not_json(): void
    {
        Http::fake([self::URL => Http::response('<html>oops</html>')]);

        $this->getJson('/api/v1/categories')->assertNotFound();
    }

    public function test_categories_are_cached_between_requests(): void
    {
        Http::fake([self::URL => Http::response($this->fixture('hot-category'))]);

        $this->getJson('/api/v1/categories')->assertOk();
        $this->getJson('/api/v1/categories')->assertOk();

        Http::assertSentCount(1);
    }

    public function test_successful_responses_can_be_revalidated_with_an_etag(): void
    {
        Http::fake([self::URL => Http::response($this->fixture('hot-category'))]);

        $response = $this->getJson('/api/v1/categories')->assertOk();

        $this->assertStringContainsString('max-age=300', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('public', $response->headers->get('Cache-Control'));
        $this->assertNotEmpty($etag = $response->headers->get('ETag'));

        $this->getJson('/api/v1/categories', ['If-None-Match' => $etag])->assertStatus(304);
    }
}

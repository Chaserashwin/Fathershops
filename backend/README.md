# FatherShops Catalog API (Laravel)

Section **A** of the FatherShops full-stack technical task: a Laravel 12 API that fetches
categories, category products and product details from the FatherStock CDN, cleans the data up,
caches it and serves it to the Next.js storefront (section B, separate repository).

No database is used - the catalog lives on the CDN and is cached on disk.

## Endpoints

| Method | Endpoint | Description | Upstream source |
| --- | --- | --- | --- |
| `GET` | `/api/v1/categories` | Categories with their images | `category/hot-category.json` |
| `GET` | `/api/v1/categories/{id}/products` | Products of one category (paginated) | `category/{id}.json` |
| `GET` | `/api/v1/products/{id}` | Full details of one product | `cache/{id}-f1.json` |

Query parameters:

- `lang` - `en` (default) or `ar`; upstream ships every text in both languages.
- `page`, `per_page` (category products only) - defaults `1` and `12`, `per_page` max `48`.

### Error handling

Every error uses one envelope, so the frontend can handle them uniformly:

```json
{ "error": { "status": 404, "code": "product_not_found", "message": "Product 1 was not found." } }
```

| Situation | Status | `code` |
| --- | --- | --- |
| Categories request fails **or** no categories exist (as required by the brief) | `404` | `categories_not_found` |
| Category does not exist | `404` | `category_not_found` |
| Product does not exist | `404` | `product_not_found` |
| Non-numeric id / unknown route | `404` | `not_found` |
| CDN unreachable, 5xx or malformed payload (category products, product) | `502` | `upstream_unavailable` |
| Invalid query parameter | `422` | Laravel validation format |
| Rate limit exceeded | `429` | `too_many_requests` |

A category that exists but has no products returns `200` with an empty list - it is not an error.

### Example

```bash
curl http://localhost:8000/api/v1/categories/20/products?per_page=2
```

```json
{
  "data": [
    {
      "id": 358120,
      "name": "Trimmer for Intimate Areas ...",
      "image": "https://sgp1.digitaloceanspaces.com/.../product_image_ae_1005003694420248-250x250.jpg",
      "price": "CN¥ 148.69",
      "stock": 261,
      "in_stock": true,
      "rating": 0,
      "reviews": 0,
      "sku": "1005003694420248"
    }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "last_page": 5, "per_page": 2, "total": 10 },
  "category": { "id": 20, "name": "Women", "image": "https://...", "parent_id": null }
}
```

## Getting started

Requirements: PHP 8.2+ and Composer.

```bash
composer setup
```

```bash
php artisan serve
```

The API is now on <http://localhost:8000>. `composer setup` installs dependencies, creates `.env`
from `.env.example` and generates the app key. Configuration (all optional) is documented in
`.env.example`: upstream URL, cache TTLs, HTTP timeout/retries, rate limit and allowed CORS origins.

```bash
composer test
```

```bash
composer lint
```

## Deployment (Render)

The repository ships a `Dockerfile` (PHP 8.3 + Apache) and a `render.yaml` blueprint.

1. On Render create a **Web Service** from this repository; the Docker runtime is detected automatically
   (or use **New > Blueprint** to apply `render.yaml`).
2. Set the environment variables:

| Variable | Value |
| --- | --- |
| `APP_KEY` | output of `php artisan key:generate --show` |
| `APP_URL` | the service URL, e.g. `https://your-api.onrender.com` |
| `CORS_ALLOWED_ORIGINS` | the storefront URL, e.g. `https://your-app.vercel.app` |
| `APP_ENV` / `APP_DEBUG` | `production` / `false` |
| `LOG_CHANNEL` | `stderr` |
| `CACHE_STORE` / `SESSION_DRIVER` | `file` / `file` |

3. Health check path: `/up`.

The container listens on the port Render provides through `$PORT`. On the free plan the service
sleeps when idle, so the first request after a pause takes longer.

## How it is built

```
routes/api.php                         versioned routes, throttling, HTTP cache headers
app/Http/Controllers/Api/V1/           thin controllers (one per resource)
app/Http/Requests/                     validation of lang / page / per_page
app/Http/Resources/                    the public JSON shape
app/Services/FatherStock/
  FatherStockClient.php                HTTP only: timeouts, retries, outcome classification
  CatalogService.php                   caching + turning payloads into DTOs
app/Data/                              readonly DTOs that normalise upstream payloads
app/Support/Text.php                   entity decoding + HTML sanitising
app/Exceptions/                        typed errors that render themselves as JSON
config/fatherstock.php                 every tunable in one place
```

### Caching

- **Application cache** - each upstream lookup is normalised and cached (`FATHERSTOCK_CACHE_TTL`,
  10 minutes by default) through Laravel's cache, so the CDN is hit at most once per resource per
  TTL. It uses the `file` store out of the box and works unchanged with Redis/Memcached
  (`CACHE_STORE`).
- **Negative caching** - "not found" answers are cached for a short time (60 s) so repeated
  requests for a missing id do not hammer the CDN. Outages are never cached, so the next request
  retries immediately.
- **HTTP caching** - successful responses carry `Cache-Control: public, max-age=300` and an `ETag`;
  clients revalidate with `If-None-Match` and get a body-less `304`.
- The cached value is the small normalised DTO, not the raw payload (a category file is ~350 KB,
  mostly description HTML that listings do not need).

### Data processing

The upstream data is inconsistent, so the API normalises it before exposing it:

- **Duplicates removed** - a category file lists the same product several times
  (`category/20.json` has 32 entries but 10 distinct products).
- **Slim listings** - list items drop the ~8 KB description HTML per product.
- **Types** - ids and stock become integers, ratings floats, flags booleans.
- **Text** - HTML entities are decoded; names are picked in the requested language with a fallback.
- **Description HTML is sanitised** with an allow-list (`symfony/html-sanitizer`): scripts, event
  handlers, inline styles and non-HTTPS media are dropped, links get `rel="noopener noreferrer nofollow"`.
- **Gallery** - image URLs are validated and de-duplicated, main image first.
- **Malformed entries** (no id / no name) are skipped instead of breaking the response.

### Resilience

- Connection and total timeouts on every upstream call, with retries on connection errors and 5xx.
- Rate limiting per client IP (`FATHERSTOCK_RATE_LIMIT_PER_MINUTE`).
- CORS restricted to `GET` from the configured origins.
- Upstream failures are logged; expected 404s are not.

### Tests

`composer test` runs 38 tests (feature + unit) against faked HTTP responses - no test can reach the
real CDN (`Http::preventStrayRequests()`). They cover the happy paths, every error case above,
de-duplication, pagination, language selection, sanitising, retries, caching and ETag revalidation.

## Notes

- Some products that appear in a category listing have no detail file on the CDN (for example
  `357631`). The API correctly answers `404` for them and the storefront shows a "Product not found" page.
- The categories endpoint answers `404` on an upstream failure because the brief asks for exactly
  that; the other two endpoints distinguish "missing" (`404`) from "upstream is down" (`502`).

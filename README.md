# FatherShops - Full Stack Developer technical task

Two independent projects, each meant to be pushed as its own GitHub repository:

| Folder | Section | Stack | Details |
| --- | --- | --- | --- |
| [`backend/`](backend/README.md) | A - PHP skills | Laravel 12, PHP 8.2 | API endpoints for categories, category products and product details, with caching |
| [`frontend/`](frontend/README.md) | B - Next.js skills | Next.js 16, React 19, TypeScript, Tailwind CSS 4 | Storefront that consumes the Laravel API |

## Run both locally

Terminal 1 - API on <http://localhost:8000>:

```bash
cd backend && composer setup && php artisan serve
```

Terminal 2 - storefront on <http://localhost:3000>:

```bash
cd frontend && npm install && cp .env.example .env.local && npm run dev
```

## Requirement checklist

**A. Laravel API** (no database)

- [x] Categories with their images - `GET /api/v1/categories`; `404` when the request fails or no categories exist
- [x] Category products - `GET /api/v1/categories/{id}/products`; `404` when the category is not found
- [x] Product details - `GET /api/v1/products/{id}`; `404` when the product is not found
- [x] Caching (application cache, negative caching, HTTP `Cache-Control` + `ETag`)
- [x] Data processing (de-duplication, normalisation, HTML sanitising, pagination)
- [x] Coding standards (Laravel Pint, service/DTO/resource layers, 38 automated tests)

**B. Next.js frontend**

- [x] Categories displayed with images
- [x] Category products displayed for the selected category
- [x] Full product details when a product is selected
- [x] Error handling when data is unavailable (404 pages, API-down state with retry, empty states, image fallbacks)

# FatherShops Storefront (Next.js)

Section **B** of the FatherShops full-stack technical task: a Next.js 16 (App Router, React 19,
TypeScript, Tailwind CSS 4) storefront that consumes the Laravel catalog API from section A
(separate repository).

## What it does

| Route | Page | API endpoint used |
| --- | --- | --- |
| `/` | Categories with their images | `GET /api/v1/categories` |
| `/categories/[id]` | Products of the selected category, paginated (`?page=`) | `GET /api/v1/categories/{id}/products` |
| `/products/[id]` | Full product details: gallery, price, stock, options, specifications, description | `GET /api/v1/products/{id}` |

### Error handling

| Situation | What the user sees |
| --- | --- |
| Category / product does not exist (API `404`), or the id in the URL is not a number | Dedicated "Category not found" / "Product not found" page |
| No categories available (API `404`) | "No categories available" message with a retry button |
| API down, timing out, rate limiting or returning an unexpected payload | "We couldn't load ..." message with **Try again** (re-fetches without a full reload) |
| Category exists but has no products / page number beyond the last page | Empty state with a way back |
| Product or category image fails to load | Neutral placeholder instead of a broken image |
| Broken images inside a product description | Removed |
| Unknown URL | 404 page |
| Unexpected exception | Error boundary (`app/error.tsx`) with retry |

While data is loading, skeleton placeholders are shown.

## Getting started

Requirements: Node.js 20.9+ and the Laravel API running (see the backend repository).

```bash
npm install
```

```bash
cp .env.example .env.local
```

```bash
npm run dev
```

Open <http://localhost:3000>. `API_URL` in `.env.local` points to the Laravel API
(default `http://localhost:8000/api/v1`).

```bash
npm run lint
```

```bash
npm run build
```

## Deployment (Vercel)

1. Import this repository in Vercel; the Next.js preset needs no extra configuration.
2. Add the environment variable `API_URL` with the deployed API base URL, for example
   `https://your-api.onrender.com/api/v1`, then deploy.
3. Add the Vercel URL to `CORS_ALLOWED_ORIGINS` on the API.

## How it is built

```
src/lib/api.ts                 typed, server-only API client
src/app/page.tsx               categories
src/app/categories/[id]/       category products (+ not-found)
src/app/products/[id]/         product details (+ not-found)
src/app/error.tsx              error boundary for unexpected exceptions
src/app/not-found.tsx          404 page
src/components/                cards, gallery, pagination, breadcrumbs, skeletons, state messages
```

- **Server Components fetch the data.** The browser never talks to the API directly, so the API URL
  stays on the server and no client-side data-fetching library is needed. Only the interactive bits
  (image gallery, retry button, image fallbacks) are Client Components.
- **Streaming with Suspense.** Each page prerenders a static shell (header, headings, skeletons)
  and streams the API-driven content into it (Partial Prerendering / Cache Components).
- **Expected failures are values, not exceptions.** `src/lib/api.ts` returns
  `{ ok: true, data } | { ok: false, status, code, message }`, so every page decides explicitly what
  to show for a 404 or an outage; only real bugs reach the error boundary.
- **One cache.** Catalog data is cached by the Laravel API (application cache + ETags). The frontend
  does not add a second cache on top, so a 404 or an outage is never served stale from two layers.
  Within one render, `React.cache` de-duplicates calls shared by a page and its metadata.
- **Images** use `next/image` (responsive sizes, lazy loading, optimisation) wrapped in a small
  component that falls back to a placeholder.
- **Product descriptions** are third-party HTML. They are sanitised by the API with an allow-list
  before they reach this app.
- **Accessibility & SEO**: semantic landmarks, breadcrumbs, skip link, focus styles, alt texts,
  per-page titles and descriptions.

## Notes

- Some products listed in a category have no detail file upstream (for example `357631`); opening
  them shows the "Product not found" page - this is the required 404 behaviour, not a bug.
- Many images inside product descriptions are hosted by a third party that often rejects requests;
  those images are hidden when they fail.

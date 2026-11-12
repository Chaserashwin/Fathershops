import "server-only";

import { cache } from "react";

/**
 * Typed client for the Laravel catalog API (section A of the task).
 *
 * Every function resolves to an `ApiResult` instead of throwing, so pages
 * handle "not found" and "service unavailable" as ordinary values and only
 * genuine bugs reach the error boundary.
 *
 * Responses are not cached a second time here: the Laravel API already
 * caches the upstream catalog (and sends ETags), which keeps a single source
 * of truth for freshness and guarantees a 404 or outage is never served
 * stale from two different layers.
 */

const API_URL = (process.env.API_URL ?? "http://localhost:8000/api/v1").replace(/\/+$/, "");
// Generous enough for an API host that has to wake up from idle.
const REQUEST_TIMEOUT_MS = Number(process.env.API_TIMEOUT_MS) || 20_000;

export type Category = {
  id: number;
  name: string;
  image: string | null;
  parent_id: number | null;
};

export type ProductSummary = {
  id: number;
  name: string;
  image: string | null;
  price: string | null;
  stock: number;
  in_stock: boolean;
  rating: number;
  reviews: number;
  sku: string | null;
};

export type ProductOption = {
  id: number;
  name: string;
  type: string;
  required: boolean;
  values: {
    id: number;
    name: string;
    image: string | null;
    stock: number;
    price_modifier: string | null;
  }[];
};

export type Product = {
  id: number;
  name: string;
  description_html: string;
  image: string | null;
  images: string[];
  price: string | null;
  currency: string | null;
  stock: number;
  in_stock: boolean;
  sku: string | null;
  model: string | null;
  type: string | null;
  source: string | null;
  rating: number;
  reviews: number;
  options: ProductOption[];
  dimensions: Record<"weight" | "length" | "width" | "height", string | null>;
  seo: { title: string | null; description: string | null };
};

export type CategoryProducts = {
  products: ProductSummary[];
  category: { id: number; name: string | null; image: string | null; parent_id: number | null };
  pagination: { currentPage: number; lastPage: number; perPage: number; total: number };
};

export type ApiFailure = {
  ok: false;
  /** HTTP status from the API, or 503 when the API could not be reached. */
  status: number;
  code: string;
  message: string;
};

export type ApiResult<T> = { ok: true; data: T } | ApiFailure;

async function request<T>(path: string, pick: (body: unknown) => T): Promise<ApiResult<T>> {
  let response: Response;

  try {
    response = await fetch(`${API_URL}${path}`, {
      headers: { Accept: "application/json" },
      signal: AbortSignal.timeout(REQUEST_TIMEOUT_MS),
    });
  } catch {
    return {
      ok: false,
      status: 503,
      code: "api_unreachable",
      message: "We couldn't reach the catalog service. Please try again in a moment.",
    };
  }

  const body: unknown = await response.json().catch(() => null);

  if (!response.ok) {
    const error = (body as { error?: { code?: string; message?: string } } | null)?.error;

    return {
      ok: false,
      status: response.status,
      code: error?.code ?? "unknown_error",
      message: error?.message ?? "Something went wrong while loading this page.",
    };
  }

  try {
    return { ok: true, data: pick(body) };
  } catch {
    return {
      ok: false,
      status: 502,
      code: "invalid_response",
      message: "The catalog service returned an unexpected response.",
    };
  }
}

/** Featured categories. Wrapped in `cache` so one render never asks twice. */
export const getCategories = cache(
  (): Promise<ApiResult<Category[]>> =>
    request("/categories", (body) => {
      const { data } = body as { data: Category[] };

      if (!Array.isArray(data)) throw new TypeError("Expected a category list");

      return data;
    }),
);

export const getCategoryProducts = cache(
  (categoryId: number, page = 1, perPage = 8): Promise<ApiResult<CategoryProducts>> =>
    request(`/categories/${categoryId}/products?page=${page}&per_page=${perPage}`, (body) => {
      const { data, meta, category } = body as {
        data: ProductSummary[];
        meta: { current_page: number; last_page: number; per_page: number; total: number };
        category: CategoryProducts["category"];
      };

      if (!Array.isArray(data)) throw new TypeError("Expected a product list");

      return {
        products: data,
        category,
        pagination: {
          currentPage: meta.current_page,
          lastPage: meta.last_page,
          perPage: meta.per_page,
          total: meta.total,
        },
      };
    }),
);

/** Shared by the product page and its `generateMetadata` (deduplicated). */
export const getProduct = cache(
  (productId: number): Promise<ApiResult<Product>> =>
    request(`/products/${productId}`, (body) => {
      const { data } = body as { data: Product };

      if (typeof data?.id !== "number") throw new TypeError("Expected a product");

      return data;
    }),
);

/** Parses a route or query value into a positive integer id, or `null`. */
export function parseId(value: string | string[] | undefined): number | null {
  if (typeof value !== "string" || !/^\d{1,15}$/.test(value)) return null;

  const id = Number(value);

  return id > 0 ? id : null;
}

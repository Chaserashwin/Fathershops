import Link from "next/link";

import type { Category, ProductSummary } from "@/lib/api";

import { SafeImage } from "./safe-image";

export function CategoryCard({ category, parentName }: { category: Category; parentName?: string }) {
  return (
    <Link
      href={`/categories/${category.id}`}
      className="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
    >
      <div className="relative aspect-[4/3] bg-slate-50">
        <SafeImage
          src={category.image}
          alt={category.name}
          sizes="(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw"
          className="p-6 transition duration-300 group-hover:scale-105"
        />
      </div>
      <div className="flex items-center justify-between gap-3 border-t border-slate-100 px-5 py-4">
        <div className="min-w-0">
          <h2 className="truncate text-base font-semibold text-slate-900">{category.name}</h2>
          <p className="text-xs text-slate-500">{parentName ? `In ${parentName}` : "Shop the collection"}</p>
        </div>
        <span
          aria-hidden
          className="grid size-8 shrink-0 place-items-center rounded-full bg-indigo-50 text-indigo-600 transition group-hover:bg-indigo-600 group-hover:text-white"
        >
          →
        </span>
      </div>
    </Link>
  );
}

export function ProductCard({ product, categoryId }: { product: ProductSummary; categoryId: number }) {
  return (
    <Link
      // The category travels along so the product page can link back to it.
      href={`/products/${product.id}?category=${categoryId}`}
      className="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
    >
      <div className="relative aspect-square bg-white">
        <SafeImage
          src={product.image}
          alt={product.name}
          sizes="(min-width: 1024px) 25vw, (min-width: 640px) 33vw, 50vw"
          className="p-3 transition duration-300 group-hover:scale-105"
        />
        {!product.in_stock && (
          <span className="absolute top-3 left-3 rounded-full bg-slate-900/80 px-2.5 py-1 text-xs font-medium text-white">
            Out of stock
          </span>
        )}
      </div>
      <div className="flex flex-1 flex-col gap-3 border-t border-slate-100 p-4">
        <h3 className="line-clamp-2 text-sm leading-5 font-medium text-slate-800 group-hover:text-indigo-700">
          {product.name}
        </h3>
        <div className="mt-auto flex items-end justify-between gap-2">
          <p className="text-base font-semibold text-slate-900">{product.price ?? "Price on request"}</p>
          {product.in_stock && <p className="text-xs text-emerald-700">{product.stock} in stock</p>}
        </div>
      </div>
    </Link>
  );
}

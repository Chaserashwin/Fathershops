import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { Suspense } from "react";

import { Breadcrumbs } from "@/components/breadcrumbs";
import { ProductCard } from "@/components/cards";
import { Pagination } from "@/components/pagination";
import { CategoryPageSkeleton } from "@/components/skeletons";
import { ApiErrorState, HomeLink, StateMessage } from "@/components/state-message";
import { getCategoryProducts, parseId } from "@/lib/api";

type Props = PageProps<"/categories/[id]">;

async function load({ params, searchParams }: Props) {
  const [{ id }, { page }] = await Promise.all([params, searchParams]);
  const categoryId = parseId(id);

  if (categoryId === null) notFound();

  const pageNumber = parseId(page) ?? 1;

  return { categoryId, pageNumber, result: await getCategoryProducts(categoryId, pageNumber) };
}

export async function generateMetadata(props: Props): Promise<Metadata> {
  const { categoryId, result } = await load(props);

  if (!result.ok) return { title: result.status === 404 ? "Category not found" : "Category" };

  return {
    title: result.data.category.name ?? `Category ${categoryId}`,
    description: `Browse ${result.data.pagination.total} products in ${result.data.category.name ?? "this category"}.`,
  };
}

export default function CategoryPage(props: Props) {
  return (
    <Suspense fallback={<CategoryPageSkeleton />}>
      <CategoryProducts {...props} />
    </Suspense>
  );
}

async function CategoryProducts(props: Props) {
  const { categoryId, pageNumber, result } = await load(props);

  if (!result.ok) {
    if (result.status === 404) notFound();

    return <ApiErrorState failure={result} what="this category" />;
  }

  const { products, category, pagination } = result.data;
  const name = category.name ?? `Category ${categoryId}`;
  const basePath = `/categories/${categoryId}`;

  return (
    <>
      <Breadcrumbs items={[{ label: "Categories", href: "/" }, { label: name }]} />

      <header className="mb-8">
        <h1 className="text-3xl font-bold tracking-tight text-slate-900">{name}</h1>
        <p className="mt-2 text-sm text-slate-600">
          {pagination.total === 1 ? "1 product" : `${pagination.total} products`}
          {pagination.lastPage > 1 && ` | Page ${pagination.currentPage} of ${pagination.lastPage}`}
        </p>
      </header>

      {products.length === 0 ? (
        <StateMessage
          title={pagination.total === 0 ? "No products here yet" : "This page is empty"}
          description={
            pagination.total === 0
              ? `There are no products in ${name} at the moment.`
              : `${name} only has ${pagination.lastPage} page${pagination.lastPage === 1 ? "" : "s"} of products.`
          }
        >
          {pagination.total > 0 && (
            <Link
              href={basePath}
              className="rounded-full bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500"
            >
              Go to first page
            </Link>
          )}
          <HomeLink />
        </StateMessage>
      ) : (
        <>
          <ul className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 lg:gap-6">
            {products.map((product) => (
              <li key={product.id}>
                <ProductCard product={product} categoryId={categoryId} />
              </li>
            ))}
          </ul>
          <Pagination basePath={basePath} currentPage={pageNumber} lastPage={pagination.lastPage} />
        </>
      )}
    </>
  );
}

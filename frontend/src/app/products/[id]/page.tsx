import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { Suspense } from "react";

import { Breadcrumbs, type Crumb } from "@/components/breadcrumbs";
import { ProductGallery } from "@/components/product-gallery";
import { RichText } from "@/components/rich-text";
import { SafeImage } from "@/components/safe-image";
import { ProductPageSkeleton } from "@/components/skeletons";
import { ApiErrorState } from "@/components/state-message";
import { getCategories, getProduct, parseId, type Product } from "@/lib/api";

type Props = PageProps<"/products/[id]">;

async function load({ params }: Props) {
  const productId = parseId((await params).id);

  if (productId === null) notFound();

  return getProduct(productId);
}

export async function generateMetadata(props: Props): Promise<Metadata> {
  const result = await load(props);

  if (!result.ok) return { title: result.status === 404 ? "Product not found" : "Product" };

  return {
    title: result.data.seo.title ?? result.data.name,
    description: result.data.seo.description ?? undefined,
  };
}

export default function ProductPage(props: Props) {
  return (
    <Suspense fallback={<ProductPageSkeleton />}>
      <ProductDetails {...props} />
    </Suspense>
  );
}

async function ProductDetails(props: Props) {
  const [result, crumbs] = await Promise.all([load(props), categoryCrumb(props)]);

  if (!result.ok) {
    if (result.status === 404) notFound();

    return <ApiErrorState failure={result} what="this product" />;
  }

  const product = result.data;
  const specs = specifications(product);

  return (
    <article>
      <Breadcrumbs items={[{ label: "Categories", href: "/" }, ...crumbs, { label: product.name }]} />

      <div className="grid gap-8 lg:grid-cols-2 lg:gap-12">
        <ProductGallery images={product.images} name={product.name} />

        <div>
          <h1 className="text-2xl leading-tight font-bold tracking-tight text-slate-900 sm:text-3xl">
            {product.name}
          </h1>

          <div className="mt-4 flex flex-wrap items-center gap-3">
            <p className="text-3xl font-bold text-slate-900">{product.price ?? "Price on request"}</p>
            <span
              className={`rounded-full px-3 py-1 text-xs font-semibold ${
                product.in_stock ? "bg-emerald-50 text-emerald-700" : "bg-slate-100 text-slate-600"
              }`}
            >
              {product.in_stock ? `In stock | ${product.stock} available` : "Out of stock"}
            </span>
          </div>

          <p className="mt-3 text-sm text-slate-600">
            {product.reviews > 0
              ? `Rated ${product.rating.toFixed(1)} out of 5 | ${product.reviews} review${product.reviews === 1 ? "" : "s"}`
              : "No reviews yet"}
          </p>

          {product.options.map((option) => (
            <section key={option.id} className="mt-6" aria-label={option.name}>
              <h2 className="text-sm font-semibold text-slate-900">
                {option.name}
                {option.required && <span className="ml-1.5 font-normal text-slate-500">(required)</span>}
              </h2>
              <ul className="mt-3 flex flex-wrap gap-2">
                {option.values.map((value) => (
                  <li
                    key={value.id}
                    className="flex items-center gap-2 rounded-full border border-slate-200 bg-white py-1 pr-3.5 pl-1 text-sm text-slate-700"
                  >
                    <span className="relative size-8 shrink-0 overflow-hidden rounded-full bg-slate-100">
                      <SafeImage src={value.image} alt="" sizes="32px" />
                    </span>
                    {value.name}
                    {value.price_modifier && <span className="text-xs text-slate-500">{value.price_modifier}</span>}
                  </li>
                ))}
              </ul>
            </section>
          ))}

          {specs.length > 0 && (
            <section className="mt-8" aria-labelledby="specifications">
              <h2 id="specifications" className="text-sm font-semibold text-slate-900">
                Specifications
              </h2>
              <dl className="mt-3 divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 bg-white text-sm">
                {specs.map(([label, value]) => (
                  <div key={label} className="grid grid-cols-[8rem_1fr] gap-4 px-4 py-2.5">
                    <dt className="text-slate-500">{label}</dt>
                    <dd className="font-medium break-words text-slate-800">{value}</dd>
                  </div>
                ))}
              </dl>
            </section>
          )}
        </div>
      </div>

      <section className="mt-12 rounded-2xl border border-slate-200 bg-white p-5 sm:p-8" aria-labelledby="description">
        <h2 id="description" className="mb-4 text-xl font-semibold text-slate-900">
          Description
        </h2>
        {product.description_html ? (
          <RichText html={product.description_html} />
        ) : (
          <p className="text-sm text-slate-500">No description has been provided for this product.</p>
        )}
      </section>
    </article>
  );
}

/**
 * Product cards link here with `?category=<id>`; when present (and valid) the
 * breadcrumb leads back to that category. It is a nicety, so any failure
 * simply drops the crumb.
 */
async function categoryCrumb({ searchParams }: Props): Promise<Crumb[]> {
  const categoryId = parseId((await searchParams).category);

  if (categoryId === null) return [];

  const categories = await getCategories();
  const category = categories.ok ? categories.data.find(({ id }) => id === categoryId) : undefined;

  return category ? [{ label: category.name, href: `/categories/${category.id}` }] : [];
}

function specifications(product: Product): [label: string, value: string][] {
  const rows: [string, string | null][] = [
    ["SKU", product.sku],
    ["Model", product.model !== product.sku ? product.model : null],
    ["Type", product.type],
    ["Source", product.source],
    ["Currency", product.currency],
    ["Weight", product.dimensions.weight],
    ["Length", product.dimensions.length],
    ["Width", product.dimensions.width],
    ["Height", product.dimensions.height],
  ];

  return rows.filter((row): row is [string, string] => Boolean(row[1]));
}

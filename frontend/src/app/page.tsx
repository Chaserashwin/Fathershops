import { Suspense } from "react";

import { CategoryCard } from "@/components/cards";
import { CardGridSkeleton } from "@/components/skeletons";
import { ApiErrorState, StateMessage } from "@/components/state-message";
import { RetryButton } from "@/components/retry-button";
import { getCategories } from "@/lib/api";

export default function HomePage() {
  return (
    <>
      {/* Static shell: prerendered and shown instantly. */}
      <section className="mb-10">
        <p className="text-sm font-semibold tracking-wide text-indigo-600 uppercase">Shop by category</p>
        <h1 className="mt-2 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
          Find what you&apos;re looking for
        </h1>
        <p className="mt-3 max-w-2xl text-slate-600">
          Pick a category to explore its products, then open any product for the full details.
        </p>
      </section>

      {/* Categories stream in from the Laravel API. */}
      <Suspense fallback={<CardGridSkeleton count={4} square={false} />}>
        <CategoryGrid />
      </Suspense>
    </>
  );
}

async function CategoryGrid() {
  const result = await getCategories();

  if (!result.ok) {
    // The API answers 404 when there are no categories (or it cannot get them).
    return result.status === 404 ? (
      <StateMessage
        title="No categories available"
        description="There are no categories to show right now. Please check back in a little while."
      >
        <RetryButton />
      </StateMessage>
    ) : (
      <ApiErrorState failure={result} what="the categories" homeLink={false} />
    );
  }

  const categories = result.data;
  const names = new Map(categories.map((category) => [category.id, category.name]));

  return (
    <ul className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:gap-6">
      {categories.map((category) => (
        <li key={category.id}>
          <CategoryCard
            category={category}
            parentName={category.parent_id ? names.get(category.parent_id) : undefined}
          />
        </li>
      ))}
    </ul>
  );
}

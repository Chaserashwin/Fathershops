/** Placeholders streamed in the static shell while API data loads. */

function Block({ className }: { className: string }) {
  return <div className={`animate-pulse rounded-lg bg-slate-200/80 ${className}`} />;
}

export function CardGridSkeleton({ count = 8, square = true }: { count?: number; square?: boolean }) {
  return (
    <div role="status" aria-label="Loading" className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 lg:gap-6">
      {Array.from({ length: count }, (_, index) => (
        <div key={index} className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
          <Block className={`rounded-none ${square ? "aspect-square" : "aspect-[4/3]"}`} />
          <div className="space-y-2 p-4">
            <Block className="h-4 w-4/5" />
            <Block className="h-4 w-2/5" />
          </div>
        </div>
      ))}
    </div>
  );
}

export function CategoryPageSkeleton() {
  return (
    <div>
      <Block className="mb-6 h-4 w-40" />
      <Block className="mb-2 h-9 w-56" />
      <Block className="mb-8 h-4 w-32" />
      <CardGridSkeleton />
    </div>
  );
}

export function ProductPageSkeleton() {
  return (
    <div role="status" aria-label="Loading product">
      <Block className="mb-6 h-4 w-64" />
      <div className="grid gap-8 lg:grid-cols-2 lg:gap-12">
        <Block className="aspect-square rounded-2xl" />
        <div className="space-y-4">
          <Block className="h-8 w-full" />
          <Block className="h-8 w-3/4" />
          <Block className="h-10 w-32" />
          <Block className="h-24 w-full" />
          <Block className="h-40 w-full" />
        </div>
      </div>
    </div>
  );
}

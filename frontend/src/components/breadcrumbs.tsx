import Link from "next/link";

export type Crumb = { label: string; href?: string };

export function Breadcrumbs({ items }: { items: Crumb[] }) {
  return (
    <nav aria-label="Breadcrumb" className="mb-6 text-sm text-slate-500">
      <ol className="flex flex-wrap items-center gap-x-2 gap-y-1">
        {items.map((item, index) => {
          const isLast = index === items.length - 1;

          return (
            <li key={`${item.label}-${index}`} className="flex min-w-0 items-center gap-2">
              {item.href && !isLast ? (
                <Link href={item.href} className="transition hover:text-indigo-600">
                  {item.label}
                </Link>
              ) : (
                <span aria-current={isLast ? "page" : undefined} className="max-w-[60vw] truncate font-medium text-slate-700 sm:max-w-md">
                  {item.label}
                </span>
              )}
              {!isLast && <span aria-hidden className="text-slate-300">/</span>}
            </li>
          );
        })}
      </ol>
    </nav>
  );
}

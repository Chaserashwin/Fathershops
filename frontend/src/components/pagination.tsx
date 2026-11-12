import Link from "next/link";

type PaginationProps = {
  basePath: string;
  currentPage: number;
  lastPage: number;
};

export function Pagination({ basePath, currentPage, lastPage }: PaginationProps) {
  if (lastPage <= 1) return null;

  const href = (page: number) => (page === 1 ? basePath : `${basePath}?page=${page}`);
  const base = "grid h-10 min-w-10 place-items-center rounded-full px-3 text-sm font-medium transition";

  return (
    <nav aria-label="Pagination" className="mt-10 flex items-center justify-center gap-2">
      {currentPage > 1 ? (
        <Link href={href(currentPage - 1)} rel="prev" className={`${base} border border-slate-300 text-slate-700 hover:bg-slate-50`}>
          ← Previous
        </Link>
      ) : (
        <span aria-disabled className={`${base} border border-slate-200 text-slate-300`}>
          ← Previous
        </span>
      )}

      {Array.from({ length: lastPage }, (_, index) => index + 1).map((page) =>
        page === currentPage ? (
          <span key={page} aria-current="page" className={`${base} bg-indigo-600 text-white`}>
            {page}
          </span>
        ) : (
          <Link key={page} href={href(page)} aria-label={`Page ${page}`} className={`${base} text-slate-700 hover:bg-slate-100`}>
            {page}
          </Link>
        ),
      )}

      {currentPage < lastPage ? (
        <Link href={href(currentPage + 1)} rel="next" className={`${base} border border-slate-300 text-slate-700 hover:bg-slate-50`}>
          Next →
        </Link>
      ) : (
        <span aria-disabled className={`${base} border border-slate-200 text-slate-300`}>
          Next →
        </span>
      )}
    </nav>
  );
}

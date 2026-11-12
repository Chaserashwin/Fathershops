"use client";

import { useRouter } from "next/navigation";
import { useTransition } from "react";

/**
 * Re-runs the server render of the current route (and so its API calls)
 * without a full page reload.
 */
export function RetryButton({ onRetry }: { onRetry?: () => void }) {
  const router = useRouter();
  const [pending, startTransition] = useTransition();

  return (
    <button
      type="button"
      disabled={pending}
      onClick={() => startTransition(() => (onRetry ? onRetry() : router.refresh()))}
      className="rounded-full bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 disabled:cursor-wait disabled:opacity-60"
    >
      {pending ? "Retrying..." : "Try again"}
    </button>
  );
}

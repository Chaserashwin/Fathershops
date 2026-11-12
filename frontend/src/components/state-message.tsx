import Link from "next/link";
import type { ReactNode } from "react";

import type { ApiFailure } from "@/lib/api";

import { RetryButton } from "./retry-button";

type StateMessageProps = {
  tone?: "neutral" | "error";
  eyebrow?: string;
  title: string;
  description: string;
  children?: ReactNode;
};

/** Centered panel used for empty, not-found and error states. */
export function StateMessage({ tone = "neutral", eyebrow, title, description, children }: StateMessageProps) {
  return (
    <div
      role={tone === "error" ? "alert" : "status"}
      className="mx-auto flex max-w-lg flex-col items-center rounded-2xl border border-slate-200 bg-white px-6 py-14 text-center shadow-sm"
    >
      <span
        className={`mb-5 grid size-14 place-items-center rounded-full ${
          tone === "error" ? "bg-rose-50 text-rose-600" : "bg-indigo-50 text-indigo-600"
        }`}
      >
        <svg viewBox="0 0 24 24" className="size-7" fill="none" stroke="currentColor" strokeWidth="1.7" aria-hidden>
          {tone === "error" ? (
            <path d="M12 8v5m0 3.5v.01M10.3 3.9 2.6 17.2A2 2 0 0 0 4.3 20h15.4a2 2 0 0 0 1.7-2.8L13.7 3.9a2 2 0 0 0-3.4 0Z" strokeLinecap="round" strokeLinejoin="round" />
          ) : (
            <path d="m20 20-4.2-4.2M18 10.5a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" strokeLinecap="round" />
          )}
        </svg>
      </span>
      {eyebrow && <p className="mb-1 text-sm font-semibold tracking-wide text-indigo-600 uppercase">{eyebrow}</p>}
      <h2 className="text-xl font-semibold text-slate-900">{title}</h2>
      <p className="mt-2 text-sm leading-6 text-slate-600">{description}</p>
      {children && <div className="mt-6 flex flex-wrap items-center justify-center gap-3">{children}</div>}
    </div>
  );
}

export function HomeLink({ label = "Browse all categories" }: { label?: string }) {
  return (
    <Link
      href="/"
      className="rounded-full border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50"
    >
      {label}
    </Link>
  );
}

/**
 * Renders a failed API call that is *not* a 404 (those use the not-found
 * page): the service is down, timed out or returned something unexpected.
 */
export function ApiErrorState({ failure, what, homeLink = true }: { failure: ApiFailure; what: string; homeLink?: boolean }) {
  return (
    <StateMessage
      tone="error"
      title={`We couldn't load ${what}`}
      description={
        failure.status === 429
          ? "You're sending requests a little too quickly. Please wait a moment and try again."
          : failure.message
      }
    >
      <RetryButton />
      {homeLink && <HomeLink label="Back to home" />}
    </StateMessage>
  );
}

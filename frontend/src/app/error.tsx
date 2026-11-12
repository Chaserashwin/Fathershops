"use client"; // Error boundaries must be Client Components

import { useEffect } from "react";

import { RetryButton } from "@/components/retry-button";
import { HomeLink, StateMessage } from "@/components/state-message";

/**
 * Last line of defence for unexpected exceptions. Expected failures
 * (missing data, API outages) are handled inside the pages themselves.
 */
export default function ErrorPage({ error, retry }: { error: unknown; retry: () => void }) {
  useEffect(() => {
    console.error(error);
  }, [error]);

  return (
    <StateMessage
      tone="error"
      title="Something went wrong"
      description="An unexpected error occurred while loading this page. Please try again."
    >
      <RetryButton onRetry={retry} />
      <HomeLink label="Back to home" />
    </StateMessage>
  );
}

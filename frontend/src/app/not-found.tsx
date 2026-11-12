import type { Metadata } from "next";

import { HomeLink, StateMessage } from "@/components/state-message";

export const metadata: Metadata = { title: "Page not found" };

export default function NotFound() {
  return (
    <StateMessage
      eyebrow="404"
      title="Page not found"
      description="The page you're looking for doesn't exist or has been moved."
    >
      <HomeLink />
    </StateMessage>
  );
}

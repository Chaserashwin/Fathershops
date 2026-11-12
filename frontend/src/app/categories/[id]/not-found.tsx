import type { Metadata } from "next";

import { HomeLink, StateMessage } from "@/components/state-message";

export const metadata: Metadata = { title: "Category not found" };

export default function CategoryNotFound() {
  return (
    <StateMessage
      eyebrow="404"
      title="Category not found"
      description="This category doesn't exist or is no longer available."
    >
      <HomeLink />
    </StateMessage>
  );
}

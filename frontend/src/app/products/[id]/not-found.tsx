import type { Metadata } from "next";

import { HomeLink, StateMessage } from "@/components/state-message";

export const metadata: Metadata = { title: "Product not found" };

export default function ProductNotFound() {
  return (
    <StateMessage
      eyebrow="404"
      title="Product not found"
      description="This product doesn't exist or is no longer available."
    >
      <HomeLink />
    </StateMessage>
  );
}

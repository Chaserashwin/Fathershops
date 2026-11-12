"use client";

import { useEffect, useRef } from "react";

/**
 * Renders a product description.
 *
 * The HTML is sanitized by the API with an allow-list (no scripts, event
 * handlers or inline styles), so injecting it is safe. Its images, however,
 * point at third-party hosts that are frequently unavailable; any image that
 * fails to load is removed so readers never see broken-image icons.
 */
export function RichText({ html }: { html: string }) {
  const container = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const root = container.current;

    if (!root) return;

    const hide = (image: HTMLImageElement) => {
      image.hidden = true;
    };

    const onError = (event: Event) => {
      if (event.target instanceof HTMLImageElement) hide(event.target);
    };

    // `error` does not bubble, so listen during the capture phase.
    root.addEventListener("error", onError, true);

    // Images that already failed before this effect ran.
    for (const image of root.querySelectorAll("img")) {
      if (image.complete && image.naturalWidth === 0) hide(image);
    }

    return () => root.removeEventListener("error", onError, true);
  }, [html]);

  return <div ref={container} className="rich-text" dangerouslySetInnerHTML={{ __html: html }} />;
}

"use client";

import Image from "next/image";
import { useCallback, useState } from "react";

type SafeImageProps = {
  src: string | null;
  alt: string;
  /** Passed to next/image so the browser downloads an appropriately sized file. */
  sizes: string;
  /** Set for the main above-the-fold image so it is not lazy-loaded. */
  eager?: boolean;
  className?: string;
};

/**
 * `next/image` for third-party catalog pictures.
 *
 * Catalog images live on hosts we do not control, so a missing or broken
 * picture is expected. Instead of a broken-image icon the component swaps in
 * a neutral placeholder. It fills its parent, which must be `relative` and
 * have a fixed aspect ratio.
 */
export function SafeImage({ src, alt, sizes, eager = false, className = "" }: SafeImageProps) {
  const [failedSrc, setFailedSrc] = useState<string | null>(null);

  // Images that failed before hydration never fire `onError`; catch them here.
  const checkAlreadyBroken = useCallback(
    (img: HTMLImageElement | null) => {
      if (img?.complete && img.naturalWidth === 0) setFailedSrc(src);
    },
    [src],
  );

  if (!src || failedSrc === src) {
    return (
      <div
        role="img"
        aria-label={`${alt} (image unavailable)`}
        className="absolute inset-0 grid place-items-center bg-slate-100 text-slate-400"
      >
        <svg viewBox="0 0 24 24" className="size-10" fill="none" stroke="currentColor" strokeWidth="1.5" aria-hidden>
          <rect x="3" y="4" width="18" height="16" rx="2" />
          <circle cx="8.5" cy="9.5" r="1.5" />
          <path d="m21 15-4.5-4.5L7 20" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
      </div>
    );
  }

  return (
    <Image
      ref={checkAlreadyBroken}
      src={src}
      alt={alt}
      fill
      sizes={sizes}
      loading={eager ? "eager" : "lazy"}
      fetchPriority={eager ? "high" : "auto"}
      onError={() => setFailedSrc(src)}
      className={`object-contain ${className}`}
    />
  );
}

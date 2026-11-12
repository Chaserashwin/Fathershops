"use client";

import { useState } from "react";

import { SafeImage } from "./safe-image";

export function ProductGallery({ images, name }: { images: string[]; name: string }) {
  const [selected, setSelected] = useState(0);
  const current = images[selected] ?? null;

  return (
    <div className="lg:sticky lg:top-24">
      <div className="relative aspect-square overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <SafeImage
          key={current}
          src={current}
          alt={name}
          sizes="(min-width: 1024px) 50vw, 100vw"
          eager
          className="p-4"
        />
      </div>

      {images.length > 1 && (
        <ul className="mt-4 grid grid-cols-5 gap-3 sm:grid-cols-6" aria-label="Product images">
          {images.map((image, index) => (
            <li key={image}>
              <button
                type="button"
                onClick={() => setSelected(index)}
                aria-label={`Show image ${index + 1} of ${images.length}`}
                aria-pressed={index === selected}
                className={`relative block aspect-square w-full overflow-hidden rounded-xl border bg-white transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 ${
                  index === selected ? "border-indigo-600 ring-2 ring-indigo-600/20" : "border-slate-200 hover:border-slate-400"
                }`}
              >
                <SafeImage src={image} alt="" sizes="96px" className="p-1" />
              </button>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}

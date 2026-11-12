import type { Metadata } from "next";
import { Geist, Geist_Mono } from "next/font/google";
import Link from "next/link";

import "./globals.css";

const geistSans = Geist({
  variable: "--font-geist-sans",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: {
    default: "FatherShops - Shop by category",
    template: "%s | FatherShops",
  },
  description: "Browse FatherShops categories, discover products and view full product details.",
};

export default function RootLayout({ children }: LayoutProps<"/">) {
  return (
    <html lang="en" className={`${geistSans.variable} ${geistMono.variable} h-full antialiased`}>
      <body className="flex min-h-full flex-col font-sans">
        <a
          href="#content"
          className="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-50 focus:rounded-full focus:bg-indigo-600 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white"
        >
          Skip to content
        </a>

        <header className="sticky top-0 z-40 border-b border-slate-200 bg-white/85 backdrop-blur">
          <div className="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-4 sm:px-6">
            <Link href="/" className="flex items-center gap-2.5 text-lg font-bold tracking-tight text-slate-900">
              <span aria-hidden className="grid size-9 place-items-center rounded-xl bg-indigo-600 text-base font-bold text-white">
                F
              </span>
              FatherShops
            </Link>
            <nav aria-label="Main">
              <Link
                href="/"
                className="rounded-full px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
              >
                Categories
              </Link>
            </nav>
          </div>
        </header>

        <main id="content" className="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6 sm:py-10">
          {children}
        </main>

        <footer className="border-t border-slate-200 bg-white">
          <p className="mx-auto w-full max-w-6xl px-4 py-6 text-sm text-slate-500 sm:px-6">
            FatherShops technical task | Next.js storefront powered by a Laravel catalog API.
          </p>
        </footer>
      </body>
    </html>
  );
}

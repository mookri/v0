import { NextResponse } from "next/server";
import {
  SITEMAP_CONTENT_TYPE,
  SITEMAP_HTTP_MAX_AGE_SECONDS,
  SITEMAP_HTTP_SWR_SECONDS,
} from "@/lib/seo/sitemap.constants";

const IS_PROD = process.env.NODE_ENV === "production";

/**
 * Security + cache headers shared by every successful sitemap response.
 * Separated so they can be tested independently of the route handler.
 */
export function buildSitemapHeaders({
  etag,
  contentLength,
  serverTimingMs,
}: {
  etag: string;
  contentLength: number;
  serverTimingMs: number;
}): HeadersInit {
  return {
    "Content-Type": SITEMAP_CONTENT_TYPE,
    "Content-Length": String(contentLength),
    ETag: etag,

    // Correct CDN behaviour — compressible XML must vary on encoding.
    Vary: "Accept-Encoding",

    // Serve stale while revalidating silently in background.
    "Cache-Control": `public, max-age=${SITEMAP_HTTP_MAX_AGE_SECONDS}, stale-while-revalidate=${SITEMAP_HTTP_SWR_SECONDS}`,

    // Propagate the same TTL to CDN edges.
    "CDN-Cache-Control": `public, max-age=${SITEMAP_HTTP_MAX_AGE_SECONDS}`,

    // Block MIME-type sniffing — sitemaps are always XML.
    "X-Content-Type-Options": "nosniff",

    // Prevent staging/preview sitemaps from leaking into Google Search Console.
    ...(!IS_PROD && { "X-Robots-Tag": "noindex" }),

    // Real observability for APM tools and browser DevTools Network tab.
    "Server-Timing": `build;dur=${serverTimingMs.toFixed(1)}`,
  };
}

export function sitemapNotFound(message: string): NextResponse {
  if (!IS_PROD) console.warn(`[sitemap] 404 — ${message}`);
  return new NextResponse("Not Found", { status: 404 });
}

export function sitemapError(message: string, err: unknown): NextResponse {
  console.error(`[sitemap] 500 — ${message}`, err);
  return new NextResponse("Internal Server Error", { status: 500 });
}

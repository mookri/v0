import { type NextRequest, NextResponse } from "next/server";
import { getSitemapChunks } from "@/lib/seo/sitemap-cache.server";
import { buildETag, isETagMatch } from "@/lib/seo/sitemap-etag";
import {
  buildSitemapHeaders,
  sitemapError,
  sitemapNotFound,
} from "@/lib/seo/sitemap-response";
import { generateSitemapXml } from "@/lib/seo/sitemap";

// Force dynamic — never let Next.js statically bake this at build time.
// The in-process cache (sitemap-cache.server.ts) handles freshness instead.
export const dynamic = "force-dynamic";

interface RouteContext {
  params: Promise<{ slug?: string[] }>;
}

// ─── Slug parsing ────────────────────────────────────────────────────────────

/**
 * Accepts:  /sitemaps/0   /sitemaps/0.xml
 * Rejects:  /sitemaps/abc   /sitemaps/0.xml/extra   /sitemaps/-1
 */
function parseSitemapId(
  slug: string[] | undefined,
): { id: number } | { error: string } {
  if (!slug || slug.length === 0) {
    return { error: "missing slug" };
  }

  if (slug.length > 1) {
    return { error: `unexpected extra segments: ${slug.slice(1).join("/")}` };
  }

  const clean = slug[0].replace(/\.xml$/i, "");

  if (!/^\d+$/.test(clean)) {
    return { error: `non-numeric id: ${clean}` };
  }

  const id = parseInt(clean, 10);

  if (!Number.isFinite(id) || id < 0) {
    return { error: `out-of-range id: ${id}` };
  }

  return { id };
}

// ─── Route handler ───────────────────────────────────────────────────────────

export async function GET(
  req: NextRequest,
  { params }: RouteContext,
): Promise<NextResponse> {
  const t0 = performance.now();

  const resolvedParams = await params;
  const parsed = parseSitemapId(resolvedParams.slug);

  if ("error" in parsed) {
    return sitemapNotFound(parsed.error);
  }

  const { id } = parsed;

  // ── Build / cache ──────────────────────────────────────────────────────────
  let chunks: Awaited<ReturnType<typeof getSitemapChunks>>["chunks"];
  let builtAt: number;

  try {
    ({ chunks, builtAt } = await getSitemapChunks());
  } catch (err) {
    return sitemapError("sitemap build failed with no usable cache", err);
  }

  const chunk = chunks[id];

  if (!chunk) {
    return sitemapNotFound(`chunk ${id} is out of range (total: ${chunks.length})`);
  }

  // ── Conditional GET (ETag / 304) ───────────────────────────────────────────
  const etag = buildETag(chunk, builtAt);

  if (isETagMatch(req.headers.get("if-none-match"), etag)) {
    return new NextResponse(null, {
      status: 304,
      headers: {
        ETag: etag,
        "Cache-Control": `public, max-age=${3_600}, stale-while-revalidate=${86_400}`,
      },
    });
  }

  // ── Serialise ──────────────────────────────────────────────────────────────
  let xml: string;

  try {
    xml = generateSitemapXml(chunk);
  } catch (err) {
    return sitemapError(`XML generation failed for chunk ${id}`, err);
  }

  const body = Buffer.from(xml, "utf-8");

  return new NextResponse(body, {
    status: 200,
    headers: buildSitemapHeaders({
      etag,
      contentLength: body.byteLength,
      serverTimingMs: performance.now() - t0,
    }),
  });
}

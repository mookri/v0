import { NextResponse } from "next/server";
import { buildSitemapChunks, buildSitemapEntries, generateSitemapXml } from "@/lib/seo/sitemap";

interface RouteContext {
  params: Promise<Record<string, string | string[] | undefined>>;
}

export async function GET(_: Request, { params }: RouteContext) {
  const resolvedParams = await params;
  const rawId = Array.isArray(resolvedParams.id) ? resolvedParams.id[0] : resolvedParams.id;
  const sitemapId = Number.parseInt(rawId ?? "", 10);

  if (!Number.isFinite(sitemapId) || sitemapId < 0) {
    return new NextResponse("Not Found", { status: 404 });
  }

  const chunks = buildSitemapChunks(buildSitemapEntries());
  const chunk = chunks[sitemapId];

  if (!chunk) {
    return new NextResponse("Not Found", { status: 404 });
  }

  return new NextResponse(generateSitemapXml(chunk), {
    headers: {
      "Content-Type": "application/xml; charset=utf-8",
      "Cache-Control": "public, max-age=0, must-revalidate",
    },
  });
}

import { NextResponse } from "next/server";
import { buildSitemapEntries, buildSitemapIndexXml } from "@/lib/seo/sitemap";

export async function GET() {
  const xml = buildSitemapIndexXml(buildSitemapEntries());

  return new NextResponse(xml, {
    headers: {
      "Content-Type": "application/xml; charset=utf-8",
      "Cache-Control": "public, max-age=0, must-revalidate",
    },
  });
}

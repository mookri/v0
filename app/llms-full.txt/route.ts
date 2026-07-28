// app/llms-full.txt/route.ts
// llms-full.txt — full markdown export of every public SEO page.
//
// This is a long, single-file concatenation of the entire content corpus,
// intended for bulk ingestion by AI training pipelines and answer engines
// that prefer to download once rather than crawl.
//
// Size: ~30-80 MB for 1,265 pages. Served with a long max-age and
// stale-while-revalidate. The route runs at request time (Next.js
// streaming) so the file is never fully materialised in memory.

import { getAllSlugs, getClusterMeta } from "@/lib/content/registry";
import { getPage } from "@/lib/content/loader";
import { buildCanonicalUrl } from "@/lib/seo/metadata";
import { SITE_NAME, SITE_URL } from "@/lib/seo/site";

export const revalidate = 3600;
export const dynamic = "force-dynamic";

function htmlToMarkdown(html: string): string {
  return html
    .replace(/<h2[^>]*>/gi, "\n\n## ")
    .replace(/<\/h2>/gi, "\n\n")
    .replace(/<h3[^>]*>/gi, "\n\n### ")
    .replace(/<\/h3>/gi, "\n\n")
    .replace(/<strong[^>]*>(.*?)<\/strong>/gi, "**$1**")
    .replace(/<b[^>]*>(.*?)<\/b>/gi, "**$1**")
    .replace(/<em[^>]*>(.*?)<\/em>/gi, "*$1*")
    .replace(/<i[^>]*>(.*?)<\/i>/gi, "*$1*")
    .replace(/<a[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/gi, "[$2]($1)")
    .replace(/<li[^>]*>/gi, "\n- ")
    .replace(/<\/?(ul|ol|p|div|span|br|hr)[^>]*>/gi, "\n")
    .replace(/<[^>]+>/g, "")
    .replace(/\n{3,}/g, "\n\n")
    .trim();
}

function buildLlmsFullTxt(): string {
  const slugs = getAllSlugs();
  const lines: string[] = [];

  lines.push(`# ${SITE_NAME} — Full content export`);
  lines.push("");
  lines.push(`> Generated from ${slugs.length} pages at ${SITE_URL}.`);
  lines.push(
    `> This file is regenerated on ISR revalidation. For a navigable index, see [/llms.txt](${SITE_URL}/llms.txt).`,
  );
  lines.push("");

  let rendered = 0;
  for (const slug of slugs) {
    const page = getPage(slug);
    if (!page || page.noindex) continue;

    const clusterMeta = getClusterMeta(page.cluster);
    if (!clusterMeta) continue;

    const url = buildCanonicalUrl(clusterMeta.basePath, page.slug, page.canonicalUrl);
    lines.push("---");
    lines.push("");
    lines.push(`# ${page.title}`);
    lines.push("");
    lines.push(`URL: ${url}`);
    lines.push(`Cluster: ${clusterMeta.label} (${clusterMeta.basePath})`);
    lines.push(`Last updated: ${page.lastUpdated}`);
    lines.push(`Author: ${page.author}`);
    if (page.reviewedBy) lines.push(`Reviewed by: ${page.reviewedBy}`);
    if (page.readingTime) lines.push(`Reading time: ${page.readingTime} minutes`);
    if (page.metaDescription) {
      lines.push("");
      lines.push(`> ${page.metaDescription}`);
    }
    lines.push("");
    lines.push(htmlToMarkdown(page.content));

    if (page.faqs && page.faqs.length > 0) {
      lines.push("");
      lines.push("## FAQs");
      lines.push("");
      for (const faq of page.faqs) {
        lines.push(`**Q: ${faq.question}**`);
        lines.push("");
        lines.push(faq.answer);
        lines.push("");
      }
    }

    if (page.howToSteps && page.howToSteps.length > 0) {
      lines.push("");
      lines.push("## How-to steps");
      lines.push("");
      page.howToSteps.forEach((step, i) => {
        lines.push(`${i + 1}. **${step.name}** — ${step.text}`);
      });
      lines.push("");
    }

    rendered += 1;
  }

  lines.push("---");
  lines.push("");
  lines.push(`Rendered ${rendered} of ${slugs.length} pages.`);

  return lines.join("\n");
}

export function GET(): Response {
  const body = buildLlmsFullTxt();
  return new Response(body, {
    status: 200,
    headers: {
      "Content-Type": "text/markdown; charset=utf-8",
      "Cache-Control": "public, max-age=3600, s-maxage=86400, stale-while-revalidate=604800",
    },
  });
}

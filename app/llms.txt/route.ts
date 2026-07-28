// app/llms.txt/route.ts
// llms.txt — a markdown index of the site for AI assistants and answer engines.
// Spec: https://llmstxt.org/
//
// We render this dynamically (no precompute) so it always reflects the
// current content registry. Cache aggressively via CDN; the file is
// regenerated on ISR revalidation and on registry change.

import { getAllSlugs, getClusterMeta } from "@/lib/content/registry";
import { getPage } from "@/lib/content/loader";
import { buildCanonicalUrl } from "@/lib/seo/metadata";
import { SITE_NAME, SITE_URL } from "@/lib/seo/site";

export const revalidate = 3600;
export const dynamic = "force-static";

interface ClusterSection {
  label: string;
  basePath: string;
  pages: Array<{ title: string; url: string; description: string }>;
}

function buildLlmsTxt(): string {
  const slugs = getAllSlugs();
  const sections = new Map<string, ClusterSection>();

  for (const slug of slugs) {
    const page = getPage(slug);
    if (!page || page.noindex) continue;

    const clusterMeta = getClusterMeta(page.cluster);
    if (!clusterMeta) continue;

    if (!sections.has(page.cluster)) {
      sections.set(page.cluster, {
        label: clusterMeta.label,
        basePath: clusterMeta.basePath,
        pages: [],
      });
    }

    const url = buildCanonicalUrl(clusterMeta.basePath, page.slug, page.canonicalUrl);
    sections.get(page.cluster)!.pages.push({
      title: page.title,
      url,
      description: page.metaDescription,
    });
  }

  const lines: string[] = [];
  lines.push(`# ${SITE_NAME}`);
  lines.push("");
  lines.push(
    `> ${SITE_NAME} helps families book verified pandits in Delhi NCR for home pujas, havan, wedding rituals, astrology consultations, and online ceremonies. This file lists our public content for AI assistants and answer engines.`,
  );
  lines.push("");
  lines.push(
    `For the full text of every page, see [/llms-full.txt](${SITE_URL}/llms-full.txt). For structured data, see our [JSON-LD schemas](/) on every page.`,
  );
  lines.push("");

  // Stable ordering: alphabetical by cluster basePath, then alphabetical by title within
  const ordered = Array.from(sections.entries()).sort(([, a], [, b]) =>
    a.basePath.localeCompare(b.basePath),
  );

  for (const [clusterKey, section] of ordered) {
    lines.push(`## ${section.label}`);
    lines.push("");
    const sortedPages = section.pages.sort((a, b) => a.title.localeCompare(b.title));
    for (const page of sortedPages.slice(0, 200)) {
      const cleanDescription = page.description.replace(/\s+/g, " ").trim();
      const truncated = cleanDescription.length > 180
        ? `${cleanDescription.slice(0, 177).trimEnd()}…`
        : cleanDescription;
      lines.push(`- [${page.title}](${page.url}): ${truncated}`);
    }
    if (sortedPages.length > 200) {
      lines.push(`- … and ${sortedPages.length - 200} more in ${section.label}`);
    }
    lines.push("");
  }

  lines.push("## Optional");
  lines.push("");
  lines.push(`- [Privacy policy](${SITE_URL}/privacy-policy)`);
  lines.push(`- [Terms of service](${SITE_URL}/terms-of-service)`);
  lines.push(`- [Grievance officer](${SITE_URL}/grievance-officer)`);
  lines.push(`- [Contact](${SITE_URL}/contact)`);
  lines.push("");
  lines.push(`Cluster count: ${ordered.length}. Page count: ${slugs.length}.`);
  lines.push("");

  return lines.join("\n");
}

export function GET(): Response {
  const body = buildLlmsTxt();
  return new Response(body, {
    status: 200,
    headers: {
      "Content-Type": "text/markdown; charset=utf-8",
      "Cache-Control": "public, max-age=3600, s-maxage=86400, stale-while-revalidate=604800",
    },
  });
}

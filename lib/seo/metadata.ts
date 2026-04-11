// /lib/seo/metadata.ts
// Auto-generates Next.js Metadata objects from SEOPage content.

import type { Metadata } from "next";
import type { SEOPage } from "@/lib/content/types";
import {
  DEFAULT_LANGUAGE,
  DEFAULT_LOCALE,
  DEFAULT_OG_IMAGE,
  DEFAULT_OG_IMAGE_HEIGHT,
  DEFAULT_OG_IMAGE_WIDTH,
  SITE_NAME,
  SITE_URL,
  toAbsoluteUrl,
  withSiteName,
} from "@/lib/seo/site";
import { normalizePublicPath, toPublicUrl } from "@/lib/seo/routes";

function resolveOpenGraphType(page: SEOPage): "article" | "website" {
  switch (page.schemaType) {
    case "Article":
    case "FAQPage":
    case "HowTo":
      return "article";
    default:
      return "website";
  }
}

/**
 * Build the canonical URL for a page based on its cluster and slug.
 */
export function buildCanonicalUrl(basePath: string, slug: string, canonicalOverride?: string): string {
  if (canonicalOverride?.trim()) {
    return toPublicUrl(canonicalOverride.trim());
  }

  return toPublicUrl(`/${basePath}/${slug}`);
}

/**
 * Generate full Next.js Metadata from an SEOPage object.
 */
export function generatePageMetadata(page: SEOPage, basePath: string): Metadata {
  const canonicalUrl = buildCanonicalUrl(basePath, page.slug, page.canonicalUrl);
  const fullTitle = withSiteName(page.og?.ogTitle ?? page.metaTitle);
  const description = page.og?.ogDescription ?? page.metaDescription;
  const imageUrl = toAbsoluteUrl(page.og?.ogImageUrl ?? page.heroImage ?? DEFAULT_OG_IMAGE);
  const normalizedCanonicalPath = normalizePublicPath(canonicalUrl);
  const robots = page.noindex
    ? {
      index: false,
      follow: false,
      nocache: true,
      googleBot: {
        index: false,
        follow: false,
        noimageindex: true,
      },
    }
    : {
      index: true,
      follow: true,
      nocache: false,
      googleBot: {
        index: true,
        follow: true,
        "max-image-preview": "large" as const,
        "max-snippet": -1,
        "max-video-preview": -1,
      },
    };

  return {
    title: fullTitle,
    description,
    keywords: [page.primaryKeyword, ...page.secondaryKeywords],
    authors: [{ name: page.author }],
    creator: page.author,
    publisher: SITE_NAME,
    category: page.cluster,
    classification: page.searchIntent,
    robots,
    openGraph: {
      title: fullTitle,
      description,
      url: canonicalUrl,
      siteName: SITE_NAME,
      images: [
        {
          url: imageUrl,
          width: DEFAULT_OG_IMAGE_WIDTH,
          height: DEFAULT_OG_IMAGE_HEIGHT,
          alt: `${page.title} - ${SITE_NAME}`,
        },
      ],
      locale: DEFAULT_LOCALE,
      type: resolveOpenGraphType(page),
      publishedTime: page.automation?.generatedAt ?? page.lastUpdated,
      modifiedTime: page.lastUpdated,
    },
    twitter: {
      card: page.og?.twitterCard ?? "summary_large_image",
      title: fullTitle,
      description,
      images: [imageUrl],
      site: "@navdhya",
    },
    alternates: {
      canonical: canonicalUrl,
      languages: {
        "en-IN": canonicalUrl,
      },
    },
    other: {
      "article:author": page.author,
      "article:modified_time": page.lastUpdated,
      "og:locale": DEFAULT_LOCALE,
      "content-language": DEFAULT_LANGUAGE,
      "x-canonical-path": normalizedCanonicalPath,
    },
  };
}

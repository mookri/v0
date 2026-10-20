// lib/seo/aeo.ts
// Answer-Engine Optimization (AEO) structured-data generators.
//
// AEO complements traditional SEO by adding structured data that helps
// AI-powered answer engines (Google AI Overviews, Perplexity, ChatGPT
// search, Bing Copilot, Claude) extract and cite content correctly.
//
// Generated schema elements (all optional, all pure-additive, all idempotent):
//   • SpeakableSpecification  — points voice/AI engines at the best excerpts
//   • ItemList (key facts)    — Google "Key Facts" pattern; 3-5 scannable facts
//   • WebPageElement (TLDR)   — short, citable summary
//   • ClaimReview             — fact-check stamp when content is reviewed
//   • Person/Organization     — author entity for E-E-A-T
//
// All functions are pure (no side effects, no I/O) and safe to call from
// server components, generateMetadata, and RSC streaming paths.

import type { SEOPage, SpeakableFAQEntry } from "@/lib/content/types";
import { SITE_URL, SITE_NAME } from "@/lib/seo/site";

type SchemaNode = Record<string, unknown>;

// CSS selectors for the actual HTML rendered by components/seo/ArticlePage.tsx.
// Keep these in sync with that component.
const ARTICLE_SELECTORS = [
  "#article-content h2",
  "#article-content h3",
  "#article-content p",
] as const;

const FAQ_SELECTOR = (index: number) => `#faq-item-${index + 1}`;

/**
 * Build a SpeakableSpecification that points answer-engines at the
 * best excerpts on the page. CSS selectors are the canonical form for
 * modern schema.org usage; XPath is included as a fallback.
 *
 * Selectors target the actual DOM emitted by ArticlePage.tsx:
 *   • #article-content h2/h3/p — main body content
 *   • #faq-item-N — each FAQ block (added in PR 0.5)
 *
 * Prefers speakableFaqs when present (editorially curated), falls
 * back to faqs, then to body content.
 */
export function buildSpeakable(page: SEOPage): SchemaNode | undefined {
  const faqs = page.speakableFaqs ?? page.faqs ?? [];

  const cssSelectors: string[] = [...ARTICLE_SELECTORS];
  faqs.slice(0, 5).forEach((_, index) => {
    cssSelectors.push(FAQ_SELECTOR(index));
  });

  return {
    "@type": "SpeakableSpecification",
    cssSelector: cssSelectors.slice(0, 6),
    xpath: ["/html/body/main[@id='article-content']/h2", "/html/body/main[@id='article-content']/p"],
  };
}

/**
 * Build a Google-style "Key Facts" ItemList. We prefer editorially-curated
 * sources (proofPoints → highlights → benefits) and fall back to
 * auto-derived sentence extraction from the first paragraphs.
 */
export function buildKeyFacts(page: SEOPage, url: string): SchemaNode | undefined {
  const candidates: string[] = [];

  if (page.proofPoints && page.proofPoints.length > 0) {
    candidates.push(...page.proofPoints);
  }
  if (candidates.length < 3 && page.serviceDetails?.highlights) {
    candidates.push(...page.serviceDetails.highlights);
  }
  if (candidates.length < 3 && page.serviceDetails?.benefits) {
    candidates.push(...page.serviceDetails.benefits);
  }
  if (candidates.length < 2) {
    candidates.push(...extractSentences(page.content, 3));
  }

  const facts = dedupeAndCap(candidates, 5);
  if (facts.length === 0) return undefined;

  return {
    "@type": "ItemList",
    "@id": `${url}#keyfacts`,
    name: `Key facts about ${page.title}`,
    description: `Scannable facts that summarize ${page.title.toLowerCase()} for answer engines and quick readers.`,
    numberOfItems: facts.length,
    itemListElement: facts.map((fact, index) => ({
      "@type": "ListItem",
      position: index + 1,
      name: stripTrailingPunctuation(fact),
    })),
  };
}

/**
 * Build a WebPageElement (TLDR) for the page. Either the editorial excerpt
 * or the meta description is used. This is the "one-line answer" that AI
 * engines often quote in their responses.
 */
export function buildTLDR(page: SEOPage, url: string): SchemaNode | undefined {
  const text = (page.excerpt ?? page.metaDescription ?? "").trim();
  if (!text) return undefined;

  return {
    "@type": "WebPageElement",
    "@id": `${url}#tldr`,
    name: `TL;DR: ${page.title}`,
    text,
    isPartOf: { "@id": `${url}#webpage` },
  };
}

/**
 * Build a ClaimReview stamp when content has gone through editorial review.
 * Only emitted for approved/reviewed content (not drafts/restricted).
 */
export function buildClaimReview(page: SEOPage, url: string): SchemaNode | undefined {
  if (!page.reviewedBy) return undefined;
  if (page.claimReviewStatus === "draft" || page.claimReviewStatus === "restricted") {
    return undefined;
  }

  return {
    "@type": "ClaimReview",
    "@id": `${url}#claimreview`,
    url,
    claimReviewed: page.title,
    reviewBody: page.excerpt ?? page.metaDescription ?? page.title,
    author: {
      "@type": "Organization",
      "@id": `${SITE_URL}#organization`,
      name: SITE_NAME,
      url: SITE_URL,
    },
    datePublished: page.automation?.generatedAt ?? page.lastUpdated,
    reviewRating: {
      "@type": "Rating",
      ratingValue: page.claimReviewStatus === "approved" ? 5 : 4,
      bestRating: 5,
      worstRating: 1,
      ratingExplanation: `Editorial review: ${page.claimReviewStatus}.`,
    },
    itemReviewed: {
      "@type": "WebPage",
      "@id": `${url}#webpage`,
      url,
      name: page.title,
    },
  };
}

/**
 * Build a Person or Organization author entity. ReviewedBy takes precedence
 * (it's a real human name) over the generic editorial team name.
 */
export function buildAuthorEntity(page: SEOPage): SchemaNode {
  if (page.reviewedBy) {
    return {
      "@type": "Person",
      name: page.reviewedBy,
      worksFor: {
        "@type": "Organization",
        "@id": `${SITE_URL}#organization`,
        name: SITE_NAME,
      },
    };
  }

  return {
    "@type": "Organization",
    name: page.author || SITE_NAME,
    url: SITE_URL,
  };
}

/**
 * Build language alternates. We don't ship translated content yet
 * (PR 14a), but the slot is reserved so the contract is stable.
 */
export function buildInLanguageAlternates(url: string): SchemaNode {
  return {
    "@type": "WebPage",
    "@id": `${url}#webpage`,
    inLanguage: ["en-IN", "hi-IN"],
    availableLanguage: ["en-IN", "hi-IN"],
  };
}

/**
 * AEO generator entry point. Returns all available AEO schema nodes
 * for the given page context. Each node is independently nullable;
 * the schema renderer drops undefined entries.
 */
export interface AEOContext {
  page: SEOPage;
  url: string;
}

export interface AEOOutput {
  speakable?: SchemaNode;
  keyFacts?: SchemaNode;
  tldr?: SchemaNode;
  claimReview?: SchemaNode;
  authorEntity?: SchemaNode;
}

export function generateAEO(context: AEOContext): AEOOutput {
  const { page, url } = context;

  return {
    speakable: buildSpeakable(page),
    keyFacts: buildKeyFacts(page, url),
    tldr: buildTLDR(page, url),
    claimReview: buildClaimReview(page, url),
    authorEntity: buildAuthorEntity(page),
  };
}

// ─── helpers ──────────────────────────────────────────────────────────────

function stripHtml(value: string): string {
  return value.replace(/<[^>]+>/g, " ").replace(/\s+/g, " ").trim();
}

function extractSentences(html: string, max: number): string[] {
  const text = stripHtml(html);
  if (!text) return [];
  const matches = text.match(/[^.!?]+[.!?]+/g) ?? [];
  return matches
    .map((s) => s.trim())
    .filter((s) => s.length > 20 && s.length < 220)
    .slice(0, max);
}

function dedupeAndCap(items: string[], cap: number): string[] {
  const seen = new Set<string>();
  const out: string[] = [];
  for (const raw of items) {
    const item = raw.trim();
    if (!item) continue;
    const key = item.toLowerCase();
    if (seen.has(key)) continue;
    seen.add(key);
    out.push(item);
    if (out.length >= cap) break;
  }
  return out;
}

function stripTrailingPunctuation(value: string): string {
  return value.replace(/[.,;:!?\s]+$/, "").trim();
}

/**
 * Derive a SpeakableFAQEntry list from a FAQEntry list, idempotently.
 * Used by scripts/backfill-speakable-faqs.ts and the schema fallback path.
 */
export function deriveSpeakableFaqs(faqs: Array<{ question: string; answer: string }>): SpeakableFAQEntry[] {
  return faqs.slice(0, 5).map((faq) => ({
    question: faq.question,
    answer: faq.answer,
    summary: faq.answer.length > 160 ? `${faq.answer.slice(0, 157).trim()}…` : faq.answer,
  }));
}

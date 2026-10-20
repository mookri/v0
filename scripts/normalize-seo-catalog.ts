import fs from "fs";
import path from "path";
import type { ContentCluster, FAQEntry, SEOPage } from "../apps/web/lib/content/types";
import { validateEntitySEO } from "../apps/web/lib/seo/entity";

type PageFile = SEOPage & {
  automation?: unknown;
  __sourceFile?: string;
  [key: string]: unknown;
};

const ROOT = path.join(process.cwd(), 'apps/web');
const PAGES_DIR = path.join(ROOT, "content", "pages");
const LAST_UPDATED = "2026-05-25";
const BASE_SLUGS = new Set(["what-is-navdhya", "about-navdhya", "navdhya-services"]);
const START_MARKER = "<!-- seo-quality-extension:start -->";
const END_MARKER = "<!-- seo-quality-extension:end -->";

const CLUSTER_BASE_PATHS: Record<ContentCluster, string> = {
  brand: "explore",
  pujas: "pujas",
  services: "services",
  ceremonies: "ceremonies",
  "ritual-guides": "ritual-guides",
  "pandit-services": "pandit-services",
  "festival-rituals": "festival-rituals",
  "spiritual-guides": "spiritual-guides",
  faq: "faq",
  blog: "blog",
  compare: "compare",
  centers: "centers",
  astrology: "astrology",
  vastu: "vastu",
};

const BASE_PAGE_EXTRA_FAQS: Record<string, FAQEntry[]> = {
  "what-is-navdhya": [
    {
      question: "Who is Navdhya for?",
      answer:
        "Navdhya is designed for families who want clearer ritual guidance, verified pandit support, and a more practical booking path for home and online ceremonies.",
    },
  ],
  "about-navdhya": [
    {
      question: "What does Navdhya try to improve for families?",
      answer:
        "Navdhya tries to improve trust, preparation clarity, and service coordination so the ritual experience feels calmer and more transparent from the first inquiry onward.",
    },
  ],
  "navdhya-services": [
    {
      question: "What should users do after reviewing Navdhya services?",
      answer:
        "Most users should open the main puja page, a relevant city or comparison page, and the pandit-verification page so they can move from overview into a confident booking decision.",
    },
  ],
};

const BASE_PAGE_RELATED_SLUGS: Record<string, string[]> = {
  "what-is-navdhya": ["about-navdhya", "navdhya-services", "navdhya-priest-verification"],
  "about-navdhya": ["what-is-navdhya", "navdhya-services", "navdhya-priest-verification"],
  "navdhya-services": ["what-is-navdhya", "about-navdhya", "navdhya-priest-verification"],
};

function readPages(): PageFile[] {
  return fs
    .readdirSync(PAGES_DIR)
    .filter((file) => file.endsWith(".json"))
    .sort()
    .map((file) => ({
      ...(JSON.parse(fs.readFileSync(path.join(PAGES_DIR, file), "utf8")) as PageFile),
      __sourceFile: file,
    }));
}

function writePage(page: PageFile) {
  const { __sourceFile, ...persisted } = page;
  const fileName = __sourceFile ?? `${page.slug}.json`;
  fs.writeFileSync(path.join(PAGES_DIR, fileName), `${JSON.stringify(persisted, null, 2)}\n`, "utf8");
}

function stripHtml(value: string) {
  return value.replace(/<[^>]+>/g, " ").replace(/\s+/g, " ").trim();
}

function countRenderableWords(page: PageFile) {
  const faqText = (page.faqs ?? []).map((faq) => `${faq.question} ${faq.answer}`).join(" ");
  const howToText = (page.howToSteps ?? []).map((step) => `${step.name} ${step.text}`).join(" ");
  const merged = `${stripHtml(page.content)} ${faqText} ${howToText}`.trim();
  return merged ? merged.split(/\s+/).filter(Boolean).length : 0;
}

function countH2s(content: string) {
  return (content.match(/<h2\b[^>]*>/g) ?? []).length;
}

function stripEnhancementBlock(content: string) {
  return content.replace(
    /<!-- seo-quality-extension:start -->[\s\S]*?<!-- seo-quality-extension:end -->/g,
    "",
  );
}

function slugToLabel(value: string) {
  return value
    .split("/")
    .filter(Boolean)
    .pop()
    ?.split("-")
    .map((segment) => segment.charAt(0).toUpperCase() + segment.slice(1))
    .join(" ");
}

function routeForPage(page: PageFile) {
  return `/${CLUSTER_BASE_PATHS[page.cluster]}/${page.slug}`;
}

function linkLabel(link: string) {
  if (link === "/pujas") return "main puja booking page";
  if (link === "/") return "Navdhya homepage";
  return slugToLabel(link) ?? "recommended next page";
}

function routeAnchor(link: string) {
  return `<a href="${link}">${linkLabel(link)}</a>`;
}

function buildMetaDescription(page: PageFile) {
  const topic = page.primaryKeyword || page.title;
  const locality = page.targetCities?.[0] ?? (page.cluster === "centers" ? page.title : "Delhi NCR");
  const templates: Record<string, string> = {
    Service: `Book ${topic} with verified pandit support, clear inclusions, and ${locality} ceremony planning from Navdhya.`,
    LocalBusiness: `Book pandit services in ${locality} with verified guidance, home puja support, and practical local planning from Navdhya.`,
    FAQPage: `Find answers about ${topic} with practical ritual guidance, clearer next steps, and trusted context from Navdhya.`,
    HowTo: `Learn ${topic} with practical ritual steps, family-ready guidance, and trusted Hindu ceremony context from Navdhya.`,
    Article: `Explore ${topic} with practical guidance, clearer next steps, and Delhi NCR booking context from Navdhya.`,
  };

  const raw = templates[page.schemaType] ?? templates.Article;
  return raw.length <= 165 ? raw : `${raw.slice(0, 162).trimEnd()}...`;
}

function buildMetaTitle(page: PageFile) {
  const topic = page.title.replace(/\s+\|.*$/, "").trim();
  const locality = page.targetCities?.[0] ?? "Delhi NCR";
  const templates: Record<string, string> = {
    Service: `${topic} | ${locality} Booking Guide`,
    LocalBusiness: `${topic} | Verified Pandit Booking`,
    FAQPage: `${topic} | Navdhya FAQs`,
    HowTo: `${topic} | Practical Ritual Guide`,
    Article: `${topic} | Navdhya Guide`,
  };
  const raw = templates[page.schemaType] ?? templates.Article;
  return raw.length <= 72 ? raw : `${raw.slice(0, 69).trimEnd()}...`;
}

function extractOutline(content: string) {
  return Array.from(content.matchAll(/<h2\b[^>]*>(.*?)<\/h2>/g)).map((match) =>
    match[1].replace(/<[^>]+>/g, "").replace(/\s+/g, " ").trim(),
  );
}

function readingTimeFromPage(page: PageFile) {
  return Math.max(6, Math.ceil(countRenderableWords(page) / 190));
}

function buildSupportBlock(page: PageFile, missingLinks: string[], needsDepth: boolean) {
  const keyword = page.primaryKeyword || page.title.toLowerCase();
  const locality = page.targetCities?.[0] ?? "Delhi NCR";
  const relatedAnchors =
    missingLinks.length > 0
      ? missingLinks.map(routeAnchor)
      : (page.requiredInternalLinks ?? []).slice(0, 3).map(routeAnchor);
  const fallbackAnchors = [
    routeAnchor("/explore/about-navdhya"),
    routeAnchor("/pujas"),
    routeAnchor("/compare/best-pandit-booking-delhi"),
  ];
  const nextAnchors = relatedAnchors.length > 0 ? relatedAnchors : fallbackAnchors;

  const paragraphs: string[] = [
    `<h2>How Navdhya helps users act on this topic</h2><p>Families searching for ${keyword} usually need more than a surface explanation. Navdhya treats this page as practical decision support, which means connecting ritual meaning, planning reality, and the next step a ${locality} family can actually take without guesswork.</p><p>That approach keeps the page useful for both search and conversion. Navdhya explains what changes based on locality, ceremony type, timing, and household readiness so the reader can decide whether to continue with a service page, a city page, or a pricing comparison.</p>`,
    `<h2>Best next Navdhya pages after this page</h2><p>If you are ready to continue, start with ${routeAnchor("/pujas")} and then move into ${nextAnchors.join(", ")}. Those pages answer the next layer of questions around service fit, locality, pricing, and what the ceremony should look like in practice.</p><p>Navdhya uses linked next-step pages deliberately so no page becomes an SEO dead end. The goal is to help the reader move from research into a clearer and more confident booking or preparation decision.</p>`,
  ];

  if (page.cluster === "centers") {
    paragraphs.push(
      `<h2>Why locality pages need real Delhi NCR context</h2><p>City-level searchers are rarely looking for abstract information. They want to know whether Navdhya understands travel timing, society access, samagri planning, and how ceremony setup changes across neighborhoods in ${locality}. Adding that context makes the page more useful than a generic city swap.</p><p>It also helps the internal linking graph work better, because locality intent can flow naturally into the exact puja page or comparison page the family should review next.</p>`,
    );
  } else if (needsDepth) {
    paragraphs.push(
      `<h2>What makes this page stronger for 2027 SEO</h2><p>People-first SEO depends on pages that solve one real problem clearly. Navdhya strengthens this page by making the user outcome explicit, clarifying what this topic is for, and linking toward the most relevant supporting pages instead of repeating generic ritual language.</p><p>That keeps the content useful for classic rankings, AI summaries, and users who want fast answers before they commit to the next step.</p>`,
    );
  }

  return `${START_MARKER}${paragraphs.join("")}${END_MARKER}`;
}

const pages = readPages();
const duplicateTitles = new Map<string, number>();
const duplicateDescriptions = new Map<string, number>();

for (const page of pages) {
  duplicateTitles.set(page.metaTitle, (duplicateTitles.get(page.metaTitle) ?? 0) + 1);
  duplicateDescriptions.set(page.metaDescription, (duplicateDescriptions.get(page.metaDescription) ?? 0) + 1);
}

let updatedCount = 0;

for (const page of pages) {
  let updated = false;

  if (duplicateTitles.get(page.metaTitle) && duplicateTitles.get(page.metaTitle)! > 1) {
    page.metaTitle = buildMetaTitle(page);
    updated = true;
  }

  if (duplicateDescriptions.get(page.metaDescription) && duplicateDescriptions.get(page.metaDescription)! > 1) {
    page.metaDescription = buildMetaDescription(page);
    updated = true;
  }

  if (BASE_SLUGS.has(page.slug)) {
    const extraFaqs = BASE_PAGE_EXTRA_FAQS[page.slug] ?? [];
    if ((page.faqs?.length ?? 0) < 3 && extraFaqs.length > 0) {
      page.faqs = [...(page.faqs ?? []), ...extraFaqs].slice(0, 3);
      updated = true;
    }

    if ((page.relatedSlugs?.length ?? 0) < 3) {
      page.relatedSlugs = BASE_PAGE_RELATED_SLUGS[page.slug] ?? page.relatedSlugs;
      updated = true;
    }
  }

  if (page.cluster === "pujas") {
    while (countRenderableWords({ ...page, content: stripEnhancementBlock(page.content).trim() }) > 1400 && (page.faqs?.length ?? 0) > 3) {
      page.faqs = page.faqs?.slice(0, -1);
      updated = true;
    }
  }

  const hadEnhancement = page.content.includes(START_MARKER);
  const cleanedContent = stripEnhancementBlock(page.content).trim();
  const tempPage = { ...page, content: cleanedContent };
  const missingLinks =
    BASE_SLUGS.has(page.slug)
      ? []
      : (page.requiredInternalLinks ?? []).filter(
          (link) => !cleanedContent.includes(`href="${link}"`) && !cleanedContent.includes(link),
        );
  const wordCount = countRenderableWords(tempPage);
  const h2Count = countH2s(cleanedContent);
  const entity = validateEntitySEO(cleanedContent);
  const needsDepth =
    page.schemaType !== "FAQPage" &&
    (wordCount < 700 || h2Count < 5 || entity.brandMentions < 4 || entity.wordCount < 500);

  if (needsDepth || missingLinks.length > 0) {
    page.content = `${cleanedContent}${buildSupportBlock(page, missingLinks, needsDepth)}`;
    page.outline = extractOutline(page.content);
    page.readingTime = readingTimeFromPage(page);
    page.lastUpdated = LAST_UPDATED;
    page.lastReviewed = LAST_UPDATED;
    updated = true;
  } else if (hadEnhancement) {
    page.content = cleanedContent;
    page.outline = extractOutline(page.content);
    page.readingTime = readingTimeFromPage(page);
    updated = true;
  }

  if (updated) {
    writePage(page);
    updatedCount += 1;
  }
}

console.log(`Normalized ${updatedCount} SEO pages for depth, links, and metadata uniqueness.`);

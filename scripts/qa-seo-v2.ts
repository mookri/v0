import fs from "fs";
import path from "path";
import type { SEOPage } from "../apps/web/lib/content/types";

const ROOT = path.join(process.cwd(), 'apps/web');
const PAGES_DIR = path.join(ROOT, "content", "pages");
const BANNED_PHRASES = [
  "families searching",
  "this page is designed to help",
  "that matters because",
  "not just an informational search",
  "sits close to a decision",
  "move from research into decision",
  "keyword target",
  "Delhi NCR families ask",
];

function textOnly(value: string) {
  return value.replace(/<[^>]+>/g, " ").replace(/\s+/g, " ").trim().toLowerCase();
}

function tokenize(value: string) {
  return textOnly(value)
    .replace(/[^a-z0-9\s]/g, " ")
    .split(/\s+/)
    .filter((token) => token.length > 2);
}

function shingle(value: string, size = 7) {
  const tokens = tokenize(value);
  const result: string[] = [];
  for (let index = 0; index <= tokens.length - size; index += 1) {
    result.push(tokens.slice(index, index + size).join(" "));
  }
  return result;
}

function jaccardSimilarity(left: string[], right: string[]) {
  const leftSet = new Set(left);
  const rightSet = new Set(right);
  const intersection = [...leftSet].filter((token) => rightSet.has(token)).length;
  const union = new Set([...leftSet, ...rightSet]).size;
  return union === 0 ? 0 : intersection / union;
}

function loadPages() {
  return fs.readdirSync(PAGES_DIR)
    .filter((file) => file.endsWith(".json"))
    .map((file) => JSON.parse(fs.readFileSync(path.join(PAGES_DIR, file), "utf8")) as SEOPage)
    .filter((page) => page.pageFamily && page.pageFamily !== "legacy");
}

function validateCounts(pages: SEOPage[], errors: string[]) {
  const counts = pages.reduce<Record<string, number>>((acc, page) => {
    acc[page.pageFamily ?? "unknown"] = (acc[page.pageFamily ?? "unknown"] ?? 0) + 1;
    return acc;
  }, {});

  const expectations: Record<string, number> = {
    "service-intent": 120,
    "ceremony-decision": 80,
    "pandit-journey": 70,
    "consultation-intent": 60,
    "trust-comparison": 45,
    "voice-faq": 45,
    "intl-country-hub": 5,
    "intl-city-hub": 20,
    "intl-service": 100,
    "refreshed-core-service": 30,
    "refreshed-global-brand": 10,
  };

  Object.entries(expectations).forEach(([family, expected]) => {
    if ((counts[family] ?? 0) !== expected) {
      errors.push(`Expected ${expected} pages for ${family}, found ${counts[family] ?? 0}.`);
    }
  });
}

function validatePage(page: SEOPage, errors: string[]) {
  const normalizedContent = textOnly(page.content);
  const isRefreshPage = page.pageFamily?.startsWith("refreshed-") ?? false;
  if ((page.outline?.length ?? 0) < 3) {
    errors.push(`${page.slug}: outline is too short.`);
  }
  if ((page.faqs?.length ?? 0) < 4) {
    errors.push(`${page.slug}: fewer than 4 FAQs.`);
  }
  if (!isRefreshPage && (page.requiredInternalLinks?.length ?? 0) === 0) {
    errors.push(`${page.slug}: missing required internal links.`);
  } else if (!isRefreshPage) {
    page.requiredInternalLinks?.forEach((url) => {
      if (!page.content.includes(`href="${url}"`)) {
        errors.push(`${page.slug}: missing required link ${url} in content.`);
      }
    });
  }
  if ((page.speakableFaqs?.length ?? 0) < 2 && page.pageFamily === "voice-faq") {
    errors.push(`${page.slug}: voice FAQ page missing speakable FAQ coverage.`);
  }
  BANNED_PHRASES.forEach((phrase) => {
    if (normalizedContent.includes(phrase.toLowerCase())) {
      errors.push(`${page.slug}: contains banned phrase "${phrase}".`);
    }
  });
}

function validateSimilarity(pages: SEOPage[], errors: string[]) {
  const grouped = pages.reduce<Record<string, SEOPage[]>>((acc, page) => {
    const signature = `${tokenize(page.title).join("|")}::${tokenize(page.primaryKeyword).join("|")}`;
    acc[signature] = acc[signature] ?? [];
    acc[signature].push(page);
    return acc;
  }, {});

  Object.values(grouped).forEach((group) => {
    const prepared = group.map((page) => ({
      page,
      contentShingles: shingle(page.content),
      titleTokens: tokenize(page.title),
      keywordTokens: tokenize(page.primaryKeyword),
    }));

    for (let index = 0; index < prepared.length; index += 1) {
      const left = prepared[index];
      for (let compareIndex = index + 1; compareIndex < prepared.length; compareIndex += 1) {
        const right = prepared[compareIndex];
        const similarity = jaccardSimilarity(left.contentShingles, right.contentShingles);
        if (similarity > 0.92) {
          errors.push(`${left.page.slug} and ${right.page.slug} are too similar (${similarity.toFixed(2)}).`);
        }
      }
    }
  });
}

function main() {
  const pages = loadPages();
  const errors: string[] = [];

  validateCounts(pages, errors);
  pages.forEach((page) => validatePage(page, errors));
  validateSimilarity(pages, errors);

  if (errors.length > 0) {
    console.error("SEO v2 QA failed:");
    errors.slice(0, 100).forEach((error) => console.error(`- ${error}`));
    process.exit(1);
  }

  console.log(`SEO v2 QA passed for ${pages.length} generated or refreshed pages.`);
}

main();

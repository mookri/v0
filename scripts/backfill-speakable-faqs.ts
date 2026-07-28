// scripts/backfill-speakable-faqs.ts
// Idempotent backfill that derives speakableFaqs from faqs for every
// content page that is missing the editorially-curated field.
//
// Safe to run repeatedly: pages that already have speakableFaqs are
// skipped; only pages with faqs but no speakableFaqs are touched.
//
// Usage:
//   npx tsx scripts/backfill-speakable-faqs.ts
//   npx tsx scripts/backfill-speakable-faqs.ts --dry-run
//
// Exit code 0 on success, 1 on failure. Designed to be run in CI and
// on local dev without side effects.

import { readFileSync, readdirSync, writeFileSync } from "node:fs";
import { join } from "node:path";
import { parseJsonString } from "../lib/content/json";
import { deriveSpeakableFaqs } from "../lib/seo/aeo";
import type { SEOPage, SpeakableFAQEntry } from "../lib/content/types";

const PAGES_DIR = join(process.cwd(), "content", "pages");
const DRY_RUN = process.argv.includes("--dry-run");

interface BackfillStats {
  total: number;
  alreadyHad: number;
  derived: number;
  skipped: number;
  errors: Array<{ file: string; error: string }>;
}

function loadPages(): string[] {
  return readdirSync(PAGES_DIR).filter((f) => f.endsWith(".json"));
}

function backfillPage(file: string): { action: "skip" | "already" | "derived"; count: number } {
  const path = join(PAGES_DIR, file);
  const raw = readFileSync(path, "utf-8");
  const page = parseJsonString<SEOPage>(raw);

  if (!page || !page.slug) {
    return { action: "skip", count: 0 };
  }

  if (page.speakableFaqs && page.speakableFaqs.length > 0) {
    return { action: "already", count: page.speakableFaqs.length };
  }

  if (!page.faqs || page.faqs.length === 0) {
    return { action: "skip", count: 0 };
  }

  const derived: SpeakableFAQEntry[] = deriveSpeakableFaqs(page.faqs);
  const updated: SEOPage = {
    ...page,
    speakableFaqs: derived,
  };

  if (!DRY_RUN) {
    writeFileSync(path, JSON.stringify(updated, null, 2) + "\n", "utf-8");
  }

  return { action: "derived", count: derived.length };
}

function main(): void {
  const files = loadPages();
  const stats: BackfillStats = {
    total: files.length,
    alreadyHad: 0,
    derived: 0,
    skipped: 0,
    errors: [],
  };

  for (const file of files) {
    try {
      const { action, count } = backfillPage(file);
      if (action === "already") {
        stats.alreadyHad += 1;
        console.log(`  ✓ ${file}: already has ${count} speakable FAQ(s)`);
      } else if (action === "derived") {
        stats.derived += 1;
        console.log(`  + ${file}: derived ${count} speakable FAQ(s)${DRY_RUN ? " (dry-run)" : ""}`);
      } else {
        stats.skipped += 1;
      }
    } catch (err) {
      const message = err instanceof Error ? err.message : String(err);
      stats.errors.push({ file, error: message });
      console.error(`  ✗ ${file}: ${message}`);
    }
  }

  console.log("");
  console.log("Backfill summary");
  console.log("================");
  console.log(`Total files:    ${stats.total}`);
  console.log(`Already had:    ${stats.alreadyHad}`);
  console.log(`Derived:        ${stats.derived}`);
  console.log(`Skipped:        ${stats.skipped}`);
  console.log(`Errors:         ${stats.errors.length}`);
  if (DRY_RUN) {
    console.log("(dry run — no files were modified)");
  }

  if (stats.errors.length > 0) {
    process.exit(1);
  }
}

main();

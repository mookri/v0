import { readdirSync, readFileSync } from "fs";
import { join } from "path";
import { parseJsonString } from "@/lib/content/json";
import { resolveSlugFromParams } from "@/lib/content/loader";
import { getAllSlugs, getRegistryEntry } from "@/lib/content/registry";
import type { ContentRegistry, SEOPage } from "@/lib/content/types";

const PAGES_DIR = join(process.cwd(), "content", "pages");
const REGISTRY_PATH = join(process.cwd(), "content", "registry.json");

describe("content registry sync", () => {
  const pageFiles = readdirSync(PAGES_DIR).filter((file) => file.endsWith(".json")).sort();

  it("indexes every content page file from disk", () => {
    expect(getAllSlugs().length).toBe(pageFiles.length);
  });

  it("keeps slug to file mappings aligned with page JSON", () => {
    for (const file of pageFiles) {
      const raw = readFileSync(join(PAGES_DIR, file), "utf-8");
      const page = parseJsonString<SEOPage>(raw);
      const entry = getRegistryEntry(page.slug);

      expect(entry).toBeTruthy();
      expect(entry?.file).toBe(file);
      expect(entry?.cluster).toBe(page.cluster);
    }
  });

  it("keeps registry.json aligned with content/pages", () => {
    const registryRaw = readFileSync(REGISTRY_PATH, "utf-8");
    const registry = parseJsonString<ContentRegistry>(registryRaw);

    expect(Object.keys(registry.pages).length).toBe(pageFiles.length);

    for (const file of pageFiles) {
      const raw = readFileSync(join(PAGES_DIR, file), "utf-8");
      const page = parseJsonString<SEOPage>(raw);
      const entry = registry.pages[page.slug];

      expect(entry).toBeTruthy();
      expect(entry.file).toBe(file);
      expect(entry.cluster).toBe(page.cluster);
    }
  });

  it("only resolves a slug when the requested cluster path matches", () => {
    expect(resolveSlugFromParams(["ceremonies", "hindu-wedding-ceremony-guide"])).toBe(
      "hindu-wedding-ceremony-guide",
    );
    expect(resolveSlugFromParams(["brand", "hindu-wedding-ceremony-guide"])).toBeNull();
    expect(resolveSlugFromParams(["festival-rituals", "durga-puja-ceremony-guide"])).toBe(
      "durga-puja-ceremony-guide",
    );
  });
});

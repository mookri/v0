import { getAllPages } from "@/lib/content/loader";

const MOJIBAKE_PATTERNS = [/â‚¹/i, /Ã¢â€šÂ¹/i, /â€“/i, /Ã¢â‚¬â€œ/i, /â€”/i];

describe("seo content encoding hygiene", () => {
  it("does not expose mojibake in normalized content or metadata", () => {
    const pages = getAllPages();

    for (const page of pages) {
      const searchableText = [
        page.title,
        page.metaTitle,
        page.metaDescription,
        page.content,
        JSON.stringify(page.serviceDetails ?? {}),
      ].join(" ");

      for (const pattern of MOJIBAKE_PATTERNS) {
        expect(searchableText).not.toMatch(pattern);
      }
    }
  });
});

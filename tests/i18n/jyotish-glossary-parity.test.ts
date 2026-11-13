import { describe, expect, it } from "vitest";
import {
  JYOTISH_GLOSSARY,
  JYOTISH_LOCALES,
  translateJyotishTerm,
} from "@navdhya/i18n";

describe("Jyotish Glossary Parity (T-JY10-09)", () => {
  it("contains all 11 supported pan-Indian locales", () => {
    expect(JYOTISH_LOCALES).toHaveLength(11);
    expect(JYOTISH_LOCALES).toContain("hi");
    expect(JYOTISH_LOCALES).toContain("ta");
    expect(JYOTISH_LOCALES).toContain("te");
    expect(JYOTISH_LOCALES).toContain("bn");
    expect(JYOTISH_LOCALES).toContain("mr");
    expect(JYOTISH_LOCALES).toContain("gu");
    expect(JYOTISH_LOCALES).toContain("kn");
    expect(JYOTISH_LOCALES).toContain("ml");
    expect(JYOTISH_LOCALES).toContain("pa");
    expect(JYOTISH_LOCALES).toContain("or");
  });

  it("ensures every term has non-empty translations across all 11 locales with zero missing keys", () => {
    for (const term of JYOTISH_GLOSSARY) {
      for (const locale of JYOTISH_LOCALES) {
        const translation = term.translations[locale];
        expect(translation, `Missing translation for ${term.key} in locale ${locale}`).toBeDefined();
        expect(translation.trim().length, `Empty translation for ${term.key} in locale ${locale}`).toBeGreaterThan(0);
      }
    }
  });

  it("translates known canonical terms accurately", () => {
    expect(translateJyotishTerm("graha.surya", "hi")).toBe("सूर्य");
    expect(translateJyotishTerm("graha.surya", "ta")).toBe("சூரியன்");
    expect(translateJyotishTerm("rashi.mesha", "bn")).toBe("মেষ");
    expect(translateJyotishTerm("panchang.tithi", "te")).toBe("తిథి");
  });

  it("falls back cleanly to English or key for unknown terms", () => {
    expect(translateJyotishTerm("unknown.term", "hi")).toBe("unknown.term");
  });
});


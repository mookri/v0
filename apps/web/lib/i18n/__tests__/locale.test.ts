import { promises as fs } from "fs";
import { join } from "path";
import {
  DEFAULT_LOCALE,
  HREFLANG_BY_LOCALE,
  LOCALES,
  LOCALE_LABELS,
  isLocale,
  isProductionReady,
  localizePath,
  localePrefix,
  resolveLocaleFromPath,
  stripLocalePrefix,
  type Locale,
} from "@/lib/i18n/locale";
import { _resetMessageCache, flattenKeys, get, getWithFallback, loadMessages } from "@/lib/i18n/messages";

const MESSAGES_DIR = join(process.cwd(), "messages");

describe("locale config", () => {
  it("LOCALES contains en and hi", () => {
    expect(LOCALES).toContain("en");
    expect(LOCALES).toContain("hi");
  });

  it("DEFAULT_LOCALE is en", () => {
    expect(DEFAULT_LOCALE).toBe("en");
  });

  it("localePrefix returns empty for default and prefix for others", () => {
    expect(localePrefix("en")).toBe("");
    expect(localePrefix("hi")).toBe("/hi");
  });

  it("HREFLANG_BY_LOCALE includes en-IN and hi-IN", () => {
    expect(HREFLANG_BY_LOCALE.en).toBe("en-IN");
    expect(HREFLANG_BY_LOCALE.hi).toBe("hi-IN");
  });

  it("LOCALE_LABELS has native script for hi", () => {
    expect(LOCALE_LABELS.hi.native).toMatch(/[\u0900-\u097F]/);
  });
});

describe("isLocale type guard", () => {
  it("accepts known locales", () => {
    expect(isLocale("en")).toBe(true);
    expect(isLocale("hi")).toBe(true);
  });
  it("rejects unknown", () => {
    expect(isLocale("fr")).toBe(false);
    expect(isLocale(42)).toBe(false);
    expect(isLocale(null)).toBe(false);
    expect(isLocale(undefined)).toBe(false);
  });
});

describe("resolveLocaleFromPath", () => {
  it("returns en for root", () => {
    expect(resolveLocaleFromPath("/")).toBe("en");
  });
  it("returns en for non-prefixed path", () => {
    expect(resolveLocaleFromPath("/pujas/foo")).toBe("en");
    expect(resolveLocaleFromPath("/find-pandit")).toBe("en");
  });
  it("returns hi for /hi/* paths", () => {
    expect(resolveLocaleFromPath("/hi")).toBe("hi");
    expect(resolveLocaleFromPath("/hi/pujas/foo")).toBe("hi");
  });
  it("tolerates BCP-47 prefix segments", () => {
    expect(resolveLocaleFromPath("/en-US/pujas/foo")).toBe("en");
  });
  it("is case-insensitive", () => {
    expect(resolveLocaleFromPath("/HI/pujas/foo")).toBe("hi");
  });
  it("handles empty string", () => {
    expect(resolveLocaleFromPath("")).toBe("en");
  });
});

describe("stripLocalePrefix", () => {
  it("leaves non-prefixed path alone", () => {
    expect(stripLocalePrefix("/pujas/foo")).toBe("/pujas/foo");
  });
  it("strips /hi prefix", () => {
    expect(stripLocalePrefix("/hi/pujas/foo")).toBe("/pujas/foo");
  });
  it("handles root", () => {
    expect(stripLocalePrefix("/")).toBe("/");
    expect(stripLocalePrefix("/hi")).toBe("/");
  });
});

describe("localizePath", () => {
  it("returns path unchanged for default locale", () => {
    expect(localizePath("/pujas/foo", "en")).toBe("/pujas/foo");
  });
  it("prefixes for non-default locale", () => {
    expect(localizePath("/pujas/foo", "hi")).toBe("/hi/pujas/foo");
  });
  it("handles root", () => {
    expect(localizePath("/", "en")).toBe("/");
    expect(localizePath("/", "hi")).toBe("/hi");
  });
  it("does not double-prefix", () => {
    expect(localizePath("/hi/pujas/foo", "hi")).toBe("/hi/pujas/foo");
  });
});

describe("isProductionReady", () => {
  it("en is production-ready", () => {
    expect(isProductionReady("en")).toBe(true);
  });
  it("hi is not yet production-ready (scaffold only)", () => {
    expect(isProductionReady("hi")).toBe(false);
  });
});

describe("loadMessages + get", () => {
  beforeEach(() => _resetMessageCache());

  it("loads messages/en.json from disk", async () => {
    const en = await loadMessages("en");
    expect(typeof en).toBe("object");
    expect(en.nav).toBeDefined();
  });

  it("loads messages/hi.json from disk", async () => {
    const hi = await loadMessages("hi");
    expect(typeof hi).toBe("object");
    expect(hi.nav).toBeDefined();
  });

  it("get returns the leaf string for a known key", async () => {
    const en = await loadMessages("en");
    expect(get(en, "nav.home")).toBe("Home");
    expect(get(en, "common.bookNow")).toBe("Book now");
  });

  it("get returns undefined for an unknown key", async () => {
    const en = await loadMessages("en");
    expect(get(en, "nav.does.not.exist")).toBeUndefined();
    expect(get(en, "totally.missing")).toBeUndefined();
  });

  it("get returns Devanagari for the hi bundle", async () => {
    const hi = await loadMessages("hi");
    const value = get(hi, "nav.home");
    expect(value).toMatch(/[\u0900-\u097F]/);
  });
});

describe("getWithFallback", () => {
  beforeEach(() => _resetMessageCache());

  it("returns the locale value when present", async () => {
    const v = await getWithFallback("nav.home", "en");
    expect(v).toBe("Home");
  });
  it("returns undefined when missing in both bundles", async () => {
    const v = await getWithFallback("nav.does.not.exist", "en");
    expect(v).toBeUndefined();
  });
});

describe("flattenKeys", () => {
  it("produces dotted keys for every leaf", () => {
    const bundle = {
      a: { b: "x", c: { d: "y" } },
      e: "z",
    };
    expect(flattenKeys(bundle).sort()).toEqual(["a.b", "a.c.d", "e"]);
  });
});

describe("en and hi bundles are structurally aligned", () => {
  beforeEach(() => _resetMessageCache());

  it("hi has the same leaf-key set as en", async () => {
    const en = await loadMessages("en");
    const hi = await loadMessages("hi");
    const enKeys = flattenKeys(en).sort();
    const hiKeys = flattenKeys(hi).sort();
    expect(hiKeys).toEqual(enKeys);
  });
});

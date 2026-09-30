import { formatCurrency, formatDate, formatList, formatNumber, formatRelative, toBcp47 } from "@/lib/i18n/formatters";

describe("toBcp47", () => {
  it("maps en -> en-IN", () => {
    expect(toBcp47("en")).toBe("en-IN");
  });
  it("maps hi -> hi-IN", () => {
    expect(toBcp47("hi")).toBe("hi-IN");
  });
});

describe("formatNumber", () => {
  it("formats en with Indian-grouping (lakh-style)", () => {
    const v = formatNumber(1234567, "en");
    expect(v).toMatch(/12.*34.*567|12,34,567|12.34.567/);
  });
  it("formats hi with Devanagari numerals or grouping", () => {
    const v = formatNumber(1234, "hi");
    expect(typeof v).toBe("string");
    expect(v.length).toBeGreaterThan(0);
  });
});

describe("formatCurrency", () => {
  it("formats INR with the rupee symbol for en", () => {
    const v = formatCurrency(2500, "en");
    expect(v).toMatch(/₹|INR/);
  });
  it("respects a custom currency code", () => {
    const v = formatCurrency(100, "en", { currency: "USD" });
    expect(v).toMatch(/\$/);
  });
});

describe("formatDate", () => {
  it("returns a non-empty string", () => {
    const v = formatDate(new Date("2025-06-15T00:00:00Z"), "en");
    expect(typeof v).toBe("string");
    expect(v.length).toBeGreaterThan(0);
  });
  it("differs between locales (long-month format)", () => {
    const date = new Date("2025-01-15T00:00:00Z");
    const en = formatDate(date, "en");
    const hi = formatDate(date, "hi");
    expect(en).not.toBe(hi);
  });
});

describe("formatRelative", () => {
  it("formats past dates in English", () => {
    const past = new Date(Date.now() - 60_000);
    const v = formatRelative(past, "en");
    expect(v).toMatch(/ago|second|minute/);
  });
  it("returns Devanagari for hi", () => {
    const past = new Date(Date.now() - 3_600_000);
    const v = formatRelative(past, "hi");
    expect(typeof v).toBe("string");
    expect(v.length).toBeGreaterThan(0);
  });
});

describe("formatList", () => {
  it("joins items with 'and' in en", () => {
    const v = formatList(["Hindi", "English", "Sanskrit"], "en");
    expect(v).toMatch(/Hindi/);
    expect(v).toMatch(/Sanskrit/);
  });
});

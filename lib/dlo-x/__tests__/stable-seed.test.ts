import { deriveStableSeed } from "@/lib/dlo-x/stable-seed";

class FakeHeaders {
  constructor(private readonly map: Record<string, string>) {}
  get(name: string): string | null {
    const v = this.map[name.toLowerCase()];
    return v ?? null;
  }
}

describe("deriveStableSeed", () => {
  it("returns explicit x-seed when provided", () => {
    const h = new FakeHeaders({ "x-seed": "test-seed-123" });
    expect(deriveStableSeed(h)).toBe("test-seed-123");
  });

  it("returns cookie value when present (sticky across requests)", () => {
    const h = new FakeHeaders({
      cookie: "navdhya-seed=cookie-seed-abc; other=val",
    });
    expect(deriveStableSeed(h)).toBe("cookie-seed-abc");
  });

  it("prefers explicit x-seed over cookie", () => {
    const h = new FakeHeaders({
      "x-seed": "header-wins",
      cookie: "navdhya-seed=cookie-value",
    });
    expect(deriveStableSeed(h)).toBe("header-wins");
  });

  it("is deterministic for same ip + ua on same day", () => {
    const h1 = new FakeHeaders({ "x-forwarded-for": "1.2.3.4", "user-agent": "Mozilla/5.0" });
    const h2 = new FakeHeaders({ "x-forwarded-for": "1.2.3.4", "user-agent": "Mozilla/5.0" });
    const s1 = deriveStableSeed(h1);
    const s2 = deriveStableSeed(h2);
    expect(s1).toBe(s2);
    expect(s1).toMatch(/^[a-f0-9]{24}$/);
  });

  it("produces different seeds for different IPs", () => {
    const a = new FakeHeaders({ "x-forwarded-for": "1.1.1.1", "user-agent": "ua" });
    const b = new FakeHeaders({ "x-forwarded-for": "2.2.2.2", "user-agent": "ua" });
    expect(deriveStableSeed(a)).not.toBe(deriveStableSeed(b));
  });

  it("produces different seeds for different user-agents on same IP", () => {
    const a = new FakeHeaders({ "x-forwarded-for": "1.1.1.1", "user-agent": "Chrome" });
    const b = new FakeHeaders({ "x-forwarded-for": "1.1.1.1", "user-agent": "Firefox" });
    expect(deriveStableSeed(a)).not.toBe(deriveStableSeed(b));
  });

  it("falls back to x-real-ip when x-forwarded-for absent", () => {
    const h = new FakeHeaders({ "x-real-ip": "5.5.5.5", "user-agent": "ua" });
    const v = deriveStableSeed(h);
    expect(v).toMatch(/^[a-f0-9]{24}$/);
  });

  it("never throws and returns a non-empty string for missing headers", () => {
    const h = new FakeHeaders({});
    const v = deriveStableSeed(h);
    expect(typeof v).toBe("string");
    expect(v.length).toBeGreaterThan(0);
  });
});

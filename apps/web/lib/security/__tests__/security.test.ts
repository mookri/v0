import {
  hasPermission,
  hasPermissionStr,
  validateTenant,
  isGlobalTenant,
  removePII,
} from "@/lib/dlo-x/security";
import {
  sanitizePlainText,
  sanitizeMarkdown,
  sanitizeUrl,
} from "@/lib/security/sanitize";
import {
  InProcessRateLimiter,
  clientIp,
} from "@/lib/security/rate-limit";
import { deterministicHash, pickVariant } from "@/lib/dlo-x/ab-testing";
import { DEV_ROLE_COOKIE, isValidDevRole, VALID_DEV_ROLES } from "@/lib/auth/cookie-names";
import {
  acquireScrollLock,
  _resetScrollLockForTests,
} from "@/lib/security/scroll-lock";
import {
  issueCsrfToken,
  validateCsrfToken,
  extractCsrfToken,
  CSRF_CONSTANTS,
} from "@/lib/security/csrf";

describe("dlo-x/security — hasPermission (P0 RBAC)", () => {
  test("User cannot escalate to Admin", () => {
    expect(hasPermission("User", "Admin")).toBe(false);
  });

  test("User cannot escalate to SuperAdmin", () => {
    expect(hasPermission("User", "SuperAdmin")).toBe(false);
  });

  test("Pandit cannot access Manager resources", () => {
    expect(hasPermission("Pandit", "Manager")).toBe(false);
  });

  test("Vendor cannot access Admin resources", () => {
    expect(hasPermission("Vendor", "Admin")).toBe(false);
  });

  test("Admin CAN access Manager resources (lower rank)", () => {
    expect(hasPermission("Admin", "Manager")).toBe(true);
  });

  test("Admin CAN access Admin resources (same rank)", () => {
    expect(hasPermission("Admin", "Admin")).toBe(true);
  });

  test("SuperAdmin can access everything", () => {
    expect(hasPermission("SuperAdmin", "User")).toBe(true);
    expect(hasPermission("SuperAdmin", "Pandit")).toBe(true);
    expect(hasPermission("SuperAdmin", "Vendor")).toBe(true);
    expect(hasPermission("SuperAdmin", "Manager")).toBe(true);
    expect(hasPermission("SuperAdmin", "Admin")).toBe(true);
    expect(hasPermission("SuperAdmin", "SuperAdmin")).toBe(true);
  });

  test("UNKNOWN required role no longer grants access (P0 fix)", () => {
    // P0: previously hasPermission('User', 'SuperAdmin') returned
    // true because BOTH unknowns defaulted to 0, and 0 >= 0.
    expect(hasPermission("User", "SuperAdmin" as never)).toBe(false);
    expect(hasPermission("Admin", "NotARole" as never)).toBe(false);
  });

  test("UNKNOWN user role cannot claim access", () => {
    expect(hasPermission("NotARole" as never, "User")).toBe(false);
    expect(hasPermission("Hacker" as never, "Admin")).toBe(false);
  });

  test("hasPermissionStr accepts plain strings, rejects unknowns", () => {
    expect(hasPermissionStr("Admin", "Manager")).toBe(true);
    expect(hasPermissionStr("User", "SuperAdmin")).toBe(false);
    expect(hasPermissionStr("???", "User")).toBe(false);
  });
});

describe("dlo-x/security — validateTenant (P0 tenant isolation)", () => {
  test("same-tenant access allowed", () => {
    expect(validateTenant("tenant-a", "tenant-a")).toBe(true);
  });

  test("cross-tenant access denied", () => {
    expect(validateTenant("tenant-a", "tenant-b")).toBe(false);
  });

  test("DEFAULT user no longer accesses other tenants (P0 fix)", () => {
    // P0: previously `userTenant === 'default'` returned true for
    // ANY resourceTenant, so a 'default' user could read every
    // tenant's data.
    expect(validateTenant("default", "tenant-a")).toBe(false);
    expect(validateTenant("default", "tenant-b")).toBe(false);
    expect(validateTenant("default", "default")).toBe(true);
  });

  test("empty / missing tenants denied", () => {
    expect(validateTenant("", "tenant-a")).toBe(false);
    expect(validateTenant("tenant-a", "")).toBe(false);
    expect(validateTenant("", "")).toBe(false);
  });

  test("global tenants are bidirectional", () => {
    expect(validateTenant("global", "global")).toBe(true);
    expect(validateTenant("public", "public")).toBe(true);
    expect(validateTenant("system", "system")).toBe(true);
  });

  test("global tenant access to private resource is denied", () => {
    expect(validateTenant("global", "tenant-a")).toBe(false);
    expect(validateTenant("tenant-a", "global")).toBe(false);
  });

  test("isGlobalTenant helper", () => {
    expect(isGlobalTenant("global")).toBe(true);
    expect(isGlobalTenant("public")).toBe(true);
    expect(isGlobalTenant("system")).toBe(true);
    expect(isGlobalTenant("tenant-a")).toBe(false);
  });
});

describe("dlo-x/security — removePII (P1 telemetry)", () => {
  test("redacts known PII fields", () => {
    const result = removePII({
      email: "alice@example.com",
      phone: "+91 98765 43210",
      password: "hunter2",
      firstName: "Alice",
    });
    expect(result.data.email).toBe("[REDACTED]");
    expect(result.data.phone).toBe("[REDACTED]");
    expect(result.data.password).toBe("[REDACTED]");
    expect(result.data.firstName).toBe("[REDACTED]");
  });

  test("preserves non-PII fields", () => {
    const result = removePII({
      event: "booking.created",
      bookingId: "bk-123",
      count: 5,
    });
    expect(result.data).toEqual({
      event: "booking.created",
      bookingId: "bk-123",
      count: 5,
    });
  });

  test("redacts by name pattern (case-insensitive)", () => {
    const result = removePII({
      UserEmail: "x@y.com",
      MobileNo: "98765",
      PAN_NUMBER: "ABCDE1234F",
      API_KEY: "sk-1234",
    });
    expect(result.data.UserEmail).toBe("[EMAIL REDACTED]");
    expect(result.data.MobileNo).toBe("[PHONE REDACTED]");
    expect(result.data.PAN_NUMBER).toBe("[PAN REDACTED]");
    expect(result.data.API_KEY).toBe("[SECRET REDACTED]");
  });

  test("redacts PII in nested objects and arrays", () => {
    const result = removePII({
      booking: {
        customer: {
          email: "x@y.com",
          phone: "+91 12345 67890",
        },
        items: [
          { sku: "p-1", name: "Item" },
          { sku: "p-2", note: "deliver to alice@example.com" },
        ],
      },
    });
    const r = result.data as Record<string, any>;
    expect(r.booking.customer.email).toBe("[REDACTED]");
    expect(r.booking.customer.phone).toBe("[REDACTED]");
    expect(r.booking.items[0].sku).toBe("p-1");
    // value-pattern: the email gets replaced; surrounding text is preserved
    expect(r.booking.items[1].note).toBe("deliver to [EMAIL REDACTED]");
  });

  test("redacts PII embedded in string values", () => {
    const result = removePII({
      message: "Reach me at alice@example.com or +91 98765 43210",
    });
    expect((result.data as any).message).toContain("[EMAIL REDACTED]");
    expect((result.data as any).message).toContain("[PHONE REDACTED]");
  });

  test("redacts Aadhaar / PAN patterns in values", () => {
    const result = removePII({
      note: "Aadhaar 2345 6789 0123 and PAN ABCDE1234F",
    });
    expect((result.data as any).note).toContain("[AADHAAR REDACTED]");
    expect((result.data as any).note).toContain("[PAN REDACTED]");
  });

  test("dryRun does not mutate the input", () => {
    const input = { email: "x@y.com" };
    const result = removePII(input, { dryRun: true });
    expect(input.email).toBe("x@y.com");
    expect(result.data).toBe(input);
    expect(result.log.length).toBeGreaterThan(0);
  });

  test("returns log of redactions", () => {
    const result = removePII({
      email: "a@b.com",
      booking: { phone: "12345" },
    });
    const paths = result.log.map((l) => l.path);
    expect(paths).toContain("email");
    expect(paths).toContain("booking.phone");
  });
});

describe("security/sanitize — sanitizePlainText", () => {
  test("removes control characters", () => {
    const input = "hello\x00\x01\x02world";
    expect(sanitizePlainText(input)).toBe("helloworld");
  });

  test("removes zero-width characters", () => {
    const input = "hello\u200B\u200C\u200D\uFEFFworld";
    expect(sanitizePlainText(input)).toBe("helloworld");
  });

  test("preserves newlines by default", () => {
    expect(sanitizePlainText("line1\nline2")).toBe("line1\nline2");
  });

  test("collapses runs of whitespace", () => {
    expect(sanitizePlainText("a    b\t\tc")).toBe("a b c");
  });

  test("trims leading/trailing whitespace", () => {
    expect(sanitizePlainText("   hello   ")).toBe("hello");
  });

  test("truncates to maxLength", () => {
    expect(sanitizePlainText("abcdef", { maxLength: 3 })).toBe("abc");
  });

  test("non-string input returns empty string", () => {
    expect(sanitizePlainText(null)).toBe("");
    expect(sanitizePlainText(undefined)).toBe("");
    expect(sanitizePlainText(42)).toBe("");
  });

  test("strips non-printable unicode outside the BMP range is preserved (Devanagari, etc.)", () => {
    const devanagari = "नमस्ते दुनिया";
    expect(sanitizePlainText(devanagari)).toBe("नमस्ते दुनिया");
  });
});

describe("security/sanitize — sanitizeMarkdown", () => {
  test("preserves markdown punctuation", () => {
    const input = "**bold** and *italic* and `code` and [link](https://x.com)";
    const out = sanitizeMarkdown(input);
    expect(out).toContain("**bold**");
    expect(out).toContain("[link](https://x.com)");
  });

  test("strips <script> tags", () => {
    const input = "hello <script>alert(1)</script> world";
    expect(sanitizeMarkdown(input)).not.toContain("script");
    expect(sanitizeMarkdown(input)).toContain("hello");
    expect(sanitizeMarkdown(input)).toContain("world");
  });

  test("strips <style> tags", () => {
    const input = "hello <style>body{}</style> world";
    expect(sanitizeMarkdown(input)).not.toContain("style");
  });

  test("strips on*= event-handler attributes", () => {
    const input = '<a href="x" onclick="bad()">click</a>';
    expect(sanitizeMarkdown(input)).not.toMatch(/onclick/i);
  });

  test("strips javascript: in href / src", () => {
    const input = "[bad](javascript:alert(1))";
    expect(sanitizeMarkdown(input)).not.toContain("javascript:");
  });

  test("strips vbscript: in href / src", () => {
    const input = "[bad](vbscript:msgbox)";
    expect(sanitizeMarkdown(input)).not.toMatch(/vbscript:/i);
  });
});

describe("security/sanitize — sanitizeUrl", () => {
  test("accepts https://", () => {
    expect(sanitizeUrl("https://example.com/path")).toBe("https://example.com/path");
  });

  test("accepts http://", () => {
    expect(sanitizeUrl("http://example.com")).toBe("http://example.com/");
  });

  test("accepts mailto:", () => {
    expect(sanitizeUrl("mailto:a@b.com")).toBe("mailto:a@b.com");
  });

  test("accepts tel: and whatsapp:", () => {
    expect(sanitizeUrl("tel:+919876543210")).toBe("tel:+919876543210");
    expect(sanitizeUrl("whatsapp://send?phone=919876543210")).toBe(
      "whatsapp://send?phone=919876543210",
    );
  });

  test("rejects javascript:", () => {
    expect(() => sanitizeUrl("javascript:alert(1)")).toThrow(/scheme not allowed/);
  });

  test("rejects vbscript:", () => {
    expect(() => sanitizeUrl("vbscript:msgbox")).toThrow(/scheme not allowed/);
  });

  test("rejects data: (default)", () => {
    expect(() => sanitizeUrl("data:text/html,<script>alert(1)</script>")).toThrow(
      /data: URLs are not allowed/,
    );
  });

  test("allows data:image/ when allowImageData=true", () => {
    const dataUrl = "data:image/png;base64,iVBORw0KGgo=";
    expect(sanitizeUrl(dataUrl, { allowImageData: true })).toBe(dataUrl);
  });

  test("rejects data:audio/ even with allowImageData=true", () => {
    expect(() =>
      sanitizeUrl("data:audio/mp3;base64,xxx", { allowImageData: true }),
    ).toThrow(/data: URLs are not allowed/);
  });

  test("rejects file:", () => {
    expect(() => sanitizeUrl("file:///etc/passwd")).toThrow(/scheme not allowed/);
  });

  test("rejects garbage", () => {
    expect(() => sanitizeUrl("not a url")).toThrow();
    expect(() => sanitizeUrl("")).toThrow(/empty input/);
    expect(() => sanitizeUrl(null)).toThrow();
    expect(() => sanitizeUrl(42)).toThrow();
  });

  test("strips zero-width / control chars before validation", () => {
    expect(() => sanitizeUrl("https://example.com​")).toThrow();
  });
});

describe("security/rate-limit — InProcessRateLimiter", () => {
  test("allows requests under the limit", () => {
    const rl = new InProcessRateLimiter({ limit: 5, windowMs: 1000 });
    for (let i = 0; i < 5; i++) {
      const r = rl.check("ip-1");
      expect(r.allowed).toBe(true);
      expect(r.limit).toBe(5);
    }
  });

  test("denies requests over the limit", () => {
    const rl = new InProcessRateLimiter({ limit: 3, windowMs: 1000 });
    expect(rl.check("ip-1").allowed).toBe(true);
    expect(rl.check("ip-1").allowed).toBe(true);
    expect(rl.check("ip-1").allowed).toBe(true);
    expect(rl.check("ip-1").allowed).toBe(false);
  });

  test("different keys have independent buckets", () => {
    const rl = new InProcessRateLimiter({ limit: 2, windowMs: 1000 });
    expect(rl.check("a").allowed).toBe(true);
    expect(rl.check("a").allowed).toBe(true);
    expect(rl.check("a").allowed).toBe(false);
    expect(rl.check("b").allowed).toBe(true);
    expect(rl.check("b").allowed).toBe(true);
    expect(rl.check("b").allowed).toBe(false);
  });

  test("cost is charged per call (DoS protection)", () => {
    const rl = new InProcessRateLimiter({ limit: 100, windowMs: 1000 });
    // 30 small requests OK
    for (let i = 0; i < 30; i++) {
      expect(rl.check("ip", 3).allowed).toBe(true);
    }
    // 1 big request (cost=5) would push to 95/100, still allowed
    expect(rl.check("ip", 5).allowed).toBe(true);
    // another big one (100) → 200 > 100, denied
    expect(rl.check("ip", 100).allowed).toBe(false);
  });

  test("window resets after expiry", async () => {
    const rl = new InProcessRateLimiter({ limit: 2, windowMs: 10 });
    expect(rl.check("ip").allowed).toBe(true);
    expect(rl.check("ip").allowed).toBe(true);
    expect(rl.check("ip").allowed).toBe(false);
    await new Promise((r) => setTimeout(r, 20));
    expect(rl.check("ip").allowed).toBe(true);
  });

  test("toHeaders produces standard X-RateLimit-* headers", () => {
    const rl = new InProcessRateLimiter({ limit: 10, windowMs: 1000 });
    const r = rl.check("ip", 3);
    const h = rl.toHeaders(r);
    expect(h["X-RateLimit-Limit"]).toBe("10");
    expect(h["X-RateLimit-Remaining"]).toBe("7");
    expect(h["X-RateLimit-Reset"]).toMatch(/^\d+$/);
  });

  test("rejects invalid config", () => {
    expect(() => new InProcessRateLimiter({ limit: 0, windowMs: 1000 })).toThrow();
    expect(() => new InProcessRateLimiter({ limit: 1, windowMs: 0 })).toThrow();
  });

  test("rejects invalid cost", () => {
    const rl = new InProcessRateLimiter({ limit: 1, windowMs: 1000 });
    expect(() => rl.check("ip", 0)).toThrow();
    expect(() => rl.check("ip", -1)).toThrow();
  });

  test("reset clears all state", () => {
    const rl = new InProcessRateLimiter({ limit: 1, windowMs: 1000 });
    expect(rl.check("a").allowed).toBe(true);
    expect(rl.check("a").allowed).toBe(false);
    rl.reset();
    expect(rl.check("a").allowed).toBe(true);
  });
});

describe("security/rate-limit — clientIp", () => {
  test("prefers first X-Forwarded-For entry", () => {
    const h = new Headers();
    h.set("x-forwarded-for", "1.2.3.4, 10.0.0.1, 10.0.0.2");
    expect(clientIp(h)).toBe("1.2.3.4");
  });

  test("falls back to X-Real-IP", () => {
    const h = new Headers();
    h.set("x-real-ip", "5.6.7.8");
    expect(clientIp(h)).toBe("5.6.7.8");
  });

  test("returns 'unknown' when nothing is set", () => {
    expect(clientIp(new Headers())).toBe("unknown");
  });

  test("trims whitespace", () => {
    const h = new Headers();
    h.set("x-forwarded-for", "  1.2.3.4  , 10.0.0.1");
    expect(clientIp(h)).toBe("1.2.3.4");
  });
});

describe("dlo-x/ab-testing — deterministicHash (P1 weak-hash fix)", () => {
  test("returns 0 for empty / non-string input", () => {
    expect(deterministicHash("")).toBe(0);
    expect(deterministicHash(null as unknown as string)).toBe(0);
    expect(deterministicHash(undefined as unknown as string)).toBe(0);
  });

  test("returns an integer in [0, 100]", () => {
    for (let i = 0; i < 200; i++) {
      const h = deterministicHash(`user-${i}`);
      expect(Number.isInteger(h)).toBe(true);
      expect(h).toBeGreaterThanOrEqual(0);
      expect(h).toBeLessThanOrEqual(100);
    }
  });

  test("is stable for the same input (idempotent)", () => {
    const a = deterministicHash("user-42:experiment-A");
    const b = deterministicHash("user-42:experiment-A");
    expect(a).toBe(b);
  });

  test("SHA-256 mixing: neighbours no longer collide (regression for DJB2)", () => {
    // Old DJB2 had visible clustering: these 4 strings often landed
    // in the same bucket. New SHA-256 + rejection sampling should
    // distribute them across [0, 100].
    const neighbours = ["user-1234", "user-1235", "user-1236", "user-1237"];
    const buckets = new Set(neighbours.map((n) => deterministicHash(n)));
    expect(buckets.size).toBeGreaterThanOrEqual(2);
  });

  test("distribution is roughly uniform (regression for modulo bias)", () => {
    const counts = new Array(101).fill(0);
    const N = 5000;
    for (let i = 0; i < N; i++) {
      counts[deterministicHash(`dist-test-${i}`)]!++;
    }
    const expected = N / 101;
    const max = Math.max(...counts);
    const min = Math.min(...counts);
    // No bucket should be more than 3x expected (rejection sampling
    // gives a very tight bound — we allow 3x to absorb noise)
    expect(max).toBeLessThan(expected * 3);
    expect(min).toBeGreaterThan(0);
  });

  test("handles unicode", () => {
    const h = deterministicHash("नमस्ते-user-1");
    expect(h).toBeGreaterThanOrEqual(0);
    expect(h).toBeLessThanOrEqual(100);
  });
});

describe("dlo-x/ab-testing — pickVariant", () => {
  test("returns one of the supplied variants", () => {
    const variants = ["A", "B", "C"] as const;
    for (let i = 0; i < 50; i++) {
      const v = pickVariant(`user-${i}`, "exp-1", variants);
      expect(variants).toContain(v);
    }
  });

  test("is deterministic per (key, experimentId)", () => {
    const v1 = pickVariant("user-1", "exp-1", ["A", "B"]);
    const v2 = pickVariant("user-1", "exp-1", ["A", "B"]);
    expect(v1).toBe(v2);
  });

  test("different experimentIds can give different results for the same key", () => {
    const variants = ["A", "B", "C", "D"] as const;
    const seen = new Set<string>();
    for (let expId = 0; expId < 5; expId++) {
      seen.add(pickVariant("user-1", `exp-${expId}`, variants));
    }
    expect(seen.size).toBeGreaterThan(1);
  });

  test("respects weights", () => {
    const variants = ["A", "B"] as const;
    const weights = { A: 9, B: 1 } as const;
    let aCount = 0;
    let bCount = 0;
    for (let i = 0; i < 1000; i++) {
      const v = pickVariant(`u${i}`, "w", variants, weights);
      if (v === "A") aCount++;
      else bCount++;
    }
    // A:B should be ~9:1, allow ±10% tolerance
    expect(aCount).toBeGreaterThan(800);
    expect(aCount).toBeLessThan(980);
    expect(bCount).toBeGreaterThan(20);
  });

  test("throws on empty variants", () => {
    expect(() => pickVariant("u", "e", [] as readonly string[])).toThrow();
  });

  test("throws on all-zero weights", () => {
    expect(() =>
      pickVariant("u", "e", ["A", "B"], { A: 0, B: 0 }),
    ).toThrow();
  });
});

describe("auth/cookie-names — single source of truth", () => {
  test("DEV_ROLE_COOKIE is the canonical string", () => {
    expect(DEV_ROLE_COOKIE).toBe("navdhya-dev-role");
  });

  test("VALID_DEV_ROLES contains all 6 roles", () => {
    expect(VALID_DEV_ROLES).toEqual([
      "User",
      "Pandit",
      "Vendor",
      "Manager",
      "Admin",
      "SuperAdmin",
    ]);
  });

  test("isValidDevRole accepts known roles", () => {
    expect(isValidDevRole("User")).toBe(true);
    expect(isValidDevRole("Admin")).toBe(true);
    expect(isValidDevRole("SuperAdmin")).toBe(true);
  });

  test("isValidDevRole rejects unknown / empty / nullish", () => {
    expect(isValidDevRole("hacker")).toBe(false);
    expect(isValidDevRole("")).toBe(false);
    expect(isValidDevRole(null)).toBe(false);
    expect(isValidDevRole(undefined)).toBe(false);
  });
});

describe("security/scroll-lock — ref-counted body lock", () => {
  beforeEach(() => {
    _resetScrollLockForTests();
    document.body.style.overflow = "";
    document.body.style.maxHeight = "";
    document.documentElement.style.overflow = "";
    document.documentElement.style.maxHeight = "";
  });

  test("first acquirer sets body overflow to hidden", () => {
    const a = acquireScrollLock();
    expect(document.body.style.overflow).toBe("hidden");
    expect(document.body.style.maxHeight).toBe("100vh");
    a.release();
    expect(document.body.style.overflow).toBe("");
  });

  test("two acquirers release in LIFO order without leaking", () => {
    const a = acquireScrollLock();
    const b = acquireScrollLock();
    expect(document.body.style.overflow).toBe("hidden");
    b.release();
    // Body should still be locked — outer acquirer is still active
    expect(document.body.style.overflow).toBe("hidden");
    a.release();
    // Now both released — restored to original (empty)
    expect(document.body.style.overflow).toBe("");
  });

  test("inner release does NOT release outer's lock (regression)", () => {
    // Reproduces the original bug: two layouts mount, inner unmounts
    // first and clobbers the body overflow, leaving the outer layout
    // with a now-unlocked page.
    document.body.style.overflow = "scroll"; // pretend the app set this
    const a = acquireScrollLock();
    const b = acquireScrollLock();
    expect(document.body.style.overflow).toBe("hidden");
    b.release();
    // Outer still wants the lock; body MUST still be hidden
    expect(document.body.style.overflow).toBe("hidden");
    a.release();
    // After outer release, restored to "scroll" (the pre-lock value)
    expect(document.body.style.overflow).toBe("scroll");
  });

  test("release is idempotent", () => {
    const a = acquireScrollLock();
    a.release();
    a.release(); // second call is a no-op
    a.release(); // third call is a no-op
    expect(document.body.style.overflow).toBe("");
  });

  test("lockHtml option also locks <html>", () => {
    const a = acquireScrollLock({ lockHtml: true });
    expect(document.documentElement.style.overflow).toBe("hidden");
    expect(document.documentElement.style.maxHeight).toBe("100vh");
    a.release();
    expect(document.documentElement.style.overflow).toBe("");
  });

  test("capHeight: false skips maxHeight", () => {
    const a = acquireScrollLock({ capHeight: false });
    expect(document.body.style.overflow).toBe("hidden");
    expect(document.body.style.maxHeight).toBe("");
    a.release();
  });

  test("preserves pre-existing maxHeight", () => {
    document.body.style.maxHeight = "500px";
    const a = acquireScrollLock();
    a.release();
    expect(document.body.style.maxHeight).toBe("500px");
  });
});

describe("security/csrf — issueCsrfToken", () => {
  test("issues a token with the expected shape", () => {
    const result = issueCsrfToken();
    expect(result.token.split(".").length).toBe(3);
    expect(result.cookieAttributes).toContain(`${CSRF_CONSTANTS.COOKIE}=`);
    expect(result.cookieAttributes).toContain("Path=/");
    expect(result.cookieAttributes).toContain("SameSite=Lax");
    expect(result.expiresAt).toBeGreaterThan(Date.now());
  });

  test("two issues produce different tokens (random nonce)", () => {
    const a = issueCsrfToken();
    const b = issueCsrfToken();
    expect(a.token).not.toBe(b.token);
  });

  test("respects custom ttlMs", () => {
    const result = issueCsrfToken({ ttlMs: 60_000 });
    expect(result.expiresAt - Date.now()).toBeLessThanOrEqual(60_000);
    expect(result.expiresAt - Date.now()).toBeGreaterThan(50_000);
  });
});

describe("security/csrf — validateCsrfToken", () => {
  test("accepts a fresh matching pair", () => {
    const { token } = issueCsrfToken();
    const v = validateCsrfToken(token, token);
    expect(v.ok).toBe(true);
  });

  test("rejects when cookie is missing", () => {
    const { token } = issueCsrfToken();
    const v = validateCsrfToken(undefined, token);
    expect(v.ok).toBe(false);
    expect(v.reason).toBe("missing-cookie");
  });

  test("rejects when header is missing", () => {
    const { token } = issueCsrfToken();
    const v = validateCsrfToken(token, undefined);
    expect(v.ok).toBe(false);
    expect(v.reason).toBe("missing-header");
  });

  test("rejects when cookie and header mismatch", () => {
    const a = issueCsrfToken();
    const b = issueCsrfToken();
    const v = validateCsrfToken(a.token, b.token);
    expect(v.ok).toBe(false);
    expect(v.reason).toBe("mismatch");
  });

  test("rejects an expired token", () => {
    const { token } = issueCsrfToken({ ttlMs: -1 });
    const v = validateCsrfToken(token, token);
    expect(v.ok).toBe(false);
    expect(v.reason).toBe("expired");
  });

  test("rejects a malformed token", () => {
    const v = validateCsrfToken("not-a-real-token", "not-a-real-token");
    expect(v.ok).toBe(false);
    expect(v.reason).toBe("malformed-token");
  });

  test("rejects a token with tampered signature", () => {
    const { token } = issueCsrfToken();
    const parts = token.split(".");
    const tampered = `${parts[0]}.${parts[1]}.AAAA${parts[2]?.slice(4)}`;
    const v = validateCsrfToken(tampered, tampered);
    expect(v.ok).toBe(false);
    // mismatch takes precedence over signature-mismatch (the
    // comparison is `cookie !== header` first; here they're equal)
    // — actually since both are tampered, they match and the
    // signature check fails. We just check the outcome.
    expect(["signature-mismatch", "mismatch"]).toContain(v.reason);
  });

  test("rejects a token signed with a different secret", () => {
    // Build a token that looks right structurally but with a wrong sig
    const { token: realToken } = issueCsrfToken();
    const parts = realToken.split(".");
    const fakeSig = "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA";
    const forged = `${parts[0]}.${parts[1]}.${fakeSig}`;
    const v = validateCsrfToken(forged, forged);
    expect(v.ok).toBe(false);
    expect(v.reason).toBe("signature-mismatch");
  });
});

describe("security/csrf — extractCsrfToken", () => {
  test("extracts cookie + header from a request", () => {
    const { token } = issueCsrfToken();
    const headers = new Headers();
    headers.set("x-csrf-token", token);
    const cookies = { get: (name: string) => (name === "navdhya-csrf" ? { value: token } : undefined) };
    const out = extractCsrfToken({ headers, cookies });
    expect(out.cookie).toBe(token);
    expect(out.header).toBe(token);
  });

  test("returns undefined when header is absent", () => {
    const out = extractCsrfToken({ headers: new Headers() });
    expect(out.cookie).toBeUndefined();
    expect(out.header).toBeUndefined();
  });
});

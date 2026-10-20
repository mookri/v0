/**
 * @jest-environment node
 */
import { NextRequest } from "next/server";
import { proxy } from "@/proxy";
import { sessionCookieName } from "@/server/session-cookie";
import { DEV_ROLE_COOKIE } from "@/lib/auth/cookie-names";

// The W1 dashboard redirect (production-only) plus its interaction with the
// strangler flag gate. Env is set per-test because proxy reads process.env
// at request time.

const SESSION_COOKIE_HEADER = `${sessionCookieName()}=test-session-token`;

function req(path: string, cookie?: string): NextRequest {
  const headers: Record<string, string> = {};
  if (cookie) headers.cookie = cookie;
  return new NextRequest(`http://localhost${path}`, { headers });
}

function withEnv<T>(env: Record<string, string | undefined>, fn: () => T): T {
  // Always neutralise the strangler gate too — an ambient shell value must
  // not turn a "serves locally" assertion into a 307.
  const full: Record<string, string | undefined> = {
    STRANGLER_CUSTOMER_LIVE: undefined,
    STRANGLER_CUSTOMER_BOOK: undefined,
    STRANGLER_CUSTOMER_USER: undefined,
    STRANGLER_CUSTOMER_SETTINGS: undefined,
    ...env,
  };
  const previous: Record<string, string | undefined> = {};
  for (const [key, value] of Object.entries(full)) {
    previous[key] = process.env[key];
    if (value === undefined) delete process.env[key];
    else process.env[key] = value;
  }
  try {
    return fn();
  } finally {
    for (const [key, value] of Object.entries(previous)) {
      if (value === undefined) delete process.env[key];
      else process.env[key] = value;
    }
  }
}

describe("proxy W1 dashboard redirect", () => {
  it("is off by default (dev/staging serve locally)", () => {
    withEnv({ NEXT_PUBLIC_PRODUCTION_REDIRECTS: undefined }, () => {
      // A console path needs a credential now (see the guard suite below), so
      // this asserts the redirect flag is off — not that /user is open.
      const response = proxy(req("/user/bookings", SESSION_COOKIE_HEADER));
      expect(response.status).toBe(200);
      expect(response.headers.get("location")).toBeNull();
    });
  });

  it("redirects dashboards to the customer origin when enabled", () => {
    withEnv({ NEXT_PUBLIC_PRODUCTION_REDIRECTS: "true" }, () => {
      const response = proxy(req("/user/bookings"));
      expect(response.status).toBe(301);
      expect(response.headers.get("location")).toBe("https://app.navdhya.com/user/bookings");
    });
  });

  it("matches the bare path and every dashboard family", () => {
    withEnv({ NEXT_PUBLIC_PRODUCTION_REDIRECTS: "true" }, () => {
      for (const path of ["/user", "/user/", "/pandit/jobs", "/vendor/x", "/manager/team"]) {
        expect(proxy(req(path)).status).toBe(301);
      }
    });
  });

  it("carries the query string so deep links survive", () => {
    withEnv({ NEXT_PUBLIC_PRODUCTION_REDIRECTS: "true" }, () => {
      const response = proxy(req("/user?tab=bookings&page=2"));
      expect(response.headers.get("location")).toBe(
        "https://app.navdhya.com/user?tab=bookings&page=2",
      );
    });
  });

  it("does not redirect other paths", () => {
    withEnv({ NEXT_PUBLIC_PRODUCTION_REDIRECTS: "true" }, () => {
      // /username and /pandits are not dashboard classes.
      for (const path of ["/", "/pujas/griha-pravesh", "/username", "/pandits"]) {
        expect(proxy(req(path)).status).toBe(200);
      }
    });
  });

  it("honours NEXT_PUBLIC_CUSTOMER_ORIGIN over the default", () => {
    withEnv(
      {
        NEXT_PUBLIC_PRODUCTION_REDIRECTS: "true",
        NEXT_PUBLIC_CUSTOMER_ORIGIN: "https://customer.test",
      },
      () => {
        const response = proxy(req("/user/bookings"));
        expect(response.status).toBe(301);
        expect(response.headers.get("location")).toBe("https://customer.test/user/bookings");
      },
    );
  });

  it("prefix matching is segment-safe (no /usertools leak)", () => {
    withEnv({ NEXT_PUBLIC_PRODUCTION_REDIRECTS: "true" }, () => {
      expect(proxy(req("/usertools")).status).toBe(200);
    });
  });
});

// W0 console access guard — G1 in docs/PLATFORM-FEATURES-AND-CONSOLE-PLAN.md.
// The role consoles were reachable by anyone (no edge guard at all); the guard
// now fails closed on a missing credential.
describe("proxy console access guard (W0)", () => {
  const CONSOLE_PATHS = [
    "/admin/users",
    "/platform/roles",
    "/manager/operations",
    "/vendor/orders",
    "/pandit/leads",
    "/user/bookings",
  ];

  it("redirects an anonymous visitor on every console to /login with next", () => {
    withEnv({}, () => {
      for (const path of CONSOLE_PATHS) {
        const response = proxy(req(path));
        expect(response.status).toBe(302);
        expect(response.headers.get("location")).toBe(
          `http://localhost/login?next=${encodeURIComponent(path)}`,
        );
      }
    });
  });

  it("admits a session-cookie holder", () => {
    withEnv({}, () => {
      for (const path of CONSOLE_PATHS) {
        expect(proxy(req(path, SESSION_COOKIE_HEADER)).status).toBe(200);
      }
    });
  });

  it("admits a matching dev-role cookie (dev only)", () => {
    withEnv({}, () => {
      expect(proxy(req("/admin/users", `${DEV_ROLE_COOKIE}=Admin`)).status).toBe(200);
      expect(proxy(req("/user/bookings", `${DEV_ROLE_COOKIE}=User`)).status).toBe(200);
    });
  });

  it("confines a dev role to its own console", () => {
    withEnv({}, () => {
      const response = proxy(req("/admin/users", `${DEV_ROLE_COOKIE}=User`));
      expect(response.status).toBe(302);
      expect(response.headers.get("location")).toBe("http://localhost/user/overview");
    });
  });

  it("lets SuperAdmin enter any console", () => {
    withEnv({}, () => {
      expect(proxy(req("/admin/users", `${DEV_ROLE_COOKIE}=SuperAdmin`)).status).toBe(200);
      expect(proxy(req("/vendor/orders", `${DEV_ROLE_COOKIE}=SuperAdmin`)).status).toBe(200);
    });
  });

  it("is segment-safe (/administrator and /usertools are not consoles)", () => {
    withEnv({}, () => {
      expect(proxy(req("/administrator")).status).toBe(200);
      expect(proxy(req("/usertools")).status).toBe(200);
    });
  });

  it("never accepts the dev-role cookie as a credential in production", () => {
    withEnv({ NODE_ENV: "production" }, () => {
      expect(proxy(req("/admin/users", `${DEV_ROLE_COOKIE}=SuperAdmin`)).status).toBe(302);
    });
  });

  it("keeps the production redirect ahead of the guard", () => {
    withEnv({ NEXT_PUBLIC_PRODUCTION_REDIRECTS: "true" }, () => {
      // No credential, but the cut-over redirect wins first (301, not 302).
      const response = proxy(req("/user/bookings"));
      expect(response.status).toBe(301);
      expect(response.headers.get("location")).toBe("https://app.navdhya.com/user/bookings");
    });
  });
});

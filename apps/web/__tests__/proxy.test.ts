/**
 * @jest-environment node
 */
import { NextRequest } from "next/server";
import { proxy } from "@/proxy";

// The W1 dashboard redirect (production-only) plus its interaction with the
// strangler flag gate. Env is set per-test because proxy reads process.env
// at request time.

function req(path: string): NextRequest {
  return new NextRequest(`http://localhost${path}`);
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
      const response = proxy(req("/user/bookings"));
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
        const response = proxy(req("/vendor/orders"));
        expect(response.headers.get("location")).toBe("https://customer.test/vendor/orders");
      },
    );
  });

  it("prefix matching is segment-safe (no /usertools leak)", () => {
    withEnv({ NEXT_PUBLIC_PRODUCTION_REDIRECTS: "true" }, () => {
      expect(proxy(req("/usertools")).status).toBe(200);
    });
  });
});

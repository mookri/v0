/**
 * @jest-environment node
 */
import { type NextRequest } from "next/server";
import { GET as search } from "@/app/api/search/route";
import { GET as suggest } from "@/app/api/search/suggest/route";

// These run against the disk SEO corpus (CUSTOMER_BACKEND unset -> the
// backend leg is disabled and the BM25 leg serves alone), which is exactly
// the degradation mode the routes promise when Laravel is unreachable.

type RouteFn = (req: NextRequest) => Promise<Response>;

function buildRequest(path: string, params: Record<string, string> = {}): never {
  const url = new URL(`http://localhost${path}`);
  for (const [key, value] of Object.entries(params)) {
    url.searchParams.set(key, value);
  }
  // The routes read `req.url` + `req.headers` — a plain Request carries
  // both; `never` makes the cast assignable to NextRequest.
  return new Request(url) as never;
}

function call(route: RouteFn, path: string, params: Record<string, string> = {}): Promise<Response> {
  return route(buildRequest(path, params));
}

describe("GET /api/search", () => {
  it("400s without a q parameter", async () => {
    const response = await call(search as RouteFn, "/api/search");
    expect(response.status).toBe(400);
    const json = await response.json();
    expect(json.error.code).toBe("VALIDATION_ERROR");
    expect(typeof json.error.requestId).toBe("string");
  });

  it("400s when q exceeds the max length", async () => {
    const response = await call(search as RouteFn, "/api/search", { q: "x".repeat(201) });
    expect(response.status).toBe(400);
    expect((await response.json()).error.code).toBe("VALIDATION_ERROR");
  });

  it("answers 200 with BM25 results from the disk corpus", async () => {
    const response = await call(search as RouteFn, "/api/search", { q: "hindu wedding" });
    expect(response.status).toBe(200);

    const json = await response.json();
    expect(json.requestId).toMatch(/^req_/);
    expect(json.data.query).toBe("hindu wedding");
    expect(json.data.results.length).toBeGreaterThan(0);
    expect(json.data.sources.bm25).toBeGreaterThan(0);
    // Backend leg is disabled in this environment.
    expect(json.data.sources.fulltext).toBe(0);

    const first = json.data.results[0];
    expect(typeof first.title).toBe("string");
    expect(first.url.startsWith("/")).toBe(true);
    expect(typeof first.score).toBe("number");
    expect(["bm25", "fulltext", "both"]).toContain(first.source);
  });

  it("caps results at the 20-item limit", async () => {
    const response = await call(search as RouteFn, "/api/search", { q: "puja", limit: "999" });
    const json = await response.json();
    expect(json.data.results.length).toBeLessThanOrEqual(20);
  });

  it("sets a short public cache window", async () => {
    const response = await call(search as RouteFn, "/api/search", { q: "diwali" });
    expect(response.headers.get("Cache-Control")).toContain("max-age=30");
  });
});

describe("GET /api/search/suggest", () => {
  it("returns an empty list (not an error) below two characters", async () => {
    const response = await call(suggest as RouteFn, "/api/search/suggest", { q: "g" });
    expect(response.status).toBe(200);
    const json = await response.json();
    expect(json.data.suggestions).toEqual([]);
  });

  it("400s when q exceeds the suggest max length", async () => {
    const response = await call(suggest as RouteFn, "/api/search/suggest", { q: "x".repeat(81) });
    expect(response.status).toBe(400);
  });

  it("returns grouped, url-bearing suggestions for a real prefix", async () => {
    const response = await call(suggest as RouteFn, "/api/search/suggest", { q: "puja" });
    expect(response.status).toBe(200);

    const json = await response.json();
    expect(Array.isArray(json.data.suggestions)).toBe(true);

    for (const group of json.data.suggestions) {
      expect(typeof group.type).toBe("string");
      expect(typeof group.label).toBe("string");
      expect(group.items.length).toBeGreaterThan(0);
      for (const item of group.items) {
        expect(typeof item.title).toBe("string");
        expect(item.url.startsWith("/")).toBe(true);
      }
    }
  });
});

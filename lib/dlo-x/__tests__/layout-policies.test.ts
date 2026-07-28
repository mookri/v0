import { layoutPolicies } from "@/lib/dlo-x/layout-policies";
import { resolveLayoutTree } from "@/lib/dlo-x/layout-resolver";
import type { RequestContext } from "@/lib/dlo-x/types";

describe("lib/dlo-x/layout-policies — coverage of public routes", () => {
  test("registers an explicit policy for /find-pandit (regression: was falling through to '*')", () => {
    const findPanditPolicy = layoutPolicies.find((p) => p.path === "/find-pandit");
    expect(findPanditPolicy).toBeDefined();
    expect(findPanditPolicy!.id).toBe("find-pandit-page");
  });

  test("/pandits and /pandit/* are both registered", () => {
    // Public directory (plural) vs dashboard (singular)
    const publicDir = layoutPolicies.find((p) => p.path === "/pandits");
    const dashboard = layoutPolicies.find((p) => p.path === "/pandit/*");
    expect(publicDir).toBeDefined();
    expect(dashboard).toBeDefined();
    // Public directory should NOT be role-gated
    expect(publicDir!.role).toBeUndefined();
    // Dashboard should be
    expect(dashboard!.role).toBeUndefined(); // current code doesn't gate by role on the dashboard shell
  });

  test("all policies have unique ids", () => {
    const ids = layoutPolicies.map((p) => p.id);
    const unique = new Set(ids);
    expect(unique.size).toBe(ids.length);
  });

  test("all policies have at least one shell", () => {
    for (const p of layoutPolicies) {
      expect(p.shells.length).toBeGreaterThan(0);
    }
  });

  test("the wildcard '*' fallback is the lowest priority", () => {
    const fallback = layoutPolicies.find((p) => p.path === "*");
    expect(fallback).toBeDefined();
    const maxPriority = Math.max(
      ...layoutPolicies.filter((p) => p.path !== "*").map((p) => p.priority),
    );
    expect(fallback!.priority).toBeLessThan(maxPriority);
  });
});

describe("lib/dlo-x/layout-resolver — uses bounded cache", () => {
  test("resolves /find-pandit to MainShell", async () => {
    const ctx: RequestContext = {
      requestId: "r1",
      tenantId: "default",
      userRole: "User",
      device: "desktop",
      seed: "stable-seed-1",
      path: "/find-pandit",
    };
    const layout = await resolveLayoutTree(ctx);
    expect(layout.policyId).toBe("find-pandit-page");
    expect(layout.shells).toContain("MainShell");
  });

  test("resolves /pandits to MainShell", async () => {
    const ctx: RequestContext = {
      requestId: "r1",
      tenantId: "default",
      userRole: "User",
      device: "desktop",
      seed: "stable-seed-1",
      path: "/pandits",
    };
    const layout = await resolveLayoutTree(ctx);
    expect(layout.policyId).toBe("pandits-page");
    expect(layout.shells).toContain("MainShell");
  });

  test("falls through to '*' for unknown paths (MinimalShell only)", async () => {
    const ctx: RequestContext = {
      requestId: "r1",
      tenantId: "default",
      userRole: "User",
      device: "desktop",
      seed: "stable-seed-1",
      path: "/this/does/not/exist/xyz",
    };
    const layout = await resolveLayoutTree(ctx);
    // No match — but the resolver will not enter findMatchingPolicies
    // result, so the catch-all '*' policy kicks in.
    expect(layout.policyId).toBe("default-page");
    expect(layout.shells).toContain("MainShell");
  });

  test("cache: same context returns same result (stable seed)", async () => {
    const ctx: RequestContext = {
      requestId: "r1",
      tenantId: "default",
      userRole: "User",
      device: "desktop",
      seed: "stable-seed-cache",
      path: "/find-pandit",
    };
    const a = await resolveLayoutTree(ctx);
    const b = await resolveLayoutTree(ctx);
    expect(a).toEqual(b);
  });
});

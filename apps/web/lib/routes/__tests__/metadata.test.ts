import { getDefaultRoleForPath, getRouteMetadata, isIndexableRoute } from "@/lib/routes/metadata";

describe("route metadata", () => {
  it("marks auth and dashboard paths as noindex", () => {
    expect(getRouteMetadata("/auth/login").noindex).toBe(true);
    expect(getRouteMetadata("/dashboard/user/overview").noindex).toBe(true);
    expect(isIndexableRoute("/dashboard/user/overview")).toBe(false);
  });

  it("resolves role allowlists for protected workspaces", () => {
    expect(getRouteMetadata("/dashboard/pandit/reviews").roleAllowlist).toEqual(["Pandit"]);
    expect(getRouteMetadata("/admin/seo").roleAllowlist).toEqual(["Admin"]);
  });

  it("uses route defaults for role-aware entry points", () => {
    expect(getDefaultRoleForPath("/dashboard/vendor/orders")).toBe("Vendor");
    expect(getDefaultRoleForPath("/admin/overview")).toBe("Admin");
    expect(getDefaultRoleForPath("/services/griha-pravesh")).toBe("User");
  });
});

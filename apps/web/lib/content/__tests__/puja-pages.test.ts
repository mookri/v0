import { getPagesByCluster } from "@/lib/content/loader";

describe("registry-backed puja pages", () => {
  it("loads the core puja catalog from content pages", () => {
    const pujaPages = getPagesByCluster("pujas");

    expect(pujaPages.length).toBeGreaterThanOrEqual(12);

    for (const page of pujaPages) {
      expect(page.schemaType).toBe("Service");
      expect(page.serviceDetails?.priceRange).toBeTruthy();
      expect((page.serviceDetails?.packages ?? []).length).toBeGreaterThanOrEqual(2);
      expect((page.serviceDetails?.process ?? []).length).toBeGreaterThanOrEqual(4);
      expect((page.serviceDetails?.serviceAreas ?? []).length).toBeGreaterThanOrEqual(2);
      expect((page.faqs ?? []).length).toBeGreaterThanOrEqual(4);
    }
  });
});

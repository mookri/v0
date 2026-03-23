import { PlacementEngine } from "@/lib/ad-orchestration/PlacementEngine";

const articleHtml = `
  <h2>Intro</h2>
  <p>This introduction paragraph is short.</p>
  <p>This second paragraph is also short.</p>
  <p>This paragraph contains enough words to be considered a viable insertion point for an in-content ad unit inside the article body and continues long enough to pass the readability threshold.</p>
  <p>This paragraph also contains enough words to be useful for placement scoring without forcing an ad immediately after a heading transition in the page layout.</p>
  <p>This paragraph is rich enough to score well and lives far enough away from earlier content to support a second insertion opportunity under the spacing rules.</p>
  <p>This paragraph is another deep-content paragraph with plenty of copy for the placement engine to consider without causing layout clutter.</p>
  <p>This final paragraph closes out the article and should not host an insertion because the planner avoids the last part of the content.</p>
`;

describe("PlacementEngine", () => {
  it("builds article opportunities with spacing and sidebar inventory", () => {
    const engine = new PlacementEngine();
    const plan = engine.buildArticlePlan(articleHtml);
    const articleOpportunities = plan.opportunities.filter((opportunity) => opportunity.surface === "article");

    expect(plan.opportunities.some((opportunity) => opportunity.surface === "sidebar")).toBe(true);
    expect(articleOpportunities.length).toBeGreaterThanOrEqual(2);
    expect(articleOpportunities.length).toBeLessThanOrEqual(2);
    expect(articleOpportunities[0]?.designIntent).toBe("editorial");
    expect(articleOpportunities[1]?.designIntent).toBe("immersive");
    expect(articleOpportunities[0]?.reservedSize.height).not.toBe(articleOpportunities[1]?.reservedSize.height);
    expect(articleOpportunities.every((opportunity) => (opportunity.preferredTemplates?.length ?? 0) > 0)).toBe(true);
    expect(plan.opportunities.every((opportunity) => opportunity.reservedSize.height > 0)).toBe(true);
  });

  it("creates three distinct in-content opportunities for long articles", () => {
    const engine = new PlacementEngine();
    const longArticleHtml = `
      <h2>Section one</h2>
      <p>This opening section introduces the ceremony planning process in a grounded way and gives enough context to support an early editorial ad without making the reading flow feel mechanical or crowded.</p>
      <p>This follow-up paragraph explains booking expectations, priest matching, and household preparation in more detail so the engine has another qualified paragraph to evaluate.</p>
      <h2>Section two</h2>
      <p>This section moves into material sourcing, samagri quality, and scheduling windows, with enough body copy to support a second in-content opportunity.</p>
      <p>This paragraph covers service packages, verification checks, and fulfillment confidence so a compact utility-style unit can slot in naturally.</p>
      <h2>Section three</h2>
      <p>This section explores festival-specific planning, family traditions, and logistical coordination across cities, which gives the engine room for a richer visual ad later in the article.</p>
      <p>This paragraph expands on support channels, follow-up guidance, and ritual customization with enough density to remain a strong candidate.</p>
      <h2>Section four</h2>
      <p>This deeper paragraph rounds out the guide with post-ceremony support and preparation notes that make a third insertion both safe and useful for readers.</p>
      <p>This final paragraph closes the article and should remain largely uninterrupted for a cleaner finish.</p>
    `;

    const plan = engine.buildArticlePlan(longArticleHtml);
    const articleOpportunities = plan.opportunities.filter((opportunity) => opportunity.surface === "article");

    expect(articleOpportunities).toHaveLength(3);
    expect(articleOpportunities.map((opportunity) => opportunity.designIntent)).toEqual(["editorial", "compact", "immersive"]);
    expect(articleOpportunities.slice(0, 2).every((opportunity) => !opportunity.lazy)).toBe(true);
  });
});

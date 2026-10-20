/**
 * @jest-environment node
 */
import React from "react";
import { renderToStaticMarkup } from "react-dom/server";
import SearchPage, { metadata } from "@/app/(seo)/search/page";

jest.mock("next/navigation", () => ({
  useRouter: () => ({ push: () => null }),
  notFound: () => {
    throw new Error("NEXT_NOT_FOUND");
  },
}));

type PageProps = React.ComponentProps<typeof SearchPage>;

function renderPage(params: Record<string, string | string[] | undefined>) {
  const element = SearchPage({ searchParams: Promise.resolve(params) } as PageProps);
  return element.then((jsx) => renderToStaticMarkup(jsx as React.ReactElement));
}

describe("/search page", () => {
  it("is noindex and does not search below two characters", async () => {
    expect(metadata.robots).toMatchObject({ index: false });

    const html = await renderPage({ q: "x" });
    expect(html).toContain("Search");
    expect(html).toContain("Type a temple, pandit, puja or festival name");
  });

  it("renders federated results from the disk corpus", async () => {
    const html = await renderPage({ q: "hindu wedding" });

    // The route tests prove the corpus answers this query; here we assert
    // the page surfaces it as linked result cards.
    expect(html).toContain("results for");
    expect(html).toContain("/ceremonies/hindu-wedding-ceremony-guide");
    expect(html).toContain("Guides &amp; Blogs");
    // Guide pages are not bookable — the Book CTA must not leak onto them
    // (bookable entity types only exist when the backend corpus is live).
    expect(html).not.toContain('href="/book"');
  });

  it("honours an allowlisted type filter chip", async () => {
    const html = await renderPage({ q: "puja", type: "temple" });
    // Unknown/absent matches still render the filter nav with All + types.
    expect(html).toContain("aria-label=\"Filter by type\"");
    expect(html).toContain("Temples");
  });

  it("ignores a non-allowlisted type instead of passing it through", async () => {
    const html = await renderPage({ q: "puja", type: "evil;drop" });
    expect(html).not.toContain("evil;drop");
  });

  it("shows the zero-results state with recovery links", async () => {
    const html = await renderPage({ q: "zzzzqqqxyzzyplugh" });
    expect(html).toContain("No results for");
    expect(html).toContain("contact our team");
  });

  it("builds an empty search form without a query", async () => {
    const html = await renderPage({});
    expect(html).toContain("Type a temple, pandit, puja or festival name");
  });
});

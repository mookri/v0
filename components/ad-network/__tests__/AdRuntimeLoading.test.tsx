import { act, render, screen, waitFor } from "@testing-library/react";
import AdSlot from "@/components/ad-network/AdSlot";
import { AdRuntimeProvider } from "@/components/ad-network/runtime/AdRuntimeProvider";

beforeAll(() => {
  class MockIntersectionObserver {
    observe() {}
    unobserve() {}
    disconnect() {}
  }

  Object.defineProperty(window, "IntersectionObserver", {
    writable: true,
    value: MockIntersectionObserver,
  });
});

afterEach(() => {
  jest.useRealTimers();
  jest.restoreAllMocks();
});

describe("Ad runtime loading", () => {
  it("retries after a transient decision failure and eventually renders the ad", async () => {
    jest.useFakeTimers();

    const fetchMock = jest.fn()
      .mockRejectedValueOnce(new Error("temporary network failure"))
      .mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          requestId: "req_retry",
          generatedAt: Date.now(),
          decisions: [
            {
              slotId: "article-inline-retry",
              requestId: "req_retry",
              decisionId: "decision_retry",
              placement: "article_inline_1",
              layout: "inline_text",
              reservedSize: { width: "fluid", height: 180, aspectRatio: "16:6" },
              tracking: {
                requestId: "req_retry",
                decisionId: "decision_retry",
                auctionId: "auction_retry",
                endpoint: "/api/ads/track",
                beaconEndpoint: "/api/ads/track-beacon",
                experimentAssignments: {},
                impressionKey: "decision_retry:impression",
                viewableKey: "decision_retry:viewable",
                clickKey: "decision_retry:click",
                renderKey: "decision_retry:render",
              },
              experimentAssignments: {},
              ad: {
                id: "navdhya-house-booking",
                title: "Book Experienced Delhi-NCR Pandits",
                description: "Ensure your rituals are performed with strict Vedic authenticity and precision.",
                ctaText: "Book Now",
                ctaUrl: "/book-pandit",
                sponsorName: "Navdhya",
                layouts: ["inline_text"],
                placements: ["article_inline_1"],
              },
            },
          ],
        }),
      } as Response);

    Object.defineProperty(global, "fetch", {
      writable: true,
      value: fetchMock,
    });

    render(
      <AdRuntimeProvider>
        <AdSlot
          eager
          opportunity={{
            slotId: "article-inline-retry",
            surface: "article",
            placementHints: ["article_inline_1"],
            anchorId: "p4",
            anchorIndex: 4,
            reservedSize: { width: "fluid", height: 180, aspectRatio: "16:6" },
            depthBucket: "mid",
            lazy: false,
          }}
        />
      </AdRuntimeProvider>,
    );

    await act(async () => {
      jest.advanceTimersByTime(80);
      await Promise.resolve();
    });

    await act(async () => {
      jest.advanceTimersByTime(400);
      await Promise.resolve();
    });

    await waitFor(() => {
      expect(screen.getByText("Book Experienced Delhi-NCR Pandits")).toBeInTheDocument();
    });

    expect(fetchMock).toHaveBeenCalledTimes(2);
  });
});

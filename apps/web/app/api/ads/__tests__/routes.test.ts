/**
 * @jest-environment node
 */
import { POST as decide } from "@/app/api/ads/decision/route";
import { POST as track } from "@/app/api/ads/track/route";
import { POST as trackBeacon } from "@/app/api/ads/track-beacon/route";
import type { DecisionRequest, TrackingBatchPayload } from "@/lib/ad-orchestration";

describe("ad orchestration routes", () => {
  it("returns empty decisions for unsupported placements without throwing", async () => {
    const payload: DecisionRequest = {
      requestId: "req_test",
      opportunities: [
        {
          slotId: "unsupported",
          surface: "feed",
          placementHints: ["feed_video_inline"],
          anchorId: "feed-1",
          anchorIndex: 1,
          reservedSize: { width: "fluid", height: 180, aspectRatio: "4:3" },
          depthBucket: "mid",
          lazy: true,
        },
      ],
      page: {
        title: "Feed page",
        categories: ["blog"],
        keywords: ["feed"],
      },
      user: {},
      client: {},
      session: {
        id: "session",
        engagementScore: 0.2,
        scrollDepth: 0.1,
      },
    };

    const response = await decide(new Request("http://localhost/api/ads/decision", {
      method: "POST",
      body: JSON.stringify(payload),
      headers: { "Content-Type": "application/json" },
    }));
    const json = await response.json();

    expect(response.status).toBe(200);
    expect(json.decisions[0].ad).toBeNull();
  });

  it("deduplicates tracked events by idempotency key", async () => {
    const payload: TrackingBatchPayload = {
      batchId: "batch_1",
      sentAt: Date.now(),
      source: "fetch",
      events: [
        {
          type: "impression",
          requestId: "req",
          decisionId: "decision",
          auctionId: "auction",
          adId: "navdhya-house-booking",
          placement: "article_inline_1",
          layout: "inline_text",
          timestamp: Date.now(),
          idempotencyKey: "decision:impression",
          experimentAssignments: {},
        },
        {
          type: "impression",
          requestId: "req",
          decisionId: "decision",
          auctionId: "auction",
          adId: "navdhya-house-booking",
          placement: "article_inline_1",
          layout: "inline_text",
          timestamp: Date.now(),
          idempotencyKey: "decision:impression",
          experimentAssignments: {},
        },
      ],
    };

    const response = await track(new Request("http://localhost/api/ads/track", {
      method: "POST",
      body: JSON.stringify(payload),
      headers: { "Content-Type": "application/json" },
    }));
    const json = await response.json();

    expect(response.status).toBe(200);
    expect(json.accepted).toBe(1);
    expect(json.duplicates).toBe(1);
  });

  it("accepts unload-safe beacon batches and preserves side effects", async () => {
    const payload: TrackingBatchPayload = {
      batchId: "batch_beacon_1",
      sentAt: Date.now(),
      source: "beacon",
      events: [
        {
          type: "click",
          requestId: "req_beacon",
          decisionId: "decision_beacon",
          auctionId: "auction_beacon",
          adId: "navdhya-house-booking",
          placement: "article_inline_1",
          layout: "native_in_content",
          timestamp: Date.now(),
          idempotencyKey: "decision_beacon:click",
          experimentAssignments: {},
          customData: {
            clearingPrice: 420,
          },
        },
      ],
    };

    const response = await trackBeacon(new Request("http://localhost/api/ads/track-beacon", {
      method: "POST",
      body: JSON.stringify(payload),
      headers: { "Content-Type": "application/json" },
    }));
    const json = await response.json();

    expect(response.status).toBe(200);
    expect(json.accepted).toBe(1);
    expect(json.duplicates).toBe(0);
  });
});

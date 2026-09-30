import { NextResponse } from "next/server";
import { getAdOrchestrationRuntime } from "@/lib/ad-orchestration";
import type { TrackingBatchPayload } from "@/lib/ad-orchestration";



async function handleTrackingBatch(payload: TrackingBatchPayload) {
  const runtime = getAdOrchestrationRuntime();
  const normalizedBatch: TrackingBatchPayload = {
    ...payload,
    events: payload.events.map((event) => ({
      ...event,
      timestamp: event.timestamp || Date.now(),
      customData: {
        ...event.customData,
        serverTimestamp: Date.now(),
      },
    })),
  };

  const result = await runtime.eventSink.record(normalizedBatch);

  for (const event of normalizedBatch.events) {
    runtime.experimentEngine.record(event);

    const ad = await runtime.repository.getById(event.adId);
    const revenue = Number(event.customData?.revenue ?? event.customData?.clearingPrice ?? 0);
    if (ad && revenue > 0 && (event.type === "impression" || event.type === "click" || event.type === "viewable")) {
      await runtime.budgetLedger.addSpend(ad.id, revenue);
    }

    if (event.type === "impression") {
      await runtime.frequencyStore.increment(`tracked:${event.adId}:${event.placement}`, 24 * 60 * 60 * 1000);
    }
  }

  return NextResponse.json({ ok: true, accepted: result.accepted, duplicates: result.duplicates });
}

export async function POST(request: Request) {
  try {
    const payload = (await request.json()) as TrackingBatchPayload;
    if (!payload?.batchId || !Array.isArray(payload.events)) {
      return NextResponse.json({ error: "Invalid tracking payload" }, { status: 400 });
    }

    return handleTrackingBatch(payload);
  } catch (error) {
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to record tracking batch" },
      { status: 500 },
    );
  }
}


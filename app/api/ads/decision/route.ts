import { NextResponse } from "next/server";
import { getAdOrchestrationRuntime } from "@/lib/ad-orchestration";
import type { DecisionRequest } from "@/lib/ad-orchestration";

export const runtime = "nodejs";

export async function POST(request: Request) {
  try {
    const payload = (await request.json()) as DecisionRequest;
    if (!payload?.requestId || !Array.isArray(payload.opportunities)) {
      return NextResponse.json({ error: "Invalid decision request" }, { status: 400 });
    }

    const runtime = getAdOrchestrationRuntime();
    const response = await runtime.decisionEngine.decideMany(payload);
    return NextResponse.json(response);
  } catch (error) {
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to resolve ad decisions" },
      { status: 500 },
    );
  }
}


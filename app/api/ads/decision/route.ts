import { NextResponse } from "next/server";
import { z } from "zod";
import { getAdOrchestrationRuntime } from "@/lib/ad-orchestration";
import type { DecisionRequest } from "@/lib/ad-orchestration";
import { validateCsrfToken } from "@/lib/security/csrf";



/** Cap on the number of opportunities in a single decision request.
 *  Anything beyond this is almost certainly a DoS attempt — a single
 *  page render needs at most ~10 opportunities. */
const MAX_OPPORTUNITIES = 20;

/** Cap on the body size (bytes). Decision requests are tiny JSON. */
const MAX_BODY_BYTES = 64 * 1024;

/**
 * Minimal envelope schema for the decision request. The downstream
 * `decideMany` method does its own per-opportunity validation, so we
 * only check the top-level shape and the required identifiers here.
 */
const DecisionRequestEnvelope = z.object({
  requestId: z.string().min(1).max(200),
  opportunities: z
    .array(
      z.object({
        slotId: z.string().min(1).max(200),
      }).passthrough(),
    )
    .min(1)
    .max(MAX_OPPORTUNITIES),
  page: z.unknown().optional(),
  user: z.unknown().optional(),
  client: z.unknown().optional(),
  session: z.unknown().optional(),
}).passthrough();

export async function POST(request: Request) {
  // CSRF check (PR 0.8). Defence in depth on top of SameSite=Lax.
  // We parse the cookie header directly so this route file can
  // stay typed as the standard `Request` (avoids loading
  // next/server's NextRequest, which transitively imports a
  // fetch API global that breaks in some test environments).
  if (process.env.NODE_ENV === 'production' || process.env.CSRF_SECRET) {
    const cookieHeader = request.headers.get('cookie') ?? '';
    // Escape regex metachars in the cookie name (hyphen is safe but
    // keep this defensive in case the constant is ever renamed).
    const escapedName = 'navdhya-csrf'.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const match = cookieHeader.match(new RegExp(`(?:^|;\\s*)${escapedName}=([^;]+)`));
    const cookie = match?.[1];
    const header = request.headers.get('x-csrf-token') ?? undefined;
    const csrf = validateCsrfToken(cookie, header);
    if (!csrf.ok) {
      return NextResponse.json(
        { error: "CSRF validation failed", reason: csrf.reason },
        { status: 403 },
      );
    }
  }

  // Body size cap
  const contentLength = request.headers.get("content-length");
  if (contentLength) {
    const n = Number(contentLength);
    if (Number.isFinite(n) && n > MAX_BODY_BYTES) {
      return NextResponse.json({ error: "Request body too large" }, { status: 413 });
    }
  }

  let raw: unknown;
  try {
    raw = await request.json();
  } catch {
    return NextResponse.json({ error: "Invalid JSON body" }, { status: 400 });
  }

  const parsed = DecisionRequestEnvelope.safeParse(raw);
  if (!parsed.success) {
    return NextResponse.json(
      {
        error: "Invalid decision request",
        details: parsed.error.issues.slice(0, 5),
      },
      { status: 400 },
    );
  }

  try {
    const runtime = getAdOrchestrationRuntime();
    const response = await runtime.decisionEngine.decideMany(parsed.data as unknown as DecisionRequest);
    return NextResponse.json(response);
  } catch (error) {
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to resolve ad decisions" },
      { status: 500 },
    );
  }
}


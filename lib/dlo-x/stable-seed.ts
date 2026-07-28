import { createHash } from "crypto";

/**
 * Deterministic seed derivation. Falls back through a chain so that:
 *   1. explicit `x-seed` header wins (testing / SSR override)
 *   2. `x-seed` cookie wins (set once per visitor, sticky)
 *   3. SHA-256(ip + ua) bucketed to 24h windows (sticky for the day, rotates daily)
 *   4. SHA-256(ip + ua) without bucket (sticky for the session — most users
 *      never change IP+UA mid-session, and a moving seed per request would
 *      break A/B test bucketing and break layout-cache hit rate)
 *   5. crypto.randomUUID() — last resort; should be rare in production
 *
 * Adding `daily` rotation gives marketing a stable cohort definition that
 * refreshes each morning without requiring identity.
 */
export function deriveStableSeed(headers: {
  get(name: string): string | null;
}): string {
  const explicit = headers.get("x-seed");
  if (explicit) return explicit;

  const cookieHeader = headers.get("cookie") ?? "";
  const seedCookie = /(?:^|;\s*)navdhya-seed=([A-Za-z0-9_-]{6,64})/.exec(cookieHeader);
  if (seedCookie) return seedCookie[1];

  const ip = headers.get("x-forwarded-for")?.split(",")[0]?.trim()
    ?? headers.get("x-real-ip")
    ?? "0.0.0.0";
  const ua = headers.get("user-agent") ?? "unknown";
  const dailyBucket = new Date().toISOString().slice(0, 10);

  const daily = createHash("sha256").update(`${ip}|${ua}|${dailyBucket}`).digest("hex");
  return daily.slice(0, 24);
}

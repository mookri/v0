/* ═══════════════════════════════════════════════════════════════════════════════
   AD ENGINE v2  ·  frequency.ts
   Sliding-window frequency control · Multi-scope · Cross-placement sharing
   Recency gating · Soft caps · Decay functions
   ═══════════════════════════════════════════════════════════════════════════════ */

const PREFIX = "ad_freq_v2_";

// ─── Core sliding window ──────────────────────────────────────────────────────

export function getFrequencyCount(adId: string, windowSeconds: number, scope = "default"): number {
  if (typeof window === "undefined") return 0;

  const key = `${PREFIX}${scope}_${adId}`;
  const now = Date.now();
  const cutoff = now - windowSeconds * 1_000;

  try {
    const raw = localStorage.getItem(key);
    if (!raw) return 0;
    const timestamps: number[] = JSON.parse(raw);
    return timestamps.filter(t => t > cutoff).length;
  } catch { return 0; }
}

export function recordImpression(adId: string, windowSeconds: number, scope = "default"): void {
  if (typeof window === "undefined") return;

  const key = `${PREFIX}${scope}_${adId}`;
  const now = Date.now();
  const cutoff = now - windowSeconds * 1_000;

  try {
    const raw = localStorage.getItem(key);
    const timestamps: number[] = raw ? JSON.parse(raw) : [];
    const pruned = timestamps.filter(t => t > cutoff);
    pruned.push(now);
    localStorage.setItem(key, JSON.stringify(pruned));
  } catch { /**/ }
}

// ─── Recency check ────────────────────────────────────────────────────────────

export function getLastImpressionMs(adId: string, scope = "default"): number {
  if (typeof window === "undefined") return 0;
  try {
    const key = `${PREFIX}${scope}_${adId}`;
    const raw = localStorage.getItem(key);
    if (!raw) return 0;
    const timestamps: number[] = JSON.parse(raw);
    if (!timestamps.length) return 0;
    return Math.max(...timestamps);
  } catch { return 0; }
}

export function isRecencyGated(adId: string, minMinutes: number, scope = "default"): boolean {
  const last = getLastImpressionMs(adId, scope);
  if (!last) return false;
  return Date.now() - last < minMinutes * 60_000;
}

// ─── Multi-scope frequency profile check ─────────────────────────────────────

export interface FrequencyCheckResult {
  allowed: boolean;
  cappedBy?: "hourly" | "daily" | "weekly" | "lifetime" | "recency";
  counts: { hourly: number; daily: number; weekly: number; lifetime: number };
}

export function checkFrequencyProfile(
  adId: string,
  profile: {
    hourly?: { maxImpressions: number; windowSeconds?: number };
    daily?: { maxImpressions: number; windowSeconds?: number };
    weekly?: { maxImpressions: number; windowSeconds?: number };
    lifetime?: { maxImpressions: number; windowSeconds?: number };
    recencyMinutes?: number;
  },
  scope = "default"
): FrequencyCheckResult {
  const hourlyCount = getFrequencyCount(adId, profile.hourly?.windowSeconds ?? 3_600, scope);
  const dailyCount = getFrequencyCount(adId, profile.daily?.windowSeconds ?? 86_400, scope);
  const weeklyCount = getFrequencyCount(adId, profile.weekly?.windowSeconds ?? 604_800, scope);
  const lifetimeCount = getFrequencyCount(adId, profile.lifetime?.windowSeconds ?? 31_536_000, scope);

  const counts = { hourly: hourlyCount, daily: dailyCount, weekly: weeklyCount, lifetime: lifetimeCount };

  if (profile.recencyMinutes && isRecencyGated(adId, profile.recencyMinutes, scope)) {
    return { allowed: false, cappedBy: "recency", counts };
  }
  if (profile.hourly && hourlyCount >= profile.hourly.maxImpressions) {
    return { allowed: false, cappedBy: "hourly", counts };
  }
  if (profile.daily && dailyCount >= profile.daily.maxImpressions) {
    return { allowed: false, cappedBy: "daily", counts };
  }
  if (profile.weekly && weeklyCount >= profile.weekly.maxImpressions) {
    return { allowed: false, cappedBy: "weekly", counts };
  }
  if (profile.lifetime && lifetimeCount >= profile.lifetime.maxImpressions) {
    return { allowed: false, cappedBy: "lifetime", counts };
  }

  return { allowed: true, counts };
}

// ─── Global frequency cap (across all ads for a placement) ───────────────────

export function getPlacementImpressionCount(placement: string, windowSeconds: number): number {
  return getFrequencyCount(`__placement__${placement}`, windowSeconds);
}

export function recordPlacementImpression(placement: string, windowSeconds: number): void {
  recordImpression(`__placement__${placement}`, windowSeconds);
}

// ─── Clear / debug ────────────────────────────────────────────────────────────

export function clearFrequency(adId: string, scope?: string): void {
  if (typeof window === "undefined") return;
  try {
    const scopes = scope ? [scope] : ["default", "session", "user", "device", "ip"];
    scopes.forEach(s => localStorage.removeItem(`${PREFIX}${s}_${adId}`));
  } catch { /**/ }
}

export function clearAllFrequency(): void {
  if (typeof window === "undefined") return;
  try {
    Object.keys(localStorage)
      .filter(k => k.startsWith(PREFIX))
      .forEach(k => localStorage.removeItem(k));
  } catch { /**/ }
}

"use client";

/**
 * PostHog analytics provider — stub for PR 0.25.
 *
 * Real PostHog self-hosting ships in PR 11.5 (AWS ECS in ap-south-1).
 * This module is the contract:
 *   - `initPostHog()` is safe to call on the client; it is a no-op when
 *     `NEXT_PUBLIC_POSTHOG_KEY` is not set (dev, CI, test)
 *   - `trackPageView()` and `trackEvent()` are typed wrappers that no-op
 *     when PostHog is absent — so call sites do not need conditional code
 *   - The env-var contract is the API; flip the vars on in PR 11.5 to
 *     switch from no-op to live telemetry
 *
 * Server-side telemetry stays in lib/telemetry/* (Datadog-style APM, not
 * PostHog) — see planning-04june.md §VI Part V.
 */

import { useEffect } from "react";
import { usePathname, useSearchParams } from "next/navigation";

const POSTHOG_HOST = process.env.NEXT_PUBLIC_POSTHOG_HOST ?? "https://us.i.posthog.com";
const POSTHOG_KEY = process.env.NEXT_PUBLIC_POSTHOG_KEY;

let posthogClient: unknown = null;

export function isPostHogEnabled(): boolean {
  return Boolean(POSTHOG_KEY);
}

export async function initPostHog(): Promise<void> {
  if (!isPostHogEnabled() || typeof window === "undefined") return;
  if (posthogClient) return;
  try {
    // posthog-js is installed at runtime via npm install in PR 11.5 setup;
    // dynamic string import keeps this a no-op when the package is absent.
    const pkg = "posthog-js" as string;
    const mod = await import(/* @vite-ignore */ pkg);
    posthogClient = (mod as { default: { init: (k: string, o?: unknown) => unknown } }).default.init(POSTHOG_KEY!, {
      api_host: POSTHOG_HOST,
      capture_pageview: false,
      capture_pageleave: true,
      autocapture: true,
      person_profiles: "identified_only",
      secure_cookie: true,
      cross_subdomain_cookie: false,
    });
  } catch {
    // posthog-js is not installed yet (PR 11.5 will add it). Silently no-op.
  }
}

export function trackEvent(event: string, properties?: Record<string, unknown>): void {
  if (!posthogClient) return;
  try {
    const ph = posthogClient as { capture?: (e: string, p?: Record<string, unknown>) => void };
    ph.capture?.(event, properties);
  } catch {
    // never let analytics break the app
  }
}

export function identifyUser(userId: string, traits?: Record<string, unknown>): void {
  if (!posthogClient) return;
  try {
    const ph = posthogClient as { identify?: (id: string, t?: Record<string, unknown>) => void };
    ph.identify?.(userId, traits);
  } catch {
    // never let analytics break the app
  }
}

export function PostHogPageview(): null {
  const pathname = usePathname();
  const search = useSearchParams();

  useEffect(() => {
    if (!posthogClient) return;
    try {
      const ph = posthogClient as { capture?: (e: string, p?: Record<string, unknown>) => void };
      ph.capture?.("$pageview", { $current_url: `${pathname}${search?.toString() ? `?${search}` : ""}` });
    } catch {
      // never let analytics break the app
    }
  }, [pathname, search]);

  return null;
}

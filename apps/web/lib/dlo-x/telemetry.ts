// lib/dlo-x/telemetry.ts

import { removePII } from "@/lib/dlo-x/security";

// Simple telemetry service for tracking layout events
export class TelemetryService {
  static track(event: string, properties: Record<string, any>) {
    const { data: sanitized, log } = removePII(properties ?? {});
    if (log.length > 0 && process.env.NODE_ENV === "development") {
      console.warn(`[Telemetry] Redacted ${log.length} PII field(s) from event '${event}':`,
        log.map((l) => l.path));
    }

    if (process.env.NODE_ENV === "development") {
      console.log(`[Telemetry] ${event}:`, sanitized);
    }

    // In production, you might send this to your analytics service
    // Example:
    // fetch("/api/telemetry", {
    //   method: "POST",
    //   headers: { "Content-Type": "application/json" },
    //   body: JSON.stringify({ event, properties: sanitized, timestamp: new Date().toISOString() })
    // });
  }
}
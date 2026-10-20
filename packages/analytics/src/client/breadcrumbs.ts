// packages/analytics/src/client/breadcrumbs.ts
//
// Bounded local ring buffer of recent user/system actions, attached ONLY to
// `app_error` events (Crashlytics-parity breadcrumb layer, plan B7/B8).
// Strict payload redaction is the caller's duty before record() for
// free-form messages; the buffer enforces size and field bounds.

import type { Breadcrumb } from '@navdhya/analytics/src/contracts/envelope';

export const DEFAULT_BREADCRUMB_CAPACITY = 50;

export interface BreadcrumbRecord {
  category: string;
  message: string;
  route?: string;
}

export class Breadcrumbs {
  private readonly buf: Breadcrumb[] = [];

  constructor(
    private readonly capacity: number = DEFAULT_BREADCRUMB_CAPACITY,
    private readonly now: () => number = Date.now,
  ) {
    if (!Number.isInteger(capacity) || capacity < 1) {
      throw new Error('breadcrumbs: capacity must be a positive integer');
    }
  }

  record(rec: BreadcrumbRecord): void {
    this.buf.push({
      at: this.now(),
      category: rec.category.slice(0, 64),
      message: rec.message.slice(0, 256),
      ...(rec.route !== undefined ? { route: rec.route.slice(0, 256) } : {}),
    });
    while (this.buf.length > this.capacity) this.buf.shift();
  }

  /** Non-destructive copy, oldest first. */
  snapshot(): Breadcrumb[] {
    return this.buf.map((b) => ({ ...b }));
  }

  /** Copy current breadcrumbs and clear the buffer (used when an error fires). */
  drain(): Breadcrumb[] {
    const out = this.snapshot();
    this.buf.length = 0;
    return out;
  }

  size(): number {
    return this.buf.length;
  }
}

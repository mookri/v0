// Bounded breadcrumb ring buffer (plan B8 crash bullet): recent context is
// attached ON ERROR, never continuously shipped — a fixed-capacity ring
// keeps memory bounded and the payload small. Breadcrumbs are redacted at
// capture time (redact.ts), so a stored breadcrumb is already safe to
// serialize.

import { redactBreadcrumb, type Breadcrumb } from './redact';

export type { Breadcrumb };

/** Default capacity: enough for a useful trail (navigation + clicks +
 *  a few events) without bloating the error payload. */
export const DEFAULT_BREADCRUMB_CAPACITY = 20;

export class BreadcrumbRing {
  private readonly buffer: Breadcrumb[] = [];
  private readonly capacity: number;

  constructor(capacity: number = DEFAULT_BREADCRUMB_CAPACITY) {
    if (!Number.isInteger(capacity) || capacity <= 0) {
      throw new Error(`BreadcrumbRing capacity must be a positive integer, got ${capacity}`);
    }
    this.capacity = capacity;
  }

  /** Capture (redacted) breadcrumb; oldest entries are evicted at capacity. */
  add(breadcrumb: Breadcrumb): void {
    this.buffer.push(redactBreadcrumb(breadcrumb));
    while (this.buffer.length > this.capacity) this.buffer.shift();
  }

  get size(): number {
    return this.buffer.length;
  }

  /** The trail attached to an error. Drains (clears) — an error report
   *  carries each breadcrumb once. */
  drain(): Breadcrumb[] {
    return this.buffer.splice(0, this.buffer.length);
  }

  /** Non-destructive snapshot (for diagnostics without consuming). */
  peek(): Breadcrumb[] {
    return [...this.buffer];
  }
}

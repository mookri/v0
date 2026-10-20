// Vendor presentation helpers (P12d): money/timestamps rendered from the
// minor-unit wire fields, shared by server and client vendor views.

export function formatMinor(minor: number): string {
  return new Intl.NumberFormat('en-IN', {
    style: 'currency',
    currency: 'INR',
    maximumFractionDigits: 2,
  }).format(minor / 100);
}

export function formatDate(iso: string): string {
  const parsed = new Date(iso);
  if (Number.isNaN(parsed.getTime())) return iso;
  return parsed.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
}

/** Envelope error shape returned by the partner BFF. */
export interface VendorViewError {
  error?: { code: string; message: string };
}

export function describeError(body: VendorViewError, fallback: string): string {
  if (!body.error) return fallback;
  return `${body.error.code} — ${body.error.message}`;
}

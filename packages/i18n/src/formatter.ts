import type { Locale } from "./locale";

/**
 * Locale-aware formatters. All use the built-in Intl APIs so we
 * never ship per-locale data files.
 *
 * For money we default to INR (Navdhya's primary market) but allow
 * callers to override the currency code for diaspora pricing.
 */

const CURRENCY_BY_LOCALE: Record<Locale, string> = {
  en: "INR",
  hi: "INR",
};

export function formatCurrency(
  amount: number,
  locale: Locale,
  options: { currency?: string; maximumFractionDigits?: number } = {},
): string {
  const currency = options.currency ?? CURRENCY_BY_LOCALE[locale];
  return new Intl.NumberFormat(toBcp47(locale), {
    style: "currency",
    currency,
    maximumFractionDigits: options.maximumFractionDigits ?? 0,
  }).format(amount);
}

export function formatNumber(value: number, locale: Locale): string {
  return new Intl.NumberFormat(toBcp47(locale)).format(value);
}

export function formatDate(
  date: Date | string | number,
  locale: Locale,
  options: Intl.DateTimeFormatOptions = { year: "numeric", month: "long", day: "numeric" },
): string {
  const d = date instanceof Date ? date : new Date(date);
  return new Intl.DateTimeFormat(toBcp47(locale), options).format(d);
}

export function formatRelative(
  date: Date | string | number,
  locale: Locale,
  base: Date = new Date(),
): string {
  const d = date instanceof Date ? date : new Date(date);
  const diffMs = d.getTime() - base.getTime();
  const diffSec = Math.round(diffMs / 1000);
  const rtf = new Intl.RelativeTimeFormat(toBcp47(locale), { numeric: "auto" });
  const abs = Math.abs(diffSec);
  if (abs < 60) return rtf.format(diffSec, "second");
  if (abs < 3600) return rtf.format(Math.round(diffSec / 60), "minute");
  if (abs < 86400) return rtf.format(Math.round(diffSec / 3600), "hour");
  if (abs < 2592000) return rtf.format(Math.round(diffSec / 86400), "day");
  if (abs < 31536000) return rtf.format(Math.round(diffSec / 2592000), "month");
  return rtf.format(Math.round(diffSec / 31536000), "year");
}

export function formatList(items: string[], locale: Locale): string {
  return new Intl.ListFormat(toBcp47(locale), { style: "long", type: "conjunction" }).format(items);
}

/** Convert our short locale code to a BCP 47 tag for Intl. */
export function toBcp47(locale: Locale): string {
  return locale === "hi" ? "hi-IN" : "en-IN";
}

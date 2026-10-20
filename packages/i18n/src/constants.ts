/**
 * i18n config — single source of truth for supported locales.
 *
 * Conventions:
 *   - DEFAULT_LOCALE is the URL-less locale (English at /)
 *   - All other locales are URL-prefixed (Hindi at /hi/)
 *   - Locale codes follow BCP 47 (language-script-region when needed)
 *   - Add a new locale by: (1) extending LOCALES, (2) adding a key to
 *     `lib/i18n/formatters.ts` if it has currency/date quirks,
 *     (3) creating `messages/<locale>.json`, (4) running
 *     `npm run i18n:translate -- --to <locale>` for the initial corpus
 *   - URL routing is added in PR 14a (Hindi content); the scaffold here
 *     only provides the helpers and the contract
 */

export const LOCALES = ["en", "hi"] as const;
export type Locale = (typeof LOCALES)[number];

export const DEFAULT_LOCALE: Locale = "en";

/** Locale prefix used in URL paths. Empty for the default locale. */
export function localePrefix(locale: Locale): string {
  return locale === DEFAULT_LOCALE ? "" : `/${locale}`;
}

/** True when this locale is fully translated and production-ready. */
export const PRODUCTION_READY_LOCALES: ReadonlySet<Locale> = new Set(["en"]);

/**
 * Display labels for the locale switcher.
 * Native script first, English second.
 */
export const LOCALE_LABELS: Record<Locale, { native: string; english: string }> = {
  en: { native: "English", english: "English" },
  hi: { native: "हिन्दी", english: "Hindi" },
};

/** Default hreflang mapping for SEO alternates. */
export const HREFLANG_BY_LOCALE: Record<Locale, string> = {
  en: "en-IN",
  hi: "hi-IN",
};

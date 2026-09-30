import {
  DEFAULT_LOCALE,
  HREFLANG_BY_LOCALE,
  LOCALES,
  LOCALE_LABELS,
  localePrefix,
  type Locale,
} from "./constants";

export { DEFAULT_LOCALE, LOCALES, LOCALE_LABELS, localePrefix, HREFLANG_BY_LOCALE };
export type { Locale };

/** Type guard for runtime values (e.g. from URL params or headers). */
export function isLocale(value: unknown): value is Locale {
  return typeof value === "string" && (LOCALES as readonly string[]).includes(value);
}

/**
 * Resolve locale from a URL pathname. Supports:
 *   /                          -> "en"
 *   /pujas/foo                 -> "en"
 *   /hi                        -> "hi"
 *   /hi/pujas/foo              -> "hi"
 *   /en-US/pujas/foo           -> "en"  (BCP-47 tolerated; prefix-mapped)
 */
export function resolveLocaleFromPath(pathname: string): Locale {
  if (!pathname || pathname === "/") return DEFAULT_LOCALE;
  const segment = pathname.split("/").filter(Boolean)[0];
  if (!segment) return DEFAULT_LOCALE;
  const head = segment.toLowerCase().split(/[-_]/)[0];
  return isLocale(head) ? head : DEFAULT_LOCALE;
}

/**
 * Strip the locale prefix from a pathname, returning the rest.
 *   /hi/pujas/foo -> /pujas/foo
 *   /pujas/foo    -> /pujas/foo
 */
export function stripLocalePrefix(pathname: string): string {
  const locale = resolveLocaleFromPath(pathname);
  if (locale === DEFAULT_LOCALE) return pathname;
  const prefix = localePrefix(locale);
  if (prefix && pathname.startsWith(prefix + "/")) return pathname.slice(prefix.length);
  if (pathname === prefix) return "/";
  return pathname;
}

/**
 * Build a localized URL. Pass the canonical (locale-less) path; this
 * function adds the locale prefix for non-default locales.
 *   localizePath("/pujas/foo", "en")  -> "/pujas/foo"
 *   localizePath("/pujas/foo", "hi")  -> "/hi/pujas/foo"
 */
export function localizePath(path: string, locale: Locale): string {
  if (locale === DEFAULT_LOCALE) return path;
  const prefix = localePrefix(locale);
  if (path.startsWith(prefix + "/")) return path;
  if (path === "/") return prefix || "/";
  return `${prefix}${path}`;
}

/** True when the locale's content is production-ready. */
export function isProductionReady(locale: Locale): boolean {
  return locale === DEFAULT_LOCALE;
}

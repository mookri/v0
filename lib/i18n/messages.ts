import { promises as fs } from "fs";
import { join } from "path";
import type { Locale } from "@/i18n";

/**
 * Deeply-typed message bundle. Keys are dot-separated (a.b.c) and
 * `next-intl`-compatible: leaves are strings (or functions for ICU
 * MessageFormat — left out of scope for v1).
 */
export type MessageNode = string | { [key: string]: MessageNode };
export type MessageBundle = { [key: string]: MessageNode };

const MESSAGES_DIR = join(process.cwd(), "messages");

const _cache = new Map<Locale, MessageBundle>();

/**
 * Load all messages for a locale from `messages/<locale>.json`.
 *
 * Server-only. Caches in-process; restart the worker to pick up edits.
 * Falls back to empty bundle on missing file so dev never crashes.
 */
export async function loadMessages(locale: Locale): Promise<MessageBundle> {
  if (_cache.has(locale)) return _cache.get(locale)!;
  const file = join(MESSAGES_DIR, `${locale}.json`);
  try {
    const raw = await fs.readFile(file, "utf8");
    const parsed = JSON.parse(raw) as MessageBundle;
    _cache.set(locale, parsed);
    return parsed;
  } catch {
    if (locale === "en") throw new Error(`i18n: missing messages/en.json (required)`);
    const empty: MessageBundle = {};
    _cache.set(locale, empty);
    return empty;
  }
}

/**
 * Look up a dotted key in a bundle, returning the leaf or `undefined`.
 *   get("nav.home", bundle)          -> "Home"
 *   get("nav.unknown.key", bundle)   -> undefined
 *   get("nested.deeper.key", bundle) -> "..."
 *
 * This is the read-side used by components. It does NOT support ICU
 * placeholders — for those, use next-intl's `useTranslations` once the
 * routing layer ships in PR 14a.
 */
export function get(bundle: MessageBundle, dottedKey: string): string | undefined {
  const segments = dottedKey.split(".");
  let cursor: MessageNode | undefined = bundle;
  for (const seg of segments) {
    if (typeof cursor !== "object" || cursor === null) return undefined;
    cursor = (cursor as Record<string, MessageNode>)[seg];
  }
  return typeof cursor === "string" ? cursor : undefined;
}

/** Get with fallback to the English bundle. */
export async function getWithFallback(
  key: string,
  locale: Locale,
): Promise<string | undefined> {
  const bundle = await loadMessages(locale);
  const direct = get(bundle, key);
  if (direct !== undefined) return direct;
  if (locale === "en") return undefined;
  const en = await loadMessages("en");
  return get(en, key);
}

/**
 * Walk the bundle and return every dotted leaf key. Used by the
 * translation pipeline to know what to translate.
 */
export function flattenKeys(bundle: MessageBundle, prefix = ""): string[] {
  const out: string[] = [];
  for (const [key, value] of Object.entries(bundle)) {
    const path = prefix ? `${prefix}.${key}` : key;
    if (typeof value === "string") {
      out.push(path);
    } else if (value && typeof value === "object") {
      out.push(...flattenKeys(value as MessageBundle, path));
    }
  }
  return out;
}

/** Reset the in-process cache (test helper). */
export function _resetMessageCache(): void {
  _cache.clear();
}

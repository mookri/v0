/**
 * Allow-list string sanitizer.
 *
 * P-ARCH-05 fix: the old `sanitizeInput` over-escaped `/` to `&#x2F;`
 * (which corrupts JSON-LD URLs) and `<` / `>` / `&` / `"` / `'` to
 * their entity forms, breaking markdown, scripts, and any text with
 * technical content. This module is the replacement.
 *
 * Two flavours:
 *   - `sanitizePlainText` — strips everything that is not in the
 *     allowed character set, normalizes whitespace, truncates to length.
 *     Use for user-supplied text fields (review comments, profile bio,
 *     notes) that get rendered as text.
 *   - `sanitizeMarkdown` — preserves markdown punctuation (asterisks,
 *     underscores, brackets, parens) but still strips script tags and
 *     event-handler attributes. Use for content the user authors in
 *     markdown and which the renderer (react-markdown, etc.) treats as
 *     text-with-formatting, never as raw HTML.
 *   - `sanitizeUrl` — validates a URL against an allow-list of schemes.
 *     Rejects `javascript:`, `data:` (except images), `vbscript:`,
 *     etc. Use for any URL the user provides (avatar, link in bio).
 *   - `sanitizeHtml` — for user-supplied HTML, use `sanitize-html`
 *     with a config that strips `<script>`, `<iframe>`, `on*=` attrs.
 *     NOT included in this PR; will be added when a use case shows up
 *     (currently all user text is markdown-only or plain).
 *
 * IMPORTANT: these helpers are NOT a substitute for output encoding.
 * React already escapes by default; keep using `{value}` (not
 * `dangerouslySetInnerHTML`) for any text rendered in JSX.
 */

const ALLOWED_PLAIN = /[^\t\n\r\u0020-\u007E\u00A0-\uFFFF]/g;
const CONTROL_CHARS = /[\u0000-\u0008\u000B-\u001F\u007F]/g;
const ZERO_WIDTH = /[\u200B-\u200D\uFEFF]/g;

export interface SanitizeOptions {
  maxLength?: number;
  /** When true, allow newlines. Default true. */
  preserveNewlines?: boolean;
  /** When true, collapse runs of whitespace to a single space. Default true. */
  collapseWhitespace?: boolean;
  /** When true, trim leading/trailing whitespace. Default true. */
  trim?: boolean;
}

const DEFAULTS: Required<SanitizeOptions> = {
  maxLength: 5000,
  preserveNewlines: true,
  collapseWhitespace: true,
  trim: true,
};

/**
 * Strip everything outside ASCII printable + extended Unicode, then
 * trim / collapse whitespace / truncate. Use for plain text.
 */
export function sanitizePlainText(input: unknown, options: SanitizeOptions = {}): string {
  const opts = { ...DEFAULTS, ...options };
  if (typeof input !== "string") return "";
  let s = input
    .replace(CONTROL_CHARS, "")
    .replace(ZERO_WIDTH, "")
    .replace(ALLOWED_PLAIN, "");
  if (opts.preserveNewlines) {
    s = s.replace(/\r\n?/g, "\n");
  } else {
    s = s.replace(/\s+/g, " ");
  }
  if (opts.collapseWhitespace) {
    s = s.replace(/[ \t]+/g, " ");
    if (opts.preserveNewlines) s = s.replace(/\n{3,}/g, "\n\n");
  }
  if (opts.trim) s = s.trim();
  if (opts.maxLength && s.length > opts.maxLength) s = s.slice(0, opts.maxLength);
  return s;
}

/**
 * Allow markdown punctuation but strip raw HTML / script tags / event
 * handlers. Output is still text-with-formatting; the renderer handles
 * the markdown → HTML conversion in a controlled way.
 */
export function sanitizeMarkdown(input: unknown, options: SanitizeOptions = {}): string {
  const cleaned = sanitizePlainText(input, options);
  // Strip <script>...</script> (case-insensitive, multi-line)
  // Strip <style>...</style>
  // Strip on*= attributes (e.g. onerror=, onclick=)
  // Strip javascript: / vbscript: in href / src
  return cleaned
    .replace(/<\s*script\b[^>]*>[\s\S]*?<\s*\/\s*script\s*>/gi, "")
    .replace(/<\s*style\b[^>]*>[\s\S]*?<\s*\/\s*style\s*>/gi, "")
    .replace(/\son\w+\s*=\s*("[^"]*"|'[^']*'|[^\s>]+)/gi, "")
    .replace(/javascript\s*:/gi, "")
    .replace(/vbscript\s*:/gi, "");
}

const ALLOWED_URL_SCHEMES = new Set([
  "https:",
  "http:",
  "mailto:",
  "tel:",
  "sms:",
  "whatsapp:",
]);

/**
 * Validate a URL. Returns the URL string on success, throws on rejection.
 * Allows only safe schemes; rejects `javascript:`, `data:` (except image),
 * `vbscript:`, `file:`, etc.
 */
export function sanitizeUrl(input: unknown, options: { allowImageData?: boolean } = {}): string {
  if (typeof input !== "string") throw new Error("sanitizeUrl: input must be a string");
  const trimmed = input.trim();
  if (!trimmed) throw new Error("sanitizeUrl: empty input");

  // Explicitly reject control chars and zero-width: the URL constructor
  // silently strips them, which would mask homograph / copy-paste attacks
  // (e.g. an attacker that pastes a URL with a ZWSP in the hostname to
  // confuse human review while the browser still treats it as the same host).
  if (CONTROL_CHARS.test(trimmed) || ZERO_WIDTH.test(trimmed)) {
    throw new Error("sanitizeUrl: input contains control or zero-width characters");
  }

  let parsed: URL;
  try {
    parsed = new URL(trimmed);
  } catch {
    throw new Error(`sanitizeUrl: not a valid URL: ${trimmed.slice(0, 80)}`);
  }

  const proto = parsed.protocol.toLowerCase();
  if (proto === "data:") {
    if (options.allowImageData && parsed.href.startsWith("data:image/")) {
      return parsed.href;
    }
    throw new Error("sanitizeUrl: data: URLs are not allowed");
  }
  if (!ALLOWED_URL_SCHEMES.has(proto)) {
    throw new Error(`sanitizeUrl: scheme not allowed: ${proto}`);
  }
  return parsed.href;
}

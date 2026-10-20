// Crash payload redaction (plan B8 crash bullet: "strict payload
// redaction"): error messages and breadcrumbs are user-visible free text —
// they routinely contain PII (emails in form errors, tokens in URLs,
// card numbers in validation messages). Everything leaving the client
// through the crash pipeline passes redactText/redactBreadcrumb first.
//
// Honest trade-offs (deliberate, documented):
//   - Card-like digit runs are redacted only when they pass the LUHN check
//     — bare 13-19 digit numbers are far more often timestamps/order ids
//     than cards, and nuking those destroys debuggability for nothing.
//   - Phone numbers are redacted when they look like phones: international
//     (+<digits>) or formatted (xxx) xxx-xxxx / xxx-xxx-xxxx. An unformatted
//     bare 10-digit run is treated as an id unless Luhn says card.
//   - Query-parameter values are stripped for a fixed sensitive-param
//     list; the parameter NAME survives (debug value without the secret).

export const REDACTED = '[redacted]';

const EMAIL_RE = /[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/gi;
const JWT_RE = /\b[A-Za-z0-9_-]{8,}\.[A-Za-z0-9_-]{8,}\.[A-Za-z0-9_-]{8,}\b/g;
const BEARER_RE = /\bBearer\s+[A-Za-z0-9._~+/-]{16,}=*/gi;
const API_KEY_RE = /\b(?:sk|pk|rk)-[A-Za-z0-9_-]{16,}\b|\bAIza[0-9A-Za-z_-]{30,}\b/g;
const INTERNATIONAL_PHONE_RE = /\+\d[\d\s().-]{7,}\d/g;
const FORMATTED_PHONE_RE = /\b\(\d{2,4}\)\s?\d{3,4}[-.\s]?\d{3,4}\b|\b\d{3}[-.\s]\d{3}[-.\s]\d{4}\b/g;
const CARD_RUN_RE = /\b(?:\d[ -]?){13,19}\b/g;
const SENSITIVE_PARAM_RE =
  /([?&](?:token|auth|authorization|password|pwd|otp|session|sid|secret|api_key|apikey|access_token)=)[^&\s"']*/gi;

function luhn(digits: string): boolean {
  let sum = 0;
  let alt = false;
  for (let index = digits.length - 1; index >= 0; index -= 1) {
    let value = digits.charCodeAt(index) - 48;
    if (value < 0 || value > 9) return false;
    if (alt) {
      value *= 2;
      if (value > 9) value -= 9;
    }
    sum += value;
    alt = !alt;
  }
  return sum % 10 === 0;
}

export function redactText(text: string): string {
  return text
    .replace(EMAIL_RE, REDACTED)
    .replace(JWT_RE, REDACTED)
    .replace(BEARER_RE, `Bearer ${REDACTED}`)
    .replace(API_KEY_RE, REDACTED)
    .replace(INTERNATIONAL_PHONE_RE, REDACTED)
    .replace(FORMATTED_PHONE_RE, REDACTED)
    .replace(CARD_RUN_RE, (match) => {
      const digits = match.replace(/[^\d]/g, '');
      return digits.length >= 13 && digits.length <= 19 && luhn(digits) ? REDACTED : match;
    })
    .replace(SENSITIVE_PARAM_RE, (_match, name: string) => `${name}${REDACTED}`);
}

export interface Breadcrumb {
  type: string;
  message: string;
  atMs: number;
  data?: Record<string, string>;
}

export function redactBreadcrumb(breadcrumb: Breadcrumb): Breadcrumb {
  const data = breadcrumb.data
    ? Object.fromEntries(
        Object.entries(breadcrumb.data).map(([key, value]) => [key, redactText(value)]),
      )
    : undefined;
  return { ...breadcrumb, message: redactText(breadcrumb.message), data };
}

import { SITE_URL, isAbsoluteUrl } from "@/lib/seo/site";

const LEGAL_SLUGS = new Set([
  "accessibility",
  "affiliate-disclosure",
  "cancellation-policy",
  "child-protection",
  "cookie-policy",
  "data-processing",
  "grievance-officer",
  "ip-policy",
  "pandit-agreement",
  "pandit-verification",
  "privacy-policy",
  "refund-policy",
  "service-guarantee",
  "terms-of-service",
  "vendor-terms",
]);

const DIRECT_ROUTE_ALIASES = new Map<string, string>([
  ["/booking", "/book"],
  ["/book-pooja", "/book"],
]);

function normalizePathname(pathname: string): string {
  if (!pathname || pathname === "/") return "/";

  let normalized = pathname.trim();
  if (!normalized.startsWith("/")) normalized = `/${normalized}`;
  normalized = normalized.replace(/\/{2,}/g, "/");

  if (normalized.length > 1 && normalized.endsWith("/")) {
    normalized = normalized.slice(0, -1);
  }

  const directAlias = DIRECT_ROUTE_ALIASES.get(normalized);
  if (directAlias) return directAlias;

  if (normalized.startsWith("/legal/")) {
    const [, , slug] = normalized.split("/");
    if (slug && LEGAL_SLUGS.has(slug)) {
      return `/${slug}`;
    }
  }

  return normalized;
}

export function normalizePublicPath(pathOrUrl: string): string {
  if (!pathOrUrl) return "/";

  if (isAbsoluteUrl(pathOrUrl)) {
    const url = new URL(pathOrUrl);
    return normalizePathname(url.pathname);
  }

  return normalizePathname(pathOrUrl);
}

export function toPublicUrl(pathOrUrl: string): string {
  if (!pathOrUrl) return SITE_URL;
  return `${SITE_URL}${normalizePublicPath(pathOrUrl)}`;
}

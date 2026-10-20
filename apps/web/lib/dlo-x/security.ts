// lib/dlo-x/security.ts
// Security utilities for DLO-X architecture

// NOTE: sanitizeInput / sanitizeHeaders removed in PR 0.25 (audit P-ARCH-17).
// They were dead code AND over-escaped `/` to `&#x2F;` (P-ARCH-05, P0) which
// would corrupt JSON-LD. Trust React's default escaping + zod at the edge.
// The allow-list sanitizer is at lib/security/sanitize.ts.

import type { Role } from "@/lib/dlo-x/types";

/**
 * Role hierarchy (P0 fix: SuperAdmin was missing, allowing any role to
 * "have permission" for an unknown SuperAdmin requirement because both
 * `|| 0` defaults made `0 >= 0` true).
 *
 *   User  <  Pandit  <  Vendor  <  Manager  <  Admin  <  SuperAdmin
 *
 * A role can access resources tagged with its own level OR ANY LOWER
 * level. SuperAdmin can access everything. User can access User-only.
 */
const ROLE_HIERARCHY: Record<Role, number> = {
  User: 1,
  Pandit: 2,
  Vendor: 3,
  Manager: 4,
  Admin: 5,
  SuperAdmin: 6,
};

const KNOWN_ROLES = new Set<string>(Object.keys(ROLE_HIERARCHY));

/**
 * Check if a user role has permission to access a resource requiring
 * `requiredRole`. Both inputs must be valid `Role` values; unknown
 * strings return `false` (deny by default).
 *
 * Fix vs the old implementation:
 *   - Unknown user role no longer "passes" with a 0 default
 *   - Unknown required role no longer "passes" with a 0 default
 *   - SuperAdmin is explicitly in the hierarchy
 *   - Inputs are typed as `Role`, not `string`
 */
export function hasPermission(userRole: Role, requiredRole: Role): boolean {
  if (!KNOWN_ROLES.has(userRole) || !KNOWN_ROLES.has(requiredRole)) return false;
  return ROLE_HIERARCHY[userRole] >= ROLE_HIERARCHY[requiredRole];
}

/** Backwards-compatible string signature for callers that don't import Role. */
export function hasPermissionStr(userRole: string, requiredRole: string): boolean {
  if (!isRole(userRole) || !isRole(requiredRole)) return false;
  return hasPermission(userRole, requiredRole);
}

function isRole(value: string): value is Role {
  return KNOWN_ROLES.has(value);
}

/**
 * Validate that a user's tenant matches the resource's tenant.
 *
 * P0 fix: the old implementation returned `true` whenever
 * `userTenant === 'default'`, allowing the `default` tenant to access
 * every other tenant's data. The default tenant is now a strict-equal
 * check, same as any other tenant.
 *
 * Global / shared resources must be explicitly listed in `GLOBAL_TENANTS`.
 */
const GLOBAL_TENANTS: ReadonlySet<string> = new Set(["global", "public", "system"]);

export function validateTenant(userTenant: string, resourceTenant: string): boolean {
  if (!userTenant || !resourceTenant) return false;
  if (userTenant === resourceTenant) return true;
  if (GLOBAL_TENANTS.has(resourceTenant) && GLOBAL_TENANTS.has(userTenant)) return true;
  return false;
}

/**
 * Tenants that are considered "shared" and any authenticated user can
 * read. Resources in these tenants must still have role-based access
 * via `hasPermission`.
 */
export function isGlobalTenant(tenant: string): boolean {
  return GLOBAL_TENANTS.has(tenant);
}

/**
 * PII fields that are redacted by `removePII`. This is the canonical
 * list — extend with caution, since adding a field here silently strips
 * it from every downstream consumer.
 */
const PII_FIELDS: ReadonlySet<string> = new Set([
  "email",
  "password",
  "phone",
  "phoneNumber",
  "address",
  "ssn",
  "creditCard",
  "cardNumber",
  "cvv",
  "firstName",
  "lastName",
  "fullName",
  "userId",
  "username",
  "aadhaar",
  "pan",
]);

/** Pattern-based PII detectors (used when callers pass arbitrary keys). */
const PII_PATTERNS: ReadonlyArray<{ test: (key: string) => boolean; redaction: string }> = [
  { test: (k) => /email/i.test(k), redaction: "[EMAIL REDACTED]" },
  { test: (k) => /phone|mobile|tel/i.test(k), redaction: "[PHONE REDACTED]" },
  { test: (k) => /aadhaar|aadhar|uidai/i.test(k), redaction: "[AADHAAR REDACTED]" },
  { test: (k) => /pan[\s_-]?no|pan[\s_-]?number/i.test(k), redaction: "[PAN REDACTED]" },
  { test: (k) => /card[\s_-]?no|credit[\s_-]?card|cvv/i.test(k), redaction: "[CARD REDACTED]" },
  { test: (k) => /password|secret|token|api[_-]?key/i.test(k), redaction: "[SECRET REDACTED]" },
];

function detectPii(key: string): string | undefined {
  if (PII_FIELDS.has(key)) return "[REDACTED]";
  for (const p of PII_PATTERNS) if (p.test(key)) return p.redaction;
  return undefined;
}

function redactStringValue(value: string): string {
  // Email
  const email = /[\w._%+-]+@[\w.-]+\.[A-Za-z]{2,}/g;
  // E.164 phone
  const phone = /\+?\d[\d\s-]{8,15}\d/g;
  // Aadhaar (12 digits, possibly space/dash grouped)
  const aadhaar = /\b[2-9]\d{3}[\s-]?\d{4}[\s-]?\d{4}\b/g;
  // PAN
  const pan = /\b[A-Z]{5}\d{4}[A-Z]\b/g;
  // 16-digit card numbers
  const card = /\b(?:\d[ -]?){13,16}\d\b/g;
  return value
    .replace(aadhaar, "[AADHAAR REDACTED]")
    .replace(pan, "[PAN REDACTED]")
    .replace(email, "[EMAIL REDACTED]")
    .replace(card, "[CARD REDACTED]")
    .replace(phone, "[PHONE REDACTED]");
}

function isPlainObject(value: unknown): value is Record<string, unknown> {
  return typeof value === "object" && value !== null && !Array.isArray(value);
}

/**
 * Recursively redact PII from a telemetry payload. Walks nested objects
 * and arrays. Returns a NEW object — does not mutate.
 *
 * Two passes:
 *   1. Field-name based (exact match or pattern)
 *   2. Value-based regex on string leaves
 *
 * Caller can pass `{ dryRun: true }` to get a list of redactions made
 * without mutating the data.
 */
export interface RemovePIIOptions {
  dryRun?: boolean;
}

export interface RedactionLog {
  path: string;
  field: string;
  redaction: string;
}

export function removePII(
  data: Record<string, unknown>,
  options: RemovePIIOptions = {},
): { data: Record<string, unknown>; log: RedactionLog[] } {
  const log: RedactionLog[] = [];
  const out = walk(data, "", log);
  if (options.dryRun) {
    return { data, log };
  }
  return { data: out as Record<string, unknown>, log };
}

function walk(value: unknown, path: string, log: RedactionLog[]): unknown {
  if (isPlainObject(value)) {
    const out: Record<string, unknown> = {};
    for (const [k, v] of Object.entries(value)) {
      const fullPath = path ? `${path}.${k}` : k;
      const redaction = detectPii(k);
      if (redaction !== undefined) {
        out[k] = redaction;
        log.push({ path: fullPath, field: k, redaction });
      } else {
        out[k] = walk(v, fullPath, log);
      }
    }
    return out;
  }
  if (Array.isArray(value)) {
    return value.map((item, idx) => walk(item, `${path}[${idx}]`, log));
  }
  if (typeof value === "string") {
    const redacted = redactStringValue(value);
    if (redacted !== value) {
      log.push({ path, field: path, redaction: "value-pattern" });
    }
    return redacted;
  }
  return value;
}

// Validate shell names to prevent injection
export function validateShellName(shellName: string): boolean {
  const validShells = [
    'MinimalShell',
    'MainShell',
    'AuthShell',
    'DashboardShell',
    'AdminShell',
    'VendorShell',
    'PoojaShell',
    'MobileShell',
    'ChromeShell'
  ];
  
  return validShells.includes(shellName);
}

// Validate policy IDs
export function validatePolicyId(policyId: string): boolean {
  // Policy IDs should be alphanumeric with hyphens
  return /^[a-zA-Z0-9\-_]+$/.test(policyId);
}

// Validate paths
export function validatePath(path: string): boolean {
  // Paths should start with / and contain only safe characters
  return /^\/[a-zA-Z0-9\-_\/]*$/.test(path);
}
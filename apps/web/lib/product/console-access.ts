import type { Role } from '@/lib/dlo-x/types';

/**
 * Console access policy (G1 in `docs/PLATFORM-FEATURES-AND-CONSOLE-PLAN.md`).
 *
 * The role workspaces (`/admin`, `/platform`, `/manager`, `/vendor`,
 * `/pandit`, `/user`) are control-plane surfaces. Before this module existed
 * they were reachable by anyone — no `proxy.ts`/middleware guard, only
 * `robots: noindex`. That was survivable while every panel rendered mock
 * data; it stops being survivable the moment W1/W2 wire real data.
 *
 * This module is deliberately **pure** (no `next/*`, no `next/headers`) so it
 * can be unit-tested directly and imported from the edge runtime.
 *
 * Division of labour:
 *   - the edge (`proxy.ts`) enforces *presence of a credential* and, when the
 *     dev-role cookie is the credential, *which console* that role may enter;
 *   - the session itself is opaque, so *token validity* and *capabilities*
 *     stay enforced server-side (the BFF rejects unauthenticated calls and
 *     Laravel's capability gates are the authority). See plan D4.
 */

export interface ConsoleEntry {
  /** The console's base path, e.g. `/admin`. */
  basePath: string;
  /** The role that owns the console. */
  role: Role;
}

/** Longest `basePath` first is not required — matching is segment-safe. */
export const CONSOLE_ROLES: readonly ConsoleEntry[] = [
  { basePath: '/admin', role: 'Admin' },
  { basePath: '/platform', role: 'SuperAdmin' },
  { basePath: '/manager', role: 'Manager' },
  { basePath: '/vendor', role: 'Vendor' },
  { basePath: '/pandit', role: 'Pandit' },
  { basePath: '/user', role: 'User' },
] as const;

/**
 * Resolve the console a pathname belongs to, or `null`.
 *
 * Segment-safe: `/usertools` is not `/user`, and `/administrator` is not
 * `/admin`. Mirrors the bare-path + descendants shape the production
 * dashboard redirect already uses.
 */
export function consoleForPath(pathname: string): ConsoleEntry | null {
  for (const entry of CONSOLE_ROLES) {
    if (pathname === entry.basePath || pathname.startsWith(`${entry.basePath}/`)) {
      return entry;
    }
  }
  return null;
}

export function isConsolePath(pathname: string): boolean {
  return consoleForPath(pathname) !== null;
}

/**
 * Whether `role` may enter the console that owns `pathname`.
 *
 * `SuperAdmin` is the platform control centre and may enter any console (it
 * is the only role whose console is not owner-scoped). Every other role is
 * confined to its own console. A pathname outside any console is unrestricted
 * here — other guards own the rest of the site.
 */
export function canAccessConsole(role: Role, pathname: string): boolean {
  const entry = consoleForPath(pathname);
  if (entry === null) return true;
  if (role === 'SuperAdmin') return true;
  return entry.role === role;
}

/** The landing page for a role's own console (`/user/overview`, …). */
export function consoleLandingForRole(role: Role): string | null {
  const entry = CONSOLE_ROLES.find((candidate) => candidate.role === role);
  return entry ? `${entry.basePath}/overview` : null;
}

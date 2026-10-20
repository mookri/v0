import { test, expect } from 'vitest';
import * as fs from 'fs';
import * as path from 'path';

/**
 * S43: every page under app/(protected) must either opt out of static
 * prerendering (`force-dynamic`) or be a client-component page — those
 * render an identical, session-free shell at build time and read user
 * data client-side after hydration. A server page there without the
 * opt-out could ship one user's cached flight payload to the next visitor
 * — a cross-user session-data leak, the same hazard class as caching
 * cookies themselves.
 *
 * This test is the guard: adding a protected server page without the
 * opt-out fails CI instead of shipping a cacheable private view.
 */
const protectedDir = path.join(process.cwd(), 'apps', 'customer', 'app', '(protected)');

function collectPages(dir: string): string[] {
  const pages: string[] = [];
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) pages.push(...collectPages(full));
    else if (entry.name === 'page.tsx') pages.push(full);
  }
  return pages;
}

test('every protected customer page is force-dynamic or a client shell', () => {
  const pages = collectPages(protectedDir);
  // Sanity: the route group must actually exist and be non-trivial.
  expect(pages.length).toBeGreaterThanOrEqual(10);

  for (const page of pages) {
    const source = fs.readFileSync(page, 'utf8');
    const isForceDynamic = /export const dynamic = ['"]force-dynamic['"]/.test(source);
    const isClientShell = source.startsWith("'use client'") || source.startsWith('"use client"');
    expect(
      isForceDynamic || isClientShell,
      `${page} must export const dynamic = 'force-dynamic' (or be a 'use client' page whose prerendered shell carries no session data)`,
    ).toBe(true);
  }
});

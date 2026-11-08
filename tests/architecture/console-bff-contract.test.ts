import { test, expect } from 'vitest';
import * as fs from 'fs';
import * as path from 'path';

/**
 * Console ↔ backend contract test (G2/G4 in
 * `docs/PLATFORM-FEATURES-AND-CONSOLE-PLAN.md`).
 *
 * The BFF (`apps/web`) is the only thing standing between the consoles and
 * Laravel. When a handler calls a Laravel path that does not exist, the
 * backend answers 404 and the failure is swallowed by the handler's
 * `Promise.allSettled` + `fallback:true` envelope — so the console renders
 * an empty panel and nothing alarms. That drift is exactly how
 * `/platform/bookings`, `/platform/content`, `/platform/seo`,
 * be referenced from `apps/web` while no such routes exist in
 * `platform-navdhya/routes/platform/api.php`.
 *
 * This test binds every literal path passed to `laravelGet/Post/Patch/Delete`
 * back to a real Laravel route. New drift fails the build. The pre-existing
 * dangling paths are pinned in `KNOWN_DANGLING` — a *dated* allow-list that
 * names the workstream that removes each entry, so the list cannot rot
 * silently (removing an endpoint the BFF no longer calls also fails the
 * build — see the "stale entries" test).
 */

const ROOT = process.cwd();
const PLATFORM = path.join(ROOT, 'platform-navdhya');
const WEB = path.join(ROOT, 'apps', 'web');

// The Laravel server mounts the API at `/api` (bootstrap/app.php apiPrefix)
// and the route files add `v1`; the BFF builds `${base}/api/v1${path}`.
const API_PREFIX = '/api/v1';

interface DanglingEntry {
  /** The path the BFF calls, as written in source. */
  path: string;
  /** Which endpoint is missing and who owns it. */
  reason: string;
  /** Workstream that removes this entry (`W2` etc.). */
  removal: string;
}

/**
 * Pre-existing drift, pinned with a removal workstream. Every entry MUST be
 * gone by the milestone named in `removal` (see the plan's M2). Adding a new
 * entry here is not a fix — it is a promise to fix it.
 */
const KNOWN_DANGLING: DanglingEntry[] = [
  // Admin console + admin stats — no platform-wide roll-up exists yet; every
  // module owns its own rows (booking/reservations, seo/pages, vendor payouts)
  // but nothing aggregates them at the kernel.
  // `/platform/security` is gone from this list as of W2: the security
  // overview now reads /platform/security (real endpoint).
  // (Allow-list is now empty — any new dangling path fails the build.)
  // SuperAdmin overview stats — 5 parallel calls that all 404.
];

// --- file walking ---------------------------------------------------------

function walk(dir: string, out: string[] = []): string[] {
  if (!fs.existsSync(dir)) return out;
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      if (entry.name === 'node_modules' || entry.name === '.next' || entry.name === '__tests__') continue;
      walk(full, out);
    } else if (/\.tsx?$/.test(entry.name) && !/\.test\.tsx?$/.test(entry.name)) {
      out.push(full);
    }
  }
  return out;
}

// --- backend route extraction --------------------------------------------

/**
 * A route path is the quoted literal that precedes the handler: `, [Ctrl::class, ...]`,
 * `, function`, or `, SomeController::class`. That shape excludes middleware
 * lists, `->name(...)`, `Route::prefix(...)` and other quoted noise.
 */
const ROUTE_LITERAL = /(['"])(\/[^'"]*|[a-z0-9][a-z0-9._:/{}()-]*)\1\s*,\s*(?:\[|function\b|[\w\\]+::class)/g;

function routePathsFrom(file: string): string[] {
  if (!fs.existsSync(file)) return [];
  const content = fs.readFileSync(file, 'utf8');
  const found: string[] = [];
  for (const match of content.matchAll(ROUTE_LITERAL)) {
    found.push(match[2]);
  }
  return found;
}

function moduleCode(moduleDir: string): string | null {
  const manifest = path.join(moduleDir, 'module.json');
  if (!fs.existsSync(manifest)) return null;
  const code = JSON.parse(fs.readFileSync(manifest, 'utf8')).code;
  return typeof code === 'string' && code.length > 0 ? code : null;
}

/** Every real route the backend serves, as paths relative to `/api/v1`. */
function backendRoutes(): string[] {
  const routes: string[] = [];

  // Kernel surfaces: /api/v1/<literal path>.
  for (const p of routePathsFrom(path.join(PLATFORM, 'routes', 'platform', 'api.php'))) {
    routes.push(p);
  }

  // Domain modules: /api/v1/<module code>/<literal path>.
  const modulesRoot = path.join(PLATFORM, 'app', 'Modules');
  if (fs.existsSync(modulesRoot)) {
    for (const entry of fs.readdirSync(modulesRoot, { withFileTypes: true })) {
      if (!entry.isDirectory()) continue;
      const code = moduleCode(path.join(modulesRoot, entry.name));
      if (!code) continue;
      for (const p of routePathsFrom(path.join(modulesRoot, entry.name, 'Routes', 'api.php'))) {
        routes.push(`${code}/${p}`);
      }
    }
  }

  return routes.map(normalize).filter((p) => p.length > 0);
}

// --- BFF call-site extraction --------------------------------------------

/**
 * `laravelGet(token, '/platform/roles', ...)` — the path is the first string
 * argument after the client+token. Template params (`${id}`) normalize to
 * `{id}` so a parameterised call matches a parameterised route.
 */
const LARAVEL_CALL =
  /laravel(?:Get|Post|Patch|Delete)\s*(?:<[^>]*>)?\s*\(\s*(?:[A-Za-z_$][\w$]*|null)\s*,\s*(['"`])(\/[^'"`]*)\1/g;

function bffCallSites(): { file: string; path: string }[] {
  const sites: { file: string; path: string }[] = [];
  for (const file of walk(WEB)) {
    // The client definition and its contract tests define the paths, they do not call them.
    if (file.includes(`${path.sep}adapters${path.sep}backend${path.sep}laravel-client.ts`)) continue;
    const content = fs.readFileSync(file, 'utf8');
    for (const match of content.matchAll(LARAVEL_CALL)) {
      sites.push({ file: path.relative(ROOT, file), path: normalize(match[2]) });
    }
  }
  return sites;
}

// --- matching -------------------------------------------------------------

function normalize(p: string): string {
  const withParams = p.replace(/\$\{[^}]*\}/g, '{param}');
  const trimmed = withParams.replace(/^\/+/, '').replace(/\/+$/, '');
  return trimmed.length === 0 ? '/' : trimmed;
}

/** Route pattern -> RegExp; `{anything}` matches one path segment. */
function patternToRegExp(pattern: string): RegExp {
  const escaped = pattern
    .split('/')
    .map((segment) => {
      const escaped = segment.replace(/[.*+?^${}()|[\]\\\\]/g, '\\\\$&');
      // Param tokens may carry a suffix (e.g. {id}:request-info), so
      // replace any escaped {...} group with a segment-safe wildcard
      // instead of requiring the whole segment to be a param.
      return escaped.replace(/\\\\\{[^}]*\\\\\}/g, '[^/]+');
    })
    .join('/');
  return new RegExp(`^${escaped}$`);
}

function resolves(pathToMatch: string, matchers: RegExp[]): boolean {
  return matchers.some((matcher) => matcher.test(pathToMatch));
}

/**
 * Memoised once-per-run views of the two filesystem scans. Rebuilding them per
 * assertion made the whole suite scale with routes × call sites (and it timed
 * out at the default 5s); nothing here changes per test, only per run.
 */
let cachedRoutes: string[] | null = null;
let cachedMatchers: RegExp[] | null = null;
let cachedSites: { file: string; path: string }[] | null = null;

function allRoutes(): string[] {
  return (cachedRoutes ??= backendRoutes());
}

function allMatchers(): RegExp[] {
  return (cachedMatchers ??= allRoutes().map(patternToRegExp));
}

function allCallSites(): { file: string; path: string }[] {
  return (cachedSites ??= bffCallSites());
}

// --- tests ----------------------------------------------------------------

test('every BFF Laravel call resolves to a real backend route', () => {
  const matchers = allMatchers();
  const allowlist = new Set(KNOWN_DANGLING.map((entry) => normalize(entry.path)));

  const unresolved = allCallSites()
    .filter(({ path: p }) => !resolves(p, matchers) && !allowlist.has(p))
    .map(({ file, path: p }) => `${p}  (${file})`);

  expect(
    unresolved,
    `BFF calls a Laravel path that does not exist. Fix the call, delete the handler, ` +
      `or (only with a removal workstream) pin it in KNOWN_DANGLING.\n${unresolved.join('\n')}`,
  ).toEqual([]);
}, 30000);

test('the dangling allow-list is dated and cannot rot', () => {
  const referenced = new Set(allCallSites().map(({ path: p }) => p));
  const matchers = allMatchers();

  for (const entry of KNOWN_DANGLING) {
    expect(entry.reason, `allow-list entry ${entry.path} needs a reason`).toBeTruthy();
    expect(entry.removal, `allow-list entry ${entry.path} needs a removal workstream (e.g. W2)`).toMatch(/^W\d+/);
  }

  // A pinned entry that is no longer called (or was finally implemented) must be removed.
  const stale = KNOWN_DANGLING.filter(
    (entry) => !referenced.has(normalize(entry.path)) || resolves(normalize(entry.path), matchers),
  ).map((entry) => entry.path);

  expect(stale, `remove these KNOWN_DANGLING entries; they are no longer dangling:\n${stale.join('\n')}`).toEqual([]);
});

test('backend route extraction actually finds routes (guards the parser)', () => {
  const routes = allRoutes();
  // Sanity: the extractor must see the kernel + module surfaces, otherwise the
  // contract test above would vacuously pass.
  expect(routes, 'expected kernel surface platform/roles').toContain('platform/roles');
  expect(routes, 'expected kernel surface identity/mfa/verify').toContain('identity/mfa/verify');
  expect(routes, 'expected module surface customers/auth/otp').toContain('customers/auth/otp');
  expect(routes, 'expected parameterised module surface booking/reservations/{id}').toContain('booking/reservations/{id}');
  expect(routes.length, 'expected a non-trivial route count').toBeGreaterThan(50);
});

test('API_PREFIX matches the client builder', () => {
  const client = fs.readFileSync(path.join(WEB, 'adapters', 'backend', 'laravel-client.ts'), 'utf8');
  expect(client).toContain('/api/v1${path}');
  expect(API_PREFIX).toBe('/api/v1');
});

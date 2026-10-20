// Jest known-failure ratchet.
//
// The web suite has pre-existing failures. A soft gate hides every future
// regression alongside them; a blanket `continue-on-error` hides the whole
// suite. This runner makes the debt explicit instead:
//
//   * every failing test must be listed in ci/known-failures/web-jest.txt
//   * a listed test that passes fails the gate (stale entry — the ratchet
//     cannot rot)
//   * a manifest whose `# expires:` date has passed fails the gate
//   * an unlisted failure fails the gate
//
// Usage
//   node tooling/scripts/ci/jest-known-failures.mjs                 # runs jest
//   node tooling/scripts/ci/jest-known-failures.mjs --reuse f.json  # reuse a report
//   node tooling/scripts/ci/jest-known-failures.mjs --update        # rewrite the manifest (local only)
//
// Exit codes: 0 = only known failures (or none), 1 = gate failure.

import { execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { dirname, join, relative, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const HERE = dirname(fileURLToPath(import.meta.url));
const ROOT = resolve(HERE, '..', '..', '..');
const APP_DIR = join(ROOT, 'apps', 'web');
const MANIFEST = join(ROOT, 'ci', 'known-failures', 'web-jest.txt');
const REPORT = join(ROOT, 'tmp', 'jest-known-failures.json');

const PASSING = new Set(['passed', 'pending', 'todo', 'skipped']);

/** Normalise to a stable, repo-relative `file :: test name` key. */
export function keyOf(filePath, fullName) {
  return `${relative(ROOT, filePath).replace(/\\/g, '/')} :: ${fullName}`;
}

/** Parse the manifest into entries plus its declared expiry. */
export function parseManifest(text) {
  const entries = [];
  let expires = null;
  for (const raw of text.split(/\r?\n/)) {
    const line = raw.trim();
    if (!line) continue;
    if (line.startsWith('#')) {
      const m = /expires:\s*(\d{4}-\d{2}-\d{2})/.exec(line);
      if (m) expires = m[1];
      continue;
    }
    entries.push(line);
  }
  return { entries, expires };
}

/**
 * Compare a jest JSON report against the manifest.
 * @returns {{unexpected: string[], stale: string[], known: string[], expired: boolean, total: number}}
 */
export function compareFailures(report, manifestText, today = new Date()) {
  const { entries, expires } = parseManifest(manifestText);
  const known = new Set(entries);
  const failed = new Set();

  for (const suite of report.testResults ?? []) {
    for (const assertion of suite.assertionResults ?? []) {
      if (PASSING.has(assertion.status)) continue;
      failed.add(keyOf(suite.name, assertion.fullName));
    }
    // A suite that could not run at all (syntax error, missing module) has no
    // assertion results — that is always a real failure, never known debt.
    if (suite.status === 'failed' && (suite.assertionResults ?? []).length === 0) {
      failed.add(keyOf(suite.name, '<suite failed to run>'));
    }
  }

  const unexpected = [...failed].filter((k) => !known.has(k)).sort();
  const stale = [...known].filter((k) => !failed.has(k)).sort();

  const expired = expires ? new Date(`${expires}T23:59:59Z`) < today : false;

  return { unexpected, stale, known: [...failed].filter((k) => known.has(k)).sort(), expired, total: failed.size };
}

function runJest() {
  mkdirSync(dirname(REPORT), { recursive: true });
  // Fail closed: a crashed jest must not leave a previous run's report behind
  // for the comparator to read as if it were current.
  rmSync(REPORT, { force: true });
  const jestBin = join(APP_DIR, 'node_modules', 'jest', 'bin', 'jest.js');
  const reportPath = join('..', '..', 'tmp', 'jest-known-failures.json');

  const args = ['--ci', '--silent', '--json', `--outputFile=${reportPath}`];
  // GitHub's standard Linux runner has 2 vCPU. The default worker count is
  // cores-1, and this suite is Next-heavy enough that an unbounded run can OOM
  // a worker (observed locally under contention: "Jest worker ran out of
  // memory and crashed"). Bound the workers, and recycle any worker that leaks.
  if (process.env.CI === 'true') args.push('--maxWorkers=2');
  args.push('--workerIdleMemoryLimit=1024MB');

  try {
    execFileSync(process.execPath, [jestBin, ...args], {
      cwd: APP_DIR,
      stdio: 'inherit',
    });
  } catch {
    // Jest exits 1 when tests fail. The report is authoritative; a missing
    // report is caught below.
  }
  if (!existsSync(REPORT)) {
    throw new Error('jest produced no JSON report — the suite did not start');
  }
  return JSON.parse(readFileSync(REPORT, 'utf8'));
}

function main(argv) {
  const reuseIndex = argv.indexOf('--reuse');
  const update = argv.includes('--update');

  if (update && process.env.CI === 'true') {
    console.log('::error::--update is forbidden in CI; change the manifest by pull request.');
    return 1;
  }

  const reusePath = reuseIndex !== -1 ? resolve(ROOT, argv[reuseIndex + 1] ?? '') : null;
  if (reusePath && !existsSync(reusePath)) {
    console.log(`::error title=Missing jest report::--reuse ${argv[reuseIndex + 1]} does not exist.`);
    return 1;
  }

  const report =
    reusePath !== null ? JSON.parse(readFileSync(reusePath, 'utf8')) : runJest();

  const manifestText = existsSync(MANIFEST) ? readFileSync(MANIFEST, 'utf8') : '';
  const result = compareFailures(report, manifestText);

  if (update) {
    const keys = [...result.known, ...result.unexpected].sort();
    const header = [
      '# Known jest failures — web app ratchet.',
      '#',
      '# Every entry is a test that fails today for a reason we have accepted',
      '# temporarily. A listed test that starts passing FAILS the gate (remove the',
      '# line). The gate also fails once `expires` has passed.',
      '#',
      '# Format: <repo-relative test file> :: <full test name>',
      '# owner: @eng-b',
      '# expires: 2026-12-31',
      '',
    ].join('\n');
    mkdirSync(dirname(MANIFEST), { recursive: true });
    writeFileSync(MANIFEST, `${header}${keys.join('\n')}\n`);
    console.log(`manifest updated with ${keys.length} entries: ${relative(ROOT, MANIFEST)}`);
    return 0;
  }

  if (result.expired) {
    console.log(`::error title=Known-failure manifest expired::${relative(ROOT, MANIFEST)} is past its expiry date.`);
  }
  for (const key of result.unexpected) {
    console.log(`::error title=Unexpected test failure::${key}`);
  }
  for (const key of result.stale) {
    console.log(`::error title=Stale known-failure entry::${key} passes now — remove it from the manifest.`);
  }

  console.log(
    `jest ratchet: ${result.total} failing, ${result.known.length} known, ` +
      `${result.unexpected.length} unexpected, ${result.stale.length} stale` +
      `${result.expired ? ', manifest expired' : ''}`,
  );

  return result.unexpected.length === 0 && result.stale.length === 0 && !result.expired ? 0 : 1;
}

process.exit(main(process.argv.slice(2)));

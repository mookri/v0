// Tests for the jest known-failure ratchet
// (tooling/scripts/ci/jest-known-failures.mjs).
//
// The ratchet is what lets a suite with accepted debt be a *blocking* gate, so
// its three failure modes are the product: an unlisted failure must fail, a
// listed test that starts passing must fail (stale), and a manifest past its
// expiry must fail. If any of those silently passes, the gate is decorative.
//
// Exercised through the CLI, because that is the contract CI depends on.

import { describe, expect, it } from 'vitest';
import { execFileSync } from 'node:child_process';
import { readFileSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import { tmpdir } from 'node:os';

const ROOT = process.cwd();
const CLI = 'tooling/scripts/ci/jest-known-failures.mjs';
const MANIFEST = join(ROOT, 'ci', 'known-failures', 'web-jest.txt');

const entries = readFileSync(MANIFEST, 'utf8')
  .split(/\r?\n/)
  .map((l) => l.trim())
  .filter((l) => l && !l.startsWith('#'));

/** Build a jest JSON report from `[file, testName, status]` triples. */
function report(triples: Array<[string, string, string]>): string {
  const byFile = new Map<string, Array<{ fullName: string; status: string }>>();
  for (const [file, name, status] of triples) {
    const list = byFile.get(file) ?? [];
    list.push({ fullName: name, status });
    byFile.set(file, list);
  }
  return JSON.stringify({
    testResults: [...byFile.entries()].map(([file, assertionResults]) => ({
      name: join(ROOT, file),
      status: assertionResults.some((a) => a.status === 'failed') ? 'failed' : 'passed',
      assertionResults,
    })),
  });
}

function run(reportJson: string): { code: number; out: string } {
  const path = join(tmpdir(), `jest-ratchet-${Math.random().toString(36).slice(2)}.json`);
  writeFileSync(path, reportJson);
  try {
    const out = execFileSync(process.execPath, [CLI, '--reuse', path], { encoding: 'utf8', cwd: ROOT });
    return { code: 0, out };
  } catch (err) {
    const failed = err as { status?: number; stdout?: string; stderr?: string };
    return { code: failed.status ?? 1, out: `${failed.stdout ?? ''}${failed.stderr ?? ''}` };
  }
}

const parse = (entry: string) => {
  const [file, ...rest] = entry.split(' :: ');
  return [file, rest.join(' :: ')] as const;
};

describe('known-failure manifest', () => {
  it('is parseable and declares an expiry', () => {
    expect(entries.length).toBeGreaterThan(0);
    for (const entry of entries) expect(entry, `malformed: ${entry}`).toContain(' :: ');

    const header = readFileSync(MANIFEST, 'utf8');
    expect(header).toMatch(/# expires: \d{4}-\d{2}-\d{2}/);
    expect(header).toMatch(/# owner: @/);
  });
});

describe('jest ratchet', () => {
  it('passes when every failure is a known failure', () => {
    const triples = entries.map((e) => [...parse(e), 'failed'] as [string, string, string]);
    const { code, out } = run(report(triples));
    expect(out).toContain(`${entries.length} known`);
    expect(out).toContain('0 unexpected');
    expect(code).toBe(0);
  });

  it('fails when a known failure starts passing (stale entry)', () => {
    const triples = entries.map((e) => [...parse(e), 'failed'] as [string, string, string]);
    triples[0] = [parse(entries[0]!)[0], parse(entries[0]!)[1], 'passed'];
    const { code, out } = run(report(triples));
    expect(code).toBe(1);
    expect(out).toContain('Stale known-failure entry');
  });

  it('fails on an unlisted failure', () => {
    const triples = entries.map((e) => [...parse(e), 'failed'] as [string, string, string]);
    triples.push(['apps/web/components/__tests__/brand-new.test.tsx', 'a brand new regression', 'failed']);
    const { code, out } = run(report(triples));
    expect(code).toBe(1);
    expect(out).toContain('Unexpected test failure');
    expect(out).toContain('a brand new regression');
  });

  it('fails when a whole suite cannot run at all', () => {
    const path = join(tmpdir(), 'jest-ratchet-broken.json');
    writeFileSync(
      path,
      JSON.stringify({
        testResults: [{ name: join(ROOT, 'apps/web/lib/__tests__/broken.test.ts'), status: 'failed', assertionResults: [] }],
      }),
    );
    let code = 0;
    let out = '';
    try {
      out = execFileSync(process.execPath, [CLI, '--reuse', path], { encoding: 'utf8', cwd: ROOT });
    } catch (err) {
      const failed = err as { status?: number; stdout?: string };
      code = failed.status ?? 1;
      out = failed.stdout ?? '';
    }
    expect(code).toBe(1);
    expect(out).toContain('<suite failed to run>');
  });

  it('fails cleanly when the report to reuse does not exist', () => {
    let code = 0;
    let out = '';
    try {
      out = execFileSync(process.execPath, [CLI, '--reuse', join(tmpdir(), 'definitely-missing.json')], {
        encoding: 'utf8',
        cwd: ROOT,
      });
    } catch (err) {
      const failed = err as { status?: number; stdout?: string };
      code = failed.status ?? 1;
      out = failed.stdout ?? '';
    }
    expect(code).toBe(1);
    expect(out).toContain('Missing jest report');
    expect(out).not.toContain('at ModuleJob.run');
  });

  it('refuses --update in CI', () => {
    let code = 0;
    try {
      execFileSync(process.execPath, [CLI, '--reuse', 'tmp/jest-failures.json', '--update'], {
        encoding: 'utf8',
        cwd: ROOT,
        env: { ...process.env, CI: 'true' },
      });
    } catch (err) {
      code = (err as { status?: number }).status ?? 1;
    }
    expect(code).toBe(1);
  });
});

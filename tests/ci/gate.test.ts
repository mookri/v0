// Tests for the CI gate aggregator (tooling/scripts/ci/gate.mjs).
//
// The gate is the only required check in ci.yml, so its failure modes are a
// merge-safety boundary: a gate that passes when `needs` is empty, or that
// silently promotes a soft job, is worse than no gate at all.
//
// The script is exercised through its real interface (process env in, exit code
// and stdout out) rather than imported, so the test fails if the CLI contract
// drifts even when the internal functions still agree with themselves.

import { describe, expect, it } from 'vitest';
import { execFileSync } from 'node:child_process';

const SCRIPT = 'tooling/scripts/ci/gate.mjs';

function run(env: Record<string, string>): { code: number; out: string } {
  try {
    const out = execFileSync(process.execPath, [SCRIPT], {
      encoding: 'utf8',
      env: { ...process.env, ...env },
    });
    return { code: 0, out };
  } catch (err) {
    const failed = err as { status?: number; stdout?: string; stderr?: string };
    return { code: failed.status ?? 1, out: `${failed.stdout ?? ''}${failed.stderr ?? ''}` };
  }
}

function verdict(env: Record<string, string>) {
  const { code, out } = run({ GATE_JSON: '1', ...env });
  const line = out.trim().split('\n').filter((l) => l.startsWith('{')).pop();
  return { code, data: line ? JSON.parse(line) : null, out };
}

describe('ci gate', () => {
  it('passes when every job succeeded or was skipped by a path filter', () => {
    const needs = {
      build: { result: 'success' },
      docs: { result: 'skipped' },
    };
    expect(run({ GATE_NEEDS: JSON.stringify(needs) }).code).toBe(0);
  });

  it('fails on failure, cancellation and timeout', () => {
    for (const result of ['failure', 'cancelled', 'timed_out']) {
      const { code, data } = verdict({ GATE_NEEDS: JSON.stringify({ unit: { result } }) });
      expect(code, result).toBe(1);
      expect(data.rows[0].verdict, result).toBe('fail');
    }
  });

  it('fails closed when needs is missing, empty or not an object', () => {
    expect(run({ GATE_NEEDS: '{}' }).code).toBe(1);
    expect(run({ GATE_NEEDS: '[]' }).code).toBe(1);
    expect(run({ GATE_NEEDS: 'null' }).code).toBe(1);
    expect(run({ GATE_NEEDS: '{"a":{"result":"success"}}' }).code).toBe(0);
  });

  it('treats an unknown result string as a failure, never as a pass', () => {
    const { code } = verdict({ GATE_NEEDS: JSON.stringify({ unit: { result: 'neutral' } }) });
    expect(code).toBe(1);
  });

  it('lets a soft-flagged job fail without blocking but annotates it', () => {
    const needs = { lint: { result: 'failure' }, unit: { result: 'success' } };
    const { code, data, out } = verdict({ GATE_NEEDS: JSON.stringify(needs), GATE_SOFT: 'lint' });
    expect(code).toBe(0);
    expect(data.rows.find((r: { job: string }) => r.job === 'lint').verdict).toBe('soft-pass');
    expect(out).toContain('::warning');
  });

  it('makes soft jobs blocking once strict mode lists them', () => {
    const needs = { lint: { result: 'failure' } };
    const { code, data } = verdict({
      GATE_NEEDS: JSON.stringify(needs),
      GATE_SOFT: 'lint',
      GATE_STRICT: 'lint',
    });
    expect(code).toBe(1);
    expect(data.rows[0].reason).toBe('soft gate is now strict');
  });

  it('treats GATE_STRICT=all as flipping every soft gate at once', () => {
    const needs = { lint: { result: 'failure' }, packages: { result: 'failure' } };
    const { code } = verdict({
      GATE_NEEDS: JSON.stringify(needs),
      GATE_SOFT: 'lint,packages',
      GATE_STRICT: 'all',
    });
    expect(code).toBe(1);
  });

  it('only flips the soft gates named in GATE_STRICT', () => {
    const needs = { lint: { result: 'failure' }, packages: { result: 'failure' } };
    const { code, data } = verdict({
      GATE_NEEDS: JSON.stringify(needs),
      GATE_SOFT: 'lint,packages',
      GATE_STRICT: 'lint',
    });
    expect(code).toBe(1);
    expect(data.rows.find((r: { job: string }) => r.job === 'packages').verdict).toBe('soft-pass');
  });

  it('is a no-op locally when GATE_NEEDS is unset', () => {
    const { code, out } = run({});
    expect(code).toBe(0);
    expect(out).toContain('GATE_NEEDS is unset');
  });

  it('exits 1 on invalid JSON instead of guessing', () => {
    expect(run({ GATE_NEEDS: '{not json' }).code).toBe(1);
  });

  it('renders a markdown summary naming every job', () => {
    const needs = { build: { result: 'success' }, unit: { result: 'failure' } };
    const { out } = run({ GATE_NEEDS: JSON.stringify(needs) });
    expect(out).toContain('❌ **Gate failed**');
    expect(out).toContain('`build`');
    expect(out).toContain('`unit`');
  });
});

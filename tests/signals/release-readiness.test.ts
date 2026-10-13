// B9 acceptance — release-readiness checklist gate (plan §13 family).
//
// The checklist (docs/analytics/release-readiness.md) names the EVIDENCE
// that closes each release item. This meta-test parses the checklist and
// asserts every referenced artifact exists — so deleting a cited test or
// runbook breaks the gate instead of silently hollowing out the checklist.
// A few load-bearing assertions are pinned by content, not just existence:
// the checklist can only rot if the ROT itself is visible here first.

import { existsSync, readdirSync, statSync } from 'node:fs';
import * as fs from 'node:fs';
import * as path from 'node:path';
import { describe, expect, it } from 'vitest';

const ROOT = process.cwd();
const CHECKLIST = path.join(ROOT, 'docs/analytics/release-readiness.md');

/** Backticked tokens that look like repo file paths (or path-ish refs). */
function referencedPaths(markdown: string): string[] {
  const refs = new Set<string>();
  for (const match of markdown.matchAll(/`([^`]+)`/g)) {
    const token = match[1]!;
    if (/^[A-Za-z0-9_\-./]+\.(ts|md|sql|json)$/.test(token)) refs.add(token);
    else if (/^[A-Za-z0-9_\-./]+\/$/.test(token)) refs.add(token); // dir ref
  }
  return [...refs];
}

describe('B9 acceptance — release-readiness checklist', () => {
  it('every evidence artifact referenced by the checklist exists', () => {
    const markdown = fs.readFileSync(CHECKLIST, 'utf8');
    const refs = referencedPaths(markdown);
    // The checklist must actually reference a substantial evidence set —
    // an emptied checklist is also a failure.
    expect(refs.length).toBeGreaterThanOrEqual(10);

    for (const ref of refs) {
      const full = path.join(ROOT, ref);
      expect(existsSync(full), `checklist references missing artifact: ${ref}`).toBe(true);
      if (ref.endsWith('/')) {
        expect(statSync(full).isDirectory(), `expected a directory: ${ref}`).toBe(true);
      }
    }
  });

  it('all seven runbooks exist and are non-trivial', () => {
    const runbookDir = path.join(ROOT, 'docs/analytics/runbooks');
    const runbooks = readdirSync(runbookDir).filter((f) => f.endsWith('.md'));
    expect(runbooks.length).toBeGreaterThanOrEqual(7);
    for (const name of runbooks) {
      const content = fs.readFileSync(path.join(runbookDir, name), 'utf8');
      // A runbook must at minimum carry detection, degradation and recovery
      // sections — those are what the drills exercise.
      expect(content).toMatch(/degradation/i);
      expect(content).toMatch(/recovery/i);
      expect(content.length).toBeGreaterThan(800);
    }
  });

  it('load-bearing assertions are pinned in the evidence tests themselves', () => {
    const mustContain: Array<[string, string]> = [
      // 10k load with the full fault matrix + exactly-once invariant:
      ['tests/signals/load-matrix.test.ts', 'toBe(10_000)'],
      ['tests/signals/load-matrix.test.ts', 'missingPersisted'],
      // Every drill is named and its runbook existence is enforced:
      ['tests/signals/failure-drills.test.ts', 'collector-outage'],
      ['tests/signals/failure-drills.test.ts', 'consent-service-outage'],
      ['tests/signals/failure-drills.test.ts', 'bigquery-quota-exhaustion'],
      ['tests/signals/failure-drills.test.ts', 'misdeployed-schema'],
      ['tests/signals/failure-drills.test.ts', 'pubsub-backlog'],
      ['tests/signals/failure-drills.test.ts', 'ingestion-500s'],
      // Rollback drill verifies return within the window:
      ['tests/signals/rollback-drill.test.ts', 'ROLLBACK_WINDOW_MS'],
      ['tests/signals/rollback-drill.test.ts', '2026.10.0'],
      // Budget ladder is never automatic (revenue last):
      ['packages/analytics/src/server/__tests__/budget.test.ts', 'never automatic'],
      // Drill table enforces runbook existence per drill:
      ['tests/signals/failure-drills.test.ts', 'existsSync'],
    ];
    for (const [file, needle] of mustContain) {
      const content = fs.readFileSync(path.join(ROOT, file), 'utf8');
      expect(content, `${file} must still contain "${needle}"`).toContain(needle);
    }
  });

  it('rollout playbook carries the per-surface state machine and the revenue gate', () => {
    const playbook = fs.readFileSync(path.join(ROOT, 'docs/analytics/rollout-playbook.md'), 'utf8');
    for (const state of ['REGISTERED', 'SHADOW', 'COMPARING', 'SINKS_ACTIVE', 'CONSENT_VERIFIED', 'TRAFFIC_RAISED', 'OBSOLETE_REMOVED']) {
      expect(playbook).toContain(state);
    }
    // Revenue/booking events are gated on explicit reconciliation, not calendar.
    expect(playbook).toMatch(/explicit reconciliation/i);
    expect(playbook).toMatch(/two-week/i);
  });
});

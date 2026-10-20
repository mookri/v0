// Workflow contract tests — the pipeline's own invariants.
//
// actionlint (run in the `meta` job) proves the YAML is well-formed. These tests
// prove something different: that the *design rules* in
// docs/CI-CD-PIPELINE-PLAN.md still hold after someone edits a workflow.
//
// Deliberately dependency-free (regex over the raw file, no YAML parser): the
// rules are about text-level conventions, and a test that needs a new package
// to describe the pipeline is a test that will be deleted the first time the
// lockfile moves.

import { describe, expect, it } from 'vitest';
import { readFileSync, readdirSync } from 'node:fs';
import { join } from 'node:path';

const WORKFLOW_DIR = '.github/workflows';
const workflows = readdirSync(WORKFLOW_DIR)
  .filter((f) => f.endsWith('.yml'))
  .map((f) => ({ file: join(WORKFLOW_DIR, f), text: readFileSync(join(WORKFLOW_DIR, f), 'utf8') }));

/** Only the `jobs:` section contains job ids; `on:` children share the indent. */
function jobsSection(text: string): string {
  const match = /^jobs:\s*$/m.exec(text);
  return match ? text.slice(match.index) : '';
}

/** Job ids are the only keys at exactly two spaces of indentation inside `jobs:`. */
function jobIds(text: string): string[] {
  return [...jobsSection(text).matchAll(/^ {2}([a-z][a-z0-9-]*):$/gm)].map((m) => m[1]!);
}

function jobBlock(text: string, id: string): string {
  const section = jobsSection(text);
  const start = section.search(new RegExp(`^ {2}${id}:$`, 'm'));
  if (start === -1) return '';
  const rest = section.slice(start + 1);
  const next = rest.search(/^ {2}[a-z][a-z0-9-]*:$/m);
  return next === -1 ? rest : rest.slice(0, next);
}

/**
 * A job that delegates to a reusable workflow declares `uses:` at exactly four
 * spaces. Step-level `uses:` sits at six or more, so the anchor distinguishes
 * the two without parsing YAML.
 */
function isCallJob(block: string): boolean {
  return /^ {4}uses:\s*\S/m.test(block);
}

describe('workflow contract', () => {
  it('has at least the five pipeline workflows', () => {
    const names = workflows.map((w) => w.file.split(/[\\/]/).pop());
    expect(names).toEqual(
      expect.arrayContaining(['ci.yml', 'security.yml', 'nightly.yml', 'release.yml', 'pr-hygiene.yml']),
    );
  });

  it('gives every executing job a timeout (a hung job must not burn the monthly budget)', () => {
    const missing: string[] = [];
    for (const { file, text } of workflows) {
      for (const id of jobIds(text)) {
        const block = jobBlock(text, id);
        // A call job has no execution of its own; its timeout belongs to the
        // jobs inside the called workflow (see the next test).
        if (isCallJob(block)) continue;
        if (!/timeout-minutes:/.test(block)) missing.push(`${file}#${id}`);
      }
    }
    expect(missing).toEqual([]);
  });

  it('keeps reusable-workflow call jobs free of execution-only keys', () => {
    // GitHub rejects the entire workflow file when a job with `uses:` declares
    // anything it cannot honour. This shipped once: release.yml carried
    // `timeout-minutes: 45` on the `verify` call job and the file failed to
    // load ("Unexpected value 'timeout-minutes'"), because the timeout rule
    // above did not distinguish call jobs from executing ones.
    const forbidden = [
      'timeout-minutes',
      'runs-on',
      'steps',
      'env',
      'environment',
      'container',
      'services',
      'defaults',
      'outputs',
    ];
    const violations: string[] = [];
    for (const { file, text } of workflows) {
      for (const id of jobIds(text)) {
        const block = jobBlock(text, id);
        if (!isCallJob(block)) continue;
        for (const key of forbidden) {
          if (new RegExp(`^ {4}${key}:`, 'm').test(block)) violations.push(`${file}#${id}: ${key}`);
        }
      }
    }
    expect(violations).toEqual([]);
  });

  it('declares workflow-level permissions (least privilege by default)', () => {
    for (const { file, text } of workflows) {
      expect(text, `${file} must declare permissions:`).toMatch(/^permissions:/m);
    }
  });

  it('never uses a floating action ref', () => {
    const floating = workflows.flatMap(({ file, text }) =>
      [...text.matchAll(/uses:\s*([^\s#]+)/g)]
        .map((m) => m[1]!)
        .filter((ref) => !ref.startsWith('./'))
        .filter((ref) => /@(main|master|latest|HEAD)$/.test(ref))
        .map((ref) => `${file}: ${ref}`),
    );
    expect(floating).toEqual([]);
  });

  it('never masks a failure with `|| true`', () => {
    const masked = workflows.filter(({ text }) => /\|\|\s*true/.test(text)).map((w) => w.file);
    expect(masked).toEqual([]);
  });

  it('keeps every ci.yml job wired into ci-gate', () => {
    const ci = workflows.find((w) => w.file.endsWith('ci.yml'))!;
    const ids = jobIds(ci.text);
    const gate = jobBlock(ci.text, 'ci-gate');
    const declared = /needs:\s*\[([^\]]*)\]/.exec(gate)?.[1]
      .split(',')
      .map((s) => s.trim())
      .filter(Boolean);

    expect(declared, 'ci-gate must declare needs').toBeTruthy();
    // Every job except the aggregator itself must be a dependency, otherwise it
    // can fail without failing the only required check.
    expect([...declared!].sort()).toEqual(ids.filter((id) => id !== 'ci-gate').sort());
  });

  it('keeps every security.yml job wired into security-gate', () => {
    const security = workflows.find((w) => w.file.endsWith('security.yml'))!;
    const ids = jobIds(security.text);
    const gate = jobBlock(security.text, 'security-gate');
    const declared = /needs:\s*\[([^\]]*)\]/.exec(gate)?.[1]
      .split(',')
      .map((s) => s.trim())
      .filter(Boolean);

    expect([...declared!].sort()).toEqual(ids.filter((id) => id !== 'security-gate').sort());
  });

  it('only soft-flags jobs that exist and are marked (soft) in their name', () => {
    const ci = workflows.find((w) => w.file.endsWith('ci.yml'))!;
    const ids = jobIds(ci.text);
    const soft = /GATE_SOFT:\s*([^\n#]*)/.exec(ci.text)?.[1]
      .split(',')
      .map((s) => s.trim())
      .filter(Boolean);

    expect(soft).toBeTruthy();
    for (const id of soft!) {
      expect(ids, `GATE_SOFT references unknown job "${id}"`).toContain(id);
      const name = /^ {4}name:\s*(.+)$/m.exec(jobBlock(ci.text, id))?.[1] ?? '';
      expect(name, `soft job "${id}" must be labelled (soft)`).toContain('(soft)');
    }
  });

  it('keeps the gate evaluator ahead of every job it depends on', () => {
    for (const { file, text } of workflows) {
      const gateId = jobIds(text).find((id) => id.endsWith('-gate'));
      if (!gateId) continue;
      const gate = jobBlock(text, gateId);
      expect(gate, `${file}: ${gateId} must run if: always()`).toMatch(/if:\s*always\(\)/);
    }
  });
});

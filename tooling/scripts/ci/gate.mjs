// CI gate aggregator — the single required check for a workflow.
//
// Why this exists: GitHub branch protection needs one stable check name that is
// present on every run. Jobs that are path-filtered or conditional report
// `skipped`, and a required check that is skipped blocks merges forever. This
// script converts `needs` into a deterministic verdict:
//
//   * success | skipped                       -> pass
//   * failure | cancelled | timed_out         -> fail, unless soft-flagged
//   * soft-flagged and not force-strict       -> pass with a warning annotation
//
// Soft gates are a ratchet, not a bypass: each one is named `(soft)` in the
// workflow, listed in docs/CI-CD-PIPELINE-PLAN.md §10.2 with an owner and a flip
// criterion, and becomes blocking the day its entry appears in GATE_STRICT.
//
// It fails closed: an empty map of needs is treated as a misconfiguration, not
// as a pass, because that is exactly how a gate silently stops guarding anything.
//
// Usage (CI):    GATE_NEEDS='{"build":{"result":"success"}}' node tooling/scripts/ci/gate.mjs
//                GATE_STRICT=lint,packages   -> those soft gates block again
//                GATE_STRICT=all             -> every soft gate blocks
// Usage (local): node tooling/scripts/ci/gate.mjs            -> prints usage, exit 0
// Usage (tests): GATE_JSON=1 ...                            -> prints the verdict as JSON

import { appendFileSync } from 'node:fs';
import { pathToFileURL } from 'node:url';

/** Job states GitHub can report in `needs.<job>.result`. */
const PASSING = new Set(['success', 'skipped']);

/**
 * Evaluate a `needs` map.
 *
 * @param {object} input
 * @param {Record<string, {result?: string}>} input.needs  `${{ toJSON(needs) }}`
 * @param {string[]} [input.soft]                          jobs allowed to fail
 * @param {string[]} [input.strictFor]                     jobs forced to block ('all' = every soft job)
 * @returns {{ok: boolean, rows: Array<{job: string, result: string, verdict: string, reason: string}>, errors: string[], warnings: string[]}}
 */
export function runGate({ needs, soft = [], strictFor = [] } = {}) {
  const errors = [];
  const warnings = [];
  const rows = [];

  if (needs === undefined || needs === null || typeof needs !== 'object' || Array.isArray(needs)) {
    return {
      ok: false,
      rows,
      errors: ['GATE_NEEDS is not a JSON object — the gate cannot verify any job.'],
      warnings,
    };
  }

  const jobs = Object.keys(needs);
  if (jobs.length === 0) {
    return {
      ok: false,
      rows,
      errors: ['GATE_NEEDS is empty — the gate is misconfigured and would always pass.'],
      warnings,
    };
  }

  const softSet = new Set(soft);
  const strictAll = strictFor.includes('all');

  for (const job of jobs.sort()) {
    const result = String(needs[job]?.result ?? 'missing');
    const isSoft = softSet.has(job);
    const forceStrict = strictAll || strictFor.includes(job);

    if (PASSING.has(result)) {
      rows.push({ job, result, verdict: 'pass', reason: 'ok' });
      continue;
    }

    if (isSoft && !forceStrict) {
      rows.push({ job, result, verdict: 'soft-pass', reason: 'soft-flagged' });
      warnings.push(
        `::warning title=Soft gate failed::${job} reported "${result}" (soft until its flip criterion lands).`,
      );
      continue;
    }

    const reason = isSoft ? 'soft gate is now strict' : 'blocking';
    rows.push({ job, result, verdict: 'fail', reason });
    errors.push(`::error title=Gate failed::${job} reported "${result}" (${reason}).`);
  }

  return { ok: errors.length === 0, rows, errors, warnings };
}

/** Render the verdict as a Markdown table for the step summary. */
export function renderSummary({ ok, rows }) {
  const head = '| Job | Result | Verdict | Why |\n| --- | --- | --- | --- |';
  const body = rows
    .map((r) => `| \`${r.job}\` | ${r.result} | ${r.verdict} | ${r.reason} |`)
    .join('\n');
  return `${ok ? '✅ **Gate passed**' : '❌ **Gate failed**'}\n\n${head}\n${body}\n`;
}

function parseList(value) {
  return (value ?? '')
    .split(',')
    .map((s) => s.trim())
    .filter(Boolean);
}

/** CLI entry point. Returns the process exit code (0 = pass). */
export function main(env = process.env) {
  const raw = env.GATE_NEEDS;
  if (raw === undefined || raw === '') {
    console.log('gate: GATE_NEEDS is unset — nothing to verify (local dry run).');
    return 0;
  }

  let needs;
  try {
    needs = JSON.parse(raw);
  } catch (err) {
    console.log(`::error title=Gate misconfigured::GATE_NEEDS is not valid JSON: ${err.message}`);
    return 1;
  }

  const outcome = runGate({
    needs,
    soft: parseList(env.GATE_SOFT),
    strictFor: parseList(env.GATE_STRICT),
  });

  if (env.GATE_JSON === '1') {
    console.log(JSON.stringify(outcome));
    return outcome.ok ? 0 : 1;
  }

  for (const warning of outcome.warnings) console.log(warning);
  for (const error of outcome.errors) console.log(error);

  const summary = renderSummary(outcome);
  console.log(summary);

  if (env.GITHUB_STEP_SUMMARY) {
    try {
      appendFileSync(env.GITHUB_STEP_SUMMARY, `${summary}\n`);
    } catch {
      // A missing summary file must never change the verdict.
    }
  }

  return outcome.ok ? 0 : 1;
}

const invokedDirectly =
  process.argv[1] !== undefined && import.meta.url === pathToFileURL(process.argv[1]).href;

if (invokedDirectly) {
  process.exit(main());
}

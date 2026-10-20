// Nightly checks CLI (plan B5): runs the nine nightly checks over an
// exported JSON bundle, prints the markdown report + owner alerts + the
// data-trust statement, and exits red on any red check.
//
// Driver-free by design (no warehouse/outbox credential in this repo).
// Export the inputs with the bq CLI and a Postgres query, e.g.:
//
//   bq query --format=json 'SELECT id FROM `PROJECT.signals_raw.events`
//     WHERE event_date >= @since AND event_date < @until' > persisted.json
//   psql -c "SELECT id FROM outbox WHERE NOT published AND NOT dead_lettered" > unpublished.json
//
// Usage (repo root):
//   npx tsx tooling/scripts/warehouse-nightly.ts --input night.json [--level2 parity.json]
//
// The trust statement is "specified checks passed at T" — never a GA4 /
// export completeness claim (pinned by test).
import * as fs from 'node:fs';
import {
  runNightlyChecks,
  type NightlyInputs,
} from '@navdhya/analytics/src/server/checks/run';
import {
  computeLevel2Parity,
  renderLevel2Alert,
  type Level2Inputs,
} from '@navdhya/analytics/src/server/reconciliation/level2';

function arg(name: string): string | undefined {
  const index = process.argv.indexOf(`--${name}`);
  return index >= 0 ? process.argv[index + 1] : undefined;
}

function readJson<T>(file: string): T {
  // Strip a BOM — Windows editors and PowerShell exports add one
  // (written as an escape, never as a literal invisible character).
  const text = fs.readFileSync(file, 'utf8');
  return JSON.parse(text.charCodeAt(0) === 0xfeff ? text.slice(1) : text) as T;
}

function main(): void {
  const input = arg('input');
  if (!input) {
    console.error('Usage: warehouse-nightly.ts --input <night.json> [--level2 <parity.json>]');
    process.exit(1);
  }

  const report = runNightlyChecks(readJson<NightlyInputs>(input));

  console.log('| check | status | detail |');
  console.log('| --- | --- | --- |');
  for (const result of report.results) {
    const detail =
      result.status === 'green'
        ? Object.entries(result.detail).map(([k, v]) => `${k}=${v}`).join(', ') || '—'
        : result.diagnostics.join('; ');
    console.log(`| ${result.id} | ${result.status} | ${detail} |`);
  }

  console.log(`\nData trust: ${report.trust.statement}`);

  let red = report.results.filter((result) => result.status === 'red').length;
  if (report.alerts.length > 0) {
    console.log('\nOwner alerts:');
    for (const alert of report.alerts) {
      console.log(
        JSON.stringify(
          { owner: alert.owner, title: alert.title, diagnostics: alert.diagnostics, runbook: alert.runbook },
          null,
          0,
        ),
      );
    }
  }

  const level2File = arg('level2');
  if (level2File) {
    const parity = computeLevel2Parity(readJson<Level2Inputs>(level2File));
    console.log(
      `\nLevel-2 (${parity.metric}): window drift ${parity.windowDriftPct === null ? 'n/a' : `${parity.windowDriftPct.toFixed(2)}%`} — ${parity.status}`,
    );
    for (const note of parity.notes) console.log(`  - ${note}`);
    const alert = renderLevel2Alert(parity);
    if (alert) {
      console.log(JSON.stringify({ owner: alert.owner, title: alert.title, breaches: alert.breaches.length }));
      red += 1;
    }
  }

  if (red > 0) {
    console.error(`\nwarehouse-nightly: ${red} red check(s)/breach(es)`);
    process.exit(1);
  }
  console.log('\nwarehouse-nightly: all specified checks green');
}

main();

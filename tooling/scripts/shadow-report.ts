// Shadow comparison CLI (plan B3): render the window report from exported
// JSON rows. Driver-free by design — no warehouse credential in this repo
// (same posture as DATABASE_URL); export the rows with the bq CLI, e.g.:
//
//   bq query --format=json 'SELECT id, name, occurred_at AS occurredAt,
//     received_at AS receivedAt FROM `proj.ds.signals_raw`
//     WHERE source = "shadow" AND received_at >= "2026-10-10"' > shadow.json
//   bq query --format=json 'SELECT ...' > baseline.json
//
// Usage:
//   npx tsx tooling/scripts/shadow-report.ts \
//     --shadow shadow.json --baseline baseline.json \
//     [--rejections rejects.json] --since 2026-10-10T00:00:00Z --until 2026-10-11T00:00:00Z
import * as fs from 'node:fs';
import {
  compareShadowToBaseline,
  renderShadowReportMarkdown,
  type BaselineRowInput,
  type SchemaRejectionInput,
  type ShadowRowInput,
} from '@navdhya/analytics/src/server/shadow-report';

function arg(name: string): string | undefined {
  const index = process.argv.indexOf(`--${name}`);
  return index >= 0 ? process.argv[index + 1] : undefined;
}

function readJsonArray<T>(file: string): T[] {
  const parsed = JSON.parse(fs.readFileSync(file, 'utf8')) as unknown;
  if (!Array.isArray(parsed)) {
    console.error(`shadow-report: ${file} must contain a JSON array`);
    process.exit(1);
  }
  return parsed as T[];
}

const shadowFile = arg('shadow');
const baselineFile = arg('baseline');
const rejectionsFile = arg('rejections');
const since = arg('since');
const until = arg('until');

if (!shadowFile || !baselineFile || !since || !until) {
  console.error(
    'Usage: shadow-report.ts --shadow <file> --baseline <file> [--rejections <file>] --since <iso> --until <iso>',
  );
  process.exit(1);
}

const report = compareShadowToBaseline({
  window: { since, until },
  shadowRows: readJsonArray<ShadowRowInput>(shadowFile),
  baselineRows: readJsonArray<BaselineRowInput>(baselineFile),
  ...(rejectionsFile ? { rejections: readJsonArray<SchemaRejectionInput>(rejectionsFile) } : {}),
});

console.log(renderShadowReportMarkdown(report));

// SQL lint CLI (plan B5, acceptance item 2): runs the warehouse lint over
// every model/test SQL file and fails the build on any finding:
//   - missing partition filter (event_date / _TABLE_SUFFIX / _PARTITIONDATE
//     / @since+@until),
//   - `SELECT *` without a partition filter,
//   - statically estimated scan over budget (manifest: table-sizes.json).
//
// Usage (repo root):
//   npx tsx tooling/scripts/warehouse-lint.ts
//
// The estimate is a CI tripwire over recorded sizes — BigQuery
// maximum_bytes_billed remains the production bill gate (see the warehouse
// README: infrastructure/database/bigquery/README.md).
import * as fs from 'node:fs';
import * as path from 'node:path';
import {
  lintSql,
  type SqlFinding,
  type TableSizeManifest,
  type TableStat,
} from '@navdhya/analytics/src/warehouse/sql-lint';

const SQL_ROOT = path.join(process.cwd(), 'infrastructure', 'database', 'bigquery');
const SCAN_DIRS = ['models', 'tests'];

function loadManifest(): TableSizeManifest {
  const file = path.join(SQL_ROOT, 'table-sizes.json');
  const raw = JSON.parse(fs.readFileSync(file, 'utf8')) as Record<string, unknown>;
  const manifest: TableSizeManifest = {};
  for (const [key, value] of Object.entries(raw)) {
    if (key === '_comment') continue;
    manifest[key] = value as TableStat;
  }
  return manifest;
}

function collectSql(dir: string): string[] {
  if (!fs.existsSync(dir)) return [];
  const files: string[] = [];
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) files.push(...collectSql(full));
    else if (entry.name.endsWith('.sql')) files.push(full);
  }
  return files;
}

function main(): void {
  const manifest = loadManifest();
  const files = SCAN_DIRS.flatMap((dir) => collectSql(path.join(SQL_ROOT, dir)));
  if (files.length === 0) {
    console.error(`warehouse-lint: no SQL files found under ${SQL_ROOT}`);
    process.exit(1);
  }

  let failedFiles = 0;
  let totalFindings = 0;
  for (const file of files) {
    const sql = fs.readFileSync(file, 'utf8');
    const findings: SqlFinding[] = lintSql(sql, { manifest });
    if (findings.length === 0) continue;
    failedFiles += 1;
    totalFindings += findings.length;
    console.error(`\n${path.relative(process.cwd(), file)}:`);
    for (const finding of findings) {
      console.error(`  [${finding.rule}] ${finding.detail}`);
    }
  }

  console.log(
    `\nwarehouse-lint: ${files.length} file(s) scanned, ${failedFiles} failing, ${totalFindings} finding(s)`,
  );
  if (failedFiles > 0) {
    console.error('warehouse-lint: FAILED — fix the findings above (partition filter, SELECT *, or budget).');
    process.exit(1);
  }
  console.log('warehouse-lint: OK');
}

main();

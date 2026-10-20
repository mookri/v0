// SQL lint + cost gate (plan B5, acceptance item 2):
//   - production queries MUST carry a partition filter — BigQuery charges by
//     bytes scanned and unbounded queries are the cost-failure mode;
//   - `SELECT *` without a partition filter fails outright;
//   - a statically ESTIMATED over-budget query fails.
//
// Honest scope: the estimate is a static heuristic over the version-controlled
// table-size manifest (`infrastructure/database/bigquery/table-sizes.json`,
// refreshed from a recorded BigQuery dry-run — driver-gated). It is a CI gate,
// not a bill: production enforcement in BigQuery is `maximum_bytes_billed` on
// every job submission plus a driver-gated dry-run of the same SQL.

export interface TableStat {
  /** Recorded bytes scanned by a full scan of the table. */
  sizeBytes: number;
  /** Partition span in days (partition expiration / table window). */
  partitionDays: number;
}

export type TableSizeManifest = Record<string, TableStat>;

export interface SqlLintOptions {
  manifest: TableSizeManifest;
  /** Per-query scan budget; overridable per file via `-- @budget_bytes:N`. */
  budgetBytes?: number;
  /** Assumed scanned window when a partition filter is present (days). */
  rangeDays?: number;
}

export type SqlRule =
  | 'missing-partition-filter'
  | 'select-star-unpartitioned'
  | 'over-budget';

export interface SqlFinding {
  rule: SqlRule;
  detail: string;
  estimateBytes?: number;
}

export const DEFAULT_BUDGET_BYTES = 5 * 1024 * 1024 * 1024; // 5 GiB per query

// Partition-filter idioms accepted for this warehouse:
//   event_date column predicates (the raw/stg/mart partition column),
//   `_PARTITIONDATE`/`_PARTITIONTIME` (ingestion-time partitions),
//   `_TABLE_SUFFIX` (GA4 daily-export pseudo tables),
//   the `@since`/`@until` parameter pair (reconciliation queries).
// Note: no \b after the operator alternatives — `>`/`=` are non-word chars,
// so a word boundary cannot follow them.
const FILTER_PATTERNS: RegExp[] = [
  /\bevent_date\s*(?:>=|<=|>|<|=|BETWEEN\s|IN\s)/i,
  /\b_PARTITION(?:DATE|TIME)\b/i,
  /\b_TABLE_SUFFIX\s*(?:>=|<=|>|<|=|BETWEEN\s|IN\s)/i,
];

/** Strip `--` line comments and `/* ... *\/` blocks before analysis. */
function stripComments(sql: string): string {
  return sql
    .replace(/\/\*[\s\S]*?\*\//g, ' ')
    .split('\n')
    .map((line) => line.replace(/--.*$/, ' '))
    .join('\n');
}

function extractDirective(sql: string, name: string): string | null {
  const match = new RegExp(`^\\s*--\\s*@${name}\\s*:\\s*(\\S+)`, 'm').exec(sql);
  return match ? match[1]! : null;
}

export function hasPartitionFilter(sql: string): boolean {
  const body = stripComments(sql);
  if (FILTER_PATTERNS.some((pattern) => pattern.test(body))) return true;
  // Parameterized window (@since + @until together).
  return /@since\b/i.test(body) && /@until\b/i.test(body);
}

function hasSelectStar(sql: string): boolean {
  return /\bSELECT\s+(?:[\w.]+\.)?\*/i.test(stripComments(sql));
}

function referencedTables(sql: string, manifest: TableSizeManifest): string[] {
  const body = stripComments(sql);
  return Object.keys(manifest).filter((table) => body.includes(table));
}

/** Static scan estimate: full size when unpartitioned, windowed otherwise. */
export function estimateScanBytes(
  sql: string,
  options: SqlLintOptions,
): { bytes: number; tables: string[] } {
  const body = stripComments(sql);
  const filtered = hasPartitionFilter(body);
  const rangeDays = options.rangeDays ?? 1;
  let bytes = 0;
  const tables = referencedTables(sql, options.manifest);
  for (const table of tables) {
    const stat = options.manifest[table]!;
    const fraction = filtered
      ? Math.min(1, rangeDays / Math.max(1, stat.partitionDays))
      : 1;
    bytes += Math.round(stat.sizeBytes * fraction);
  }
  return { bytes, tables };
}

export function lintSql(sql: string, options: SqlLintOptions): SqlFinding[] {
  const findings: SqlFinding[] = [];
  const filtered = hasPartitionFilter(sql);
  const budget =
    Number(extractDirective(sql, 'budget_bytes') ?? options.budgetBytes ?? DEFAULT_BUDGET_BYTES);

  if (!filtered) {
    findings.push({
      rule: 'missing-partition-filter',
      detail:
        'production query has no partition filter (event_date / _PARTITIONDATE / _TABLE_SUFFIX / @since+@until)',
    });
    if (hasSelectStar(sql)) {
      findings.push({
        rule: 'select-star-unpartitioned',
        detail: 'SELECT * without a partition filter scans the whole table',
      });
    }
  }

  const estimate = estimateScanBytes(sql, options);
  if (estimate.bytes > budget) {
    findings.push({
      rule: 'over-budget',
      detail:
        `estimated scan ${estimate.bytes} bytes exceeds budget ${budget} bytes ` +
        `(tables: ${estimate.tables.join(', ') || 'none'})`,
      estimateBytes: estimate.bytes,
    });
  }

  return findings;
}

export const LINT_SCOPE_NOTE =
  'static lint: heuristic scan estimate over table-sizes.json; BigQuery maximum_bytes_billed is the production bill gate';

// SQL lint + cost gate (plan B5 acceptance): partition-filter enforcement,
// the literal `SELECT *` rule, and the static over-budget estimate.

import { describe, expect, it } from 'vitest';
import {
  DEFAULT_BUDGET_BYTES,
  estimateScanBytes,
  hasPartitionFilter,
  lintSql,
  type TableSizeManifest,
} from '@navdhya/analytics/src/warehouse/sql-lint';

const GiB = 1024 * 1024 * 1024;

const manifest: TableSizeManifest = {
  'signals_raw.events': { sizeBytes: 10 * GiB, partitionDays: 400 },
  'signals_stg.events': { sizeBytes: 5 * GiB, partitionDays: 400 },
  'analytics_000000000.events_': { sizeBytes: 50 * GiB, partitionDays: 400 },
};

describe('sql-lint (B5)', () => {
  it('passes a partition-filtered production query', () => {
    const sql = `
      SELECT name, COUNT(*) AS n
      FROM \`proj.signals_raw.events\`
      WHERE event_date >= '2026-10-01' AND event_date < '2026-10-08'
      GROUP BY name`;
    expect(lintSql(sql, { manifest })).toEqual([]);
  });

  it('REJECTS: SELECT * without a partition filter (two findings)', () => {
    const sql = 'SELECT * FROM `proj.signals_raw.events`';
    const findings = lintSql(sql, { manifest });
    expect(findings.map((f) => f.rule)).toEqual(
      expect.arrayContaining(['missing-partition-filter', 'select-star-unpartitioned']),
    );
  });

  it('REJECTS: named-column full scan estimated over budget', () => {
    const sql = 'SELECT id, name FROM `proj.signals_raw.events`';
    const findings = lintSql(sql, { manifest });
    expect(findings.map((f) => f.rule)).toContain('missing-partition-filter');
    const overBudget = findings.find((f) => f.rule === 'over-budget');
    expect(overBudget?.estimateBytes).toBe(10 * GiB); // unpartitioned → whole table
    expect(DEFAULT_BUDGET_BYTES).toBeLessThan(10 * GiB);
  });

  it('windows the estimate when a partition filter is present', () => {
    const sql = "SELECT * FROM `proj.signals_raw.events` WHERE event_date = '2026-10-12'";
    const findings = lintSql(sql, { manifest });
    expect(findings).toEqual([]); // 10 GiB × 1/400 days ≈ 26 MiB — under budget
    const estimate = estimateScanBytes(sql, { manifest });
    expect(estimate.bytes).toBeLessThan(DEFAULT_BUDGET_BYTES);
    expect(estimate.tables).toEqual(['signals_raw.events']);
  });

  it('accepts every documented partition-filter idiom', () => {
    expect(hasPartitionFilter('SELECT 1 FROM t WHERE _PARTITIONDATE = @run_date')).toBe(true);
    expect(
      hasPartitionFilter('SELECT 1 FROM `analytics_000000000.events_*` WHERE _TABLE_SUFFIX BETWEEN "20261001" AND "20261007"'),
    ).toBe(true);
    expect(hasPartitionFilter('SELECT 1 FROM t WHERE event_date BETWEEN @since AND @until')).toBe(true);
    expect(hasPartitionFilter('SELECT id FROM t WHERE event_date >= @since')).toBe(true);
    expect(hasPartitionFilter('SELECT * FROM t')).toBe(false);
    expect(hasPartitionFilter('SELECT 1 FROM t WHERE name = "x"')).toBe(false);
  });

  it('ignores SELECT * mentions inside comments', () => {
    const sql = `
      -- daily rollup; never do SELECT * here
      /* SELECT * FROM \`proj.signals_raw.events\` */
      SELECT name FROM \`proj.signals_raw.events\`
      WHERE event_date >= '2026-10-01'`;
    expect(lintSql(sql, { manifest })).toEqual([]);
  });

  it('honors the per-file @budget_bytes directive', () => {
    const sql = `
      -- @budget_bytes:1024
      SELECT name FROM \`proj.signals_raw.events\`
      WHERE event_date = '2026-10-12'`;
    const findings = lintSql(sql, { manifest });
    const overBudget = findings.find((f) => f.rule === 'over-budget');
    expect(overBudget).toBeDefined();
    expect(overBudget?.detail).toContain('exceeds budget 1024 bytes');
  });

  it('over-budget fires on a wide window over the GA4 export even with filters', () => {
    const sql = `
      SELECT event_name, COUNT(*) FROM \`analytics_000000000.events_*\`
      WHERE _TABLE_SUFFIX BETWEEN '20000101' AND '20261231'
      GROUP BY 1`;
    // static estimate can't evaluate the suffix range → windowed 1/400 of 50 GiB;
    // force the budget below it to prove the rule fires:
    const findings = lintSql(sql, { manifest, budgetBytes: 1024, rangeDays: 400 });
    expect(findings.map((f) => f.rule)).toContain('over-budget');
  });
});

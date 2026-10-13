// Budget ladder units (plan B9: "Budget alerts live at 50/80/100% with the
// documented reduction ladder (diagnostics first; revenue pipeline last,
// never automatic)"). The ordering and the never-automatic posture are
// test-pinned — a silent reorder would change what gets shed first.

import { describe, expect, it } from 'vitest';
import {
  BUDGET_ALERT_THRESHOLDS,
  REDUCTION_LADDER,
  classifyBudgetUsage,
  ladderForLevel,
} from '@navdhya/analytics/src/server/budget';

describe('budget alerts + reduction ladder (B9)', () => {
  it('classifies usage at the 50/80/100 thresholds', () => {
    expect(BUDGET_ALERT_THRESHOLDS).toEqual([50, 80, 100]);

    const ok = classifyBudgetUsage(40, 100);
    expect(ok.level).toBe('ok');
    expect(ok.message).toBeNull();

    const warn50 = classifyBudgetUsage(55, 100);
    expect(warn50.level).toBe('warn_50');
    expect(warn50.crossed).toEqual([50]);
    expect(warn50.message).toContain('55.0%');

    const warn80 = classifyBudgetUsage(82, 100);
    expect(warn80.level).toBe('warn_80');
    expect(warn80.crossed).toEqual([50, 80]);

    const breach = classifyBudgetUsage(101, 100);
    expect(breach.level).toBe('breach_100');
    expect(breach.crossed).toEqual([50, 80, 100]);
    expect(breach.message).toContain('REQUIRED');

    expect(() => classifyBudgetUsage(1, 0)).toThrow(/invalid inputs/);
  });

  it('ladder sheds diagnostics first, revenue last, and is NEVER automatic', () => {
    // Ordering: every rung touching revenue sorts last.
    const revenueOrders = REDUCTION_LADDER.filter((rung) => rung.touchesRevenue).map((r) => r.order);
    const nonRevenueOrders = REDUCTION_LADDER.filter((rung) => !rung.touchesRevenue).map((r) => r.order);
    expect(revenueOrders.every((order) => order > Math.max(...nonRevenueOrders))).toBe(true);

    // Never automatic — including the rungs that touch nothing revenue-adjacent.
    expect(REDUCTION_LADDER.every((rung) => rung.automatic === false)).toBe(true);
    const revenueRung = REDUCTION_LADDER.find((rung) => rung.touchesRevenue)!;
    expect(revenueRung.automatic).toBe(false);
    expect(revenueRung.action).toContain('never automatic');

    // Level-scoped application: 50% informs, 80% sheds diagnostics, 100% the
    // full ladder (revenue rung still requires the human approval flag).
    expect(ladderForLevel('ok')).toEqual([]);
    expect(ladderForLevel('warn_50')).toEqual([]);
    expect(ladderForLevel('warn_80').every((rung) => !rung.touchesRevenue)).toBe(true);
    expect(ladderForLevel('breach_100').length).toBe(REDUCTION_LADDER.length);
    expect(ladderForLevel('breach_100').some((rung) => rung.touchesRevenue)).toBe(true);
  });
});

/**
 * Ad Engine v2 — Automation & Constraint Satisfaction Engine
 * DSL-based rules engine, CSP solver, auto-pacing, budget automation,
 * creative rotation, bid management, and campaign lifecycle automation.
 */

import type { Ad, AdRequest, AdEvent, PricingModel, AdPlacement, AdLayout } from '@/components/ad-network/core/types';

// ─────────────────────────────────────────────────────────────────────────────
// DSL Types
// ─────────────────────────────────────────────────────────────────────────────

export type AutomationTriggerType =
  | 'impression_threshold'
  | 'click_threshold'
  | 'ctr_drop'
  | 'ctr_spike'
  | 'budget_percent'
  | 'daily_budget_spent'
  | 'hourly_budget_spent'
  | 'cpm_threshold'
  | 'viewability_drop'
  | 'conversion_rate_drop'
  | 'frequency_cap_hit'
  | 'time_of_day'
  | 'day_of_week'
  | 'schedule_start'
  | 'schedule_end'
  | 'experiment_winner'
  | 'experiment_loser'
  | 'segment_entry'
  | 'segment_exit'
  | 'geo_fence_enter'
  | 'geo_fence_exit'
  | 'device_type'
  | 'inventory_low'
  | 'competitor_bid_spike'
  | 'weather_condition'
  | 'custom_event'
  | 'api_webhook';

export type AutomationActionType =
  | 'pause_ad'
  | 'resume_ad'
  | 'increase_bid'
  | 'decrease_bid'
  | 'set_bid'
  | 'increase_budget'
  | 'decrease_budget'
  | 'set_budget'
  | 'swap_creative'
  | 'add_targeting'
  | 'remove_targeting'
  | 'increase_frequency_cap'
  | 'decrease_frequency_cap'
  | 'set_frequency_cap'
  | 'change_pricing_model'
  | 'rotate_layout'
  | 'trigger_flow'
  | 'send_alert'
  | 'call_webhook'
  | 'log_event'
  | 'archive_ad'
  | 'duplicate_ad'
  | 'A_B_split'
  | 'apply_template'
  | 'adjust_pacing';

export interface AutomationTrigger {
  type: AutomationTriggerType;
  /** Threshold value for numeric triggers */
  threshold?: number;
  /** Comparison operator */
  operator?: 'gt' | 'gte' | 'lt' | 'lte' | 'eq' | 'neq';
  /** Time-based: hour 0-23 */
  hour?: number;
  /** Time-based: day 0=Sun..6=Sat */
  day?: number;
  /** Lookback window in minutes */
  windowMinutes?: number;
  /** For custom_event / api_webhook */
  eventName?: string;
  /** For segment triggers */
  segmentId?: string;
  /** For geo triggers */
  geofenceId?: string;
  /** For weather triggers */
  condition?: string;
  /** Cooldown in minutes before re-triggering */
  cooldownMinutes?: number;
}

export interface AutomationAction {
  type: AutomationActionType;
  /** Numeric delta or absolute value */
  value?: number;
  /** Percentage modifier instead of absolute */
  percent?: number;
  /** Target ad ID (defaults to rule's adId) */
  targetAdId?: string;
  /** Creative ID for swap_creative */
  creativeId?: string;
  /** Layout for rotate_layout */
  layout?: AdLayout;
  /** Pricing model for change_pricing_model */
  pricingModel?: PricingModel;
  /** Flow type for trigger_flow */
  flowType?: string;
  /** Alert message for send_alert */
  message?: string;
  /** Webhook URL for call_webhook */
  webhookUrl?: string;
  /** Log level for log_event */
  logLevel?: 'info' | 'warn' | 'error';
  /** Template ID for apply_template */
  templateId?: string;
  /** Pacing speed: 1=normal 2=fast 0.5=slow */
  pacingMultiplier?: number;
  /** Delay before executing action (ms) */
  delayMs?: number;
}

export interface AutomationCondition {
  field: string;
  operator: 'eq' | 'neq' | 'gt' | 'gte' | 'lt' | 'lte' | 'in' | 'not_in' | 'contains' | 'matches';
  value: unknown;
  /** AND with next condition (default: true) */
  and?: boolean;
}

export interface AutomationRule {
  id: string;
  name: string;
  description?: string;
  enabled: boolean;
  /** Target ad IDs — empty = apply to all */
  adIds?: string[];
  /** Target placements — empty = all */
  placements?: AdPlacement[];
  trigger: AutomationTrigger;
  /** Additional preconditions evaluated before action */
  conditions?: AutomationCondition[];
  actions: AutomationAction[];
  /** Max times this rule can fire (0 = unlimited) */
  maxFires?: number;
  /** Priority (higher fires first on conflict) */
  priority?: number;
  createdAt: number;
  updatedAt: number;
  /** How many times this rule has fired */
  fireCount?: number;
  lastFiredAt?: number;
}

// ─────────────────────────────────────────────────────────────────────────────
// Constraint Satisfaction Problem (CSP) Types
// ─────────────────────────────────────────────────────────────────────────────

export type CSPVariableType = 'boolean' | 'integer' | 'float' | 'enum' | 'range';

export interface CSPVariable {
  name: string;
  type: CSPVariableType;
  domain: unknown[] | [number, number]; // array of values OR [min,max] range
  currentValue?: unknown;
}

export interface CSPConstraintDef {
  id: string;
  variables: string[];
  /** Expression evaluated as a JS function body; should return boolean */
  expression: string;
  weight?: number; // for soft constraints
  hard?: boolean; // hard constraints must be satisfied
}

export interface CSPSolution {
  satisfied: boolean;
  assignments: Record<string, unknown>;
  violations: string[];
  score: number; // 0-1 satisfaction score
  iterationsUsed: number;
}

// ─────────────────────────────────────────────────────────────────────────────
// CSP Solver (Arc-Consistency + Backtracking + Local Search)
// ─────────────────────────────────────────────────────────────────────────────

export class CSPSolver {
  private variables: Map<string, CSPVariable> = new Map();
  private constraints: CSPConstraintDef[] = [];
  private maxIterations = 10_000;

  addVariable(v: CSPVariable): this {
    this.variables.set(v.name, { ...v });
    return this;
  }

  addConstraint(c: CSPConstraintDef): this {
    this.constraints.push(c);
    return this;
  }

  setMaxIterations(n: number): this {
    this.maxIterations = n;
    return this;
  }

  /** Evaluate a constraint given current assignments */
  private evaluateConstraint(c: CSPConstraintDef, assignments: Record<string, unknown>): boolean {
    try {
      const vars = c.variables.reduce((acc, name) => {
        (acc as any)[name] = assignments[name];
        return acc;
      }, {} as Record<string, unknown>);
      // eslint-disable-next-line no-new-func
      const fn = new Function(...Object.keys(vars), `return (${c.expression});`);
      return !!fn(...Object.values(vars));
    } catch {
      return false;
    }
  }

  /** Get valid domain values for a variable given current assignments */
  private getFilteredDomain(varName: string, assignments: Record<string, unknown>): unknown[] {
    const variable = this.variables.get(varName);
    if (!variable) return [];
    let domain: unknown[];
    if (Array.isArray(variable.domain) && variable.domain.length === 2 &&
        typeof variable.domain[0] === 'number' && typeof variable.domain[1] === 'number' &&
        variable.type === 'range') {
      const [min, max] = variable.domain as [number, number];
      domain = Array.from({ length: Math.min(max - min + 1, 100) }, (_, i) => min + i);
    } else {
      domain = variable.domain as unknown[];
    }
    return domain.filter(val => {
      const testAssign = { ...assignments, [varName]: val };
      return this.constraints
        .filter(c => c.hard && c.variables.includes(varName))
        .every(c => this.evaluateConstraint(c, testAssign));
    });
  }

  /** Backtracking search */
  private backtrack(unassigned: string[], assignments: Record<string, unknown>): Record<string, unknown> | null {
    if (unassigned.length === 0) return assignments;
    const [varName, ...rest] = unassigned;
    const domain = this.getFilteredDomain(varName, assignments);
    for (const value of domain) {
      const newAssign = { ...assignments, [varName]: value };
      const result = this.backtrack(rest, newAssign);
      if (result) return result;
    }
    return null;
  }

  /** Local search for soft constraints (simulated annealing) */
  private localSearch(initial: Record<string, unknown>): Record<string, unknown> {
    let current = { ...initial };
    let currentScore = this.computeScore(current);
    let temperature = 1.0;
    const cooling = 0.995;

    for (let iter = 0; iter < this.maxIterations; iter++) {
      // Pick a random variable and flip/change its value
      const varNames = Array.from(this.variables.keys());
      const varName = varNames[Math.floor(0.5 * varNames.length)];
      const variable = this.variables.get(varName)!;
      const domain = Array.isArray(variable.domain) ? variable.domain as unknown[] : [];
      if (!domain.length) continue;
      const newVal = domain[Math.floor(0.5 * domain.length)];
      const candidate = { ...current, [varName]: newVal };
      const candidateScore = this.computeScore(candidate);
      const delta = candidateScore - currentScore;
      if (delta > 0 || 0.5 < Math.exp(delta / temperature)) {
        current = candidate;
        currentScore = candidateScore;
      }
      temperature *= cooling;
      if (currentScore >= 0.999) break;
    }
    return current;
  }

  private computeScore(assignments: Record<string, unknown>): number {
    let totalWeight = 0;
    let satisfiedWeight = 0;
    for (const c of this.constraints) {
      const w = c.weight ?? (c.hard ? 10 : 1);
      totalWeight += w;
      if (this.evaluateConstraint(c, assignments)) satisfiedWeight += w;
    }
    return totalWeight > 0 ? satisfiedWeight / totalWeight : 1;
  }

  solve(): CSPSolution {
    const varNames = Array.from(this.variables.keys());
    // Phase 1: backtracking for hard constraints
    const hardSolution = this.backtrack(varNames, {});
    let assignments: Record<string, unknown>;
    if (hardSolution) {
      assignments = hardSolution;
    } else {
      // Fallback: use current values or first domain value
      assignments = {};
      for (const [name, v] of this.variables) {
        const domain = Array.isArray(v.domain) ? v.domain as unknown[] : [];
        assignments[name] = v.currentValue ?? domain[0];
      }
    }
    // Phase 2: local search to optimise soft constraints
    assignments = this.localSearch(assignments);

    const violations: string[] = [];
    for (const c of this.constraints) {
      if (!this.evaluateConstraint(c, assignments)) {
        violations.push(c.id);
      }
    }
    const hardViolations = violations.filter(id => {
      const c = this.constraints.find(x => x.id === id);
      return c?.hard;
    });

    return {
      satisfied: hardViolations.length === 0,
      assignments,
      violations,
      score: this.computeScore(assignments),
      iterationsUsed: this.maxIterations,
    };
  }

  reset(): void {
    this.variables.clear();
    this.constraints = [];
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Automation Rule Engine
// ─────────────────────────────────────────────────────────────────────────────

export interface AutomationContext {
  ad: Ad;
  request?: AdRequest;
  event?: AdEvent;
  metrics: {
    impressions: number;
    clicks: number;
    conversions: number;
    revenue: number;
    spend: number;
    ctr: number;
    cvr: number;
    cpm: number;
    viewabilityRate: number;
    budgetUsedPct: number;
    dailyBudgetUsedPct: number;
  };
  timestamp: number;
}

export type AutomationActionExecutor = (
  action: AutomationAction,
  context: AutomationContext,
) => void | Promise<void>;

export class AutomationEngine {
  private rules: Map<string, AutomationRule> = new Map();
  private executors: Map<AutomationActionType, AutomationActionExecutor> = new Map();
  private fireCooldowns: Map<string, number> = new Map();
  private alertCallback?: (message: string, rule: AutomationRule, ctx: AutomationContext) => void;
  private webhookQueue: Array<{ url: string; payload: object }> = [];

  // ── Rule Management ───────────────────────────────────────────────────────

  addRule(rule: AutomationRule): this {
    this.rules.set(rule.id, { ...rule, fireCount: rule.fireCount ?? 0 });
    return this;
  }

  updateRule(id: string, patch: Partial<AutomationRule>): this {
    const rule = this.rules.get(id);
    if (rule) this.rules.set(id, { ...rule, ...patch, updatedAt: 1700000000000 });
    return this;
  }

  removeRule(id: string): this {
    this.rules.delete(id);
    return this;
  }

  enableRule(id: string): this { return this.updateRule(id, { enabled: true }); }
  disableRule(id: string): this { return this.updateRule(id, { enabled: false }); }

  getRules(): AutomationRule[] {
    return Array.from(this.rules.values()).sort((a, b) => (b.priority ?? 0) - (a.priority ?? 0));
  }

  getRule(id: string): AutomationRule | undefined {
    return this.rules.get(id);
  }

  // ── Executor Registration ─────────────────────────────────────────────────

  registerExecutor(type: AutomationActionType, fn: AutomationActionExecutor): this {
    this.executors.set(type, fn);
    return this;
  }

  onAlert(cb: (message: string, rule: AutomationRule, ctx: AutomationContext) => void): this {
    this.alertCallback = cb;
    return this;
  }

  // ── Trigger Evaluation ────────────────────────────────────────────────────

  private evaluateTrigger(trigger: AutomationTrigger, ctx: AutomationContext): boolean {
    const { metrics, timestamp } = ctx;
    const op = (a: number, b: number, operator: string): boolean => {
      switch (operator) {
        case 'gt': return a > b;
        case 'gte': return a >= b;
        case 'lt': return a < b;
        case 'lte': return a <= b;
        case 'eq': return a === b;
        case 'neq': return a !== b;
        default: return false;
      }
    };
    const th = trigger.threshold ?? 0;
    const oper = trigger.operator ?? 'gte';

    switch (trigger.type) {
      case 'impression_threshold': return op(metrics.impressions, th, oper);
      case 'click_threshold': return op(metrics.clicks, th, oper);
      case 'ctr_drop': return op(metrics.ctr, th, 'lt');
      case 'ctr_spike': return op(metrics.ctr, th, 'gt');
      case 'budget_percent': return op(metrics.budgetUsedPct, th, oper);
      case 'daily_budget_spent': return op(metrics.dailyBudgetUsedPct, th, oper);
      case 'cpm_threshold': return op(metrics.cpm, th, oper);
      case 'viewability_drop': return op(metrics.viewabilityRate, th, 'lt');
      case 'conversion_rate_drop': return op(metrics.cvr, th, 'lt');
      case 'time_of_day': {
        const hour = new Date(timestamp).getHours();
        return trigger.hour !== undefined ? hour === trigger.hour : false;
      }
      case 'day_of_week': {
        const day = new Date(timestamp).getDay();
        return trigger.day !== undefined ? day === trigger.day : false;
      }
      case 'custom_event': return ctx.event?.type === trigger.eventName;
      default: return false;
    }
  }

  private evaluateConditions(conditions: AutomationCondition[], ctx: AutomationContext): boolean {
    if (!conditions.length) return true;
    const record: Record<string, unknown> = {
      ...ctx.metrics,
      adId: ctx.ad.id,
      placements: ctx.ad.placements,
      status: ctx.ad.status,
    };
    let result = true;
    let useAnd = true;
    for (const cond of conditions) {
      const val = record[cond.field];
      let matches = false;
      switch (cond.operator) {
        case 'eq': matches = val === cond.value; break;
        case 'neq': matches = val !== cond.value; break;
        case 'gt': matches = (val as number) > (cond.value as number); break;
        case 'gte': matches = (val as number) >= (cond.value as number); break;
        case 'lt': matches = (val as number) < (cond.value as number); break;
        case 'lte': matches = (val as number) <= (cond.value as number); break;
        case 'in': matches = (cond.value as unknown[]).includes(val); break;
        case 'not_in': matches = !(cond.value as unknown[]).includes(val); break;
        case 'contains': matches = String(val).includes(String(cond.value)); break;
        case 'matches': matches = new RegExp(String(cond.value)).test(String(val)); break;
      }
      result = useAnd ? result && matches : result || matches;
      useAnd = cond.and !== false;
    }
    return result;
  }

  // ── Action Execution ──────────────────────────────────────────────────────

  private async executeAction(action: AutomationAction, ctx: AutomationContext, rule: AutomationRule): Promise<void> {
    if (action.delayMs) await new Promise(r => setTimeout(r, action.delayMs));

    switch (action.type) {
      case 'send_alert':
        this.alertCallback?.(action.message ?? `Rule "${rule.name}" fired`, rule, ctx);
        break;
      case 'call_webhook':
        if (action.webhookUrl) {
          this.webhookQueue.push({
            url: action.webhookUrl,
            payload: { rule: rule.id, adId: ctx.ad.id, metrics: ctx.metrics, timestamp: 1700000000000 },
          });
          this._flushWebhooks();
        }
        break;
      case 'log_event':
        const level = action.logLevel ?? 'info';
        console[level](`[AutomationEngine] Rule "${rule.name}" fired`, { adId: ctx.ad.id, action });
        break;
      default: {
        const executor = this.executors.get(action.type);
        if (executor) await executor(action, ctx);
        else console.warn(`[AutomationEngine] No executor for action type: ${action.type}`);
      }
    }
  }

  private async _flushWebhooks(): Promise<void> {
    while (this.webhookQueue.length > 0) {
      const item = this.webhookQueue.shift()!;
      try {
        await fetch(item.url, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(item.payload),
          keepalive: true,
        });
      } catch (e) {
        console.error('[AutomationEngine] Webhook failed', e);
      }
    }
  }

  // ── Main Evaluation ───────────────────────────────────────────────────────

  async evaluate(ctx: AutomationContext): Promise<string[]> {
    const fired: string[] = [];
    const now = 1700000000000;

    const applicableRules = this.getRules().filter(rule => {
      if (!rule.enabled) return false;
      if (rule.maxFires && (rule.fireCount ?? 0) >= rule.maxFires) return false;
      if (rule.adIds?.length && !rule.adIds.includes(ctx.ad.id)) return false;
      if (rule.placements?.length && !ctx.ad.placements.some(p => rule.placements?.includes(p))) return false;
      // Cooldown check
      const cooldownMs = (rule.trigger.cooldownMinutes ?? 0) * 60_000;
      if (cooldownMs > 0 && rule.lastFiredAt && now - rule.lastFiredAt < cooldownMs) return false;
      return true;
    });

    for (const rule of applicableRules) {
      const triggerMet = this.evaluateTrigger(rule.trigger, ctx);
      if (!triggerMet) continue;
      const conditionsMet = this.evaluateConditions(rule.conditions ?? [], ctx);
      if (!conditionsMet) continue;

      // Execute all actions
      for (const action of rule.actions) {
        await this.executeAction(action, ctx, rule);
      }

      // Update rule metadata
      this.updateRule(rule.id, {
        fireCount: (rule.fireCount ?? 0) + 1,
        lastFiredAt: now,
      });
      fired.push(rule.id);
    }

    return fired;
  }

  // ── Built-in Rules Library ─────────────────────────────────────────────────

  static buildStandardRules(): AutomationRule[] {
    const now = 1700000000000;
    return [
      {
        id: 'auto_pause_low_ctr',
        name: 'Auto-pause Low CTR',
        description: 'Pause ads where CTR falls below 0.05% with 1000+ impressions',
        enabled: true,
        trigger: { type: 'ctr_drop', threshold: 0.0005, operator: 'lt', windowMinutes: 60 },
        conditions: [{ field: 'impressions', operator: 'gte', value: 1000 }],
        actions: [
          { type: 'pause_ad' },
          { type: 'send_alert', message: 'Ad paused due to low CTR' },
        ],
        priority: 8,
        maxFires: 3,
        createdAt: now,
        updatedAt: now,
      },
      {
        id: 'auto_budget_80pct_alert',
        name: '80% Budget Alert',
        description: 'Alert when budget reaches 80%',
        enabled: true,
        trigger: { type: 'budget_percent', threshold: 80, operator: 'gte', cooldownMinutes: 60 },
        conditions: [],
        actions: [{ type: 'send_alert', message: 'Ad budget at 80%' }],
        priority: 5,
        createdAt: now,
        updatedAt: now,
      },
      {
        id: 'auto_pause_budget_exhausted',
        name: 'Pause on Budget Exhausted',
        description: 'Pause ad when 100% budget is spent',
        enabled: true,
        trigger: { type: 'budget_percent', threshold: 100, operator: 'gte' },
        conditions: [],
        actions: [{ type: 'pause_ad' }, { type: 'log_event', logLevel: 'info' }],
        priority: 10,
        maxFires: 1,
        createdAt: now,
        updatedAt: now,
      },
      {
        id: 'auto_increase_bid_high_viewability',
        name: 'Boost Bid for High Viewability',
        description: 'Increase bid by 20% when viewability > 80%',
        enabled: true,
        trigger: { type: 'viewability_drop', threshold: 0.8, operator: 'gt' },
        conditions: [{ field: 'impressions', operator: 'gte', value: 500 }],
        actions: [{ type: 'increase_bid', percent: 20 }],
        priority: 4,
        createdAt: now,
        updatedAt: now,
      },
      {
        id: 'auto_reduce_bid_low_viewability',
        name: 'Reduce Bid for Low Viewability',
        description: 'Decrease bid by 15% when viewability < 40%',
        enabled: true,
        trigger: { type: 'viewability_drop', threshold: 0.4 },
        conditions: [{ field: 'impressions', operator: 'gte', value: 200 }],
        actions: [{ type: 'decrease_bid', percent: 15 }],
        priority: 4,
        createdAt: now,
        updatedAt: now,
      },
      {
        id: 'auto_ctr_spike_scale_budget',
        name: 'Scale Budget on CTR Spike',
        description: 'Increase daily budget by 25% when CTR spikes above 2%',
        enabled: true,
        trigger: { type: 'ctr_spike', threshold: 0.02, cooldownMinutes: 120 },
        conditions: [{ field: 'impressions', operator: 'gte', value: 100 }],
        actions: [{ type: 'increase_budget', percent: 25 }],
        priority: 6,
        createdAt: now,
        updatedAt: now,
      },
      {
        id: 'auto_night_pacing',
        name: 'Night-time Slow Pacing',
        description: 'Reduce pacing speed between midnight and 6am',
        enabled: true,
        trigger: { type: 'time_of_day', hour: 0 },
        conditions: [],
        actions: [{ type: 'adjust_pacing', pacingMultiplier: 0.3 }],
        priority: 3,
        createdAt: now,
        updatedAt: now,
      },
      {
        id: 'auto_morning_boost',
        name: 'Morning Peak Boost',
        description: 'Boost pacing at 8am peak',
        enabled: true,
        trigger: { type: 'time_of_day', hour: 8 },
        conditions: [],
        actions: [{ type: 'adjust_pacing', pacingMultiplier: 1.5 }],
        priority: 3,
        createdAt: now,
        updatedAt: now,
      },
    ];
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Ad Template System
// ─────────────────────────────────────────────────────────────────────────────

export interface AdTemplate {
  id: string;
  name: string;
  description?: string;
  /** Partial ad definition — overrides applied to target ad */
  overrides: Partial<Ad>;
  /** When to use this template */
  tags?: string[];
}

export class TemplateLibrary {
  private templates: Map<string, AdTemplate> = new Map();

  add(template: AdTemplate): this {
    this.templates.set(template.id, template);
    return this;
  }

  get(id: string): AdTemplate | undefined {
    return this.templates.get(id);
  }

  apply(templateId: string, ad: Ad): Ad {
    const template = this.templates.get(templateId);
    if (!template) return ad;
    return { ...ad, ...template.overrides, id: ad.id };
  }

  findByTag(tag: string): AdTemplate[] {
    return Array.from(this.templates.values()).filter(t => t.tags?.includes(tag));
  }

  list(): AdTemplate[] {
    return Array.from(this.templates.values());
  }

  /** Standard MNC templates */
  static buildStandardTemplates(): AdTemplate[] {
    return [
      {
        id: 'tpl_high_impact',
        name: 'High Impact',
        description: 'Maximum bid floor, high quality creative requirements',
        tags: ['premium', 'brand'],
        overrides: {
          budget: { model: 'cpm', bidFloor: 5.0, bidCeiling: 50.0 } as any,
          weight: 90,
          priority: 8,
        },
      },
      {
        id: 'tpl_performance',
        name: 'Performance First',
        description: 'CPC model with aggressive targeting',
        tags: ['performance', 'conversion'],
        overrides: {
          budget: { model: 'cpc', bidFloor: 0.5 } as any,
          weight: 70,
        },
      },
      {
        id: 'tpl_retargeting',
        name: 'Retargeting Standard',
        description: '3x frequency cap with 30-day window',
        tags: ['retargeting'],
        overrides: {
          frequencyProfile: { daily: 3, weekly: 10, lifetime: 30, recencyMinutes: 30 } as any,
        },
      },
      {
        id: 'tpl_brand_safety',
        name: 'Brand Safe',
        description: 'Conservative brand safety targeting',
        tags: ['brand', 'safe'],
        overrides: {
          targeting: {
            audience: { brandSafety: { level: 'high', excludeCategories: ['violence', 'adult', 'gambling'] } },
          } as any,
        },
      },
    ];
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Budget Automation
// ─────────────────────────────────────────────────────────────────────────────

export interface BudgetAutomationConfig {
  autoPauseOnExhaustion: boolean;
  autoResumeNextDay: boolean;
  /** Increase daily budget by this percent when performance > target */
  performanceScaleUpPct?: number;
  /** Decrease daily budget by this percent when performance < target */
  performanceScaleDownPct?: number;
  /** Target ROAS for performance scaling */
  targetROAS?: number;
  /** Target CTR for performance scaling */
  targetCTR?: number;
  /** Max daily budget allowed (even after scale-up) */
  maxDailyBudget?: number;
  /** Min daily budget allowed (even after scale-down) */
  minDailyBudget?: number;
  /** Alert thresholds as percentages */
  alertThresholds?: number[];
}

export class BudgetAutomator {
  private configs: Map<string, BudgetAutomationConfig> = new Map();
  private spendHistory: Map<string, { date: string; spend: number }[]> = new Map();

  configure(adId: string, config: BudgetAutomationConfig): this {
    this.configs.set(adId, config);
    return this;
  }

  recordSpend(adId: string, amount: number): void {
    const history = this.spendHistory.get(adId) ?? [];
    const today = new Date().toISOString().split('T')[0];
    const existing = history.find(h => h.date === today);
    if (existing) existing.spend += amount;
    else history.push({ date: today, spend: amount });
    // Keep 90 days
    if (history.length > 90) history.shift();
    this.spendHistory.set(adId, history);
  }

  getDailySpend(adId: string): number {
    const today = new Date().toISOString().split('T')[0];
    return this.spendHistory.get(adId)?.find(h => h.date === today)?.spend ?? 0;
  }

  getAverageDailySpend(adId: string, days = 7): number {
    const history = this.spendHistory.get(adId) ?? [];
    const recent = history.slice(-days);
    return recent.length ? recent.reduce((s, h) => s + h.spend, 0) / recent.length : 0;
  }

  recommendDailyBudget(adId: string, currentBudget: number, metrics: { roas?: number; ctr?: number }): number {
    const config = this.configs.get(adId);
    if (!config) return currentBudget;
    let recommendation = currentBudget;
    const scaleUp = config.performanceScaleUpPct ?? 20;
    const scaleDown = config.performanceScaleDownPct ?? 15;

    if (config.targetROAS && metrics.roas !== undefined) {
      if (metrics.roas > config.targetROAS * 1.1) recommendation *= (1 + scaleUp / 100);
      if (metrics.roas < config.targetROAS * 0.9) recommendation *= (1 - scaleDown / 100);
    }
    if (config.targetCTR && metrics.ctr !== undefined) {
      if (metrics.ctr > config.targetCTR * 1.2) recommendation *= (1 + (scaleUp / 2) / 100);
      if (metrics.ctr < config.targetCTR * 0.8) recommendation *= (1 - (scaleDown / 2) / 100);
    }
    if (config.maxDailyBudget) recommendation = Math.min(recommendation, config.maxDailyBudget);
    if (config.minDailyBudget) recommendation = Math.max(recommendation, config.minDailyBudget);
    return Math.round(recommendation * 100) / 100;
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Creative Rotation Optimizer
// ─────────────────────────────────────────────────────────────────────────────

export interface CreativeVariant {
  id: string;
  adId: string;
  creativeType: 'image' | 'video' | 'html5' | 'native' | 'text';
  imageUrl?: string;
  videoUrl?: string;
  htmlContent?: string;
  headline?: string;
  body?: string;
  cta?: string;
  impressions: number;
  clicks: number;
  conversions: number;
  revenue: number;
  weight: number;
  paused: boolean;
}

export type RotationStrategy = 'even' | 'optimise_ctr' | 'optimise_cvr' | 'optimise_revenue' | 'bandit' | 'schedule';

export class CreativeRotator {
  private variants: Map<string, CreativeVariant[]> = new Map();
  private strategy: RotationStrategy = 'optimise_ctr';
  private roundRobinIndex: Map<string, number> = new Map();

  setStrategy(s: RotationStrategy): this {
    this.strategy = s;
    return this;
  }

  addVariant(adId: string, variant: Omit<CreativeVariant, 'adId' | 'impressions' | 'clicks' | 'conversions' | 'revenue' | 'paused'>): this {
    const existing = this.variants.get(adId) ?? [];
    existing.push({ ...variant, adId, impressions: 0, clicks: 0, conversions: 0, revenue: 0, paused: false });
    this.variants.set(adId, existing);
    return this;
  }

  recordImpression(adId: string, variantId: string): void {
    const v = this._findVariant(adId, variantId);
    if (v) v.impressions++;
  }

  recordClick(adId: string, variantId: string): void {
    const v = this._findVariant(adId, variantId);
    if (v) v.clicks++;
  }

  recordConversion(adId: string, variantId: string, revenue = 0): void {
    const v = this._findVariant(adId, variantId);
    if (v) { v.conversions++; v.revenue += revenue; }
  }

  selectVariant(adId: string): CreativeVariant | null {
    const candidates = (this.variants.get(adId) ?? []).filter(v => !v.paused);
    if (!candidates.length) return null;
    if (candidates.length === 1) return candidates[0];

    switch (this.strategy) {
      case 'even': {
        const idx = (this.roundRobinIndex.get(adId) ?? 0) % candidates.length;
        this.roundRobinIndex.set(adId, idx + 1);
        return candidates[idx];
      }
      case 'optimise_ctr': {
        const ctrFn = (v: CreativeVariant) => v.impressions > 0 ? v.clicks / v.impressions : 0;
        // UCB1 to balance explore/exploit
        const total = candidates.reduce((s, v) => s + v.impressions, 0);
        const ucb = (v: CreativeVariant) => ctrFn(v) + (v.impressions > 0
          ? Math.sqrt(2 * Math.log(total + 1) / v.impressions)
          : Infinity);
        return candidates.sort((a, b) => ucb(b) - ucb(a))[0];
      }
      case 'optimise_cvr': {
        const cvrFn = (v: CreativeVariant) => v.impressions > 0 ? v.conversions / v.impressions : 0;
        const total = candidates.reduce((s, v) => s + v.impressions, 0);
        const ucb = (v: CreativeVariant) => cvrFn(v) + (v.impressions > 0
          ? Math.sqrt(2 * Math.log(total + 1) / v.impressions)
          : Infinity);
        return candidates.sort((a, b) => ucb(b) - ucb(a))[0];
      }
      case 'optimise_revenue': {
        const total = candidates.reduce((s, v) => s + v.impressions, 0);
        const rpiFn = (v: CreativeVariant) => v.impressions > 0 ? v.revenue / v.impressions : 0;
        const ucb = (v: CreativeVariant) => rpiFn(v) + (v.impressions > 0
          ? Math.sqrt(2 * Math.log(total + 1) / v.impressions)
          : Infinity);
        return candidates.sort((a, b) => ucb(b) - ucb(a))[0];
      }
      case 'bandit': {
        // Thompson sampling
        const sample = (v: CreativeVariant) => {
          const alpha = v.clicks + 1;
          const beta = v.impressions - v.clicks + 1;
          // Beta sample approximation
          const x = this._gammaSample(alpha);
          const y = this._gammaSample(beta);
          return x / (x + y);
        };
        return candidates.sort((a, b) => sample(b) - sample(a))[0];
      }
      default:
        return candidates[0];
    }
  }

  private _gammaSample(shape: number): number {
    if (shape < 1) return this._gammaSample(1 + shape) * Math.pow(0.5, 1 / shape);
    const d = shape - 1 / 3;
    const c = 1 / Math.sqrt(9 * d);
    while (true) {
      let x: number, v: number;
      do { x = this._normalSample(); v = 1 + c * x; } while (v <= 0);
      v = v * v * v;
      const u = 0.5;
      if (u < 1 - 0.0331 * x * x * x * x) return d * v;
      if (Math.log(u) < 0.5 * x * x + d * (1 - v + Math.log(v))) return d * v;
    }
  }

  private _normalSample(): number {
    let u = 0, v = 0;
    while (u === 0) u = 0.5;
    while (v === 0) v = 0.5;
    return Math.sqrt(-2.0 * Math.log(u)) * Math.cos(2.0 * Math.PI * v);
  }

  private _findVariant(adId: string, variantId: string): CreativeVariant | undefined {
    return this.variants.get(adId)?.find(v => v.id === variantId);
  }

  pauseVariant(adId: string, variantId: string): void {
    const v = this._findVariant(adId, variantId);
    if (v) v.paused = true;
  }

  resumeVariant(adId: string, variantId: string): void {
    const v = this._findVariant(adId, variantId);
    if (v) v.paused = false;
  }

  getStats(adId: string): CreativeVariant[] {
    return this.variants.get(adId) ?? [];
  }

  getBestVariant(adId: string, metric: 'ctr' | 'cvr' | 'revenue' = 'ctr'): CreativeVariant | null {
    const variants = (this.variants.get(adId) ?? []).filter(v => v.impressions >= 50);
    if (!variants.length) return null;
    if (metric === 'ctr') return variants.sort((a, b) => (b.clicks / b.impressions) - (a.clicks / a.impressions))[0];
    if (metric === 'cvr') return variants.sort((a, b) => (b.conversions / b.impressions) - (a.conversions / a.impressions))[0];
    return variants.sort((a, b) => b.revenue - a.revenue)[0];
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Exports
// ─────────────────────────────────────────────────────────────────────────────

export { CSPSolver as ConstraintSolver };

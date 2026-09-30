export type SignalSource = 'PROFILE' | 'PREFERENCE' | 'SAVED' | 'COMMERCE' | 'SEARCH' | 'ANALYTICS' | 'BOOKING' | 'LIFECYCLE';

export interface PersonalizationSignal {
  type: string;
  value: string;
  source: SignalSource;
  observedAt: string;
  expiresAt?: string;
  confidence?: number;
}

export interface PersonalizationContext {
  customerId?: string;
  anonymous: boolean;
  locale?: string;
  surface: 'WEB_HOME' | 'CUSTOMER_HOME' | 'DISCOVERY' | 'SEARCH' | 'CONTENT';
  lifecycleState?: string;
  experimentVariant?: string;
}

export interface PersonalizationDecision {
  decisionId: string;
  surface: PersonalizationContext['surface'];
  candidates: { id: string; reason: string }[];
  policyVersion: string;
  generatedAt: string;
}

function signalLive(signal: PersonalizationSignal, nowIso: string): boolean {
  return !signal.expiresAt || signal.expiresAt > nowIso;
}

// Saved intent outranks transient behavior; explicit preferences never decay.
export function resolveDecision(input: {
  decisionId: string;
  context: PersonalizationContext;
  signals: PersonalizationSignal[];
  candidates: { id: string; categories: string[] }[];
  policyVersion: string;
  nowIso: string;
}): PersonalizationDecision {
  const live = input.signals.filter((s) => signalLive(s, input.nowIso));
  const savedCategories = new Set(live.filter((s) => s.type === 'saved-category').map((s) => s.value));
  const ranked = [...input.candidates].sort((a, b) => {
    const score = (id: string) => (savedCategories.has(id) ? 0 : 1);
    const aBest = Math.min(...a.categories.map(score), 1);
    const bBest = Math.min(...b.categories.map(score), 1);
    return aBest - bBest || a.id.localeCompare(b.id);
  });
  return {
    decisionId: input.decisionId,
    surface: input.context.surface,
    candidates: ranked.slice(0, 10).map((c) => ({ id: c.id, reason: c.categories.some((cat) => savedCategories.has(cat)) ? 'saved-match' : 'default' })),
    policyVersion: input.policyVersion,
    generatedAt: input.nowIso,
  };
}

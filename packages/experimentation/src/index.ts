export type ExperimentStatus = 'DRAFT' | 'ACTIVE' | 'PAUSED' | 'COMPLETED';

export interface ExperimentVariant {
  id: string;
  key: string;
  allocation: number;
}

export interface Experiment {
  id: string;
  name: string;
  status: ExperimentStatus;
  variants: ExperimentVariant[];
}

export interface ExperimentAssignment {
  experimentId: string;
  variantId: string;
  subjectId: string;
  assignedAt: string;
}

export interface ExperimentExposure {
  experimentId: string;
  variantId: string;
  surfaceId: string;
  occurredAt: string;
}

function hashSubject(experimentId: string, subjectId: string): number {
  const s = `${experimentId}:${subjectId}`;
  let h = 0;
  for (let i = 0; i < s.length; i++) h = (h * 31 + s.charCodeAt(i)) >>> 0;
  return h % 100;
}

// Deterministic, stable per subject. Unknown experiments fall back to control.
export function assignVariant(experiment: Experiment, subjectId: string, nowIso: string): ExperimentAssignment {
  if (experiment.status !== 'ACTIVE' || experiment.variants.length === 0) {
    return { experimentId: experiment.id, variantId: 'control', subjectId, assignedAt: nowIso };
  }
  const bucket = hashSubject(experiment.id, subjectId);
  let cursor = 0;
  for (const v of experiment.variants) {
    cursor += v.allocation;
    if (bucket < cursor) return { experimentId: experiment.id, variantId: v.id, subjectId, assignedAt: nowIso };
  }
  return { experimentId: experiment.id, variantId: experiment.variants[0]!.id, subjectId, assignedAt: nowIso };
}

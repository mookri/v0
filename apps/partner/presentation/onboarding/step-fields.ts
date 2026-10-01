import { OnboardingStringKey } from '@/presentation/onboarding/strings';

export type StepFieldType = 'text' | 'tel' | 'number' | 'checkbox' | 'csv' | 'info';

export interface StepField {
  name: string;
  labelKey: OnboardingStringKey;
  type: StepFieldType;
}

export interface StepLayout {
  // 'object' assembles {field: value}; 'bare' stores the single field's
  // value directly (arrays for services/areas/documents).
  shape: 'object' | 'bare';
  fields: StepField[];
}

// UI-owned field layout per step (predicates stay in the domain).
// Gap note: track-studio (stage 8) moves this into versioned track data;
// until then adding a step touches this file AND the domain definition.
export const STEP_FIELDS: Record<string, StepLayout> = {
  identity: {
    shape: 'object',
    fields: [
      { name: 'name', labelKey: 'steps.identity.name', type: 'text' },
      { name: 'phone', labelKey: 'steps.identity.phone', type: 'tel' },
    ],
  },
  credentials: {
    shape: 'object',
    fields: [{ name: 'certifications', labelKey: 'steps.credentials.certifications', type: 'csv' }],
  },
  services: { shape: 'bare', fields: [{ name: 'services', labelKey: 'steps.services.list', type: 'csv' }] },
  areas: { shape: 'bare', fields: [{ name: 'areas', labelKey: 'steps.areas.list', type: 'csv' }] },
  availability: {
    shape: 'object',
    fields: [{ name: 'set', labelKey: 'steps.availability.set', type: 'checkbox' }],
  },
  business: {
    shape: 'object',
    fields: [
      { name: 'gstin', labelKey: 'steps.business.gstin', type: 'text' },
      { name: 'bankAccount', labelKey: 'steps.business.bankAccount', type: 'text' },
    ],
  },
  capacity: {
    shape: 'object',
    fields: [{ name: 'weeklySlots', labelKey: 'steps.capacity.weeklySlots', type: 'number' }],
  },
  documents: { shape: 'bare', fields: [{ name: 'documents', labelKey: 'steps.documents.hint', type: 'info' }] },
  agreement: {
    shape: 'object',
    fields: [{ name: 'accepted', labelKey: 'steps.agreement.accepted', type: 'checkbox' }],
  },
  review: { shape: 'bare', fields: [{ name: 'review', labelKey: 'steps.review.title', type: 'info' }] },
};

export function parseFieldValue(type: StepFieldType, raw: string | boolean): unknown {
  if (type === 'checkbox') return raw === true || raw === 'on' || raw === 'true';
  if (type === 'number') {
    const n = typeof raw === 'string' ? parseInt(raw, 10) : NaN;
    return Number.isNaN(n) ? 0 : n;
  }
  if (type === 'csv') {
    if (typeof raw !== 'string') return [];
    return raw
      .split(',')
      .map((s) => s.trim())
      .filter((s) => s.length > 0);
  }
  return raw;
}

export function assembleStepValue(stepId: string, inputs: Record<string, string | boolean>): unknown {
  const layout = STEP_FIELDS[stepId];
  if (!layout) throw new Error(`UNKNOWN_STEP_LAYOUT:${stepId}`);
  const parsed: Record<string, unknown> = {};
  for (const field of layout.fields) {
    if (field.type === 'info') continue;
    parsed[field.name] = parseFieldValue(field.type, inputs[field.name] ?? '');
  }
  if (layout.shape === 'bare') {
    const only = layout.fields.find((f) => f.type !== 'info');
    return only ? parsed[only.name] : [];
  }
  return parsed;
}

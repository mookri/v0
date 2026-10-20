import { TrackDefinition } from '@navdhya/partner/src/onboarding/tracks';

// Draft evidence: free-form per-step payloads collected by the UI.
// Predicates below are the single source of truth for step completeness —
// UI renders from these, never its own rules.
export type DraftEvidence = Record<string, unknown>;

function nonEmptyArray(value: unknown): boolean {
  return Array.isArray(value) && value.length > 0;
}

function nonEmptyString(value: unknown): boolean {
  return typeof value === 'string' && value.length > 0;
}

const STEP_PREDICATES: Record<string, (evidence: DraftEvidence) => boolean> = {
  identity: (e) => {
    const v = e['identity'] as { name?: unknown; phone?: unknown } | undefined;
    return !!v && nonEmptyString(v.name) && nonEmptyString(v.phone);
  },
  credentials: (e) => nonEmptyArray((e['credentials'] as { certifications?: unknown } | undefined)?.certifications),
  services: (e) => nonEmptyArray(e['services']),
  areas: (e) => nonEmptyArray(e['areas']),
  availability: (e) => (e['availability'] as { set?: unknown } | undefined)?.set === true,
  documents: (e) => nonEmptyArray(e['documents']),
  business: (e) => {
    const v = e['business'] as { gstin?: unknown; bankAccount?: unknown } | undefined;
    return !!v && nonEmptyString(v.gstin) && nonEmptyString(v.bankAccount);
  },
  capacity: (e) => {
    const v = e['capacity'] as { weeklySlots?: unknown } | undefined;
    return typeof v?.weeklySlots === 'number' && v.weeklySlots > 0;
  },
  agreement: (e) => (e['agreement'] as { accepted?: unknown } | undefined)?.accepted === true,
  review: () => true,
};

// Required steps incomplete for this draft — drives the applicant checklist
// and the submit gate (empty = submittable).
export function missingSteps(def: TrackDefinition, evidence: DraftEvidence): string[] {
  return def.steps
    .filter((s) => s.required)
    .filter((s) => {
      const predicate = STEP_PREDICATES[s.id];
      if (!predicate) throw new Error(`UNKNOWN_STEP:${s.id}`);
      return !predicate(evidence);
    })
    .map((s) => s.id);
}

export function isSubmittable(def: TrackDefinition, evidence: DraftEvidence): boolean {
  return missingSteps(def, evidence).length === 0;
}

import {
  assembleStepValue as assembleDomain,
  StepLayout,
  StepFieldType,
  StepField,
} from '@navdhya/partner/src/onboarding/fields';
import { listTracks } from '@navdhya/partner/src/onboarding/tracks';
import { onboardingStrings, OnboardingStringKey } from '@/presentation/onboarding/strings';

// Single source is the versioned track data (track-studio foundation).
// This module is a UI-typed view over it: label keys narrowed to the
// dictionary type so untranslated keys fail typecheck, not runtime.
export type { StepLayout, StepFieldType, StepField };
export { parseFieldValue } from '@navdhya/partner/src/onboarding/fields';

export const STEP_FIELDS: Record<string, StepLayout> = (() => {
  const merged: Record<string, StepLayout> = {};
  for (const def of listTracks()) {
    for (const step of def.steps) {
      for (const field of step.layout.fields) {
        if (!(field.labelKey in onboardingStrings.en)) {
          throw new Error(`UNTRANSLATED_LABEL:${def.track}/${step.id}.${field.name}`);
        }
      }
      merged[step.id] = step.layout;
    }
  }
  return merged;
})();

export function labelKey(key: string): OnboardingStringKey {
  return key as OnboardingStringKey;
}

export function assembleStepValue(stepId: string, inputs: Record<string, string | boolean>): unknown {
  const layout = STEP_FIELDS[stepId];
  if (!layout) throw new Error(`UNKNOWN_STEP_LAYOUT:${stepId}`);
  return assembleDomain(stepId, layout, inputs);
}

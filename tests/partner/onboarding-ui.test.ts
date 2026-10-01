import { test, expect } from 'vitest';
import { onboardingStrings, resolveOnboardingLocale } from '@apps/partner/presentation/onboarding/strings';
import { STEP_FIELDS, assembleStepValue } from '@apps/partner/presentation/onboarding/step-fields';
import { getTrackDefinition, listTracks } from '@navdhya/partner/src/onboarding/tracks';
import { missingSteps } from '@navdhya/partner/src/onboarding/steps';

test('hindi covers every english key; locale resolves safely', () => {
  const enKeys = Object.keys(onboardingStrings.en);
  expect(Object.keys(onboardingStrings.hi)).toEqual(enKeys);
  for (const key of enKeys) {
    expect(onboardingStrings.hi[key as keyof typeof onboardingStrings.hi].length).toBeGreaterThan(0);
  }
  expect(resolveOnboardingLocale('hi')).toBe('hi');
  expect(resolveOnboardingLocale('fr')).toBe('en');
  expect(resolveOnboardingLocale(undefined)).toBe('en');
});

test('every track step has a field layout', () => {
  for (const def of listTracks()) {
    for (const step of def.steps) {
      expect(STEP_FIELDS[step.id], `layout for ${def.track}/${step.id}`).toBeDefined();
    }
  }
});

test('assembled form values satisfy the domain submit gate per track', () => {
  const raw: Record<string, Record<string, string | boolean>> = {
    identity: { name: 'Ravi', phone: '+91' },
    credentials: { certifications: 'vedic, tantra' },
    services: { services: 'lakshmi-puja, havan' },
    areas: { areas: 'delhi, noida' },
    availability: { set: true },
    business: { gstin: 'G', bankAccount: 'B' },
    capacity: { weeklySlots: '10' },
    documents: { documents: '' },
    agreement: { accepted: true },
    review: {},
  };
  const evidence: Record<string, unknown> = {};
  for (const [step, inputs] of Object.entries(raw)) evidence[step] = assembleStepValue(step, inputs);
  expect(evidence['services']).toEqual(['lakshmi-puja', 'havan']);
  expect(evidence['capacity']).toEqual({ weeklySlots: 10 });
  const docsFirst = { ...evidence, documents: ['doc_1'] };
  for (const def of listTracks()) {
    expect(missingSteps(def, docsFirst)).toEqual([]);
  }
  expect(() => assembleStepValue('nope', {})).toThrow('UNKNOWN_STEP_LAYOUT');
});

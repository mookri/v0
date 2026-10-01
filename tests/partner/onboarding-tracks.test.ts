import { test, expect } from 'vitest';
import {
  canTransitionApplication,
  assertApplicationTransition,
  applicationTransitionTable,
  ApplicationStatus,
} from '@navdhya/partner/src/domain/partner-application';
import { getTrackDefinition, listTracks } from '@navdhya/partner/src/onboarding/tracks';
import { missingSteps, isSubmittable } from '@navdhya/partner/src/onboarding/steps';

test('model tour: every legal path explored, terminals closed, illegal throws', () => {
  const table = applicationTransitionTable();
  const terminals: ApplicationStatus[] = ['APPROVED', 'WITHDRAWN'];
  // BFS from DRAFT over legal transitions.
  const seen = new Set<ApplicationStatus>(['DRAFT']);
  const queue: ApplicationStatus[] = ['DRAFT'];
  while (queue.length > 0) {
    const from = queue.pop()!;
    for (const to of table[from] ?? []) {
      expect(canTransitionApplication(from, to)).toBe(true);
      expect(() => assertApplicationTransition(from, to)).not.toThrow();
      if (!seen.has(to)) {
        seen.add(to);
        queue.push(to);
      }
    }
  }
  // All states reachable; terminals have no exits.
  expect([...seen].sort()).toEqual(['APPEALED', 'APPROVED', 'DRAFT', 'REJECTED', 'SUBMITTED', 'UNDER_REVIEW', 'WITHDRAWN']);
  for (const t of terminals) expect(table[t]).toEqual([]);
  // Appeal path exists; approved/withdrawn are absorbing.
  expect(canTransitionApplication('REJECTED', 'APPEALED')).toBe(true);
  expect(canTransitionApplication('APPEALED', 'UNDER_REVIEW')).toBe(true);
  expect(canTransitionApplication('APPROVED', 'DRAFT')).toBe(false);
  expect(() => assertApplicationTransition('APPROVED', 'DRAFT')).toThrow('ILLEGAL_APPLICATION_TRANSITION');
  expect(() => assertApplicationTransition('DRAFT', 'APPROVED')).toThrow('ILLEGAL_APPLICATION_TRANSITION');
});

test('three versioned tracks with SLAs and no track-specific code', () => {
  const tracks = listTracks();
  expect(tracks.map((t) => t.version)).toEqual(['pandit-v1', 'partner-vendor-v1', 'partner-individual-v1']);
  expect(getTrackDefinition('pandit').slaHours).toBe(48);
  expect(getTrackDefinition('partner-vendor').slaHours).toBe(72);
  expect(() => getTrackDefinition('pandit', 'pandit-v9')).toThrow('UNKNOWN_TRACK');
  for (const def of tracks) {
    expect(def.steps.length).toBeGreaterThan(0);
    expect(def.steps.filter((s) => s.required).length).toBeGreaterThan(0);
  }
});

test('step predicates drive the submit gate per track', () => {
  const pandit = getTrackDefinition('pandit');
  expect(isSubmittable(pandit, {})).toBe(false);
  expect(missingSteps(pandit, {})).toEqual(['identity', 'credentials', 'services', 'areas', 'availability', 'documents', 'agreement']);
  const full = {
    identity: { name: 'Ravi', phone: '+91' },
    credentials: { certifications: ['vedic'] },
    services: ['lakshmi-puja'],
    areas: ['delhi'],
    availability: { set: true },
    documents: ['id-proof'],
    agreement: { accepted: true },
  };
  expect(missingSteps(pandit, full)).toEqual([]);
  expect(isSubmittable(pandit, full)).toBe(true);
  const vendor = getTrackDefinition('partner-vendor');
  expect(missingSteps(vendor, full)).toEqual(['business', 'capacity']);
  expect(isSubmittable(vendor, { ...full, business: { gstin: 'G', bankAccount: 'B' }, capacity: { weeklySlots: 10 } })).toBe(true);
});

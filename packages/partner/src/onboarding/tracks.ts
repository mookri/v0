import { ApplicationTrack } from '@navdhya/partner/src/domain/partner-application';
import { StepLayout, StepField } from '@navdhya/partner/src/onboarding/fields';

export interface TrackStep {
  id: string;
  required: boolean;
  layout: StepLayout;
}

export interface TrackDefinition {
  track: ApplicationTrack;
  version: string;
  slaHours: number;
  steps: TrackStep[];
}

const text = (name: string, labelKey: string): StepField => ({ name, labelKey, type: 'text' });
const csv = (name: string, labelKey: string): StepField => ({ name, labelKey, type: 'csv' });

const identityLayout: StepLayout = {
  shape: 'object',
  fields: [
    text('name', 'steps.identity.name'),
    { name: 'phone', labelKey: 'steps.identity.phone', type: 'tel' },
  ],
};
const credentialsLayout: StepLayout = {
  shape: 'object',
  fields: [csv('certifications', 'steps.credentials.certifications')],
};
const servicesLayout: StepLayout = { shape: 'bare', fields: [csv('services', 'steps.services.list')] };
const areasLayout: StepLayout = { shape: 'bare', fields: [csv('areas', 'steps.areas.list')] };
const availabilityLayout: StepLayout = {
  shape: 'object',
  fields: [{ name: 'set', labelKey: 'steps.availability.set', type: 'checkbox' }],
};
const businessLayout: StepLayout = {
  shape: 'object',
  fields: [text('gstin', 'steps.business.gstin'), text('bankAccount', 'steps.business.bankAccount')],
};
const capacityLayout: StepLayout = {
  shape: 'object',
  fields: [{ name: 'weeklySlots', labelKey: 'steps.capacity.weeklySlots', type: 'number' }],
};
const documentsLayout: StepLayout = { shape: 'bare', fields: [{ name: 'documents', labelKey: 'steps.documents.hint', type: 'info' }] };
const agreementLayout: StepLayout = {
  shape: 'object',
  fields: [{ name: 'accepted', labelKey: 'steps.agreement.accepted', type: 'checkbox' }],
};
const reviewLayout: StepLayout = { shape: 'bare', fields: [{ name: 'review', labelKey: 'steps.review.title', type: 'info' }] };

const step = (id: string, required: boolean, layout: StepLayout) => ({ id, required, layout });

// Versioned, effective-dated by convention: new rules ship as new versions;
// in-flight applications pin theirs. Field layouts live here (track-studio
// foundation) — UI renders them, predicates validate them.
const TRACKS: TrackDefinition[] = [
  {
    track: 'pandit',
    version: 'pandit-v1',
    slaHours: 48,
    steps: [
      step('identity', true, identityLayout),
      step('credentials', true, credentialsLayout),
      step('services', true, servicesLayout),
      step('areas', true, areasLayout),
      step('availability', true, availabilityLayout),
      step('documents', true, documentsLayout),
      step('agreement', true, agreementLayout),
      step('review', false, reviewLayout),
    ],
  },
  {
    track: 'partner-vendor',
    version: 'partner-vendor-v1',
    slaHours: 72,
    steps: [
      step('identity', true, identityLayout),
      step('business', true, businessLayout),
      step('services', true, servicesLayout),
      step('areas', true, areasLayout),
      step('capacity', true, capacityLayout),
      step('documents', true, documentsLayout),
      step('agreement', true, agreementLayout),
      step('review', false, reviewLayout),
    ],
  },
  {
    track: 'partner-individual',
    version: 'partner-individual-v1',
    slaHours: 72,
    steps: [
      step('identity', true, identityLayout),
      step('services', true, servicesLayout),
      step('areas', true, areasLayout),
      step('availability', false, availabilityLayout),
      step('documents', true, documentsLayout),
      step('agreement', true, agreementLayout),
      step('review', false, reviewLayout),
    ],
  },
];

export function getTrackDefinition(track: ApplicationTrack, version?: string): TrackDefinition {
  const def = TRACKS.find((t) => t.track === track && (!version || t.version === version));
  if (!def) throw new Error(`UNKNOWN_TRACK:${track}@${version ?? 'latest'}`);
  return def;
}

export function listTracks(): TrackDefinition[] {
  return [...TRACKS];
}

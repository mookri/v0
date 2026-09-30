import { DomainManifest } from '@navdhya/contracts/src/governance/manifest';

export const manifest: DomainManifest = {
  domain: 'booking',
  owner: 'booking-domain',
  sourceOfTruth: 'booking',
  database: 'shared',
  consumes: [],
  produces: ['BookingCreated', 'BookingConfirmed', 'BookingCancelled', 'BookingRescheduled'],
  deployable: false,
  stage: 0,
  scalingProfile: 'transactional',
  dataClassification: 'restricted',
};

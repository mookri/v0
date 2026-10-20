import { DomainManifest } from '@navdhya/contracts/src/governance/manifest';

export const manifest: DomainManifest = {
  domain: 'payment',
  owner: 'payment-domain',
  sourceOfTruth: 'payment',
  database: 'shared',
  consumes: [],
  produces: ['PaymentCaptured', 'PaymentFailed', 'PaymentRefunded'],
  deployable: false,
  stage: 0,
  scalingProfile: 'transactional',
  dataClassification: 'restricted',
};

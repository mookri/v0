import { DomainManifest } from '@navdhya/contracts/src/governance/manifest';

export const manifest: DomainManifest = {
  domain: 'pricing',
  owner: 'pricing-domain',
  sourceOfTruth: 'pricing',
  database: 'shared',
  consumes: [],
  produces: [],
  deployable: false,
  stage: 0,
  scalingProfile: 'transactional',
  dataClassification: 'internal',
};

import { DomainManifest } from '@navdhya/contracts/src/governance/manifest';

export const manifest: DomainManifest = {
  domain: 'availability',
  owner: 'availability-domain',
  sourceOfTruth: 'availability',
  database: 'shared',
  consumes: [],
  produces: [],
  deployable: false,
  stage: 0,
  scalingProfile: 'transactional',
  dataClassification: 'internal',
};

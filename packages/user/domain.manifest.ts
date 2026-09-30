import { DomainManifest } from '@navdhya/contracts/src/governance/manifest';

export const manifest: DomainManifest = {
  domain: 'user',
  owner: 'user-domain',
  sourceOfTruth: 'user',
  database: 'shared',
  consumes: [],
  produces: [],
  deployable: false,
  stage: 0,
  scalingProfile: 'transactional',
  dataClassification: 'restricted',
};

import { DomainManifest } from '@navdhya/contracts/src/governance/manifest';

export const manifest: DomainManifest = {
  domain: 'pandit',
  owner: 'pandit-domain',
  sourceOfTruth: 'pandit',
  database: 'shared',
  consumes: [],
  produces: [],
  deployable: false,
  stage: 0,
  scalingProfile: 'read-heavy',
  dataClassification: 'restricted',
};

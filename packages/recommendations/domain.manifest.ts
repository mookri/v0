import { DomainManifest } from '@navdhya/contracts/src/governance/manifest';

export const manifest: DomainManifest = {
  domain: 'recommendations',
  owner: 'recommendations-domain',
  sourceOfTruth: 'recommendations',
  database: 'shared',
  consumes: [],
  produces: [],
  deployable: false,
  stage: 0,
  scalingProfile: 'read-heavy',
  dataClassification: 'internal',
};

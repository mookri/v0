import { DomainManifest } from '@navdhya/contracts/src/governance/manifest';

export const manifest: DomainManifest = {
  domain: 'search',
  owner: 'search-domain',
  sourceOfTruth: 'search',
  database: 'shared',
  consumes: [],
  produces: [],
  deployable: false,
  stage: 0,
  scalingProfile: 'read-heavy',
  dataClassification: 'public',
};

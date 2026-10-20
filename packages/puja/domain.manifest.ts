import { DomainManifest } from '@navdhya/contracts/src/governance/manifest';

export const manifest: DomainManifest = {
  domain: 'puja',
  owner: 'puja-domain',
  sourceOfTruth: 'puja',
  database: 'shared',
  consumes: [],
  produces: [],
  deployable: false,
  stage: 0,
  scalingProfile: 'read-heavy',
  dataClassification: 'public',
};

import { test, expect } from 'vitest';
import { validateManifestShape, DomainManifest } from '@navdhya/contracts/src/governance/manifest';

const valid: DomainManifest = {
  domain: 'booking',
  owner: 'booking-domain',
  sourceOfTruth: 'booking',
  database: 'shared',
  consumes: [],
  produces: ['BookingCreated'],
  deployable: false,
  stage: 0,
  scalingProfile: 'transactional',
  dataClassification: 'restricted',
};

test('valid manifests pass; each broken field reports its code', () => {
  expect(validateManifestShape(valid)).toEqual([]);
  expect(validateManifestShape({ ...valid, domain: '' })).toContain('MANIFEST_DOMAIN');
  expect(validateManifestShape({ ...valid, consumes: 'x' as unknown as [] })).toContain('MANIFEST_CONSUMES');
  expect(validateManifestShape({ ...valid, stage: 7 as unknown as 0 })).toContain('MANIFEST_STAGE');
  expect(validateManifestShape({ ...valid, scalingProfile: 'y' as unknown as 'transactional' })).toContain('MANIFEST_PROFILE');
  expect(validateManifestShape({ ...valid, dataClassification: 'y' as unknown as 'restricted' })).toContain('MANIFEST_CLASS');
  expect(validateManifestShape({})).toEqual([
    'MANIFEST_DOMAIN',
    'MANIFEST_OWNER',
    'MANIFEST_SOURCE',
    'MANIFEST_DATABASE',
    'MANIFEST_CONSUMES',
    'MANIFEST_PRODUCES',
    'MANIFEST_DEPLOYABLE',
    'MANIFEST_STAGE',
    'MANIFEST_PROFILE',
    'MANIFEST_CLASS',
  ]);
});

// Domain manifest contract (§45): machine-readable ownership, stage, and
// coupling declarations per capability. Validators (domain:check) verify
// claims against the scanned tree; docs are generated from these, not
// maintained in parallel.

export type DeploymentStage = 0 | 1 | 2 | 3 | 4 | 5;

export type ScalingProfile =
  | 'transactional'
  | 'read-heavy'
  | 'queue-oriented'
  | 'worker-oriented'
  | 'realtime'
  | 'analytical'
  | 'static';

export type DataClassification = 'public' | 'internal' | 'restricted';

export interface DomainManifest {
  domain: string;
  owner: string;
  sourceOfTruth: string;
  // 'shared' until the domain owns schema/database (see §9).
  database: string;
  consumes: string[];
  produces: string[];
  deployable: boolean;
  stage: DeploymentStage;
  scalingProfile: ScalingProfile;
  dataClassification: DataClassification;
}

export function validateManifestShape(manifest: Partial<DomainManifest>): string[] {
  const errors: string[] = [];
  if (!manifest.domain || typeof manifest.domain !== 'string') errors.push('MANIFEST_DOMAIN');
  if (!manifest.owner || typeof manifest.owner !== 'string') errors.push('MANIFEST_OWNER');
  if (!manifest.sourceOfTruth || typeof manifest.sourceOfTruth !== 'string') errors.push('MANIFEST_SOURCE');
  if (!manifest.database || typeof manifest.database !== 'string') errors.push('MANIFEST_DATABASE');
  if (!Array.isArray(manifest.consumes)) errors.push('MANIFEST_CONSUMES');
  if (!Array.isArray(manifest.produces)) errors.push('MANIFEST_PRODUCES');
  if (typeof manifest.deployable !== 'boolean') errors.push('MANIFEST_DEPLOYABLE');
  if (
    typeof manifest.stage !== 'number' ||
    !Number.isInteger(manifest.stage) ||
    manifest.stage < 0 ||
    manifest.stage > 5
  ) {
    errors.push('MANIFEST_STAGE');
  }
  const profiles: ScalingProfile[] = [
    'transactional',
    'read-heavy',
    'queue-oriented',
    'worker-oriented',
    'realtime',
    'analytical',
    'static',
  ];
  if (!manifest.scalingProfile || !profiles.includes(manifest.scalingProfile)) errors.push('MANIFEST_PROFILE');
  const classes: DataClassification[] = ['public', 'internal', 'restricted'];
  if (!manifest.dataClassification || !classes.includes(manifest.dataClassification)) errors.push('MANIFEST_CLASS');
  return errors;
}

import { ApplicationTrack } from '@navdhya/partner/src/domain/partner-application';

export interface TrackStep {
  id: string;
  required: boolean;
}

export interface TrackDefinition {
  track: ApplicationTrack;
  version: string;
  slaHours: number;
  steps: TrackStep[];
}

// Versioned, effective-dated by convention: new rules ship as new versions;
// in-flight applications pin theirs. Shared engine renders all tracks;
// divergence unresolved here is a design smell.
const TRACKS: TrackDefinition[] = [
  {
    track: 'pandit',
    version: 'pandit-v1',
    slaHours: 48,
    steps: [
      { id: 'identity', required: true },
      { id: 'credentials', required: true },
      { id: 'services', required: true },
      { id: 'areas', required: true },
      { id: 'availability', required: true },
      { id: 'documents', required: true },
      { id: 'agreement', required: true },
      { id: 'review', required: false },
    ],
  },
  {
    track: 'partner-vendor',
    version: 'partner-vendor-v1',
    slaHours: 72,
    steps: [
      { id: 'identity', required: true },
      { id: 'business', required: true },
      { id: 'services', required: true },
      { id: 'areas', required: true },
      { id: 'capacity', required: true },
      { id: 'documents', required: true },
      { id: 'agreement', required: true },
      { id: 'review', required: false },
    ],
  },
  {
    track: 'partner-individual',
    version: 'partner-individual-v1',
    slaHours: 72,
    steps: [
      { id: 'identity', required: true },
      { id: 'services', required: true },
      { id: 'areas', required: true },
      { id: 'availability', required: false },
      { id: 'documents', required: true },
      { id: 'agreement', required: true },
      { id: 'review', required: false },
    ],
  },
];

export function getTrackDefinition(track: ApplicationTrack, version?: string): TrackDefinition {
  const def = TRACKS.find((t) => t.track === track && (!version || t.version === version));
  if (!def) throw new Error(`UNKNOWN_TRACK:${track}@${version ?? 'latest'}`);
  return def;
}

export function listTracks(): TrackDefinition[] {
  return [...TRACKS];
}

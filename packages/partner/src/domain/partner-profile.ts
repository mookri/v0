export type PartnerVisibility = 'PRIVATE' | 'APPLICATION_REVIEW' | 'PUBLIC' | 'UNLISTED';

export interface PartnerProfile {
  partnerId: string;
  displayName: string;
  bio?: string;
  languages: string[];
  experienceYears?: number;
  visibility: PartnerVisibility;
  publicationVersion: number;
}

export function isPublicProfile(profile: PartnerProfile): boolean {
  return profile.visibility === 'PUBLIC';
}

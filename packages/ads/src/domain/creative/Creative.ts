export type CreativeId = string;
export type CreativeStatus = 'pending' | 'approved' | 'rejected';
export type AdFormat = 'native' | 'image' | 'video' | 'sponsored-card' | 'hero' | 'rectangle' | 'vertical' | 'text';

export interface Creative {
  id: CreativeId;
  version: number;
  format: AdFormat;
  headline: string;
  body?: string;
  ctaText: string;
  destination: string;
  isSponsored: boolean;
  status: CreativeStatus;
}

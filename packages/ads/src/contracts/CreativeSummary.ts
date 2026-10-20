import { Creative } from '@navdhya/ads/src/domain/creative/Creative';

export type CreativeSummary = Pick<Creative, 'id' | 'format' | 'headline' | 'ctaText' | 'destination' | 'isSponsored' | 'version'>;

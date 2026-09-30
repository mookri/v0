import { Creative } from '../domain/creative/Creative';

export type CreativeSummary = Pick<Creative, 'id' | 'format' | 'headline' | 'ctaText' | 'destination' | 'isSponsored' | 'version'>;

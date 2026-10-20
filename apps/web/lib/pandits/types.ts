// lib/pandits/types.ts

/**
 * Shared types for the pandit (priest) directory.
 *
 * Why this module exists (P1, audit):
 *   The `app/find-pandit` and `app/pandits` routes were both bundling
 *   near-identical 6-entry `mockPandits` arrays. Changes to one
 *   (e.g. price updates, new skills) silently drifted from the other.
 *   This module is the single source of truth for the directory
 *   data shape, with optional extended fields (about, certifications)
 *   used by the detail UI on the `find-pandit` route.
 *
 * When the real Drizzle/Postgres-backed directory lands (PR 11), the
 * shape will continue to come from this file — only the loader changes.
 */

export interface PanditCore {
  id: number;
  name: string;
  specialization: string;
  location: string;
  experience: string;
  rating: number;
  image: string;
  skills: string[];
  price: string;
  languages: string[];
}

/** Extended metadata used on the detail card. */
export interface PanditDetails {
  about: string;
  certifications: string[];
}

export type Pandit = PanditCore & Partial<PanditDetails>;

/** Filter options shared between the directory and search UIs.
 *  `language` is optional because the simpler `/pandits` directory
 *  doesn't expose a language filter, while `/find-pandit` does. */
export interface PanditFilterOptions {
  location: string[];
  specialization: string[];
  rating: number | null;
  experience: string[];
  searchQuery: string;
  language?: string[];
}

export const PANDIT_EXPERIENCE_OPTIONS: readonly string[] = [
  "5+ years",
  "10+ years",
  "15+ years",
  "20+ years",
] as const;

/** Top-level locations; derived from data in production, hard-coded here. */
export const PANDIT_LOCATIONS: readonly string[] = [
  "Delhi",
  "Gurgaon",
  "Noida",
] as const;

/** Top-level languages; derived from data in production, hard-coded here. */
export const PANDIT_LANGUAGES: readonly string[] = [
  "Hindi",
  "English",
  "Tamil",
  "Telugu",
  "Malayalam",
  "Punjabi",
  "Gujarati",
] as const;

/** Top-level specializations; derived from data in production, hard-coded here. */
export const PANDIT_SPECIALIZATIONS: readonly string[] = [
  "Vedic Rituals & Astrology Specialist",
  "Business & Prosperity Rituals",
  "South Indian Rituals Specialist",
  "Wedding & Life Event Ceremonies",
  "Women's Wellness & Fertility Rituals",
  "Health & Healing Ceremonies",
] as const;

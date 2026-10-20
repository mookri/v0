export const RATING_MIN = 1;
export const RATING_MAX = 5;

export interface RatingDimensions {
  punctuality?: number;
  communication?: number;
  ritualExperience?: number;
  professionalism?: number;
}

export interface Rating {
  overall?: number;
  dimensions?: RatingDimensions;
}

export function isValidRating(rating: Rating): boolean {
  const check = (v: number | undefined): boolean =>
    v === undefined || (Number.isInteger(v) && v >= RATING_MIN && v <= RATING_MAX);
  if (!check(rating.overall)) return false;
  for (const v of Object.values(rating.dimensions ?? {})) {
    if (!check(v)) return false;
  }
  return true;
}

export interface Clock {
  now(): Date;
}

export const SystemClock: Clock = {
  now: () => new Date(),
};

export function FixedClock(fixed: Date): Clock {
  return { now: () => new Date(fixed) };
}

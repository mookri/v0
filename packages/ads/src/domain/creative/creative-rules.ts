import { Creative } from './Creative';

const ALLOWLIST = [/^https:\/\//, /^\/[a-z0-9\-/]*$/i];

export function isDestinationAllowed(destination: string): boolean {
  return ALLOWLIST.some((re) => re.test(destination));
}

export function isCreativeServable(c: Creative): boolean {
  return c.status === 'approved' && isDestinationAllowed(c.destination);
}

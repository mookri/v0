import { Pandit } from './Pandit';

export function canAcceptBookings(pandit: Pandit): boolean {
  return pandit.status === 'VERIFIED';
}

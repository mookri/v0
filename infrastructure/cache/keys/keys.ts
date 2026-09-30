import { cacheKey } from '@navdhya/contracts/src/common/cache';

export const BookingCacheKeys = {
  booking: (id: string) => cacheKey('booking', 'v1', id),
  userBookings: (userId: string) => cacheKey('booking-list', 'v1', userId),
};

export const PujaCacheKeys = {
  puja: (id: string) => cacheKey('puja', 'v1', id),
  featured: (city: string) => cacheKey('puja-featured', 'v2', city),
};

export const AvailabilityCacheKeys = {
  preview: (pujaId: string) => cacheKey('availability-preview', 'v1', pujaId),
};

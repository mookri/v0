import { getDashboardSection } from '@/server/temples';

export function GET(req: Request, ctx: { params: Promise<{ templeId: string }> }) {
  return getDashboardSection(req, ctx, {
    section: 'overview',
    mockPayload: {
      total_pujas: 0,
      active_pujas: 0,
      total_bookings: 0,
      upcoming_bookings: 0,
      revenue_minor: 0,
      avg_occupancy: 0,
      recent_bookings: [],
    },
    message: 'Dashboard overview is unavailable.',
  });
}

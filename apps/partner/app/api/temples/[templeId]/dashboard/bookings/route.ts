import { getDashboardSection } from '@/server/temples';

export function GET(req: Request, ctx: { params: Promise<{ templeId: string }> }) {
  return getDashboardSection(req, ctx, {
    section: 'bookings',
    mockPayload: { data: [], meta: { page: { limit: 20, has_more: false, next_cursor: null } } },
    message: 'Dashboard bookings are unavailable.',
    transform: (data, meta) => ({ data, meta }),
  });
}

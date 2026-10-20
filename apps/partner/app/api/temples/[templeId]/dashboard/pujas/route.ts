import { getDashboardSection } from '@/server/temples';

export function GET(req: Request, ctx: { params: Promise<{ templeId: string }> }) {
  return getDashboardSection(req, ctx, {
    section: 'pujas',
    mockPayload: { data: [], meta: { page: { limit: 20, has_more: false, next_cursor: null } } },
    message: 'Dashboard pujas are unavailable.',
    transform: (data, meta) => ({ data, meta }),
  });
}

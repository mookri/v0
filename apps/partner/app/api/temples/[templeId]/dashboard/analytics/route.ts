import { getDashboardSection } from '@/server/temples';

export function GET(req: Request, ctx: { params: Promise<{ templeId: string }> }) {
  const { searchParams } = new URL(req.url);
  const months = searchParams.get('months') ?? undefined;

  return getDashboardSection(req, ctx, {
    section: 'analytics',
    mockPayload: { revenue_by_month: [], occupancy_by_puja: [], peak_hours: [] },
    message: 'Dashboard analytics are unavailable.',
    query: months ? { months } : {},
  });
}

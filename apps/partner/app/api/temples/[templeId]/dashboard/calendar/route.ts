import { getDashboardSection } from '@/server/temples';

// Backend requires year/month (422 otherwise) — default to the current
// month when the page does not pass one.
export function GET(req: Request, ctx: { params: Promise<{ templeId: string }> }) {
  const { searchParams } = new URL(req.url);
  const now = new Date();
  const year = searchParams.get('year') ?? String(now.getUTCFullYear());
  const month = searchParams.get('month') ?? String(now.getUTCMonth() + 1);

  return getDashboardSection(req, ctx, {
    section: 'calendar',
    mockPayload: { items: [] },
    message: 'Dashboard calendar is unavailable.',
    query: { year, month },
    // Kernel returns the day list directly under `data`
    transform: (data) => ({ items: data }),
  });
}

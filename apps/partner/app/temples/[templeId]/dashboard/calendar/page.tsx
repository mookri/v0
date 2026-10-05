import { TempleDashboardCalendarView } from '@/presentation/temples/TempleDashboardCalendarView';
import { partnerApi } from '@/adapters/backend/partner-api-client';

interface TempleDashboardCalendarPageProps {
  params: Promise<{ templeId: string }>;
}

export default async function TempleDashboardCalendarPage({ params }: { params: Promise<{ templeId: string }> }) {
  const { templeId } = await params;

  try {
    const data = await partnerApi.getTempleDashboardCalendar(templeId);
    const calendar = data as { items: Array<{
      date: string;
      slots: Array<{ id: string; puja_name: string; start: string; end: string; capacity: int; booked: int }>;
    }> };
    return <TempleDashboardCalendarView calendar={calendar.items} />;
  } catch {
    return <div className="p-6 max-w-4xl mx-auto text-center">
      <h1 className="text-2xl font-bold mb-4">Unable to load calendar</h1>
      <a href="/temples" className="text-indigo-600 hover:underline">Back to temples</a>
    </div>;
  }
}
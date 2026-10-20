import { TempleDashboardBookingsView } from '@/presentation/temples/TempleDashboardBookingsView';
import { partnerApi } from '@/adapters/backend/partner-api-client';

interface TempleDashboardBookingsPageProps {
  params: Promise<{ templeId: string }>;
}

export default async function TempleDashboardBookingsPage({ params }: { params: Promise<{ templeId: string }> }) {
  const { templeId } = await params;

  try {
    const data = await partnerApi.getTempleDashboardBookings(templeId);
    const bookings = (data as { data: Array<{
      id: string;
      puja_name: string;
      user_name: string;
      user_email: string;
      start_at: string;
      end_at: string;
      status: string;
      amount_minor: number;
    }> }).data ?? [];
    return <TempleDashboardBookingsView bookings={bookings} />;
  } catch {
    return <div className="p-6 max-w-4xl mx-auto text-center">
      <h1 className="text-2xl font-bold mb-4">Unable to load bookings</h1>
      <a href="/temples" className="text-indigo-600 hover:underline">Back to temples</a>
    </div>;
  }
}
import { TempleDashboardOverviewView } from '@/presentation/temples/TempleDashboardOverviewView';
import { partnerApi } from '@/adapters/backend/partner-api-client';

interface TempleDashboardPageProps {
  params: Promise<{ templeId: string }>;
}

export default async function TempleDashboardOverviewPage({ params }: TempleDashboardPageProps) {
  const { templeId } = await params;

  try {
    const data = await partnerApi.getTempleDashboardOverview(templeId);
    const overview = data as {
      total_pujas: number;
      active_pujas: number;
      total_bookings: number;
      upcoming_bookings: number;
      revenue_minor: number;
      avg_occupancy: number;
      recent_bookings: Array<{ id: string; puja_name: string; user_name: string; start_at: string; status: string }>;
    };
    return <TempleDashboardOverviewView overview={overview} />;
  } catch {
    return <div className="p-6 max-w-4xl mx-auto text-center">
      <h1 className="text-2xl font-bold mb-4">Unable to load dashboard</h1>
      <a href="/temples" className="text-indigo-600 hover:underline">Back to temples</a>
    </div>;
  }
}
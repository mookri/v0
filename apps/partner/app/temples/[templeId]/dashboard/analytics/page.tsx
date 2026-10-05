import { TempleAnalyticsView } from '@/presentation/temples/TempleAnalyticsView';
import { partnerApi } from '@/adapters/backend/partner-api-client';

interface TempleDashboardAnalyticsPageProps {
  params: Promise<{ templeId: string }>;
}

export default async function TempleDashboardAnalyticsPage({ params }: TempleDashboardAnalyticsPageProps) {
  const { templeId } = await params;

  try {
    const data = await partnerApi.getTempleDashboardAnalytics(templeId);
    const analytics = data as {
      revenue_by_month: Array<{ month: string; revenue_minor: number; bookings: number }>;
      occupancy_by_puja: Array<{ puja_name: string; occupancy: number; bookings: number }>;
      peak_hours: Array<{ hour: number; bookings: number }>;
    };
    return <TempleAnalyticsView analytics={analytics} />;
  } catch {
    return (
      <div className="p-6 max-w-4xl mx-auto text-center">
        <h1 className="text-2xl font-bold mb-4">Unable to load analytics</h1>
        <a href="/temples" className="text-indigo-600 hover:underline">
          Back to temples
        </a>
      </div>
    );
  }
}

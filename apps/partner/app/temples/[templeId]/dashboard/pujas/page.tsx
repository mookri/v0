import { TempleDashboardPujasView } from '@/presentation/temples/TempleDashboardPujasView';
import { partnerApi } from '@/adapters/backend/partner-api-client';

interface TempleDashboardPujasPageProps {
  params: Promise<{ templeId: string }>;
}

export default async function TempleDashboardPujasPage({ params }: { params: Promise<{ templeId: string }> }) {
  const { templeId } = await params;

  try {
    const data = await partnerApi.getTempleDashboardPujas(templeId);
    const pujas = (data as { data: Array<{
      id: string;
      puja_id: string;
      puja_name: string;
      price_override_minor: number | null;
      capacity: number | null;
      schedule_json: string | null;
      status: string;
      bookings_count: number;
    }> }).data ?? [];
    return <TempleDashboardPujasView pujas={pujas} templeId={templeId} />;
  } catch {
    return <div className="p-6 max-w-4xl mx-auto text-center">
      <h1 className="text-2xl font-bold mb-4">Unable to load pujas</h1>
      <a href="/temples" className="text-indigo-600 hover:underline">Back to temples</a>
    </div>;
  }
}
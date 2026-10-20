import { TempleManagersView } from '@/presentation/temples/TempleManagersView';
import { partnerApi } from '@/adapters/backend/partner-api-client';

interface TempleManagersPageProps {
  params: Promise<{ templeId: string }>;
}

export default async function TempleManagersPage({ params }: TempleManagersPageProps) {
  const { templeId } = await params;

  try {
    const managers = await partnerApi.getTempleManagers(templeId);
    return <TempleManagersView templeId={templeId} managers={Array.isArray(managers) ? managers : []} />;
  } catch {
    return <div className="p-6 max-w-4xl mx-auto text-center">
      <h1 className="text-2xl font-bold mb-4">Temple not found</h1>
      <p className="text-gray-500 mb-4">The temple you're looking for doesn't exist or has been removed.</p>
      <a href="/temples" className="text-indigo-600 hover:underline">Back to temples</a>
    </div>;
  }
}

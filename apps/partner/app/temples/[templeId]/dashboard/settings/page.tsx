import { TempleSettingsView } from '@/presentation/temples/TempleSettingsView';
import { partnerApi } from '@/adapters/backend/partner-api-client';

interface TempleDashboardSettingsPageProps {
  params: Promise<{ templeId: string }>;
}

export default async function TempleDashboardSettingsPage({ params }: TempleDashboardSettingsPageProps) {
  const { templeId } = await params;

  try {
    const data = await partnerApi.getTempleDashboardSettings(templeId);
    const settings = data as {
      temple: {
        id: string;
        slug: string;
        name: string;
        trust_name: string;
        type: string;
        address: string;
        city: string;
        state: string;
        lat: number | null;
        lng: number | null;
        images: string[];
        amenities: string[];
        description: string | null;
        status: string;
        verification_status: string;
      };
      managers: Array<{
        id: string;
        user_id: string;
        role: string;
        invited_at: string;
        accepted_at: string | null;
        user: { id: string; name: string; email: string } | null;
      }>;
      amenities: string[];
    };
    return <TempleSettingsView settings={settings} />;
  } catch {
    return (
      <div className="p-6 max-w-4xl mx-auto text-center">
        <h1 className="text-2xl font-bold mb-4">Unable to load settings</h1>
        <a href="/temples" className="text-indigo-600 hover:underline">
          Back to temples
        </a>
      </div>
    );
  }
}

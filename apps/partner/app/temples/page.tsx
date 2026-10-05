import { TemplesListView } from '@/presentation/temples/TemplesListView';
import { partnerApi } from '@/adapters/backend/partner-api-client';

export default async function TemplesPage() {
  try {
    const data = await partnerApi.getTemples({ limit: 20 });
    const temples = (data as { data: Array<{
      id: string;
      slug: string;
      name: string;
      trust_name: string;
      type: string;
      city: string;
      state: string;
      status: string;
      verification_status: string;
    }> }).data ?? [];
    return <TemplesListView temples={temples} />;
  } catch {
    return <TemplesListView temples={[]} />;
  }
}
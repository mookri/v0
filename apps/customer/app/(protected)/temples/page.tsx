import { TemplesPageView } from '@/presentation/temples/TemplesPage';
import { getServerCustomerApi } from '@/adapters/backend/customer-api-server';

export const dynamic = 'force-dynamic';

export default async function TemplesPage() {
  try {
    const api = await getServerCustomerApi();
    const data = await api.getTemples({ limit: 20 });
    const temples = (data as { data: Array<{
      id: string;
      slug: string;
      name: string;
      trust_name: string;
      type: string;
      city: string;
      state: string;
      lat: number | null;
      lng: number | null;
      images: string[];
      amenities: string[];
      description: string | null;
      rating_avg: number;
      review_count: number;
      verification_status: string;
      status: string;
    }> }).data ?? [];
    return <TemplesPageView temples={temples} />;
  } catch {
    return <TemplesPageView temples={[]} />;
  }
}
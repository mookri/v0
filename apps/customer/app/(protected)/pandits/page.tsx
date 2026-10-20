import { PanditsPageView } from '@/presentation/pandits/PanditsPage';
import { getServerCustomerApi } from '@/adapters/backend/customer-api-server';

export const dynamic = 'force-dynamic';

export default async function PanditsPage() {
  try {
    const api = await getServerCustomerApi();
    const data = await api.getPandits({ limit: 20 });
    // The BFF list envelope is `{items, pagination}` (not the backend's
    // `{data, meta}`); reading `.data` here always produced an empty list.
    const pandits = (data as { items: Array<{
      id: string;
      name: string;
      bio?: string;
      specializations: string[];
      market: string;
      languages: string[];
      experience_years: number;
      rating_avg: number;
      review_count: number;
    }> }).items ?? [];
    return <PanditsPageView pandits={pandits} />;
  } catch {
    return <PanditsPageView pandits={[]} />;
  }
}
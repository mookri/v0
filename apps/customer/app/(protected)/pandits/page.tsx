import { PanditsPageView } from '@/presentation/pandits/PanditsPage';
import { customerApi } from '@/adapters/backend/customer-api-client';

export default async function PanditsPage() {
  try {
    const data = await customerApi.getPandits({ limit: 20 });
    const pandits = (data as { data: Array<{
      id: string;
      name: string;
      bio?: string;
      specializations: string[];
      market: string;
      languages: string[];
      experience_years: number;
      rating_avg: number;
      review_count: number;
    }> }).data ?? [];
    return <PanditsPageView pandits={pandits} />;
  } catch {
    return <PanditsPageView pandits={[]} />;
  }
}
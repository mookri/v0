import { DiscoverPageView } from '@/presentation/discover/DiscoverPage';
import { getServerCustomerApi } from '@/adapters/backend/customer-api-server';
import type { DiscoverFeed } from '@/application/discover/discover-feed';

export const dynamic = 'force-dynamic';

export default async function DiscoverPage() {
  try {
    const api = await getServerCustomerApi();
    const feed = (await api.getDiscover()) as DiscoverFeed;
    return <DiscoverPageView feed={feed ?? { recommended: [], popular: [], recent: [] }} />;
  } catch {
    return <DiscoverPageView feed={{ recommended: [], popular: [], recent: [] }} />;
  }
}
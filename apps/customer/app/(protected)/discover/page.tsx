import { DiscoverPageView } from '@/presentation/discover/DiscoverPage';
import { customerApi } from '@/adapters/backend/customer-api-client';
import type { DiscoverFeed } from '@/application/discover/discover-feed';

export const dynamic = 'force-dynamic';

export default async function DiscoverPage() {
  try {
    const data = await customerApi.getHome(); // getHome includes discover feed
    const feed = (data as { discover: DiscoverFeed }).discover ?? { recommended: [], popular: [], recent: [] };
    return <DiscoverPageView feed={feed} />;
  } catch {
    return <DiscoverPageView feed={{ recommended: [], popular: [], recent: [] }} />;
  }
}
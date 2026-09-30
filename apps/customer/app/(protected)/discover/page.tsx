import { getDiscoverFeed } from '@/application/discover/discover-feed';
import { DiscoverPageView } from '@/presentation/discover/DiscoverPage';

export default function DiscoverPage() {
  const feed = getDiscoverFeed({});
  return <DiscoverPageView feed={feed} />;
}

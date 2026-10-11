import { SavedPageView } from '@/presentation/saved/SavedPage';
import { customerApi } from '@/adapters/backend/customer-api-client';
import type { SavedItemCard, SavedItemType } from '@/application/saved/saved-items';

export const dynamic = 'force-dynamic';

export default async function SavedPage() {
  try {
    const data = await customerApi.getSaved();
    const rawItems = (data as { items: { id: string; type: string; title: string; href: string }[] }).items || [];
    const items: SavedItemCard[] = rawItems.map(item => ({
      id: item.id,
      type: item.type as SavedItemType,
      title: item.title,
      href: item.href,
    }));
    return <SavedPageView items={items} />;
  } catch (e: any) {
    if (e.status === 401) {
      return <SavedPageView items={[]} />;
    }
    return <SavedPageView items={[]} />;
  }
}
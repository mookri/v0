import { listSavedItems } from '@/application/saved/saved-items';
import { SavedPageView } from '@/presentation/saved/SavedPage';

export default function SavedPage() {
  const items = listSavedItems([], () => null);
  return <SavedPageView items={items} />;
}

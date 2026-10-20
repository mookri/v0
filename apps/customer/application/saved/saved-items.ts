export type SavedItemType = 'puja' | 'content';

export interface SavedItem {
  id: string;
  type: SavedItemType;
  resourceId: string;
  createdAt: string;
}

export interface SavedItemCard {
  id: string;
  type: SavedItemType;
  title: string;
  href: string;
}

export function toSavedItemCard(
  item: SavedItem,
  resolve: (item: SavedItem) => { title: string; href: string } | null,
): SavedItemCard | null {
  const resolved = resolve(item);
  if (!resolved) return null;
  return { id: item.id, type: item.type, title: resolved.title, href: resolved.href };
}

export function listSavedItems(
  items: SavedItem[],
  resolve: (item: SavedItem) => { title: string; href: string } | null,
): SavedItemCard[] {
  const cards: SavedItemCard[] = [];
  for (const item of items) {
    const card = toSavedItemCard(item, resolve);
    if (card) cards.push(card);
  }
  return cards;
}

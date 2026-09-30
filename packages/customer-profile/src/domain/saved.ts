export type SavedEntityType = 'PUJA' | 'PANDIT' | 'SERVICE' | 'ARTICLE' | 'COLLECTION';

export interface SavedItem {
  id: string;
  customerId: string;
  entityType: SavedEntityType;
  entityId: string;
  listId?: string;
  titleAtSave?: string;
  savedAt: string;
}

export interface SavedList {
  id: string;
  customerId: string;
  name: string;
  type: 'SYSTEM' | 'CUSTOM';
  createdAt: string;
}

export interface SavedSearch {
  id: string;
  customerId: string;
  name?: string;
  query: { text: string; entityTypes?: string[]; filters?: Record<string, string[]> };
  createdAt: string;
  lastUsedAt?: string;
}

export function saveKey(customerId: string, entityType: string, entityId: string, listId?: string): string {
  return `${customerId}:${entityType}:${entityId}:${listId ?? 'default'}`;
}

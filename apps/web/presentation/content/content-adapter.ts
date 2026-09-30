import type { ContentDocument } from '../../../../packages/content/src/domain/ContentDocument';

// Source corpus stays at apps/web/content; this adapter maps raw JSON rows
// into the canonical ContentDocument without mutating the source.
export interface RawContentRow {
  id: string;
  slug: string;
  cluster: string;
  title: string;
  metaDescription?: string;
  lastUpdated?: string;
}

const BASE_PATH_BY_CLUSTER: Record<string, string> = {
  pujas: 'pujas',
  brand: 'explore',
  services: 'services',
  ceremonies: 'ceremonies',
};

export function toCanonicalDocument(row: RawContentRow, locale = 'en'): ContentDocument {
  const now = '2026-09-30T00:00:00.000Z';
  return {
    id: row.id,
    type: row.cluster === 'pujas' ? 'puja-guide' : 'article',
    slug: row.slug,
    basePath: BASE_PATH_BY_CLUSTER[row.cluster] ?? row.cluster,
    locale,
    title: row.title,
    summary: row.metaDescription,
    body: [],
    status: 'published',
    version: 1,
    createdAt: now,
    updatedAt: row.lastUpdated ?? now,
    publishedAt: now,
  };
}

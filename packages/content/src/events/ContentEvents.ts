import { ContentDocument } from '@navdhya/content/src/domain/ContentDocument';

export type ContentPublished = { type: 'ContentPublished'; documentId: string; slug: string };
export type ContentUpdated = { type: 'ContentUpdated'; documentId: string; slug: string };
export type ContentUnpublished = { type: 'ContentUnpublished'; documentId: string; slug: string };

export type ContentEvent = ContentPublished | ContentUpdated | ContentUnpublished;

export function publishedEvent(doc: ContentDocument): ContentPublished {
  return { type: 'ContentPublished', documentId: doc.id, slug: doc.slug };
}

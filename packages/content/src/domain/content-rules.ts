import { ContentDocument } from './ContentDocument';

export function isPublishable(doc: ContentDocument): boolean {
  return doc.status === 'approved' || doc.status === 'published';
}

export function isPublic(doc: ContentDocument, nowIso: string): boolean {
  if (doc.status !== 'published') return false;
  if (doc.publishAt && doc.publishAt > nowIso) return false;
  if (doc.unpublishAt && doc.unpublishAt < nowIso) return false;
  return true;
}

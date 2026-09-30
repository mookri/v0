import { ContentRepository } from '../ports/ContentRepository';
import { ContentDocument } from '../domain/ContentDocument';
import { isPublic } from '../domain/content-rules';

export async function resolveContent(
  repo: ContentRepository,
  slug: string,
  locale: string,
  nowIso: string,
): Promise<ContentDocument | null> {
  const doc = await repo.findBySlug(slug, locale);
  if (!doc || !isPublic(doc, nowIso)) return null;
  return doc;
}

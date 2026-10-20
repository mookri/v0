import { ContentRepository } from '@navdhya/content/src/ports/ContentRepository';
import { ContentDocument } from '@navdhya/content/src/domain/ContentDocument';
import { isPublic } from '@navdhya/content/src/domain/content-rules';

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

import { ContentRepository } from '@navdhya/content/src/ports/ContentRepository';
import { ContentId } from '@navdhya/content/src/domain/ContentId';
import { ContentDocument } from '@navdhya/content/src/domain/ContentDocument';

export async function getDocument(repo: ContentRepository, id: ContentId): Promise<ContentDocument | null> {
  return repo.findById(id);
}

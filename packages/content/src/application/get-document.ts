import { ContentRepository } from '../ports/ContentRepository';
import { ContentId } from '../domain/ContentId';
import { ContentDocument } from '../domain/ContentDocument';

export async function getDocument(repo: ContentRepository, id: ContentId): Promise<ContentDocument | null> {
  return repo.findById(id);
}

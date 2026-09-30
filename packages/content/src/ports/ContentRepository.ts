import { ContentDocument } from '../domain/ContentDocument';
import { ContentId } from '../domain/ContentId';

export interface ContentRepository {
  findById(id: ContentId): Promise<ContentDocument | null>;
  findBySlug(slug: string, locale: string): Promise<ContentDocument | null>;
  listPublished(locale?: string): Promise<ContentDocument[]>;
}

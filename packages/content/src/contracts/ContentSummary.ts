import { ContentId } from '../domain/ContentId';
import { ContentType } from '../domain/ContentType';

export interface ContentSummary {
  id: ContentId;
  type: ContentType;
  slug: string;
  title: string;
  locale: string;
}

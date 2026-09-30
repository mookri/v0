import { ContentId } from '@navdhya/content/src/domain/ContentId';
import { ContentType } from '@navdhya/content/src/domain/ContentType';

export interface ContentSummary {
  id: ContentId;
  type: ContentType;
  slug: string;
  title: string;
  locale: string;
}

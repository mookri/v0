import { ContentDocument } from '@navdhya/content/src/domain/ContentDocument';

export interface ContentSource {
  loadAll(): Promise<ContentDocument[]>;
}

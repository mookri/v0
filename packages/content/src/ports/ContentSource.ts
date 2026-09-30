import { ContentDocument } from '../domain/ContentDocument';

export interface ContentSource {
  loadAll(): Promise<ContentDocument[]>;
}

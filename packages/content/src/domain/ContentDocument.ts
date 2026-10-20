import { ContentId } from './ContentId';
import { ContentType } from './ContentType';
import { PublicationState } from './PublicationState';
import { Locale } from './Locale';
import { ContentBlock } from './ContentBlock';

export type ContentRelation = 'relatedTo' | 'partOf' | 'references' | 'alternativeTo';

export interface EntityReference {
  type: string;
  id: string;
}

export interface ContentRelationship {
  relation: ContentRelation;
  targetId: ContentId;
}

export interface ContentDocument {
  id: ContentId;
  type: ContentType;
  slug: string;
  basePath: string;
  locale: Locale;
  title: string;
  summary?: string;
  body: ContentBlock[];
  status: PublicationState;
  version: number;
  canonicalDocumentId?: ContentId;
  previousSlugs?: string[];
  relationships?: ContentRelationship[];
  entityReferences?: EntityReference[];
  publishAt?: string;
  unpublishAt?: string;
  createdAt: string;
  updatedAt: string;
  publishedAt?: string;
}

import { ContentDocument } from '../domain/ContentDocument';
import { ContentSummary } from './ContentSummary';
import { EntityReference } from '../domain/ContentDocument';

export interface Breadcrumb {
  label: string;
  href: string;
}

export interface PublicContentPage {
  document: ContentDocument;
  canonicalUrl: string;
  locale: string;
  breadcrumbs: Breadcrumb[];
  relatedContent: ContentSummary[];
  entityReferences: EntityReference[];
}

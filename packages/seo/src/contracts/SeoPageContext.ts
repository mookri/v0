export interface SeoPageContext {
  canonicalUrl: string;
  title: string;
  description?: string;
  robots?: string;
  breadcrumbs?: { label: string; href: string }[];
  alternates?: { hrefLang: string; href: string }[];
}

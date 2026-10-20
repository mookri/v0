export interface SeoMetadata {
  title: string;
  description?: string;
  canonical?: string;
  robots?: string;
  openGraph?: { type: 'article' | 'website'; title: string; description?: string };
}

export function resolveMetadata(input: {
  title: string;
  description?: string;
  canonicalUrl: string;
  schemaType?: string;
  noindex?: boolean;
}): SeoMetadata {
  return {
    title: input.title,
    description: input.description,
    canonical: input.canonicalUrl,
    robots: input.noindex ? 'noindex, nofollow' : 'index, follow',
    openGraph: {
      type: input.schemaType === 'Article' || input.schemaType === 'FAQPage' ? 'article' : 'website',
      title: input.title,
      description: input.description,
    },
  };
}

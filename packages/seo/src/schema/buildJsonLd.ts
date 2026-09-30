export interface StructuredData {
  '@context': string;
  '@type': string;
  [key: string]: unknown;
}

export function buildJsonLd(input: {
  type: string;
  name: string;
  url: string;
  description?: string;
}): StructuredData {
  return {
    '@context': 'https://schema.org',
    '@type': input.type,
    name: input.name,
    url: input.url,
    ...(input.description ? { description: input.description } : {}),
  };
}

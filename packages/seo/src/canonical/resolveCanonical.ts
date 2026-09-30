export function resolveCanonicalUrl(siteUrl: string, basePath: string, slug: string, override?: string): string {
  if (override?.trim()) return override.trim().startsWith('http') ? override.trim() : `${siteUrl}${override.trim()}`;
  return `${siteUrl}/${basePath}/${slug}`;
}

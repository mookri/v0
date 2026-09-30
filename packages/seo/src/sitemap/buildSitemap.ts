export interface SitemapUrl {
  loc: string;
  lastmod?: string;
}

export function partitionSitemap(urls: SitemapUrl[], maxPerFile = 5000): SitemapUrl[][] {
  const parts: SitemapUrl[][] = [];
  for (let i = 0; i < urls.length; i += maxPerFile) parts.push(urls.slice(i, i + maxPerFile));
  return parts;
}

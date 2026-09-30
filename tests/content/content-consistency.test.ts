import { test, expect } from 'vitest';
import { toCanonicalDocument } from '../../apps/web/presentation/content/content-adapter';
import { resolveCanonicalUrl } from '../../packages/seo/src/canonical/resolveCanonical';
import { resolveMetadata } from '../../packages/seo/src/metadata/resolveMetadata';
import { buildJsonLd } from '../../packages/seo/src/schema/buildJsonLd';
import { buildLlmsTxt, AeoDocument } from '../../packages/aeo/src/Aeo';

const SITE = 'https://www.navdhya.com';

test('golden puja page: single canonical identity across surfaces', () => {
  const doc = toCanonicalDocument({
    id: 'puja-core-009',
    slug: 'annaprashan',
    cluster: 'pujas',
    title: 'Annaprashan Ceremony in Delhi NCR',
    metaDescription: 'Book Annaprashan ceremony in Delhi NCR.',
  });
  const canonical = resolveCanonicalUrl(SITE, doc.basePath, doc.slug);
  const meta = resolveMetadata({ title: doc.title, description: doc.summary, canonicalUrl: canonical, schemaType: 'Service' });
  const jsonLd = buildJsonLd({ type: 'Service', name: doc.title, url: canonical, description: doc.summary });
  const llms = buildLlmsTxt([{ title: doc.title, canonicalUrl: canonical, summary: doc.summary ?? doc.title }]);
  const aeo: AeoDocument = {
    title: doc.title,
    summary: doc.summary ?? doc.title,
    canonicalUrl: canonical,
    facts: [],
    sourceUpdatedAt: doc.updatedAt,
  };

  expect(canonical).toBe('https://www.navdhya.com/pujas/annaprashan');
  expect(meta.canonical).toBe(canonical);
  expect(jsonLd.url).toBe(canonical);
  expect(aeo.canonicalUrl).toBe(canonical);
  expect(llms).toContain(canonical);
});

test('unpublished content never indexes', async () => {
  const { isIndexable } = await import('../../packages/seo/src/indexing/isIndexable');
  expect(isIndexable({ status: 'draft', env: 'production' })).toBe(false);
  expect(isIndexable({ status: 'published', env: 'staging' })).toBe(false);
  expect(isIndexable({ status: 'published', env: 'production' })).toBe(true);
});

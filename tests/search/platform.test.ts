import { test, expect } from 'vitest';
import { normalizeQueryText } from '../../packages/search/src/domain/CanonicalQuery';
import { isEligible } from '../../packages/search/src/domain/SearchDocument';
import { expandSynonyms, suggestCorrection } from '../../packages/search/src/domain/Synonyms';
import { scoreDocument, rankDeterministic, DEFAULT_RANKING_POLICY } from '../../packages/search/src/ranking/ranking';
import { shouldApplyIndexEvent } from '../../packages/search/src/ports/SearchEngine';
import { emptyResult } from '../../packages/search/src/domain/SearchResultContract';
import { composeSurface } from '../../packages/discovery/src/index';
import { InMemorySearchEngine } from '../../infrastructure/search/InMemorySearchEngine';

const doc = (over: Record<string, unknown> = {}) => ({
  id: 'p1',
  entityType: 'puja' as const,
  title: 'Lakshmi Puja',
  locale: 'en-IN',
  categories: ['wealth'],
  tags: [],
  searchableText: ['lakshmi puja wealth'],
  facets: { occasion: ['wealth'] },
  status: 'ACTIVE' as const,
  visibility: 'public' as const,
  sourceVersion: 3,
  schemaVersion: 7,
  indexedAt: '2026-09-30T00:00:00.000Z',
  ...over,
});

test('normalization unifies case/diacritics/punctuation', () => {
  expect(normalizeQueryText('  LAKSHMI—Puja!! ')).toBe('lakshmi puja');
});

test('synonyms expand Hinglish variants; typos suggest', () => {
  expect(expandSynonyms(['laxmi', 'pooja'])).toEqual(['lakshmi', 'puja']);
  expect(suggestCorrection('lakhsmi', ['Lakshmi Puja'])).toBe('Lakshmi Puja');
  expect(suggestCorrection('xyzzy', ['Lakshmi Puja'])).toBeNull();
});

test('eligibility filters inactive + unauthorized before ranking', () => {
  expect(isEligible(doc(), 'public')).toBe(true);
  expect(isEligible(doc({ status: 'INACTIVE' }), 'public')).toBe(false);
  expect(isEligible(doc({ visibility: 'admin' }), 'public')).toBe(false);
  expect(isEligible(doc({ visibility: 'admin' }), 'admin')).toBe(true);
});

test('ranking is deterministic with stable tiebreak', () => {
  const ranked = rankDeterministic([
    { id: 'b', score: 1 },
    { id: 'a', score: 1 },
    { id: 'c', score: 2 },
  ]);
  expect(ranked.map((r) => r.id)).toEqual(['c', 'a', 'b']);
  const s = scoreDocument(doc(), { textScore: 1, popularityScore: 1, availabilityScore: 1, locationScore: 1, qualityScore: 1 }, DEFAULT_RANKING_POLICY);
  expect(s).toBeCloseTo(1);
});

test('indexing is idempotent and version-gated', () => {
  expect(shouldApplyIndexEvent(undefined, 1)).toBe(true);
  expect(shouldApplyIndexEvent(5, 4)).toBe(false);
  expect(shouldApplyIndexEvent(5, 6)).toBe(true);
});

test('empty results carry policy versions + suggestions', () => {
  const r = emptyResult('q1', 'Lakshmi Puja');
  expect(r.items).toEqual([]);
  expect(r.meta.rankingPolicyVersion).toBe('puja-default-v1');
  expect(r.didYouMean).toBe('Lakshmi Puja');
});

test('in-memory engine retrieves lexically with facets intact', async () => {
  const engine = new InMemorySearchEngine();
  await engine.bulkIndex([doc(), doc({ id: 'p2', title: 'Ganesh Puja', searchableText: ['ganesh puja'] })]);
  const res = await engine.search({ text: 'lakshmi', limit: 10, locale: 'en-IN' });
  expect(res.total).toBe(1);
  expect(res.documents[0]?.id).toBe('p1');
  expect(await engine.suggest('lak', 5)).toEqual(['Lakshmi Puja']);
});

test('discovery degrades gracefully when providers fail', async () => {
  const surface = await composeSurface('home', 'home', [
    { getSection: async () => ({ id: 's1', type: 'popular' as const, items: [], source: 'search' as const, criticality: 'important' as const }) },
    { getSection: async () => { throw new Error('reco down'); } },
  ]);
  expect(surface.sections).toHaveLength(1);
});

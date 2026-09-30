import { test, expect } from 'vitest';
import { decide } from '../../packages/ads/src/decision/decision-engine/decide';
import { AdRequest } from '../../packages/ads/src/contracts/AdRequest';
import { CampaignCandidate } from '../../packages/ads/src/decision/Candidate';

function req(over: Partial<AdRequest> = {}): AdRequest {
  return {
    requestId: 'r1',
    product: 'web',
    placementId: 'homepage.hero',
    pageContext: { routeId: 'home' },
    locale: 'en',
    consent: { personalizedAds: true, measurement: true },
    timestamp: '2026-10-01T00:00:00.000Z',
    ...over,
  };
}

function cand(over: Partial<CampaignCandidate['campaign']> = {}, creativeOver: Partial<CampaignCandidate['creative']> = {}): CampaignCandidate {
  return {
    campaign: {
      id: 'c1', source: 'external', status: 'active', priority: 1, bid: 2,
      placementIds: ['homepage.hero'], formats: ['native'],
      ...over,
    },
    creative: {
      id: 'cr1', version: 1, format: 'native', headline: 'H', ctaText: 'Go',
      destination: 'https://example.com', isSponsored: true, status: 'approved',
      ...creativeOver,
    },
  };
}

const FORMATS = ['native', 'image'];

test('active campaign fills', () => {
  const d = decide(req(), [cand()], { placementFormats: FORMATS });
  expect(d.status).toBe('filled');
  if (d.status === 'filled') expect(d.creative.isSponsored).toBe(true);
});

test('inactive campaign empties', () => {
  const d = decide(req(), [cand({ status: 'paused' })], { placementFormats: FORMATS });
  expect(d.status).toBe('empty');
});

test('expired campaign empties', () => {
  const d = decide(req(), [cand({ endAt: '2020-01-01T00:00:00.000Z' })], { placementFormats: FORMATS });
  expect(d.status).toBe('empty');
});

test('wrong placement empties', () => {
  const d = decide(req({ placementId: 'other' }), [cand()], { placementFormats: FORMATS });
  expect(d.status).toBe('empty');
});

test('no consent blocks personalized candidate', () => {
  const c = cand({ segmentIds: ['wedding-interest'] });
  const d = decide(req({ consent: { personalizedAds: false, measurement: true } }), [c], { placementFormats: FORMATS });
  expect(d.status).toBe('empty');
});

test('higher bid wins; ties break by id', () => {
  const a = cand({ id: 'c-a', bid: 1 });
  const b = cand({ id: 'c-b', bid: 5 });
  const d = decide(req(), [a, b], { placementFormats: FORMATS });
  expect(d.status).toBe('filled');
  if (d.status === 'filled') expect(d.campaign.id).toBe('c-b');
});

test('empty inventory yields structured empty, not null', () => {
  const d = decide(req(), [], { placementFormats: FORMATS });
  expect(d.status).toBe('empty');
  if (d.status === 'empty') expect(d.reason).toBe('NO_ELIGIBLE_CAMPAIGN');
});

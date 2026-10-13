// Governed dataLayer units (plan B4): gtm_ga4-only gate, the `purchase`
// double-fire guard, deterministic consent-mode shapes.

import { describe, expect, it } from 'vitest';
import {
  CONSENT_MODE_DEFAULTS,
  consentModeUpdate,
  gtmContainer,
  pushGtmEvent,
  type DataLayerPush,
} from '@navdhya/analytics/src/adapters/ga4-data-layer';

describe('ga4-data-layer (B4)', () => {
  it('pushes a gtm_ga4-eligible event with its declared params', () => {
    const dataLayer: DataLayerPush[] = [];
    const outcome = pushGtmEvent(dataLayer, 'page_view', { page_type: 'checkout' });
    expect(outcome).toEqual({ pushed: true });
    expect(dataLayer).toEqual([{ event: 'page_view', page_type: 'checkout' }]);
  });

  it('GUARD: `purchase` cannot be re-fired through GTM — it is not a browser event', () => {
    const dataLayer: DataLayerPush[] = [];
    const outcome = pushGtmEvent(dataLayer, 'purchase', { value: 999, currency: 'INR' });
    expect(outcome.pushed).toBe(false);
    expect(outcome.reason).toContain('unknown event "purchase"');
    expect(dataLayer).toEqual([]); // confirmation pages cannot duplicate server truth

    // The browser's OWN confirmation event is still pushable:
    expect(pushGtmEvent(dataLayer, 'purchase_viewed', { value: 999, item_count: 1 }).pushed).toBe(true);
  });

  it('rejects events outside the GTM sink: server_ga4 and internal_only', () => {
    const dataLayer: DataLayerPush[] = [];
    const server = pushGtmEvent(dataLayer, 'payment_succeeded', { value: 1, currency: 'INR', transaction_ref: 'TX' });
    expect(server.pushed).toBe(false);
    expect(server.reason).toContain('not GTM-eligible');

    const ads = pushGtmEvent(dataLayer, 'ad_click', { placement: 'feed', ad_ref: 'a1' });
    expect(ads.pushed).toBe(false);
    expect(dataLayer).toEqual([]);
  });

  it('strips forbidden keys and validates declared params', () => {
    const dataLayer: DataLayerPush[] = [];
    // Undeclared `email` is stripped (data minimization) before validation:
    expect(pushGtmEvent(dataLayer, 'page_view', { page_type: 'home', email: 'x@y.z' }).pushed).toBe(true);
    expect(dataLayer[0]).toEqual({ event: 'page_view', page_type: 'home' });

    // Missing required param → rejected, nothing pushed:
    expect(pushGtmEvent(dataLayer, 'view_item', {}).pushed).toBe(false);
  });

  it('consent shapes are deterministic: UNKNOWN counts as denied', () => {
    expect(CONSENT_MODE_DEFAULTS).toEqual({
      event: 'consent_default',
      analytics_storage: 'denied',
      ad_storage: 'denied',
      wait_for_update: 500,
    });
    expect(consentModeUpdate({ analytics: 'GRANTED', ads: 'GRANTED' })).toEqual({
      event: 'consent_update',
      analytics_storage: 'granted',
      ad_storage: 'granted',
    });
    expect(consentModeUpdate({ analytics: 'DENIED', ads: 'GRANTED' })).toEqual({
      event: 'consent_update',
      analytics_storage: 'denied',
      ad_storage: 'granted',
    });
    expect(consentModeUpdate({ analytics: 'UNKNOWN', ads: 'UNKNOWN' })).toEqual({
      event: 'consent_update',
      analytics_storage: 'denied',
      ad_storage: 'denied',
    });
  });

  it('validates container ids and builds CSP-friendly URLs', () => {
    expect(gtmContainer('GTM-ABC123Z')).toEqual({
      loaderUrl: 'https://www.googletagmanager.com/gtm.js?id=GTM-ABC123Z',
      noscriptUrl: 'https://www.googletagmanager.com/ns.html?id=GTM-ABC123Z',
    });
    expect(() => gtmContainer('evil.com/x')).toThrow(/invalid container id/);
  });
});

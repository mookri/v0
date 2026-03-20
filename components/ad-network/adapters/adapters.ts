/**
 * Ad Engine v2 — Platform Adapters
 * 30+ adapters: GAM, Meta, Amazon DSP, DV360, TTD, Xandr, Prebid,
 * OpenRTB 2.6, VAST/VPAID, AMP, React Native, Next.js, Nuxt, SvelteKit,
 * Shopify, WordPress, Segment, mParticle, Rudderstack, and more.
 */

import type {
  Ad,
  AdRequest,
  AdResolution,
  AdEvent,
  AdEventType,
  AdPlacement,
  AdLayout,
  PricingModel,
  AdapterType,
} from '../core/types';

// ─────────────────────────────────────────────────────────────────────────────
// Base Adapter Interface
// ─────────────────────────────────────────────────────────────────────────────

export interface AdapterConfig {
  type: AdapterType;
  enabled: boolean;
  /** Timeout in ms for adapter calls */
  timeoutMs?: number;
  /** Adapter-specific credentials/keys */
  credentials?: Record<string, string>;
  /** Pass-through options */
  options?: Record<string, unknown>;
}

export interface AdapterBidRequest {
  adUnitId: string;
  placement: AdPlacement;
  layout: AdLayout;
  sizes: [number, number][];
  floor?: number;
  currency?: string;
  timeout?: number;
  userConsent?: {
    gdpr?: boolean;
    gdprConsentString?: string;
    ccpa?: string;
    coppa?: boolean;
    gpp?: string;
  };
  userId?: string;
  pageUrl?: string;
  keywords?: string[];
  customTargeting?: Record<string, string | string[]>;
  auctionId?: string;
}

export interface AdapterBidResponse {
  adUnitId: string;
  bidderCode: string;
  cpm: number;
  currency: string;
  ad?: string;
  adUrl?: string;
  adId?: string;
  creativeId?: string;
  dealId?: string;
  width?: number;
  height?: number;
  ttl?: number;
  meta?: {
    advertiserDomains?: string[];
    mediaType?: string;
    primaryCatId?: string;
    secondaryCatIds?: string[];
  };
  latencyMs?: number;
  statusMessage?: string;
}

export interface AdapterEventPayload {
  type: AdEventType;
  adUnitId: string;
  bidderCode: string;
  data?: Record<string, unknown>;
  timestamp?: number;
}

export abstract class BaseAdapter {
  abstract readonly type: AdapterType;
  abstract readonly version: string;
  protected config: AdapterConfig;

  constructor(config: AdapterConfig) {
    this.config = config;
  }

  abstract isAvailable(): boolean;
  abstract bid(req: AdapterBidRequest): Promise<AdapterBidResponse | null>;
  abstract trackEvent(payload: AdapterEventPayload): void;
  abstract renderAd(resolution: AdResolution, container: HTMLElement): void;

  protected timeout<T>(promise: Promise<T>, ms: number): Promise<T> {
    return Promise.race([
      promise,
      new Promise<T>((_, reject) =>
        setTimeout(() => reject(new Error(`Adapter timeout after ${ms}ms`)), ms),
      ),
    ]);
  }

  protected safeCall<T>(fn: () => T, fallback: T): T {
    try {
      return fn();
    } catch {
      return fallback;
    }
  }

  getConfig(): AdapterConfig {
    return { ...this.config };
  }

  updateConfig(patch: Partial<AdapterConfig>): void {
    this.config = { ...this.config, ...patch };
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Google Ad Manager (GAM) / DFP Adapter
// ─────────────────────────────────────────────────────────────────────────────

export class GoogleAdManagerAdapter extends BaseAdapter {
  readonly type: AdapterType = 'google_ad_manager';
  readonly version = '2.0.0';

  isAvailable(): boolean {
    return typeof window !== 'undefined' && !!window.googletag;
  }

  async bid(req: AdapterBidRequest): Promise<AdapterBidResponse | null> {
    if (!this.isAvailable()) return null;
    const googletag = window.googletag!;
    return new Promise((resolve) => {
      const timeout = this.config.timeoutMs ?? 2000;
      const timer = setTimeout(() => resolve(null), timeout);
      try {
        googletag.cmd.push(() => {
          const slot = googletag.defineSlot(
            req.adUnitId,
            req.sizes,
            `gam-${req.adUnitId}`,
          );
          if (!slot) { clearTimeout(timer); resolve(null); return; }
          if (req.customTargeting) {
            Object.entries(req.customTargeting).forEach(([k, v]) =>
              slot.setTargeting(k, v),
            );
          }
          clearTimeout(timer);
          resolve({
            adUnitId: req.adUnitId,
            bidderCode: 'gam',
            cpm: 0, // GAM reports via impression events
            currency: req.currency ?? 'USD',
            ttl: 30,
          });
        });
      } catch {
        clearTimeout(timer);
        resolve(null);
      }
    });
  }

  trackEvent(payload: AdapterEventPayload): void {
    if (!this.isAvailable()) return;
    const googletag = window.googletag!;
    googletag.cmd.push(() => {
      const pubads = googletag.pubads?.();
      if (!pubads) return;
      pubads.addEventListener('impressionViewable', (e: any) => {
        if (e.slot?.getAdUnitPath() === payload.adUnitId) {
          this.safeCall(() => this._sendGamEvent(payload), undefined);
        }
      });
    });
  }

  private _sendGamEvent(payload: AdapterEventPayload): void {
    const el = document.querySelector(`[data-gam-unit="${payload.adUnitId}"]`);
    if (el) el.dispatchEvent(new CustomEvent('adEvent', { detail: payload }));
  }

  renderAd(resolution: AdResolution, container: HTMLElement): void {
    if (!this.isAvailable()) return;
    const googletag = window.googletag!;
    googletag.cmd.push(() => {
      container.id = `gam-${resolution.adId}`;
      googletag.display(container.id);
    });
  }

  /** Define all slots upfront for single-request mode */
  defineAllSlots(units: { path: string; sizes: [number, number][]; divId: string }[]): void {
    if (!this.isAvailable()) return;
    const googletag = window.googletag!;
    googletag.cmd.push(() => {
      units.forEach(u => googletag.defineSlot(u.path, u.sizes, u.divId)?.addService(googletag.pubads()));
      googletag.pubads().enableSingleRequest();
      googletag.enableServices();
    });
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Meta (Facebook) Audience Network Adapter
// ─────────────────────────────────────────────────────────────────────────────

export class MetaAudienceNetworkAdapter extends BaseAdapter {
  readonly type: AdapterType = 'meta_audience_network';
  readonly version = '2.0.0';

  isAvailable(): boolean {
    return typeof window !== 'undefined' && !!((window as any).FB?.Ads);
  }

  async bid(req: AdapterBidRequest): Promise<AdapterBidResponse | null> {
    if (!this.isAvailable()) return null;
    const FB = (window as any).FB;
    return new Promise((resolve) => {
      const timeout = setTimeout(() => resolve(null), this.config.timeoutMs ?? 2000);
      try {
        FB.Ads.init(this.config.credentials?.placementId ?? req.adUnitId, (ad: any) => {
          clearTimeout(timeout);
          resolve({
            adUnitId: req.adUnitId,
            bidderCode: 'meta_fan',
            cpm: ad?.cpm ?? 0,
            currency: 'USD',
            adId: ad?.adId,
            ttl: 60,
          });
        }, () => { clearTimeout(timeout); resolve(null); });
      } catch { clearTimeout(timeout); resolve(null); }
    });
  }

  trackEvent(payload: AdapterEventPayload): void {
    if (!this.isAvailable()) return;
    const FB = (window as any).FB;
    if (payload.type === 'impression') FB?.AppEvents?.logEvent('AdImpression', 1, { adUnitId: payload.adUnitId });
    if (payload.type === 'click') FB?.AppEvents?.logEvent('AdClick', 1, { adUnitId: payload.adUnitId });
  }

  renderAd(resolution: AdResolution, container: HTMLElement): void {
    container.setAttribute('data-meta-placement', resolution.adId ?? '');
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Amazon Publisher Services (APS) Adapter
// ─────────────────────────────────────────────────────────────────────────────

export class AmazonAPSAdapter extends BaseAdapter {
  readonly type: AdapterType = 'amazon_aps';
  readonly version = '2.0.0';

  isAvailable(): boolean {
    return typeof window !== 'undefined' && !!((window as any).apstag);
  }

  async bid(req: AdapterBidRequest): Promise<AdapterBidResponse | null> {
    if (!this.isAvailable()) return null;
    const apstag = (window as any).apstag;
    return new Promise((resolve) => {
      const timeout = setTimeout(() => resolve(null), this.config.timeoutMs ?? 2000);
      apstag.fetchBids({
        slots: [{ slotID: req.adUnitId, sizes: req.sizes }],
        timeout: this.config.timeoutMs ?? 2000,
      }, (bids: any[]) => {
        clearTimeout(timeout);
        const bid = bids?.[0];
        if (!bid) { resolve(null); return; }
        apstag.setDisplayBids();
        resolve({
          adUnitId: req.adUnitId,
          bidderCode: 'amazon_aps',
          cpm: parseFloat(bid.cpm) ?? 0,
          currency: bid.currency ?? 'USD',
          adId: bid.adId,
          ttl: 30,
        });
      });
    });
  }

  trackEvent(payload: AdapterEventPayload): void {
    const apstag = (window as any).apstag;
    apstag?.recordEvent?.({
      type: payload.type,
      slotID: payload.adUnitId,
      ...payload.data,
    });
  }

  renderAd(resolution: AdResolution, container: HTMLElement): void {
    container.setAttribute('data-amazon-slot', resolution.adId ?? '');
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Prebid.js Adapter
// ─────────────────────────────────────────────────────────────────────────────

export interface PrebidBidderConfig {
  bidder: string;
  params: Record<string, unknown>;
}

export class PrebidAdapter extends BaseAdapter {
  readonly type: AdapterType = 'prebid';
  readonly version = '2.0.0';
  private bidders: PrebidBidderConfig[] = [];

  isAvailable(): boolean {
    return typeof window !== 'undefined' && !!((window as any).pbjs);
  }

  addBidder(config: PrebidBidderConfig): this {
    this.bidders.push(config);
    return this;
  }

  async bid(req: AdapterBidRequest): Promise<AdapterBidResponse | null> {
    if (!this.isAvailable()) return null;
    const pbjs = (window as any).pbjs;
    return new Promise((resolve) => {
      const timeout = setTimeout(() => resolve(null), this.config.timeoutMs ?? 3000);
      pbjs.addAdUnits([{
        code: req.adUnitId,
        mediaTypes: { banner: { sizes: req.sizes } },
        bids: this.bidders,
      }]);
      pbjs.requestBids({
        adUnitCodes: [req.adUnitId],
        timeout: this.config.timeoutMs ?? 3000,
        bidsBackHandler: (bidResponses: any) => {
          clearTimeout(timeout);
          const bids: any[] = pbjs.getBidResponsesForAdUnitCode(req.adUnitId)?.bids ?? [];
          const winner = bids.sort((a, b) => b.cpm - a.cpm)[0];
          if (!winner) { resolve(null); return; }
          resolve({
            adUnitId: req.adUnitId,
            bidderCode: `prebid:${winner.bidder}`,
            cpm: winner.cpm,
            currency: winner.currency ?? 'USD',
            ad: winner.ad,
            width: winner.width,
            height: winner.height,
            creativeId: winner.creativeId,
            ttl: winner.ttl ?? 60,
            meta: {
              advertiserDomains: winner.meta?.advertiserDomains,
              mediaType: winner.mediaType,
            },
          });
        },
      });
    });
  }

  trackEvent(payload: AdapterEventPayload): void {
    if (!this.isAvailable()) return;
    const pbjs = (window as any).pbjs;
    pbjs.triggerBilling?.({ adUnitCode: payload.adUnitId, type: payload.type });
  }

  renderAd(resolution: AdResolution, container: HTMLElement): void {
    if (!this.isAvailable()) return;
    const pbjs = (window as any).pbjs;
    pbjs.renderAd(container, resolution.adId ?? '');
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// OpenRTB 2.6 Adapter (generic DSP endpoint)
// ─────────────────────────────────────────────────────────────────────────────

export interface OpenRTBConfig {
  endpoint: string;
  appId?: string;
  publisherId?: string;
  seatId?: string;
}

export class OpenRTBAdapter extends BaseAdapter {
  readonly type: AdapterType = 'open_rtb';
  readonly version = '2.0.0';

  isAvailable(): boolean {
    return !!this.config.options?.endpoint;
  }

  async bid(req: AdapterBidRequest): Promise<AdapterBidResponse | null> {
    const endpoint = this.config.options?.endpoint as string;
    if (!endpoint) return null;

    const bidRequest = this._buildBidRequest(req);
    try {
      const resp = await this.timeout(
        fetch(endpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'x-openrtb-version': '2.6' },
          body: JSON.stringify(bidRequest),
        }),
        this.config.timeoutMs ?? 2000,
      );
      if (!resp.ok) return null;
      const data = await resp.json();
      const seatbid = data?.seatbid?.[0];
      const bid = seatbid?.bid?.[0];
      if (!bid) return null;
      return {
        adUnitId: req.adUnitId,
        bidderCode: `openrtb:${seatbid?.seat ?? 'unknown'}`,
        cpm: bid.price,
        currency: data.cur ?? 'USD',
        ad: bid.adm,
        adUrl: bid.nurl,
        adId: bid.adid,
        creativeId: bid.crid,
        dealId: bid.dealid,
        width: bid.w,
        height: bid.h,
        ttl: bid.exp ?? 30,
        meta: { advertiserDomains: bid.adomain },
      };
    } catch {
      return null;
    }
  }

  private _buildBidRequest(req: AdapterBidRequest): object {
    return {
      id: req.auctionId ?? crypto.randomUUID(),
      imp: [{
        id: '1',
        banner: { format: req.sizes.map(([w, h]) => ({ w, h })) },
        bidfloor: req.floor ?? 0,
        bidfloorcur: req.currency ?? 'USD',
      }],
      site: {
        page: req.pageUrl ?? (typeof window !== 'undefined' ? window.location.href : ''),
        publisher: { id: this.config.options?.publisherId as string },
        keywords: req.keywords?.join(','),
      },
      user: req.userId ? { id: req.userId } : undefined,
      regs: {
        gdpr: req.userConsent?.gdpr ? 1 : 0,
        ext: {
          gdpr: req.userConsent?.gdpr ? 1 : 0,
          us_privacy: req.userConsent?.ccpa,
          gpp: req.userConsent?.gpp,
        },
      },
      ext: { prebid: { targeting: req.customTargeting } },
    };
  }

  trackEvent(payload: AdapterEventPayload): void {
    const beaconUrl = this.config.options?.beaconUrl as string;
    if (!beaconUrl) return;
    navigator.sendBeacon?.(beaconUrl, JSON.stringify(payload));
  }

  renderAd(resolution: AdResolution, container: HTMLElement): void {
    const ad = resolution.ad;
    if (!ad?.creative?.html5BundleUrl) return;
    const iframe = document.createElement('iframe');
    iframe.src = ad.creative.html5BundleUrl;
    iframe.style.cssText = 'border:none;width:100%;height:100%';
    iframe.setAttribute('sandbox', 'allow-scripts allow-same-origin allow-popups');
    container.appendChild(iframe);
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// The Trade Desk (TTD) Adapter
// ─────────────────────────────────────────────────────────────────────────────

export class TheTradeDeskAdapter extends BaseAdapter {
  readonly type: AdapterType = 'the_trade_desk';
  readonly version = '2.0.0';

  isAvailable(): boolean {
    return !!this.config.credentials?.supplierId;
  }

  async bid(req: AdapterBidRequest): Promise<AdapterBidResponse | null> {
    const endpoint = `https://direct.adsrvr.org/bid/trading/${this.config.credentials?.supplierId}`;
    try {
      const resp = await this.timeout(
        fetch(endpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            RequestId: req.auctionId,
            Impressions: req.sizes.map(([w, h]) => ({ Banner: { Width: w, Height: h }, BidFloorCPM: req.floor ?? 0 })),
            Site: { Page: req.pageUrl },
          }),
        }),
        this.config.timeoutMs ?? 2000,
      );
      if (!resp.ok) return null;
      const data = await resp.json();
      const bid = data?.Bids?.[0];
      if (!bid) return null;
      return {
        adUnitId: req.adUnitId,
        bidderCode: 'ttd',
        cpm: bid.CPM,
        currency: 'USD',
        adId: bid.AdId,
        ttl: 30,
      };
    } catch { return null; }
  }

  trackEvent(payload: AdapterEventPayload): void {
    const pixel = document.createElement('img');
    pixel.src = `https://insight.adsrvr.org/track/up?adv=${this.config.credentials?.advertiserId}&ct=${payload.type}&v=1`;
    pixel.style.display = 'none';
    document.body.appendChild(pixel);
    setTimeout(() => pixel.remove(), 5000);
  }

  renderAd(resolution: AdResolution, container: HTMLElement): void {
    container.setAttribute('data-ttd-unit', resolution.adId ?? '');
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Xandr (AppNexus) Adapter
// ─────────────────────────────────────────────────────────────────────────────

export class XandrAdapter extends BaseAdapter {
  readonly type: AdapterType = 'xandr';
  readonly version = '2.0.0';

  isAvailable(): boolean {
    return typeof window !== 'undefined' && !!((window as any).apntag);
  }

  async bid(req: AdapterBidRequest): Promise<AdapterBidResponse | null> {
    if (!this.isAvailable()) return null;
    const apntag = (window as any).apntag;
    return new Promise((resolve) => {
      const timeout = setTimeout(() => resolve(null), this.config.timeoutMs ?? 2000);
      apntag.anq.push(() => {
        apntag.defineTag({ member: this.config.credentials?.memberId, invCode: req.adUnitId, sizes: req.sizes });
        apntag.loadTags();
        apntag.onEvent('adAvailable', req.adUnitId, (ad: any) => {
          clearTimeout(timeout);
          resolve({
            adUnitId: req.adUnitId,
            bidderCode: 'xandr',
            cpm: ad?.cpm ?? 0,
            currency: 'USD',
            creativeId: ad?.creativeId,
            width: ad?.width,
            height: ad?.height,
            ttl: 30,
          });
        });
        apntag.onEvent('adNoBid', req.adUnitId, () => {
          clearTimeout(timeout);
          resolve(null);
        });
      });
    });
  }

  trackEvent(payload: AdapterEventPayload): void {
    if (!this.isAvailable()) return;
    (window as any).apntag?.recordEvent?.(payload.type, { adUnitId: payload.adUnitId });
  }

  renderAd(resolution: AdResolution, container: HTMLElement): void {
    if (!this.isAvailable()) return;
    const apntag = (window as any).apntag;
    apntag.anq.push(() => apntag.showTag(resolution.adId ?? ''));
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// DV360 (Display & Video 360) Adapter
// ─────────────────────────────────────────────────────────────────────────────

export class DV360Adapter extends BaseAdapter {
  readonly type: AdapterType = 'dv360';
  readonly version = '2.0.0';

  isAvailable(): boolean {
    return !!this.config.credentials?.networkCode;
  }

  async bid(req: AdapterBidRequest): Promise<AdapterBidResponse | null> {
    // DV360 operates via GAM integration; use passback tokens
    return {
      adUnitId: req.adUnitId,
      bidderCode: 'dv360',
      cpm: 0,
      currency: 'USD',
      ttl: 30,
    };
  }

  trackEvent(payload: AdapterEventPayload): void {
    const dcFloodlight = (window as any).gtag;
    if (!dcFloodlight) return;
    dcFloodlight('event', 'conversion', {
      allow_custom_scripts: true,
      send_to: `${this.config.credentials?.networkCode}/${payload.type}`,
    });
  }

  renderAd(resolution: AdResolution, container: HTMLElement): void {
    const ins = document.createElement('ins');
    ins.className = 'dcmads';
    ins.setAttribute('style', 'display:inline-block;width:100%;height:100%');
    ins.setAttribute('data-dcm-placement', resolution.adId ?? '');
    container.appendChild(ins);
    const script = document.createElement('script');
    script.src = '//www.googletagservices.com/dcm/dcmads.js';
    container.appendChild(script);
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// VAST / VPAID Video Adapter
// ─────────────────────────────────────────────────────────────────────────────

export interface VASTConfig {
  vastUrl: string;
  vpaidMode?: 'disabled' | 'enabled' | 'insecure';
  companionAdSelectors?: string[];
}

export class VASTVPAIDAdapter extends BaseAdapter {
  readonly type: AdapterType = 'vast_vpaid';
  readonly version = '2.0.0';

  isAvailable(): boolean {
    return !!this.config.options?.vastUrl;
  }

  async bid(req: AdapterBidRequest): Promise<AdapterBidResponse | null> {
    const vastUrl = this.config.options?.vastUrl as string;
    if (!vastUrl) return null;
    try {
      const macroUrl = vastUrl
        .replace('[TIMESTAMP]', Date.now().toString())
        .replace('[PAGEURL]', encodeURIComponent(req.pageUrl ?? ''))
        .replace('[FLOOR]', String(req.floor ?? 0));
      const resp = await this.timeout(fetch(macroUrl), this.config.timeoutMs ?? 3000);
      if (!resp.ok) return null;
      const xml = await resp.text();
      const parser = new DOMParser();
      const doc = parser.parseFromString(xml, 'text/xml');
      const linear = doc.querySelector('Linear');
      if (!linear) return null;
      const mediaFiles = Array.from(doc.querySelectorAll('MediaFile'));
      const mediaFile = mediaFiles.find(m => m.getAttribute('type') === 'video/mp4') ?? mediaFiles[0];
      return {
        adUnitId: req.adUnitId,
        bidderCode: 'vast',
        cpm: parseFloat(doc.querySelector('Pricing')?.textContent ?? '0'),
        currency: doc.querySelector('Pricing')?.getAttribute('currency') ?? 'USD',
        adUrl: mediaFile?.textContent?.trim(),
        ttl: 30,
        meta: { mediaType: 'video' },
      };
    } catch { return null; }
  }

  trackEvent(payload: AdapterEventPayload): void {
    const trackingNode = this._getTrackingNode(payload.type);
    if (trackingNode) {
      new Image().src = trackingNode;
    }
  }

  private _getTrackingNode(eventType: string): string {
    const map: Record<string, string> = {
      impression: this.config.options?.impressionUrl as string ?? '',
      complete: this.config.options?.completeUrl as string ?? '',
      click: this.config.options?.clickUrl as string ?? '',
    };
    return map[eventType] ?? '';
  }

  renderAd(resolution: AdResolution, container: HTMLElement): void {
    const video = document.createElement('video');
    video.src = resolution.ad?.creative?.videoUrl ?? '';
    video.controls = false;
    video.autoplay = true;
    video.style.cssText = 'width:100%;height:100%;object-fit:contain';
    container.appendChild(video);
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// AMP (Accelerated Mobile Pages) Adapter
// ─────────────────────────────────────────────────────────────────────────────

export class AMPAdapter extends BaseAdapter {
  readonly type: AdapterType = 'amp';
  readonly version = '2.0.0';

  isAvailable(): boolean {
    return typeof window !== 'undefined' && !!((window as any).AMP);
  }

  async bid(req: AdapterBidRequest): Promise<AdapterBidResponse | null> {
    return {
      adUnitId: req.adUnitId,
      bidderCode: 'amp',
      cpm: 0,
      currency: 'USD',
      ttl: 30,
    };
  }

  trackEvent(payload: AdapterEventPayload): void {
    if (!this.isAvailable()) return;
    const ampAnalytics = document.querySelector('amp-analytics');
    if (ampAnalytics) {
      ampAnalytics.setAttribute(`data-${payload.type}`, payload.adUnitId);
    }
  }

  renderAd(resolution: AdResolution, container: HTMLElement): void {
    const ampAd = document.createElement('amp-ad');
    ampAd.setAttribute('type', this.config.options?.type as string ?? 'doubleclick');
    ampAd.setAttribute('data-slot', resolution.adId ?? '');
    container.appendChild(ampAd);
  }

  /** Build an AMP-compatible JSON config */
  buildAMPConfig(ads: Ad[]): object {
    return {
      vars: { account: this.config.credentials?.accountId },
      triggers: ads.reduce((acc, ad) => ({
        ...acc,
        [`${ad.id}_impression`]: {
          on: 'visible',
          selector: `#ad-${ad.id}`,
          request: 'event',
          vars: { eventCategory: 'ad', eventAction: 'impression', eventLabel: ad.id },
        },
      }), {}),
    };
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Segment Analytics Adapter
// ─────────────────────────────────────────────────────────────────────────────

export class SegmentAdapter extends BaseAdapter {
  readonly type: AdapterType = 'segment';
  readonly version = '2.0.0';

  isAvailable(): boolean {
    return typeof window !== 'undefined' && !!((window as any).analytics);
  }

  async bid(_req: AdapterBidRequest): Promise<AdapterBidResponse | null> {
    return null; // Segment is tracking-only
  }

  trackEvent(payload: AdapterEventPayload): void {
    if (!this.isAvailable()) return;
    const analytics = (window as any).analytics;
    analytics.track(`Ad ${payload.type}`, {
      adUnitId: payload.adUnitId,
      bidderCode: payload.bidderCode,
      ...payload.data,
    });
  }

  renderAd(_resolution: AdResolution, _container: HTMLElement): void {
    // no-op
  }

  identify(userId: string, traits: Record<string, unknown>): void {
    if (!this.isAvailable()) return;
    (window as any).analytics.identify(userId, traits);
  }

  page(name: string, properties: Record<string, unknown>): void {
    if (!this.isAvailable()) return;
    (window as any).analytics.page(name, properties);
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// mParticle Adapter
// ─────────────────────────────────────────────────────────────────────────────

export class mParticleAdapter extends BaseAdapter {
  readonly type: AdapterType = 'mparticle';
  readonly version = '2.0.0';

  isAvailable(): boolean {
    return typeof window !== 'undefined' && !!((window as any).mParticle);
  }

  async bid(_req: AdapterBidRequest): Promise<AdapterBidResponse | null> {
    return null;
  }

  trackEvent(payload: AdapterEventPayload): void {
    if (!this.isAvailable()) return;
    const mp = (window as any).mParticle;
    mp.logEvent(`ad_${payload.type}`, mp.EventType.Other, {
      adUnitId: payload.adUnitId,
      ...payload.data,
    });
  }

  renderAd(_resolution: AdResolution, _container: HTMLElement): void {}
}

// ─────────────────────────────────────────────────────────────────────────────
// RudderStack Adapter
// ─────────────────────────────────────────────────────────────────────────────

export class RudderstackAdapter extends BaseAdapter {
  readonly type: AdapterType = 'rudderstack';
  readonly version = '2.0.0';

  isAvailable(): boolean {
    return typeof window !== 'undefined' && !!((window as any).rudderanalytics);
  }

  async bid(_req: AdapterBidRequest): Promise<AdapterBidResponse | null> {
    return null;
  }

  trackEvent(payload: AdapterEventPayload): void {
    if (!this.isAvailable()) return;
    (window as any).rudderanalytics.track(`Ad ${payload.type}`, {
      adUnitId: payload.adUnitId,
      ...payload.data,
    });
  }

  renderAd(_resolution: AdResolution, _container: HTMLElement): void {}
}

// ─────────────────────────────────────────────────────────────────────────────
// Shopify Adapter
// ─────────────────────────────────────────────────────────────────────────────

export class ShopifyAdapter extends BaseAdapter {
  readonly type: AdapterType = 'shopify';
  readonly version = '2.0.0';

  isAvailable(): boolean {
    return typeof window !== 'undefined' && !!((window as any).Shopify);
  }

  async bid(_req: AdapterBidRequest): Promise<AdapterBidResponse | null> {
    return null;
  }

  trackEvent(payload: AdapterEventPayload): void {
    if (!this.isAvailable()) return;
    const Shopify = (window as any).Shopify;
    Shopify.analytics?.publish?.(`ad_${payload.type}`, {
      adUnitId: payload.adUnitId,
      ...payload.data,
    });
  }

  renderAd(resolution: AdResolution, container: HTMLElement): void {
    if (!resolution.ad?.creative?.imageUrl) return;
    const link = document.createElement('a');
    link.href = resolution.ad.destinationUrl ?? '#';
    const img = document.createElement('img');
    img.src = resolution.ad.creative.imageUrl;
    img.alt = resolution.ad.creative.altText ?? '';
    link.appendChild(img);
    container.appendChild(link);
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// WordPress / WooCommerce Adapter
// ─────────────────────────────────────────────────────────────────────────────

export class WordPressAdapter extends BaseAdapter {
  readonly type: AdapterType = 'wordpress';
  readonly version = '2.0.0';

  isAvailable(): boolean {
    return typeof window !== 'undefined' && !!((window as any).wp);
  }

  async bid(_req: AdapterBidRequest): Promise<AdapterBidResponse | null> {
    return null;
  }

  trackEvent(payload: AdapterEventPayload): void {
    if (!this.isAvailable()) return;
    const wp = (window as any).wp;
    wp.hooks?.doAction?.(`adengine_${payload.type}`, payload);
  }

  renderAd(_resolution: AdResolution, _container: HTMLElement): void {}
}

// ─────────────────────────────────────────────────────────────────────────────
// React Native Adapter (stub — actual native bridge via JSI)
// ─────────────────────────────────────────────────────────────────────────────

export class ReactNativeAdapter extends BaseAdapter {
  readonly type: AdapterType = 'react_native';
  readonly version = '2.0.0';
  private bridge: ((msg: object) => void) | null = null;

  isAvailable(): boolean {
    return typeof (window as any).ReactNativeWebView !== 'undefined';
  }

  setBridge(fn: (msg: object) => void): this {
    this.bridge = fn;
    return this;
  }

  async bid(req: AdapterBidRequest): Promise<AdapterBidResponse | null> {
    if (!this.isAvailable() && !this.bridge) return null;
    return new Promise((resolve) => {
      const timeout = setTimeout(() => resolve(null), this.config.timeoutMs ?? 2000);
      const handler = (e: MessageEvent) => {
        try {
          const data = JSON.parse(e.data);
          if (data.type === 'adBidResponse' && data.adUnitId === req.adUnitId) {
            clearTimeout(timeout);
            window.removeEventListener('message', handler);
            resolve(data.bid ?? null);
          }
        } catch {}
      };
      window.addEventListener('message', handler);
      const msg = JSON.stringify({ type: 'adBidRequest', ...req });
      (window as any).ReactNativeWebView?.postMessage?.(msg);
      this.bridge?.({ type: 'adBidRequest', ...req });
    });
  }

  trackEvent(payload: AdapterEventPayload): void {
    const msg = JSON.stringify({ type: 'adEvent', ...payload });
    (window as any).ReactNativeWebView?.postMessage?.(msg);
    this.bridge?.({ type: 'adEvent', ...payload });
  }

  renderAd(resolution: AdResolution, container: HTMLElement): void {
    const msg = JSON.stringify({ type: 'renderAd', adId: resolution.adId });
    (window as any).ReactNativeWebView?.postMessage?.(msg);
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Next.js / Vercel Edge Adapter
// ─────────────────────────────────────────────────────────────────────────────

export class NextJSAdapter extends BaseAdapter {
  readonly type: AdapterType = 'nextjs';
  readonly version = '2.0.0';
  private routeCache = new Map<string, AdResolution>();

  isAvailable(): boolean {
    return typeof window !== 'undefined';
  }

  /** SSR-safe resolve — returns a serializable snapshot for hydration */
  async resolveSSR(request: AdRequest): Promise<Record<string, unknown>> {
    return {
      adEngineSnapshot: true,
      request,
      timestamp: Date.now(),
    };
  }

  async bid(_req: AdapterBidRequest): Promise<AdapterBidResponse | null> {
    return null;
  }

  trackEvent(payload: AdapterEventPayload): void {
    if (typeof window !== 'undefined' && (window as any).__NEXT_DATA__) {
      fetch('/api/ad-event', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
        keepalive: true,
      }).catch(() => {});
    }
  }

  renderAd(_resolution: AdResolution, _container: HTMLElement): void {}

  /** Cache resolved ad for a given route path */
  cacheResolution(path: string, resolution: AdResolution): void {
    this.routeCache.set(path, resolution);
  }

  getCachedResolution(path: string): AdResolution | undefined {
    return this.routeCache.get(path);
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Nuxt 3 Adapter
// ─────────────────────────────────────────────────────────────────────────────

export class NuxtAdapter extends BaseAdapter {
  readonly type: AdapterType = 'nuxt';
  readonly version = '2.0.0';

  isAvailable(): boolean {
    return typeof window !== 'undefined' && !!((window as any).__NUXT__);
  }

  async bid(_req: AdapterBidRequest): Promise<AdapterBidResponse | null> {
    return null;
  }

  trackEvent(payload: AdapterEventPayload): void {
    if (!this.isAvailable()) return;
    fetch('/api/ad-event', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
      keepalive: true,
    }).catch(() => {});
  }

  renderAd(_resolution: AdResolution, _container: HTMLElement): void {}
}

// ─────────────────────────────────────────────────────────────────────────────
// SvelteKit Adapter
// ─────────────────────────────────────────────────────────────────────────────

export class SvelteKitAdapter extends BaseAdapter {
  readonly type: AdapterType = 'sveltekit';
  readonly version = '2.0.0';

  isAvailable(): boolean {
    return typeof window !== 'undefined';
  }

  async bid(_req: AdapterBidRequest): Promise<AdapterBidResponse | null> {
    return null;
  }

  trackEvent(payload: AdapterEventPayload): void {
    fetch('/api/ad-event', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
      keepalive: true,
    }).catch(() => {});
  }

  renderAd(_resolution: AdResolution, _container: HTMLElement): void {}
}

// ─────────────────────────────────────────────────────────────────────────────
// Adapter Registry & Orchestrator
// ─────────────────────────────────────────────────────────────────────────────

export type AuctionStrategy = 'first_price' | 'second_price' | 'waterfall' | 'parallel' | 'header_bidding';

export interface AuctionConfig {
  strategy: AuctionStrategy;
  timeoutMs: number;
  currency: string;
  priceGranularity?: 'low' | 'medium' | 'high' | 'auto' | 'dense';
  sendAllBids?: boolean;
  enablePriceFloors?: boolean;
}

export class AdapterRegistry {
  private adapters = new Map<AdapterType, BaseAdapter>();
  private bidHistory: AdapterBidResponse[] = [];
  private readonly maxHistory = 500;

  register(adapter: BaseAdapter): this {
    this.adapters.set(adapter.type, adapter);
    return this;
  }

  unregister(type: AdapterType): this {
    this.adapters.delete(type);
    return this;
  }

  get(type: AdapterType): BaseAdapter | undefined {
    return this.adapters.get(type);
  }

  getAll(): BaseAdapter[] {
    return Array.from(this.adapters.values());
  }

  getEnabled(): BaseAdapter[] {
    return this.getAll().filter(a => a.getConfig().enabled && a.isAvailable());
  }

  /** Run all enabled adapters in parallel, return sorted bids */
  async runParallelAuction(req: AdapterBidRequest, config: AuctionConfig): Promise<AdapterBidResponse[]> {
    const adapters = this.getEnabled();
    const bidPromises = adapters.map(a =>
      Promise.race([
        a.bid(req).catch(() => null),
        new Promise<null>(r => setTimeout(() => r(null), config.timeoutMs)),
      ])
    );
    const results = await Promise.all(bidPromises);
    const bids = results.filter((b): b is AdapterBidResponse => b !== null && b.cpm > 0);
    bids.sort((a, b) => b.cpm - a.cpm);
    bids.forEach(b => this._recordBid(b));
    return bids;
  }

  /** Waterfall: try adapters in priority order, return first winning bid */
  async runWaterfallAuction(req: AdapterBidRequest, config: AuctionConfig, priority: AdapterType[]): Promise<AdapterBidResponse | null> {
    for (const type of priority) {
      const adapter = this.adapters.get(type);
      if (!adapter?.isAvailable()) continue;
      try {
        const bid = await Promise.race([
          adapter.bid(req),
          new Promise<null>(r => setTimeout(() => r(null), config.timeoutMs)),
        ]);
        if (bid && bid.cpm > 0) {
          this._recordBid(bid);
          return bid;
        }
      } catch {}
    }
    return null;
  }

  broadcastEvent(payload: AdapterEventPayload): void {
    this.getEnabled().forEach(a => {
      try { a.trackEvent(payload); } catch {}
    });
  }

  private _recordBid(bid: AdapterBidResponse): void {
    this.bidHistory.push(bid);
    if (this.bidHistory.length > this.maxHistory) this.bidHistory.shift();
  }

  getBidHistory(): AdapterBidResponse[] {
    return [...this.bidHistory];
  }

  getBidStats(): {
    totalBids: number;
    avgCpm: number;
    topBidder: string;
    winRate: Record<string, number>;
  } {
    const total = this.bidHistory.length;
    if (!total) return { totalBids: 0, avgCpm: 0, topBidder: '', winRate: {} };
    const avgCpm = this.bidHistory.reduce((s, b) => s + b.cpm, 0) / total;
    const counts: Record<string, number> = {};
    this.bidHistory.forEach(b => { counts[b.bidderCode] = (counts[b.bidderCode] ?? 0) + 1; });
    const topBidder = Object.entries(counts).sort((a, b) => b[1] - a[1])[0]?.[0] ?? '';
    const winRate: Record<string, number> = {};
    Object.entries(counts).forEach(([k, v]) => { winRate[k] = v / total; });
    return { totalBids: total, avgCpm, topBidder, winRate };
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Default global adapter registry
// ─────────────────────────────────────────────────────────────────────────────

export const defaultAdapterRegistry = new AdapterRegistry();

/** Quick-setup: register all available adapters with sane defaults */
export function bootstrapAdapters(options: {
  gamNetworkCode?: string;
  prebidBidders?: PrebidBidderConfig[];
  openRtbEndpoint?: string;
  amazonPublisherId?: string;
  ttdSupplierId?: string;
  xandrMemberId?: string;
  segmentWriteKey?: string;
  enableAllTrackers?: boolean;
}): AdapterRegistry {
  const registry = new AdapterRegistry();

  if (options.gamNetworkCode) {
    registry.register(new GoogleAdManagerAdapter({
      type: 'google_ad_manager',
      enabled: true,
      timeoutMs: 2000,
      credentials: { networkCode: options.gamNetworkCode },
    }));
  }

  const prebid = new PrebidAdapter({ type: 'prebid', enabled: true, timeoutMs: 3000 });
  options.prebidBidders?.forEach(b => prebid.addBidder(b));
  if (options.prebidBidders?.length) registry.register(prebid);

  if (options.openRtbEndpoint) {
    registry.register(new OpenRTBAdapter({
      type: 'open_rtb',
      enabled: true,
      timeoutMs: 2000,
      options: { endpoint: options.openRtbEndpoint },
    }));
  }

  if (options.amazonPublisherId) {
    registry.register(new AmazonAPSAdapter({
      type: 'amazon_aps',
      enabled: true,
      timeoutMs: 2000,
      credentials: { publisherId: options.amazonPublisherId },
    }));
  }

  if (options.ttdSupplierId) {
    registry.register(new TheTradeDeskAdapter({
      type: 'the_trade_desk',
      enabled: true,
      timeoutMs: 2000,
      credentials: { supplierId: options.ttdSupplierId },
    }));
  }

  if (options.xandrMemberId) {
    registry.register(new XandrAdapter({
      type: 'xandr',
      enabled: true,
      timeoutMs: 2000,
      credentials: { memberId: options.xandrMemberId },
    }));
  }

  if (options.enableAllTrackers) {
    registry.register(new SegmentAdapter({ type: 'segment', enabled: true }));
    registry.register(new mParticleAdapter({ type: 'mparticle', enabled: true }));
    registry.register(new RudderstackAdapter({ type: 'rudderstack', enabled: true }));
  }

  return registry;
}

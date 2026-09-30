/**
 * Ad Engine v2 — Registry
 * Central ad registry, sample ad library, and registry management utilities.
 * FIXED: placements/layouts arrays, budget (not pricing), PricingModel UPPERCASE,
 *        FrequencyProfile objects, ISO createdAt, correct AdType values,
 *        correct creative field names, correct AdExperiment shape.
 */

import type { Ad, AdStatus, AdPlacement, AdLayout, PricingModel } from './types';

// ─────────────────────────────────────────────────────────────────────────────
// Registry
// ─────────────────────────────────────────────────────────────────────────────

export class AdRegistry {
  private ads: Map<string, Ad> = new Map();
  private listeners: Array<(ads: Ad[]) => void> = [];

  register(ad: Ad): this {
    this.ads.set(ad.id, ad);
    this._notify();
    return this;
  }

  registerMany(ads: Ad[]): this {
    ads.forEach(a => this.ads.set(a.id, a));
    this._notify();
    return this;
  }

  get(id: string): Ad | undefined {
    return this.ads.get(id);
  }

  getAll(): Ad[] {
    return Array.from(this.ads.values());
  }

  // FIX: was a.placement === placement (singular) — Ad has placements[] array
  getByPlacement(placement: AdPlacement): Ad[] {
    return this.getAll().filter(a => a.placements.includes(placement) && a.status !== 'archived');
  }

  // FIX: was a.layout === layout (singular) — Ad has layouts[] array
  getByLayout(layout: AdLayout): Ad[] {
    return this.getAll().filter(a => a.layouts.includes(layout));
  }

  getActive(): Ad[] {
    return this.getAll().filter(a => a.status === 'active');
  }

  update(id: string, patch: Partial<Ad>): this {
    const existing = this.ads.get(id);
    if (existing) {
      this.ads.set(id, { ...existing, ...patch, id });
      this._notify();
    }
    return this;
  }

  remove(id: string): this {
    this.ads.delete(id);
    this._notify();
    return this;
  }

  setStatus(id: string, status: AdStatus): this {
    return this.update(id, { status });
  }

  count(): number { return this.ads.size; }

  onChange(cb: (ads: Ad[]) => void): () => void {
    this.listeners.push(cb);
    return () => { this.listeners = this.listeners.filter(l => l !== cb); };
  }

  private _notify(): void {
    const all = this.getAll();
    this.listeners.forEach(l => l(all));
  }

  clear(): void {
    this.ads.clear();
    this._notify();
  }

  /** Export registry as JSON */
  toJSON(): string {
    return JSON.stringify(this.getAll(), null, 2);
  }

  /** Import registry from JSON */
  fromJSON(json: string): this {
    const ads: Ad[] = JSON.parse(json);
    this.registerMany(ads);
    return this;
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Sample Ad Library  (fully corrected)
// ─────────────────────────────────────────────────────────────────────────────

export const sampleAds: Ad[] = [
  // ── Hero / Full-Page ──────────────────────────────────────────────────────
  {
    id: 'ad_hero_001',
    // FIX: was placement:'homepage_hero', layout:'hero_full'
    placements: ['homepage_hero'],
    layouts: ['hero'],
    status: 'active',
    type: 'display',
    title: 'Discover the Future',
    description: 'Experience next-generation products tailored for you.',
    ctaText: 'Shop Now',
    ctaUrl: 'https://example.com/shop',
    sponsorName: 'ExampleBrand',
    priority: 9,
    weight: 80,
    // FIX: was pricing:{model:'cpm',...} → budget:{model:'CPM',...}
    budget: { model: 'CPM', bidFloor: 400, bidCeiling: 3000, totalBudget: 1000000, dailyCap: 50000 },
    schedule: {
      startDate: "2099-01-01T00:00:00.000Z",
      endDate: "2099-01-01T00:00:00.000Z",
      timezone: 'America/New_York',
      paceEvenly: true,
    },
    // FIX: was {hourly:2, daily:5} → {hourly:{maxImpressions:2,windowSeconds:3600},...}
    frequencyProfile: {
      hourly:   { maxImpressions: 2,   windowSeconds: 3600 },
      daily:    { maxImpressions: 5,   windowSeconds: 86400 },
      weekly:   { maxImpressions: 20,  windowSeconds: 604800 },
      lifetime: { maxImpressions: 100, windowSeconds: 999999999 },
    },
    creative: {
      imageUrl:      'https://example.com/creatives/hero_001.jpg',
      // FIX: was imageHiDpi → imageUrlHiDpi
      imageUrlHiDpi: 'https://example.com/creatives/hero_001@2x.jpg',
      // FIX: was imageWebP → imageUrlWebp
      imageUrlWebp:  'https://example.com/creatives/hero_001.webp',
      // FIX: was altText → alt
      alt:           'Hero brand banner for ExampleBrand',
      aspectRatio:   '16:9',
    },
    targeting: {
      geo: { countries: ['US', 'CA', 'GB'], excludeCountries: [] },
      tech: { devices: ['desktop', 'tablet'] },
    },
    tags: ['brand', 'awareness', 'hero'],
    // FIX: was now (number) → ISO string
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },

  // ── Sidebar Display ───────────────────────────────────────────────────────
  {
    id: 'ad_sidebar_001',
    // FIX: was placement:'article_sidebar' (invalid), layout:'sidebar_sticky' (invalid)
    placements: ['sidebar_top'],
    layouts: ['half_page'],
    status: 'active',
    type: 'display',
    title: 'Special Offer Inside',
    description: 'Limited-time deals for our loyal readers.',
    ctaText: 'Learn More',
    ctaUrl: 'https://example.com/offers',
    sponsorName: 'SidebarCo',
    priority: 6,
    weight: 60,
    // FIX: was pricing:{model:'vcpm',...} → budget:{model:'vCPM',...}
    budget: { model: 'vCPM', bidFloor: 250, bidCeiling: 1500, dailyCap: 20000 },
    schedule: {
      startDate: new Date().toISOString(),
      endDate: "2099-01-01T00:00:00.000Z",
      timezone: 'UTC',
      paceEvenly: true,
    },
    frequencyProfile: {
      daily:  { maxImpressions: 8,  windowSeconds: 86400 },
      weekly: { maxImpressions: 30, windowSeconds: 604800 },
    },
    creative: {
      imageUrl:     'https://example.com/creatives/sidebar_001.jpg',
      imageUrlWebp: 'https://example.com/creatives/sidebar_001.webp',
      alt:          'Sidebar Advertisement',
    },
    targeting: {
      geo:  { countries: ['US'] },
      tech: { devices: ['desktop'] },
    },
    tags: ['sidebar', 'sticky'],
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },

  // ── In-Feed Native ────────────────────────────────────────────────────────
  {
    id: 'ad_native_feed_001',
    // FIX: was placement:'feed_inline' (invalid), layout:'native_card' (invalid)
    placements: ['feed_item'],
    layouts: ['native_feed'],
    status: 'active',
    type: 'native',
    title: "You Won't Believe These Results",
    description: 'Thousands of users are achieving their goals with this simple approach.',
    ctaText: 'Read More',
    ctaUrl: 'https://example.com/article',
    sponsorName: 'ContentPartner',
    priority: 7,
    weight: 70,
    // FIX: was pricing:{model:'cpc',...}
    budget: { model: 'CPC', bidFloor: 30, bidCeiling: 500, dailyCap: 30000 },
    schedule: {
      startDate: new Date().toISOString(),
      timezone: 'UTC',
      paceEvenly: true,
    },
    frequencyProfile: {
      daily:  { maxImpressions: 3,  windowSeconds: 86400 },
      weekly: { maxImpressions: 12, windowSeconds: 604800 },
    },
    creative: {
      imageUrl:     'https://example.com/creatives/native_001.jpg',
      imageUrlWebp: 'https://example.com/creatives/native_001.webp',
      alt:          'Sponsored Content',
    },
    targeting: {
      geo:  { countries: ['US', 'CA', 'AU'] },
      tech: { devices: ['mobile', 'tablet', 'desktop'] },
      audience: { contextualCategories: ['lifestyle', 'technology'] },
    },
    tags: ['native', 'feed', 'content'],
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },

  // ── Video Pre-Roll ────────────────────────────────────────────────────────
  {
    id: 'ad_video_preroll_001',
    // FIX: was placement:'video_preroll', layout:'video_preroll'
    placements: ['pre_roll'],
    layouts: ['video_pre_roll'],
    status: 'active',
    type: 'video',
    title: 'Premium Video Ad',
    description: 'Watch this 30-second message from our sponsor.',
    ctaText: 'Learn More',
    ctaUrl: 'https://example.com/video-landing',
    sponsorName: 'VideoCo',
    priority: 8,
    weight: 75,
    // FIX: was pricing:{model:'cpcv',...}
    budget: { model: 'CPCV', bidFloor: 500, bidCeiling: 4000, dailyCap: 80000, completionMinPct: 0.75 },
    schedule: {
      startDate: new Date().toISOString(),
      endDate: "2099-01-01T00:00:00.000Z",
      timezone: 'US/Eastern',
      paceEvenly: true,
    },
    frequencyProfile: {
      hourly: { maxImpressions: 1, windowSeconds: 3600 },
      daily:  { maxImpressions: 3, windowSeconds: 86400 },
    },
    creative: {
      videoUrl:             'https://example.com/creatives/video_001.mp4',
      // FIX: was videoHLS → videoUrlHls
      videoUrlHls:          'https://example.com/creatives/video_001.m3u8',
      // FIX: was videoPoster → videoPosterUrl
      videoPosterUrl:       'https://example.com/creatives/video_001_poster.jpg',
      // FIX: was videoDurationSec → videoDurationMs (milliseconds)
      videoDurationMs:      30000,
      videoSkippableAfterMs: 5000,
      alt:                  'Video Advertisement',
    },
    targeting: {
      geo:  { countries: ['US', 'CA'] },
      tech: { devices: ['desktop', 'tablet', 'mobile'], connectionTypes: ['wifi', '4g'] },
    },
    tags: ['video', 'preroll'],
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },

  // ── Interstitial ──────────────────────────────────────────────────────────
  {
    id: 'ad_interstitial_001',
    placements: ['interstitial'],
    layouts: ['interstitial_full'],
    status: 'active',
    type: 'display',
    title: 'Exclusive Offer — Act Now!',
    description: 'Get 50% off your first purchase. Limited time only.',
    ctaText: 'Get Started',
    ctaUrl: 'https://example.com/offer',
    sponsorName: 'OfferBrand',
    priority: 9,
    weight: 85,
    budget: { model: 'CPM', bidFloor: 600, bidCeiling: 5000, dailyCap: 40000 },
    schedule: {
      startDate: new Date().toISOString(),
      timezone: 'UTC',
      paceEvenly: true,
    },
    frequencyProfile: {
      hourly: { maxImpressions: 1, windowSeconds: 3600 },
      daily:  { maxImpressions: 2, windowSeconds: 86400 },
      weekly: { maxImpressions: 8, windowSeconds: 604800 },
    },
    creative: {
      imageUrl:     'https://example.com/creatives/interstitial_001.jpg',
      imageUrlWebp: 'https://example.com/creatives/interstitial_001.webp',
      alt:          'Full Page Advertisement',
    },
    targeting: {
      geo:      { countries: ['US', 'GB', 'CA', 'AU'] },
      tech:     { devices: ['mobile'] },
      audience: { segments: ['mobile_users', 'app_users'] },
    },
    tags: ['interstitial', 'mobile'],
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },

  // ── Carousel ──────────────────────────────────────────────────────────────
  {
    id: 'ad_carousel_001',
    // FIX: was placement:'feed_inline', layout:'carousel_cards' (invalid)
    placements: ['feed_item'],
    layouts: ['carousel_product'],
    status: 'active',
    type: 'display',
    title: 'Shop Our Top Products',
    description: 'Swipe to explore our best deals.',
    ctaText: 'Shop Now',
    ctaUrl: 'https://example.com/products',
    sponsorName: 'RetailBrand',
    priority: 7,
    weight: 65,
    // FIX: was pricing:{model:'cpc',...}
    budget: { model: 'CPC', bidFloor: 50, bidCeiling: 800, dailyCap: 35000 },
    schedule: {
      startDate: new Date().toISOString(),
      timezone: 'UTC',
    },
    frequencyProfile: {
      daily:  { maxImpressions: 5,  windowSeconds: 86400 },
      weekly: { maxImpressions: 20, windowSeconds: 604800 },
    },
    creative: {
      imageUrl: 'https://example.com/creatives/carousel_001_1.jpg',
      alt:      'Product Carousel',
      thumbnails: [
        'https://example.com/creatives/c1.jpg',
        'https://example.com/creatives/c2.jpg',
        'https://example.com/creatives/c3.jpg',
      ],
    },
    targeting: {
      geo:      { countries: ['US', 'CA'] },
      audience: { retargeting: { pixelId: 'px_001', windowDays: 30 } },
    },
    tags: ['carousel', 'ecommerce'],
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },

  // ── Sponsored Article ─────────────────────────────────────────────────────
  {
    id: 'ad_sponsored_001',
    // FIX: was placement:'article_inline' (invalid)
    placements: ['article_inline_1'],
    layouts: ['sponsored_article'],
    status: 'active',
    type: 'sponsored',
    title: 'How Our Product Changed the Game',
    description: 'A deep dive into the technology behind our breakthrough product.',
    ctaText: 'Read Full Story',
    ctaUrl: 'https://example.com/story',
    sponsorName: 'TechPartner',
    priority: 7,
    weight: 70,
    // FIX: was pricing:{model:'flat_rate',...}
    budget: { model: 'flat_rate', bidFloor: 0, totalBudget: 500000 },
    schedule: {
      startDate: new Date().toISOString(),
      endDate: "2099-01-01T00:00:00.000Z",
      timezone: 'UTC',
    },
    frequencyProfile: {
      lifetime: { maxImpressions: 3, windowSeconds: 999999999 },
    },
    creative: {
      imageUrl: 'https://example.com/creatives/sponsored_001.jpg',
      alt:      'Sponsored Article Image',
    },
    targeting: {
      geo:      { countries: ['US'] },
      audience: { contextualCategories: ['technology', 'innovation'] },
    },
    tags: ['sponsored', 'content', 'native'],
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },

  // ── App Install (Mobile) ──────────────────────────────────────────────────
  {
    id: 'ad_app_install_001',
    // FIX: was placement:'app_banner' (invalid), layout:'app_install_card' (invalid), type:'app_install' (invalid)
    placements: ['app_banner_top'],
    layouts: ['app_install_banner'],
    status: 'active',
    type: 'display',
    title: 'Download Our App Free',
    description: '4.9★ rating · 10M+ downloads',
    ctaText: 'Install Now',
    ctaUrl: 'https://apps.apple.com/app/example',
    sponsorName: 'AppCo',
    priority: 8,
    weight: 75,
    // FIX: was pricing:{model:'cpi',...}
    budget: { model: 'CPI', bidFloor: 150, bidCeiling: 1000, dailyCap: 60000 },
    schedule: {
      startDate: new Date().toISOString(),
      timezone: 'UTC',
      paceEvenly: true,
    },
    frequencyProfile: {
      daily:  { maxImpressions: 4,  windowSeconds: 86400 },
      weekly: { maxImpressions: 15, windowSeconds: 604800 },
    },
    creative: {
      imageUrl: 'https://example.com/creatives/app_install_001.jpg',
      iconUrl:  'https://example.com/creatives/app_icon.png',
      alt:      'Install Our App',
    },
    targeting: {
      geo:      { countries: ['US', 'IN', 'BR'] },
      tech:     { devices: ['mobile'], operatingSystems: ['ios', 'android'] },
      audience: { segments: ['app_prospects'] },
    },
    tags: ['app_install', 'mobile'],
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },

  // ── CTV / OTT ─────────────────────────────────────────────────────────────
  {
    id: 'ad_ctv_001',
    // FIX: was placement:'ctv_preroll' (invalid), layout:'ctv_standard' (invalid)
    placements: ['ctv_pre_roll'],
    layouts: ['ctv_display'],
    status: 'active',
    type: 'video',
    title: 'Premium CTV Spot',
    description: '30-second connected TV advertisement.',
    ctaText: 'Learn More',
    ctaUrl: 'https://example.com/ctv',
    sponsorName: 'TVBrand',
    priority: 9,
    weight: 90,
    // FIX: was pricing:{model:'cpm',...}
    budget: { model: 'CPM', bidFloor: 1500, bidCeiling: 8000, dailyCap: 150000 },
    schedule: {
      startDate: new Date().toISOString(),
      timezone: 'America/Los_Angeles',
      paceEvenly: true,
    },
    frequencyProfile: {
      daily:  { maxImpressions: 2, windowSeconds: 86400 },
      weekly: { maxImpressions: 6, windowSeconds: 604800 },
    },
    creative: {
      videoUrl:        'https://example.com/creatives/ctv_001.mp4',
      videoUrlHls:     'https://example.com/creatives/ctv_001.m3u8',
      videoPosterUrl:  'https://example.com/creatives/ctv_001_poster.jpg',
      videoDurationMs: 30000,
      alt:             'CTV Advertisement',
    },
    targeting: {
      geo:  { countries: ['US'], dmaRegions: ['501', '803'] },
      tech: { devices: ['ctv'] },
    },
    tags: ['ctv', 'video', 'premium'],
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },

  // ── DOOH (Digital Out-of-Home) ────────────────────────────────────────────
  {
    id: 'ad_dooh_001',
    // FIX: was placement:'dooh' (invalid), layout:'dooh_billboard' (invalid), type:'dooh' (invalid)
    placements: ['dooh_billboard'],
    layouts: ['dooh_static'],
    status: 'active',
    type: 'display',
    title: 'See Us Outdoors',
    description: 'Digital out-of-home advertising that moves.',
    ctaText: 'Visit Us',
    ctaUrl: 'https://example.com/visit',
    sponsorName: 'OutdoorBrand',
    priority: 8,
    weight: 80,
    // FIX: was pricing:{model:'tenancy_day',...}
    budget: { model: 'tenancy_day', bidFloor: 20000, totalBudget: 1000000 },
    schedule: {
      startDate: new Date().toISOString(),
      endDate: "2099-01-01T00:00:00.000Z",
      timezone: 'America/New_York',
      daypartingMatrix: Array.from({ length: 7 }, () =>
        Array.from({ length: 24 }, (_, h) => h >= 6 && h <= 22)
      ) as boolean[][],
    },
    creative: {
      imageUrl: 'https://example.com/creatives/dooh_001.jpg',
      alt:      'Outdoor Advertisement',
    },
    targeting: {
      geo: { cities: ['New York', 'Los Angeles', 'Chicago'] },
    },
    tags: ['dooh', 'outdoor', 'billboard'],
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },

  // ── Email ─────────────────────────────────────────────────────────────────
  {
    id: 'ad_email_001',
    // FIX: was layout:'email_banner' (invalid), type:'email' (invalid)
    placements: ['email_header'],
    layouts: ['email_masthead'],
    status: 'active',
    type: 'display',
    title: 'Exclusive Offer Just for You',
    description: 'Special savings from our email sponsor.',
    ctaText: 'Claim Now',
    ctaUrl: 'https://example.com/email-offer',
    sponsorName: 'EmailSponsor',
    priority: 5,
    weight: 50,
    // FIX: was pricing:{model:'cpm',...}
    budget: { model: 'CPM', bidFloor: 300, bidCeiling: 1200, dailyCap: 20000 },
    schedule: {
      startDate: new Date().toISOString(),
      timezone: 'UTC',
    },
    frequencyProfile: {
      lifetime: { maxImpressions: 5, windowSeconds: 999999999 },
    },
    creative: {
      imageUrl: 'https://example.com/creatives/email_001.jpg',
      alt:      'Email Header Ad',
    },
    targeting: {
      geo:      { countries: ['US', 'CA', 'GB'] },
      audience: { segments: ['email_subscribers'] },
    },
    tags: ['email'],
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },

  // ── A/B Experiment Ad ─────────────────────────────────────────────────────
  {
    id: 'ad_experiment_001',
    // FIX: was placement:'article_sidebar' (invalid), layout:'sidebar_medium_rectangle' (invalid)
    placements: ['sidebar_top'],
    layouts: ['medium_rectangle'],
    status: 'active',
    type: 'display',
    title: 'Version A — Try Our Product',
    description: 'Control variant of our sidebar A/B test.',
    ctaText: 'Try Free',
    ctaUrl: 'https://example.com/trial',
    sponsorName: 'ABTestCo',
    priority: 7,
    weight: 65,
    // FIX: was pricing:{model:'cpm',...}
    budget: { model: 'CPM', bidFloor: 200, bidCeiling: 1200, dailyCap: 25000 },
    schedule: {
      startDate: new Date().toISOString(),
      timezone: 'UTC',
    },
    frequencyProfile: {
      daily:  { maxImpressions: 5,  windowSeconds: 86400 },
      weekly: { maxImpressions: 20, windowSeconds: 604800 },
    },
    creative: {
      imageUrl: 'https://example.com/creatives/exp_001_a.jpg',
      alt:      'Experimental Ad Variant A',
    },
    // FIX: experiment shape must match AdExperiment interface (key, type, variant, trafficAllocation)
    experiment: {
      key:               'sidebar_creative_test',
      type:              'ab',
      variant:           'control',
      trafficAllocation: 0.5,
      confidenceLevel:   0.95,
      minDetectableEffect: 0.05,
    },
    tags: ['experiment', 'ab_test'],
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },

  // ── Article Mid (article_mid) — matches the user's <AdSlot placement="article_mid" /> ──
  {
    id: 'ad_article_mid_001',
    placements: ['article_mid'],
    layouts: ['medium_rectangle', 'native_in_content', 'leaderboard'],
    status: 'active',
    type: 'native',
    title: 'Upgrade Your Reading Experience',
    description: 'Discover tools used by 2M+ professionals to get more done.',
    ctaText: 'Explore Now',
    ctaUrl: 'https://example.com/tools',
    sponsorName: 'ProductHunt',
    eyebrow: 'Sponsored · Tools',
    priority: 8,
    weight: 80,
    budget: { model: 'CPM', bidFloor: 300, bidCeiling: 2000, dailyCap: 30000 },
    schedule: {
      startDate: new Date().toISOString(),
      timezone: 'UTC',
      paceEvenly: true,
    },
    frequencyProfile: {
      daily:  { maxImpressions: 3,  windowSeconds: 86400 },
      weekly: { maxImpressions: 10, windowSeconds: 604800 },
    },
    creative: {
      imageUrl:     'https://example.com/creatives/article_mid_001.jpg',
      imageUrlWebp: 'https://example.com/creatives/article_mid_001.webp',
      alt:          'Article mid-page advertisement',
    },
    targeting: {
      tech: { devices: ['desktop', 'mobile', 'tablet'] },
    },
    tags: ['article', 'mid', 'native'],
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
  // ── Article Bottom (article_bottom) ──────────────────────────────────────
  {
    id: 'ad_article_bottom_001',
    placements: ['article_bottom'],
    layouts: ['leaderboard', 'billboard'],
    status: 'active',
    type: 'display',
    title: 'Plan Your Perfect Ritual',
    description: 'Expert guidance for all Vedic ceremonies. Reliable samagri and verified priests.',
    ctaText: 'View Guides',
    ctaUrl: 'https://example.com/guides',
    sponsorName: 'Navdhya Rituals',
    priority: 8,
    weight: 90,
    budget: { model: 'CPM', bidFloor: 400, totalBudget: 2000000 },
    creative: {
      imageUrl: 'https://picsum.photos/seed/navdhya-bottom/1200/500',
      alt: 'Article bottom advertisement',
    },
    tags: ['article', 'bottom', 'brand'],
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
  // ── Article Inline 1 (article_inline_1) ──────────────────────────────────
  {
    id: 'ad_article_inline_1_001',
    placements: ['article_inline_1'],
    layouts: ['hero', 'native_in_content'],
    status: 'active',
    type: 'native',
    title: 'Daily spiritual insights',
    description: 'Join 50k+ seekers receiving mindful rituals every morning.',
    ctaText: 'Start Reading',
    ctaUrl: 'https://example.com/newsletter',
    sponsorName: 'SpiritualDaily',
    priority: 7,
    weight: 70,
    budget: { model: 'CPC', bidFloor: 40, dailyCap: 5000 },
    imageUrl: 'https://picsum.photos/seed/navdhya-spiritual/1200/600',
    creative: {
      imageUrl: 'https://picsum.photos/seed/navdhya-spiritual/1200/600',
      alt: 'Daily spiritual insights — sunrise meditation',
    },
    tags: ['article', 'inline', 'native'],
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
  // ── Article Inline 2 (article_inline_2) ──────────────────────────────────
  {
    id: 'ad_article_inline_2_001',
    placements: ['article_inline_2'],
    layouts: ['inline_text', 'native_in_content'],
    status: 'active',
    type: 'native',
    title: 'Vedic Astrology Consultation',
    description: 'Get deep insights into your life path with our senior astrologers.',
    ctaText: 'Book Now',
    ctaUrl: 'https://example.com/astrology',
    sponsorName: 'VedicInsights',
    priority: 7,
    weight: 70,
    budget: { model: 'CPC', bidFloor: 50, dailyCap: 8000 },
    imageUrl: 'https://picsum.photos/seed/navdhya-astro/1200/600',
    creative: {
      imageUrl: 'https://picsum.photos/seed/navdhya-astro/1200/600',
      alt: 'Vedic astrology consultation session',
    },
    tags: ['article', 'inline', 'native'],
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
];

// ─────────────────────────────────────────────────────────────────────────────
// Default global registry
// ─────────────────────────────────────────────────────────────────────────────

export const defaultRegistry = new AdRegistry();
defaultRegistry.registerMany(sampleAds);

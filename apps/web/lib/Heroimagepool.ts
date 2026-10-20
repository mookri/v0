/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║   NAVDHYA — HERO IMAGE ENGINE  (Next.js · custom host · 2027)  ║
 * ║  JSON-native · CDN-URL-resolved · Smart-shift · SSR-safe       ║
 * ╚══════════════════════════════════════════════════════════════════╝
 */

import poolData from "@/content/images/imgData.json";

// ─── CDN resolution ───────────────────────────────────────────────────────────
// In dev: images are served from /public → paths are root-relative (/images/hero/…)
// In prod: prepend NEXT_PUBLIC_CDN_URL (Cloudflare, S3, etc.)
// Set NEXT_PUBLIC_CDN_URL="" to keep root-relative paths in production too.

const CDN = (process.env.NEXT_PUBLIC_CDN_URL ?? "").replace(/\/$/, "");

function cdnUrl(path?: string): string {
    if (!path) return "";
    if (path.startsWith("http")) return path;
    const normalizedPath = path.startsWith("/") ? path : `/${path}`;
    if (!CDN) return normalizedPath;
    return `${CDN}${normalizedPath}`;
}

// ─── Types ────────────────────────────────────────────────────────────────────

export type ImageCategory =
    | "ritual" | "temple" | "festival"
    | "nature" | "culture" | "spiritual" | "generic";

export type SelectionStrategy =
    | "deterministic"
    | "daily-shift"
    | "weekly-shift"
    | "monthly-shift"
    | "random";

/** Raw shape of one entry in hero-images.json */
interface RawImage {
    id: string;
    slug: string;
    category: ImageCategory;
    src: string;
    srcSet?: {
        webp?: string;
        avif?: string;
        jpg?: string;
        thumb?: string;
    };
    width: number;
    height: number;
    alt: string;
    dominantColor: string;
    blurDataURL: string;
    tags: string[];
    credit: { author: string; license: string };
    active: boolean;
    addedAt: string;
}

/** Resolved image — all URLs are CDN-absolute, ready for next/image */
export interface HeroImage extends Omit<RawImage, "src" | "srcSet"> {
    /** CDN-resolved primary src for next/image */
    src: string;
    /** CDN-resolved srcSet variants */
    srcSet: {
        webp: string;
        avif: string;
        jpg: string;
        thumb: string;
    };
    /**
     * Convenience: the tiny thumbnail URL.
     * Pass to next/image's `blurDataURL` prop for blur-up.
     * (Or use the base64 blurDataURL from the JSON directly.)
     */
    thumbnailUrl: string;
}

export interface PickOptions {
    category?: ImageCategory;
    /** Defaults to JSON config.defaultStrategy ("weekly-shift") */
    strategy?: SelectionStrategy;
    /** A/B test slot — adds salt to the hash without changing slug */
    slot?: string;
    /** Image IDs to exclude from selection */
    exclude?: string[];
}

export interface PickResult {
    image: HeroImage;
    strategy: SelectionStrategy;
    epoch: string;
    poolSize: number;
    index: number;
}

// ─── Pool hydration ───────────────────────────────────────────────────────────

function resolve(raw: RawImage): HeroImage {
    const srcSet = raw.srcSet || {};
    return {
        ...raw,
        src: cdnUrl(raw.src),
        srcSet: {
            webp: cdnUrl(srcSet.webp || raw.src),
            avif: cdnUrl(srcSet.avif || raw.src),
            jpg: cdnUrl(srcSet.jpg || raw.src),
            thumb: cdnUrl(srcSet.thumb || raw.src),
        },
        thumbnailUrl: cdnUrl(srcSet.thumb || raw.src),
    };
}

const POOL: HeroImage[] = (poolData.images as RawImage[])
    .filter(img => img.active !== false)
    .map(resolve);

const DEFAULT_STRATEGY = (poolData.config.defaultStrategy ?? "weekly-shift") as SelectionStrategy;

// ─── Epoch Calculators ────────────────────────────────────────────────────────

function getEpoch(strategy: SelectionStrategy): string {
    const now = new Date();
    const Y = now.getUTCFullYear();
    const M = String(now.getUTCMonth() + 1).padStart(2, "0");
    const D = String(now.getUTCDate()).padStart(2, "0");

    switch (strategy) {
        case "daily-shift":
            return `${Y}-${M}-${D}`;

        case "weekly-shift": {
            const jan1 = new Date(Date.UTC(Y, 0, 1));
            const week = Math.ceil(
                (((now.getTime() - jan1.getTime()) / 86_400_000) + jan1.getUTCDay() + 1) / 7
            );
            return `${Y}-W${String(week).padStart(2, "0")}`;
        }

        case "monthly-shift":
            return `${Y}-${M}`;

        default:
            return "static";
    }
}

// ─── djb2 hash ────────────────────────────────────────────────────────────────

function djb2(str: string): number {
    let h = 5381;
    for (let i = 0; i < str.length; i++) {
        h = (((h << 5) + h) ^ str.charCodeAt(i)) >>> 0;
    }
    return h;
}

// ─── Pool filter ─────────────────────────────────────────────────────────────

function filterPool(pool: HeroImage[], category?: ImageCategory, exclude: string[] = []): HeroImage[] {
    const base = exclude.length ? pool.filter(img => !exclude.includes(img.id)) : pool;
    if (!category) return base;
    const match = base.filter(img => img.category === category);
    return match.length > 0 ? match : base; // graceful fallback
}

// ─── Tag-to-category router ───────────────────────────────────────────────────

const TAG_MAP: Record<string, ImageCategory> = {
    puja: "ritual", hawan: "ritual", yajna: "ritual", yagya: "ritual",
    homam: "ritual", abhishek: "ritual", pooja: "ritual", agnihotra: "ritual",
    mandir: "temple", temple: "temple", tirth: "temple", kshetra: "temple",
    gurudwara: "temple", devalaya: "temple",
    diwali: "festival", holi: "festival", navratri: "festival",
    "ganesh-chaturthi": "festival", "durga-puja": "festival",
    "kumbh-mela": "festival", pongal: "festival", onam: "festival",
    forest: "nature", river: "nature", mountain: "nature", himalaya: "nature",
    lotus: "nature", banyan: "nature", valley: "nature", waterfall: "nature",
    dance: "culture", bharatanatyam: "culture", vedic: "culture",
    sanskrit: "culture", idol: "culture", sculpture: "culture",
    yoga: "spiritual", meditation: "spiritual", aarti: "spiritual",
    rudraksha: "spiritual", mantra: "spiritual", sadhana: "spiritual",
};

export function resolveCategory(tags: string[]): ImageCategory | undefined {
    for (const tag of tags) {
        const match = TAG_MAP[tag.toLowerCase().replace(/\s+/g, "-")];
        if (match) return match;
    }
    return undefined;
}

// ─── Core selector ────────────────────────────────────────────────────────────

/**
 * Pick a hero image from the pool with smart weekly-shift rotation.
 *
 * SSR-safe: all strategies except "random" produce identical results
 * on server and client — no hydration mismatch.
 *
 * @param key   Page slug or URL path — used as hash seed.
 * @param opts  Category, strategy, slot, exclude.
 *
 * @example
 * const { image } = pickHeroImage("/services/puja", { category: "ritual" });
 * const { image } = pickHeroImage(page.slug, { category: resolveCategory(page.tags) });
 */
export function pickHeroImage(key: string, opts: PickOptions = {}): PickResult {
    const strategy = opts.strategy ?? DEFAULT_STRATEGY;

    if (strategy === "random") {
        if (typeof window === "undefined") {
            console.warn("[HeroImagePool] strategy='random' called during SSR — falling back to 'weekly-shift'");
            return pickHeroImage(key, { ...opts, strategy: "weekly-shift" });
        }
        const pool = filterPool(POOL, opts.category, opts.exclude);
        const idx = Math.floor(Math.random() * pool.length);
        return { image: pool[idx], strategy, epoch: "random", poolSize: pool.length, index: idx };
    }

    const epoch = getEpoch(strategy);
    const seed = opts.slot ? `${key}::${epoch}::${opts.slot}` : `${key}::${epoch}`;
    const pool = filterPool(POOL, opts.category, opts.exclude);
    const idx = djb2(seed) % pool.length;

    return { image: pool[idx], strategy, epoch, poolSize: pool.length, index: idx };
}

/** Returns just the HeroImage — shorthand. */
export function getHeroImage(key: string, opts?: PickOptions): HeroImage {
    return pickHeroImage(key, opts).image;
}

/** Returns the full active pool, optionally filtered. */
export function getPool(category?: ImageCategory): HeroImage[] {
    return filterPool(POOL, category);
}

// ─── next/image prop builder ──────────────────────────────────────────────────

/**
 * Returns props ready to spread onto `<Image>` from next/image.
 * Handles src, alt, width, height, placeholder, blurDataURL.
 *
 * @example
 * import Image from "next/image";
 * const imgProps = getNextImageProps(image, { priority: true });
 * return <div className="relative h-[500px]"><Image fill {...imgProps} /></div>;
 *
 * @example (with fill — most common for hero banners)
 * const imgProps = getNextImageProps(image, { priority: true, fill: true });
 * return <div className="relative h-[500px]"><Image {...imgProps} /></div>;
 */
export function getNextImageProps(
    img: HeroImage,
    opts: {
        priority?: boolean;
        fill?: boolean;
        sizes?: string;
        quality?: number;
    } = {}
) {
    const base = {
        src: img.src,
        alt: img.alt,
        placeholder: "blur" as const,
        blurDataURL: img.blurDataURL,
        quality: opts.quality ?? 85,
        priority: opts.priority ?? true,
        sizes: opts.sizes ?? "(max-width: 640px) 100vw, (max-width: 1024px) 100vw, 1200px",
    };

    if (opts.fill) {
        return { ...base, fill: true };
    }

    return { ...base, width: img.width, height: img.height };
}

// ─── Next.js App Router metadata helper ───────────────────────────────────────

/**
 * Returns a metadata `other` object that injects a `<link rel="preload">` for
 * the resolved hero image in the document `<head>`.
 *
 * @example
 * export async function generateMetadata({ params }) {
 *   const img = getHeroImage(params.slug, { category: "ritual" });
 *   return { other: buildPreloadMeta(img) };
 * }
 */
export function buildPreloadMeta(img: HeroImage): Record<string, string> {
    return {
        "link:preload:hero": `<${img.src}>; rel=preload; as=image; fetchpriority=high`,
    };
}

// ─── Pool stats ───────────────────────────────────────────────────────────────

export function getPoolStats() {
    const cats = [...new Set(POOL.map(img => img.category))];
    return {
        total: POOL.length,
        byCategory: Object.fromEntries(
            cats.map(cat => [cat, POOL.filter(img => img.category === cat).length])
        ),
        cdnBase: CDN || "(root-relative / local)",
        lastRefreshed: (poolData._meta as { lastRefreshed: string }).lastRefreshed,
        version: (poolData._meta as { version: string }).version,
    };
}

export const POOL_VERSION = (poolData._meta as { version: string }).version;
export const POOL_SHIFT_CYCLE = (poolData._meta as { shiftCycle: string }).shiftCycle;
/* ═══════════════════════════════════════════════════════════════════════════
   AD LAYOUTS  ·  SponsoredAds.tsx + StickyAds.tsx
   SponsoredBannerAd / NativeProductAd / AppInstallAd
   StickyFooterAd / StickyHeaderAd / FloatingBoxAd
   ALL visual values from CSS vars only.
   ═══════════════════════════════════════════════════════════════════════════ */
"use client";

import { useEffect, useRef, useState, useCallback } from "react";
import {
    ArrowUpRight, ShieldCheck, Star, Download, X, ChevronDown, ChevronUp, Tag
} from "lucide-react";
import { V, css } from "./Adtokens";
import { useAdLazy } from "./Adprovider";
import type { Ad } from "./core/types";

interface BaseProps { ad: Ad; onImpression?: () => void; onClick?: () => void; onClose?: () => void; }

// ─────────────────────────────────────────────────────────────────────────────
// SponsoredBannerAd  —  full-width branded section banner
// ─────────────────────────────────────────────────────────────────────────────

export function SponsoredBannerAd({ ad, onImpression, onClick }: BaseProps) {
    const imgUrl = ad.creative?.imageUrl ?? ad.imageUrl;
    const { ref, visible, loaded, imgError, onImgLoad, onImgError } =
        useAdLazy(imgUrl, onImpression);

    const accentOverride = ad.creative?.accentColor;

    return (
        <div ref={ref} className="w-full my-6 overflow-hidden" style={{ borderRadius: css(V.rSm) }}>
            {/* Header strip */}
            <div
                className="flex items-center justify-between"
                style={{
                    padding: "6px 16px",
                    background: css(V.bgCard),
                    borderBottom: `1px solid ${css(V.bgBorder)}`,
                    borderLeft: `1px solid ${css(V.bgBorder)}`,
                    borderRight: `1px solid ${css(V.bgBorder)}`,
                    borderTop: `1px solid ${css(V.bgBorder)}`,
                    borderBottomColor: "transparent",
                }}
            >
                <span
                    className="uppercase"
                    style={{
                        fontSize: 8,
                        letterSpacing: css(V.labelSpacing),
                        color: css(V.onLight3),
                        fontFamily: css(V.fontBody),
                    }}
                >
                    Sponsored content
                </span>
                {ad.sponsorName && (
                    <span style={{ fontSize: 9, fontWeight: 600, color: css(V.onLight2), fontFamily: css(V.fontBody) }}>
                        {ad.sponsorName}
                    </span>
                )}
            </div>

            {/* Main banner */}
            <div
                className="relative group"
                style={{
                    height: "clamp(130px, 15vw, 180px)",
                    background: ad.creative?.bgColor ?? css(V.bgFallback),
                }}
            >
                {visible && imgUrl && !imgError && (
                    <img
                        src={imgUrl}
                        alt={ad.creative?.alt ?? ad.title}
                        loading="lazy"
                        decoding="async"
                        onLoad={onImgLoad}
                        onError={onImgError}
                        className="absolute inset-0 w-full h-full object-cover"
                        style={{ opacity: loaded ? 0.38 : 0, transition: `opacity ${css(V.easeImage)}` }}
                    />
                )}

                {/* Accent left stripe */}
                <div
                    className="absolute left-0 top-0 bottom-0"
                    style={{ width: 3, background: accentOverride ?? css(V.accent) }}
                />

                <div className="absolute inset-0" style={{ background: css(V.overlaySide) }} />

                <div className="relative z-10 h-full flex items-center" style={{ paddingLeft: 24, paddingRight: 20, gap: 20 }}>
                    {/* Optional logo */}
                    {ad.creative?.logoUrl && (
                        <div
                            className="flex-shrink-0 overflow-hidden"
                            style={{
                                width: 48, height: 48,
                                borderRadius: css(V.rMd),
                                background: "rgba(255,255,255,0.10)",
                                border: `1px solid rgba(255,255,255,0.18)`,
                            }}
                        >
                            <img src={ad.creative.logoUrl} alt={ad.sponsorName ?? ""} className="w-full h-full object-contain" style={{ padding: 6 }} />
                        </div>
                    )}

                    <div className="flex-1 min-w-0">
                        {ad.eyebrow && (
                            <p className="mb-1 uppercase" style={{ fontSize: 9, letterSpacing: css(V.labelSpacing), color: css(V.onDark3), fontFamily: css(V.fontBody) }}>
                                {ad.eyebrow}
                            </p>
                        )}
                        <h3
                            className="truncate"
                            style={{ fontSize: "clamp(15px, 2vw, 18px)", fontWeight: 600, color: css(V.onDark1), fontFamily: css(V.fontBody), lineHeight: 1.2 }}
                        >
                            {ad.title}
                        </h3>
                        {ad.description && (
                            <p
                                className="line-clamp-2 hidden sm:block"
                                style={{ fontSize: "clamp(11px, 1.4vw, 13px)", color: css(V.onDark3), fontFamily: css(V.fontBody), marginTop: 4, lineHeight: 1.4 }}
                            >
                                {ad.description}
                            </p>
                        )}
                    </div>

                    <a
                        href={ad.ctaUrl}
                        onClick={onClick}
                        target="_blank"
                        rel="noopener noreferrer nofollow"
                        className="flex-shrink-0 inline-flex items-center gap-1.5 whitespace-nowrap"
                        style={{
                            height: 40,
                            padding: "0 16px",
                            background: accentOverride ?? css(V.accent),
                            color: css(V.accentFg),
                            fontSize: 12,
                            fontWeight: 700,
                            fontFamily: css(V.fontBody),
                            textDecoration: "none",
                            borderRadius: css(V.rSm),
                            transition: `background ${css(V.easeFast)}`,
                        }}
                    >
                        {ad.ctaText} <ArrowUpRight size={12} />
                    </a>
                </div>
            </div>

            {ad.disclaimer && (
                <div
                    style={{
                        padding: "4px 16px",
                        background: css(V.bgCard),
                        border: `1px solid ${css(V.bgBorder)}`,
                        borderTop: "none",
                    }}
                >
                    <p style={{ fontSize: 8, color: css(V.onLight3), fontFamily: css(V.fontBody) }}>{ad.disclaimer}</p>
                </div>
            )}
        </div>
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// NativeProductAd  —  shoppable product card with price + rating
// ─────────────────────────────────────────────────────────────────────────────

export interface NativeProductAdProps extends BaseProps {
    price?: string;
    originalPrice?: string;
    rating?: number;
    reviewCount?: number;
}

export function NativeProductAd({
    ad, price, originalPrice, rating, reviewCount, onImpression, onClick
}: NativeProductAdProps) {
    const imgUrl = ad.creative?.imageUrl ?? ad.imageUrl;
    const { ref, visible, loaded, imgError, onImgLoad, onImgError } =
        useAdLazy(imgUrl, onImpression);

    return (
        <div
            ref={ref}
            className="overflow-hidden"
            style={{
                width: "100%",
                maxWidth: 240,
                background: css(V.bgCard),
                border: `1px solid ${css(V.bgBorder)}`,
                borderRadius: css(V.rLg),
                boxShadow: css(V.shadowCard),
                transition: `box-shadow ${css(V.easeBase)}, transform ${css(V.easeBase)}`,
            }}
            onMouseEnter={(e) => {
                const el = e.currentTarget as HTMLDivElement;
                el.style.boxShadow = css(V.shadowFloat);
                el.style.transform = "translateY(-2px)";
            }}
            onMouseLeave={(e) => {
                const el = e.currentTarget as HTMLDivElement;
                el.style.boxShadow = css(V.shadowCard);
                el.style.transform = "translateY(0)";
            }}
        >
            {/* Image */}
            <a href={ad.ctaUrl} onClick={onClick} target="_blank" rel="noopener noreferrer nofollow" style={{ display: "block" }}>
                <div
                    className="relative overflow-hidden"
                    style={{ aspectRatio: "1/1", background: css(V.bgFallback) }}
                >
                    {visible && imgUrl && !imgError && (
                        <img
                            src={imgUrl}
                            alt={ad.creative?.alt ?? ad.title}
                            loading="lazy"
                            decoding="async"
                            onLoad={onImgLoad}
                            onError={onImgError}
                            className="absolute inset-0 w-full h-full object-cover"
                            style={{ opacity: loaded ? 1 : 0, transition: `opacity ${css(V.easeImage)}, transform ${css(V.easeHover)}` }}
                        />
                    )}
                    {/* Labels */}
                    <div className="absolute" style={{ top: 8, left: 8, display: "flex", flexDirection: "column" as const, gap: 4 }}>
                        {ad.badge && (
                            <span style={{ background: css(V.accent), color: css(V.accentFg), fontSize: 8, fontWeight: 900, textTransform: "uppercase" as const, padding: "2px 6px", borderRadius: css(V.rSm), fontFamily: css(V.fontBody) }}>
                                {ad.badge}
                            </span>
                        )}
                        {originalPrice && price && (
                            <span style={{ background: "#dc2626", color: "#fff", fontSize: 8, fontWeight: 700, padding: "2px 6px", borderRadius: css(V.rSm), fontFamily: css(V.fontBody) }}>
                                SALE
                            </span>
                        )}
                    </div>
                    <div className="absolute" style={{ top: 8, right: 8, background: css(V.bgSponsorTag), backdropFilter: "blur(6px)", fontSize: 7, fontWeight: 500, color: css(V.onDark2), padding: "2px 8px", borderRadius: css(V.rPill), fontFamily: css(V.fontBody) }}>
                        Ad
                    </div>
                </div>
            </a>

            {/* Details */}
            <div style={{ padding: 12 }}>
                <p className="mb-1 uppercase" style={{ fontSize: 8, letterSpacing: "0.2em", color: css(V.onLight3), fontFamily: css(V.fontBody) }}>
                    {ad.sponsorName ?? "Sponsored"}
                </p>
                <h4 className="mb-1.5 line-clamp-2" style={{ fontSize: 13, fontWeight: 600, color: css(V.onLight1), fontFamily: css(V.fontBody), lineHeight: 1.3 }}>
                    {ad.title}
                </h4>

                {typeof rating === "number" && (
                    <div className="flex items-center gap-1 mb-2">
                        <div className="flex items-center gap-0.5">
                            {Array.from({ length: 5 }, (_, i) => (
                                <Star
                                    key={i}
                                    size={10}
                                    style={{
                                        color: i < Math.floor(rating) ? css(V.accent) : css(V.onLight3),
                                        fill: i < Math.floor(rating) ? css(V.accent) : "currentColor",
                                        opacity: i >= Math.floor(rating) ? 0.3 : 1,
                                    }}
                                />
                            ))}
                        </div>
                        {reviewCount != null && (
                            <span style={{ fontSize: 10, color: css(V.onLight3), fontFamily: css(V.fontBody) }}>
                                ({reviewCount.toLocaleString()})
                            </span>
                        )}
                    </div>
                )}

                {price && (
                    <div className="flex items-baseline gap-2 mb-3">
                        <span style={{ fontSize: 17, fontWeight: 700, color: css(V.onLight1), fontFamily: css(V.fontBody) }}>{price}</span>
                        {originalPrice && (
                            <span style={{ fontSize: 12, color: css(V.onLight3), textDecoration: "line-through", fontFamily: css(V.fontBody) }}>{originalPrice}</span>
                        )}
                    </div>
                )}

                <a
                    href={ad.ctaUrl}
                    onClick={onClick}
                    target="_blank"
                    rel="noopener noreferrer nofollow"
                    className="flex items-center justify-center gap-1.5 w-full"
                    style={{
                        height: 36,
                        background: css(V.accent),
                        color: css(V.accentFg),
                        fontSize: 12,
                        fontWeight: 700,
                        fontFamily: css(V.fontBody),
                        textDecoration: "none",
                        borderRadius: css(V.rMd),
                        transition: `background ${css(V.easeFast)}`,
                    }}
                >
                    <Tag size={11} /> {ad.ctaText}
                </a>

                {ad.verified && (
                    <div className="flex items-center justify-center gap-1 mt-2" style={{ fontSize: 9, color: css(V.onLight3), fontFamily: css(V.fontBody) }}>
                        <ShieldCheck size={9} style={{ color: css(V.success) }} /> Verified seller
                    </div>
                )}
            </div>
        </div>
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// AppInstallAd  —  mobile app install banner
// ─────────────────────────────────────────────────────────────────────────────

export interface AppInstallAdProps extends BaseProps {
    appRating?: number;
    appRatingLabel?: string;
    appPrice?: string;
    platform?: "ios" | "android" | "auto";
}

export function AppInstallAd({
    ad, appRating, appRatingLabel, appPrice = "Free",
    platform = "auto", onImpression, onClick
}: AppInstallAdProps) {
    const { ref } = useAdLazy(undefined, onImpression);

    const isIOS =
        platform === "ios" ||
        (platform === "auto" &&
            typeof navigator !== "undefined" &&
            /iPhone|iPad/i.test(navigator.userAgent));

    const storeUrl = isIOS ? ad.ctaUrl : (ad.ctaSecondaryUrl ?? ad.ctaUrl);

    return (
        <div
            ref={ref}
            className="w-full overflow-hidden"
            style={{ background: ad.creative?.bgColor ?? css(V.bgFallback) }}
        >
            <div className="flex items-center gap-4" style={{ padding: "12px 16px" }}>
                {/* Icon */}
                {ad.creative?.iconUrl ? (
                    <img
                        src={ad.creative.iconUrl}
                        alt={`${ad.title} icon`}
                        className="flex-shrink-0"
                        style={{ width: 56, height: 56, borderRadius: css(V.rIcon) }}
                        loading="lazy"
                    />
                ) : (
                    <div
                        className="flex-shrink-0 flex items-center justify-center"
                        style={{ width: 56, height: 56, borderRadius: css(V.rIcon), background: "rgba(255,255,255,0.10)" }}
                    >
                        <Download size={24} style={{ color: css(V.onDark3) }} />
                    </div>
                )}

                {/* Info */}
                <div className="flex-1 min-w-0">
                    <p className="mb-0.5 uppercase" style={{ fontSize: 8, letterSpacing: "0.2em", color: css(V.onDarkLabel), fontFamily: css(V.fontBody) }}>
                        {ad.sponsorName ?? "Sponsored"}
                    </p>
                    <h4 className="truncate" style={{ fontSize: 14, fontWeight: 600, color: css(V.onDark1), fontFamily: css(V.fontBody) }}>
                        {ad.title}
                    </h4>
                    <div className="flex items-center gap-2 mt-0.5">
                        {typeof appRating === "number" && (
                            <div className="flex items-center gap-0.5">
                                {Array.from({ length: 5 }, (_, i) => (
                                    <Star
                                        key={i}
                                        size={8}
                                        style={{
                                            color: i < Math.floor(appRating) ? css(V.accent) : css(V.onDarkLabel),
                                            fill: "currentColor",
                                        }}
                                    />
                                ))}
                                {appRatingLabel && (
                                    <span style={{ fontSize: 9, color: css(V.onDark3), marginLeft: 3, fontFamily: css(V.fontBody) }}>
                                        {appRatingLabel}
                                    </span>
                                )}
                            </div>
                        )}
                        <span style={{ fontSize: 9, color: css(V.onDarkLabel), fontFamily: css(V.fontBody) }}>{appPrice}</span>
                    </div>
                </div>

                {/* CTA */}
                <a
                    href={storeUrl}
                    onClick={onClick}
                    target="_blank"
                    rel="noopener noreferrer nofollow"
                    className="flex-shrink-0 whitespace-nowrap"
                    style={{
                        height: 36,
                        padding: "0 16px",
                        background: css(V.accent),
                        color: css(V.accentFg),
                        fontSize: 12,
                        fontWeight: 700,
                        fontFamily: css(V.fontBody),
                        textDecoration: "none",
                        borderRadius: css(V.rPill),
                        display: "inline-flex",
                        alignItems: "center",
                        transition: `background ${css(V.easeFast)}`,
                    }}
                >
                    {ad.ctaText ?? "Install"}
                </a>
            </div>
            <p style={{ fontSize: 7, textAlign: "center" as const, color: css(V.onDarkLabel), paddingBottom: 4, fontFamily: css(V.fontBody) }}>Ad</p>
        </div>
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// StickyFooterAd  —  anchored bottom bar with dismiss
// ─────────────────────────────────────────────────────────────────────────────

export interface StickyFooterAdProps extends BaseProps {
    zIndex?: number;
    closeDelayMs?: number;
}

export function StickyFooterAd({
    ad, onImpression, onClick, onClose, zIndex = 1000, closeDelayMs = 0
}: StickyFooterAdProps) {
    const [dismissed, setDismissed] = useState(false);
    const [canClose, setCanClose] = useState(closeDelayMs === 0);
    const firedRef = useRef(false);

    useEffect(() => {
        if (firedRef.current) return;
        const t = setTimeout(() => {
            firedRef.current = true;
            onImpression?.();
        }, 100);
        return () => clearTimeout(t);
    }, [onImpression]);

    useEffect(() => {
        if (closeDelayMs > 0) {
            const t = setTimeout(() => setCanClose(true), closeDelayMs);
            return () => clearTimeout(t);
        }
    }, [closeDelayMs]);

    const handleClose = useCallback(() => {
        setDismissed(true);
        onClose?.();
    }, [onClose]);

    if (dismissed) return null;

    return (
        <div
            className="fixed bottom-0 left-0 right-0 w-full overflow-hidden"
            style={{
                zIndex,
                background: ad.creative?.bgColor ?? css(V.bgFallback),
                boxShadow: css(V.shadowFloat),
                animation: "ad-slide-up 0.3s ease-out",
            }}
            role="complementary"
            aria-label="Advertisement"
        >
            <div
                className="flex items-center gap-4 max-w-screen-xl mx-auto"
                style={{ padding: "12px 16px" }}
            >
                <div className="flex-shrink-0">
                    <p className="uppercase" style={{ fontSize: 7, letterSpacing: css(V.labelSpacing), color: css(V.onDarkLabel), fontFamily: css(V.fontBody), marginBottom: 2 }}>Sponsored</p>
                    {ad.sponsorName && (
                        <p style={{ fontSize: 10, fontWeight: 600, color: css(V.onDark3), fontFamily: css(V.fontBody) }}>{ad.sponsorName}</p>
                    )}
                </div>

                <div className="flex-1 min-w-0">
                    <p className="truncate" style={{ fontSize: "clamp(12px, 2vw, 14px)", fontWeight: 600, color: css(V.onDark1), fontFamily: css(V.fontBody) }}>
                        {ad.title}
                    </p>
                    {ad.description && (
                        <p className="truncate hidden sm:block" style={{ fontSize: 11, color: css(V.onDark3), fontFamily: css(V.fontBody) }}>
                            {ad.description}
                        </p>
                    )}
                </div>

                <a
                    href={ad.ctaUrl}
                    onClick={onClick}
                    target="_blank"
                    rel="noopener noreferrer nofollow"
                    className="flex-shrink-0 inline-flex items-center gap-1.5 whitespace-nowrap"
                    style={{
                        height: 36,
                        padding: "0 14px",
                        background: css(V.accent),
                        color: css(V.accentFg),
                        fontSize: 12,
                        fontWeight: 700,
                        fontFamily: css(V.fontBody),
                        textDecoration: "none",
                        borderRadius: css(V.rSm),
                    }}
                >
                    {ad.ctaText} <ArrowUpRight size={12} />
                </a>

                <button
                    onClick={handleClose}
                    disabled={!canClose}
                    aria-label="Close advertisement"
                    style={{
                        flexShrink: 0,
                        width: 28,
                        height: 28,
                        borderRadius: css(V.rPill),
                        background: "rgba(255,255,255,0.10)",
                        border: "none",
                        cursor: canClose ? "pointer" : "not-allowed",
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "center",
                        opacity: canClose ? 1 : 0.35,
                        transition: `background ${css(V.easeFast)}`,
                    }}
                >
                    <X size={13} style={{ color: css(V.onDark1) }} />
                </button>
            </div>

            {closeDelayMs > 0 && !canClose && (
                <div style={{ height: 2, background: "rgba(255,255,255,0.10)" }}>
                    <div
                        style={{
                            height: "100%",
                            background: "rgba(255,255,255,0.40)",
                            animation: `ad-progress-drain ${closeDelayMs}ms linear forwards`,
                        }}
                    />
                </div>
            )}
        </div>
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// FloatingBoxAd  —  corner floating card with dismiss
// ─────────────────────────────────────────────────────────────────────────────

type FloatingPosition = "bottom-right" | "bottom-left" | "top-right" | "top-left";

const POS: Record<FloatingPosition, React.CSSProperties> = {
    "bottom-right": { bottom: 16, right: 16 },
    "bottom-left": { bottom: 16, left: 16 },
    "top-right": { top: 16, right: 16 },
    "top-left": { top: 16, left: 16 },
};

export interface FloatingBoxAdProps extends BaseProps {
    position?: FloatingPosition;
    zIndex?: number;
    closeDelayMs?: number;
}

export function FloatingBoxAd({
    ad, onImpression, onClick, onClose,
    position = "bottom-right", zIndex = 500, closeDelayMs = 2500
}: FloatingBoxAdProps) {
    const [dismissed, setDismissed] = useState(false);
    const [canClose, setCanClose] = useState(closeDelayMs === 0);
    const imgUrl = ad.creative?.imageUrl ?? ad.imageUrl;
    const { ref, visible, loaded, imgError, onImgLoad, onImgError } =
        useAdLazy(imgUrl, onImpression, "0px");

    useEffect(() => {
        if (closeDelayMs > 0) {
            const t = setTimeout(() => setCanClose(true), closeDelayMs);
            return () => clearTimeout(t);
        }
    }, [closeDelayMs]);

    if (dismissed) return null;

    return (
        <div
            ref={ref}
            className="fixed overflow-hidden"
            style={{
                ...POS[position],
                zIndex,
                width: "clamp(260px, 30vw, 300px)",
                background: ad.creative?.bgColor ?? css(V.bgFallback),
                borderRadius: css(V.rXl),
                boxShadow: css(V.shadowFloat),
            }}
            role="complementary"
            aria-label="Advertisement"
        >
            {/* Close button */}
            <button
                onClick={() => { setDismissed(true); onClose?.(); }}
                disabled={!canClose}
                aria-label={canClose ? "Close advertisement" : "Please wait"}
                className="absolute"
                style={{
                    top: 8,
                    right: 8,
                    zIndex: 10,
                    width: 24,
                    height: 24,
                    borderRadius: css(V.rPill),
                    background: "rgba(0,0,0,0.40)",
                    border: "none",
                    cursor: canClose ? "pointer" : "not-allowed",
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "center",
                    opacity: canClose ? 1 : 0.4,
                    transition: `opacity ${css(V.easeFast)}`,
                }}
            >
                <X size={11} style={{ color: css(V.onDark1) }} />
            </button>

            {/* Image */}
            {imgUrl && (
                <div className="relative overflow-hidden" style={{ aspectRatio: "4/3" }}>
                    {visible && !imgError && (
                        <img
                            src={imgUrl}
                            alt={ad.creative?.alt ?? ad.title}
                            className="w-full h-full object-cover"
                            loading="lazy"
                            onLoad={onImgLoad}
                            onError={onImgError}
                            style={{ opacity: loaded ? 1 : 0, transition: `opacity ${css(V.easeImage)}` }}
                        />
                    )}
                    <div className="absolute inset-0" style={{ background: css(V.overlayDark) }} />
                </div>
            )}

            {/* Content */}
            <div style={{ padding: imgUrl ? 16 : "40px 16px 16px" }}>
                <div className="flex items-center justify-between mb-2">
                    <span className="uppercase" style={{ fontSize: 8, letterSpacing: css(V.labelSpacing), color: css(V.onDarkLabel), fontFamily: css(V.fontBody) }}>
                        {ad.sponsorName ?? "Sponsored"}
                    </span>
                    {ad.verified && (
                        <span className="flex items-center gap-0.5" style={{ fontSize: 8, color: css(V.onDarkLabel), fontFamily: css(V.fontBody) }}>
                            <ShieldCheck size={8} style={{ color: css(V.success) }} /> Verified
                        </span>
                    )}
                </div>

                <h4 className="mb-1.5" style={{ fontSize: 14, fontWeight: 600, color: css(V.onDark1), fontFamily: css(V.fontBody), lineHeight: 1.3 }}>
                    {ad.title}
                </h4>
                {ad.description && (
                    <p className="mb-4 line-clamp-2" style={{ fontSize: 11, color: css(V.onDark3), fontFamily: css(V.fontBody), lineHeight: 1.5 }}>
                        {ad.description}
                    </p>
                )}

                <a
                    href={ad.ctaUrl}
                    onClick={onClick}
                    target="_blank"
                    rel="noopener noreferrer nofollow"
                    className="flex items-center justify-center gap-1.5 w-full"
                    style={{
                        height: 40,
                        background: css(V.accent),
                        color: css(V.accentFg),
                        fontSize: 12,
                        fontWeight: 700,
                        fontFamily: css(V.fontBody),
                        textDecoration: "none",
                        borderRadius: css(V.rMd),
                        transition: `background ${css(V.easeFast)}`,
                    }}
                >
                    {ad.ctaText} <ArrowUpRight size={12} />
                </a>
            </div>
        </div>
    );
}
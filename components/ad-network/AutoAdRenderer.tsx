"use client";

import type { CSSProperties, ReactNode } from "react";
import {
  ArrowRight,
  Mail,
  Newspaper,
  Package2,
  Play,
  ShieldCheck,
  Sparkles,
  Tv,
} from "lucide-react";
import type { Ad, AdLayout } from "./core/types";
import { layoutRegistry } from "@/lib/ad-orchestration/LayoutRegistry";
import { V, css, resolveTextScheme, schemeTokens } from "./Adtokens";

interface AutoAdRendererProps {
  ad: Ad;
  layout: AdLayout;
}

function uniq(values: string[]): string[] {
  return Array.from(new Set(values));
}

function getMediaUrl(ad: Ad): string | undefined {
  return ad.creative?.imageUrl ?? ad.creative?.imageUrlWebp ?? ad.imageUrl ?? ad.creative?.videoPosterUrl;
}

function getThumbnailUrls(ad: Ad): string[] {
  const lead = getMediaUrl(ad);
  return uniq([lead, ...(ad.creative?.thumbnails ?? [])].filter((value): value is string => Boolean(value)));
}

function placeholderIcon(layout: AdLayout) {
  const spec = layoutRegistry.getSpec(layout);
  switch (spec.family) {
    case "commerce":
      return <Package2 size={34} />;
    case "email":
      return <Mail size={34} />;
    case "video":
      return <Play size={34} />;
    case "story":
      return <Sparkles size={34} />;
    case "display":
    case "sidebar":
      return <Tv size={34} />;
    default:
      return <Newspaper size={34} />;
  }
}

function clickProps(ad: Ad) {
  return {
    href: ad.ctaUrl,
    target: "_blank",
    rel: "noopener noreferrer nofollow sponsored",
  };
}

function CTAButton({
  ad,
  accent,
  foreground,
  fullWidth = false,
  compact = false,
}: {
  ad: Ad;
  accent: string;
  foreground: string;
  fullWidth?: boolean;
  compact?: boolean;
}) {
  const ghost = accent === "transparent";
  return (
    <a
      {...clickProps(ad)}
      style={{
        display: "inline-flex",
        alignItems: "center",
        justifyContent: "center",
        gap: 8,
        width: fullWidth ? "100%" : undefined,
        minWidth: compact ? 118 : 138,
        height: compact ? 36 : 40,
        padding: compact ? "0 13px" : "0 18px",
        borderRadius: css(V.rMd),
        background: ghost ? "rgba(255,255,255,0.03)" : accent,
        color: foreground,
        border: ghost ? "1px solid rgba(255,255,255,0.12)" : "1px solid rgba(255,255,255,0.08)",
        fontFamily: css(V.fontBody),
        fontSize: compact ? 10.5 : 11,
        fontWeight: 700,
        letterSpacing: "0.11em",
        textDecoration: "none",
        textTransform: "uppercase",
        boxShadow: ghost ? "none" : css(V.shadowCta),
        transition: css(V.easeBase),
      }}
    >
      <span>{ad.ctaText || "Explore"}</span>
      <ArrowRight size={13} />
    </a>
  );
}

function LabelChip({ text }: { text: string }) {
  return (
    <span
      style={{
        display: "inline-flex",
        alignItems: "center",
        justifyContent: "center",
        minWidth: 28,
        height: 18,
        padding: "0 7px",
        borderRadius: css(V.rSm),
        border: "1px solid rgba(255,255,255,0.08)",
        background: "rgba(255,255,255,0.04)",
        color: "rgba(255,255,255,0.72)",
        fontFamily: css(V.fontBody),
        fontSize: 8,
        fontWeight: 700,
        letterSpacing: "0.14em",
        textTransform: "uppercase",
      }}
    >
      {text}
    </span>
  );
}

function MediaSurface({
  ad,
  layout,
  aspectRatio,
  minHeight,
  borderRight,
}: {
  ad: Ad;
  layout: AdLayout;
  aspectRatio?: string;
  minHeight?: number;
  borderRight?: boolean;
}) {
  const mediaUrl = getMediaUrl(ad);
  const accent = ad.creative?.accentColor ?? css(V.accent);

  return (
    <div
      style={{
        position: "relative",
        overflow: "hidden",
        minHeight,
        aspectRatio,
        background: ad.creative?.bgGradient ?? "linear-gradient(135deg, #d7ebe5 0%, #c0d8d1 100%)",
        borderRight: borderRight ? "1px solid rgba(255,255,255,0.08)" : undefined,
      }}
    >
      {mediaUrl ? (
        <img
          src={mediaUrl}
          alt={ad.creative?.alt ?? ad.title}
          loading="lazy"
          decoding="async"
          style={{ width: "100%", height: "100%", objectFit: "cover" }}
        />
      ) : (
        <div
          style={{
            width: "100%",
            height: "100%",
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            color: "rgba(31, 41, 55, 0.4)",
          }}
        >
          {placeholderIcon(layout)}
        </div>
      )}
      <div
        style={{
          position: "absolute",
          inset: 0,
          boxShadow: `inset 0 -1px 0 rgba(255,255,255,0.08), inset -1px 0 0 rgba(255,255,255,0.06)`,
          borderBottom: `1px solid ${accent}22`,
          pointerEvents: "none",
        }}
      />
    </div>
  );
}

function SponsorRow({ ad, muted }: { ad: Ad; muted: string }) {
  return (
    <div style={{ display: "flex", alignItems: "center", gap: 8, flexWrap: "wrap" }}>
      <LabelChip text="Ad" />
      <span
        style={{
          color: muted,
          fontFamily: css(V.fontBody),
          fontSize: 9,
          fontWeight: 700,
          letterSpacing: "0.18em",
          textTransform: "uppercase",
        }}
      >
        {ad.sponsorName ?? "Navdhya"}
      </span>
      {ad.eyebrow && (
        <span
          style={{
            color: muted,
            fontFamily: css(V.fontBody),
            fontSize: 10,
            fontWeight: 500,
            letterSpacing: "0.04em",
          }}
        >
          {ad.eyebrow}
        </span>
      )}
      {ad.verified && (
        <span style={{ display: "inline-flex", alignItems: "center", gap: 4, color: css(V.success), fontSize: 10 }}>
          <ShieldCheck size={11} />
          <span style={{ fontFamily: css(V.fontBody), fontWeight: 600 }}>Verified</span>
        </span>
      )}
    </div>
  );
}

function TextBlock({
  ad,
  heading,
  body,
}: {
  ad: Ad;
  heading: string;
  body: string;
}) {
  return (
    <>
      <h3
        style={{
          margin: 0,
          color: heading,
          fontFamily: css(V.fontDisplay),
          fontSize: "clamp(1.05rem, 1.7vw, 1.42rem)",
          lineHeight: 1.2,
          fontWeight: 700,
        }}
      >
        {ad.title}
      </h3>
      {ad.description && (
        <p
          style={{
            margin: 0,
            color: body,
            fontFamily: css(V.fontBody),
            fontSize: 13,
            lineHeight: 1.55,
          }}
        >
          {ad.description}
        </p>
      )}
    </>
  );
}

function DarkPanel({
  children,
  accent,
  minHeight,
  style,
}: {
  children: ReactNode;
  accent: string;
  minHeight?: number;
  style?: CSSProperties;
}) {
  return (
    <div
      style={{
        width: "100%",
        minHeight,
        overflow: "hidden",
        borderRadius: css(V.rLg),
        border: "1px solid rgba(255,255,255,0.06)",
        background: `linear-gradient(180deg, rgba(255,255,255,0.018) 0%, rgba(255,255,255,0.01) 100%), ${css(V.bgCardDark)}`,
        boxShadow: css(V.shadowCard),
        backdropFilter: "blur(10px)",
        position: "relative",
        ...style,
      }}
    >
      <div
        style={{
          position: "absolute",
          inset: 0,
          borderRadius: css(V.rLg),
          boxShadow: "inset 0 1px 0 rgba(255,255,255,0.05)",
          pointerEvents: "none",
        }}
      />
      {children}
      <div style={{ height: 1, background: accent, opacity: 0.82 }} />
    </div>
  );
}

function TickerLayout({ ad, layout }: AutoAdRendererProps) {
  const accent = ad.creative?.accentColor ?? css(V.accent);

  return (
    <DarkPanel accent={accent} minHeight={76}>
      <div
        style={{
          display: "flex",
          alignItems: "center",
          justifyContent: "space-between",
          gap: 16,
          padding: "14px 16px",
          flexWrap: "wrap",
        }}
      >
        <div style={{ display: "flex", flexDirection: "column", gap: 8, minWidth: 0, flex: "1 1 240px" }}>
          <SponsorRow ad={ad} muted="rgba(255,255,255,0.62)" />
          <div style={{ minWidth: 0 }}>
            <div style={{ color: "rgba(255,255,255,0.94)", fontFamily: css(V.fontBody), fontSize: 13.5, fontWeight: 700, lineHeight: 1.4, whiteSpace: "normal", display: "-webkit-box", WebkitLineClamp: 2, WebkitBoxOrient: "vertical", overflow: "hidden" }}>
              {ad.title}
            </div>
          </div>
        </div>
        <div style={{ display: "flex", alignItems: "center", flex: "1 1 auto", justifyContent: "flex-end" }}>
          <CTAButton ad={ad} accent="transparent" foreground="rgba(255,255,255,0.96)" compact />
        </div>
      </div>
    </DarkPanel>
  );
}

function SplitLayout({ ad, layout }: AutoAdRendererProps) {
  const spec = layoutRegistry.getSpec(layout);
  const scheme = resolveTextScheme(ad.creative?.textColor, ad.creative?.bgColor);
  const palette = schemeTokens(scheme);
  const accent = ad.creative?.accentColor ?? css(V.accent);

  return (
    <DarkPanel accent={accent} minHeight={spec.reservedSize.height}>
      <div
        style={{
          display: "grid",
          gridTemplateColumns: getMediaUrl(ad) || spec.template === "split" ? "minmax(132px, 30%) 1fr" : "1fr",
          minHeight: spec.reservedSize.height,
        }}
      >
        {(getMediaUrl(ad) || spec.template === "split") && (
          <MediaSurface
            ad={ad}
            layout={layout}
            minHeight={spec.reservedSize.height}
            borderRight
          />
        )}
        <div
          style={{
            display: "flex",
            flexDirection: "column",
            justifyContent: "space-between",
            gap: 14,
            padding: 20,
          }}
        >
          <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
            <SponsorRow ad={ad} muted={palette.label} />
            {ad.badge && (
              <span
                style={{
                  display: "inline-flex",
                  alignItems: "center",
                  alignSelf: "flex-start",
                  height: 24,
                  padding: "0 10px",
                  borderRadius: css(V.rPill),
                  background: "rgba(255,255,255,0.05)",
                  color: palette.label,
                  fontFamily: css(V.fontBody),
                  fontSize: 9,
                  fontWeight: 700,
                  letterSpacing: "0.12em",
                  textTransform: "uppercase",
                }}
              >
                {ad.badge}
              </span>
            )}
            <TextBlock ad={ad} heading={palette.heading} body={palette.body} />
          </div>
          <div style={{ display: "flex", gap: 12, flexWrap: "wrap", alignItems: "center", justifyContent: "space-between" }}>
            <CTAButton ad={ad} accent={palette.ctaBg} foreground={palette.ctaFg} />
            <span style={{ color: palette.label, fontFamily: css(V.fontBody), fontSize: 11, lineHeight: 1.4 }}>
              {ad.description ? "Editorially matched offer" : "Context-aware sponsor unit"}
            </span>
          </div>
        </div>
      </div>
    </DarkPanel>
  );
}

function PosterLayout({ ad, layout }: AutoAdRendererProps) {
  const spec = layoutRegistry.getSpec(layout);
  const accent = ad.creative?.accentColor ?? css(V.accent);
  const panelHeight = spec.reservedSize.height;

  return (
    <DarkPanel accent={accent} minHeight={panelHeight}>
      <div style={{ display: "grid", gridTemplateRows: "minmax(128px, 50%) 1fr", minHeight: panelHeight }}>
        <MediaSurface ad={ad} layout={layout} minHeight={Math.max(130, Math.round(panelHeight * 0.48))} />
        <div style={{ display: "flex", flexDirection: "column", gap: 12, padding: 20 }}>
          <SponsorRow ad={ad} muted="rgba(255,255,255,0.72)" />
          {ad.badge && (
            <span style={{ color: "rgba(255,255,255,0.58)", fontFamily: css(V.fontBody), fontSize: 10, fontWeight: 700, letterSpacing: "0.14em", textTransform: "uppercase" }}>
              {ad.badge}
            </span>
          )}
          <TextBlock ad={ad} heading="rgba(255,255,255,0.98)" body="rgba(255,255,255,0.84)" />
          <CTAButton ad={ad} accent="transparent" foreground="rgba(255,255,255,0.96)" fullWidth />
        </div>
      </div>
    </DarkPanel>
  );
}

function RibbonLayout({ ad, layout }: AutoAdRendererProps) {
  const accent = ad.creative?.accentColor ?? css(V.accent);
  const mediaUrl = getMediaUrl(ad);

  return (
    <DarkPanel accent={accent} minHeight={140}>
      <div
        style={{
          display: "grid",
          gridTemplateColumns: mediaUrl ? "minmax(136px, 23%) 1fr auto" : "1fr auto",
          alignItems: "stretch",
          minHeight: 140,
        }}
      >
        {mediaUrl && <MediaSurface ad={ad} layout={layout} minHeight={140} borderRight />}
        <div style={{ display: "flex", flexDirection: "column", justifyContent: "center", gap: 8, padding: "16px 18px" }}>
          <SponsorRow ad={ad} muted="rgba(255,255,255,0.64)" />
          <div style={{ color: "rgba(255,255,255,0.98)", fontFamily: css(V.fontDisplay), fontSize: "clamp(0.98rem, 1.4vw, 1.22rem)", fontWeight: 700, lineHeight: 1.15 }}>
            {ad.title}
          </div>
          {ad.description && (
            <div style={{ color: "rgba(255,255,255,0.7)", fontFamily: css(V.fontBody), fontSize: 12.5, lineHeight: 1.45 }}>
              {ad.description}
            </div>
          )}
        </div>
        <div style={{ display: "flex", alignItems: "center", justifyContent: "center", padding: 18 }}>
          <CTAButton ad={ad} accent={accent} foreground={css(V.accentFg)} compact />
        </div>
      </div>
    </DarkPanel>
  );
}

function StoryLayout({ ad, layout }: AutoAdRendererProps) {
  const accent = ad.creative?.accentColor ?? css(V.accent);

  return (
    <div
      style={{
        position: "relative",
        width: "100%",
        minHeight: 460,
        overflow: "hidden",
        borderRadius: css(V.rXl),
        background: "linear-gradient(180deg, rgba(30,41,59,1) 0%, rgba(15,23,42,1) 100%)",
        boxShadow: css(V.shadowStory),
      }}
    >
      <MediaSurface ad={ad} layout={layout} minHeight={460} />
      <div style={{ position: "absolute", inset: 0, background: css(V.overlayStory) }} />
      <div style={{ position: "absolute", inset: 0, padding: 18, display: "flex", flexDirection: "column", justifyContent: "space-between" }}>
        <SponsorRow ad={ad} muted="rgba(255,255,255,0.7)" />
        <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
          {ad.eyebrow && (
            <span style={{ color: "rgba(255,255,255,0.72)", fontFamily: css(V.fontBody), fontSize: 10, letterSpacing: "0.16em", textTransform: "uppercase" }}>
              {ad.eyebrow}
            </span>
          )}
          <TextBlock ad={ad} heading="rgba(255,255,255,0.98)" body="rgba(255,255,255,0.82)" />
          <CTAButton ad={ad} accent={accent} foreground={css(V.accentFg)} />
        </div>
      </div>
    </div>
  );
}

function HeroLayout({ ad, layout }: AutoAdRendererProps) {
  const accent = ad.creative?.accentColor ?? css(V.accent);

  return (
    <div
      style={{
        position: "relative",
        width: "100%",
        minHeight: 360,
        overflow: "hidden",
        borderRadius: css(V.rXl),
        background: ad.creative?.bgGradient ?? "linear-gradient(135deg, #113128 0%, #06120d 100%)",
        boxShadow: css(V.shadowFloat),
      }}
    >
      <MediaSurface ad={ad} layout={layout} minHeight={360} />
      <div style={{ position: "absolute", inset: 0, background: css(V.overlayHero) }} />
      <div style={{ position: "absolute", inset: 0, padding: "24px 24px 28px", display: "flex", flexDirection: "column", justifyContent: "space-between" }}>
        <SponsorRow ad={ad} muted="rgba(255,255,255,0.72)" />
        <div style={{ maxWidth: 620, display: "flex", flexDirection: "column", gap: 12 }}>
          <TextBlock ad={ad} heading="rgba(255,255,255,0.99)" body="rgba(255,255,255,0.82)" />
          <div style={{ display: "flex", gap: 12, flexWrap: "wrap", alignItems: "center" }}>
            <CTAButton ad={ad} accent={accent} foreground={css(V.accentFg)} />
            {ad.badge && (
              <span style={{ color: "rgba(255,255,255,0.78)", fontFamily: css(V.fontBody), fontSize: 11, fontWeight: 700, letterSpacing: "0.14em", textTransform: "uppercase" }}>
                {ad.badge}
              </span>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

function VideoLayout({ ad, layout }: AutoAdRendererProps) {
  const accent = ad.creative?.accentColor ?? css(V.accent);

  return (
    <DarkPanel accent={accent} minHeight={320}>
      <div style={{ position: "relative" }}>
        <MediaSurface ad={ad} layout={layout} minHeight={220} />
        <div
          style={{
            position: "absolute",
            inset: 0,
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            pointerEvents: "none",
          }}
        >
          <div
            style={{
              width: 68,
              height: 68,
              borderRadius: "50%",
              background: "rgba(15,23,42,0.72)",
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              color: "#fff",
            }}
          >
            <Play size={28} fill="currentColor" />
          </div>
        </div>
      </div>
      <div style={{ padding: 16, display: "flex", flexDirection: "column", gap: 10 }}>
        <SponsorRow ad={ad} muted="rgba(255,255,255,0.72)" />
        <TextBlock ad={ad} heading="rgba(255,255,255,0.98)" body="rgba(255,255,255,0.8)" />
        <CTAButton ad={ad} accent={accent} foreground={css(V.accentFg)} />
      </div>
    </DarkPanel>
  );
}

function CarouselLayout({ ad, layout }: AutoAdRendererProps) {
  const accent = ad.creative?.accentColor ?? css(V.accent);
  const thumbnails = getThumbnailUrls(ad).slice(0, 4);

  return (
    <DarkPanel accent={accent} minHeight={280}>
      <div style={{ display: "flex", flexDirection: "column", gap: 0 }}>
        <div style={{ padding: 18, display: "flex", flexDirection: "column", gap: 12 }}>
          <SponsorRow ad={ad} muted="rgba(255,255,255,0.72)" />
          <TextBlock ad={ad} heading="rgba(255,255,255,0.98)" body="rgba(255,255,255,0.8)" />
        </div>
        <div style={{ display: "grid", gridTemplateColumns: `repeat(${Math.max(1, thumbnails.length || 3)}, minmax(0, 1fr))`, gap: 6, padding: "0 18px", background: "transparent" }}>
          {(thumbnails.length > 0 ? thumbnails : ["placeholder-1", "placeholder-2", "placeholder-3"]).map((thumbnail, index) => (
            <div key={`${thumbnail}-${index}`} style={{ minHeight: 116, overflow: "hidden", borderRadius: css(V.rMd) }}>
              {thumbnail.startsWith("placeholder-") ? (
                <MediaSurface ad={ad} layout={layout} minHeight={116} />
              ) : (
                <img
                  src={thumbnail}
                  alt={`${ad.title} frame ${index + 1}`}
                  loading="lazy"
                  decoding="async"
                  style={{ width: "100%", height: "100%", minHeight: 116, objectFit: "cover" }}
                />
              )}
            </div>
          ))}
        </div>
        <div style={{ padding: 18 }}>
          <CTAButton ad={ad} accent={accent} foreground={css(V.accentFg)} fullWidth />
        </div>
      </div>
    </DarkPanel>
  );
}

function InteractiveLayout({ ad, layout }: AutoAdRendererProps) {
  const accent = ad.creative?.accentColor ?? css(V.accent);

  return (
    <DarkPanel accent={accent} minHeight={300}>
      <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", minHeight: 300 }}>
        <div style={{ padding: 20, display: "flex", flexDirection: "column", gap: 12, justifyContent: "space-between" }}>
          <div style={{ display: "flex", flexDirection: "column", gap: 10 }}>
            <SponsorRow ad={ad} muted="rgba(255,255,255,0.72)" />
            <TextBlock ad={ad} heading="rgba(255,255,255,0.98)" body="rgba(255,255,255,0.8)" />
            <div style={{ display: "flex", gap: 8, flexWrap: "wrap" }}>
              {["Verified", "Flexible", "Responsive"].map((chip) => (
                <span
                  key={chip}
                  style={{
                    display: "inline-flex",
                    alignItems: "center",
                    height: 28,
                    padding: "0 10px",
                    borderRadius: css(V.rPill),
                    background: "rgba(255,255,255,0.08)",
                    color: "rgba(255,255,255,0.82)",
                    fontFamily: css(V.fontBody),
                    fontSize: 11,
                    fontWeight: 600,
                  }}
                >
                  {chip}
                </span>
              ))}
            </div>
          </div>
          <CTAButton ad={ad} accent={accent} foreground={css(V.accentFg)} />
        </div>
        <div style={{ display: "flex", alignItems: "center", justifyContent: "center", padding: 20, background: "linear-gradient(135deg, rgba(15,139,109,0.14), rgba(255,255,255,0.02))" }}>
          <div
            style={{
              width: "100%",
              maxWidth: 220,
              padding: 14,
              borderRadius: css(V.rMd),
              border: "1px solid rgba(255,255,255,0.08)",
              background: "rgba(15,23,42,0.48)",
            }}
          >
            <div style={{ height: 8, borderRadius: css(V.rPill), background: "rgba(255,255,255,0.08)", marginBottom: 10 }} />
            <div style={{ height: 8, width: "72%", borderRadius: css(V.rPill), background: accent, marginBottom: 18 }} />
            <div style={{ display: "grid", gap: 8 }}>
              {[1, 2, 3].map((item) => (
                <div key={item} style={{ height: 36, borderRadius: css(V.rSm), background: "rgba(255,255,255,0.06)" }} />
              ))}
            </div>
          </div>
        </div>
      </div>
    </DarkPanel>
  );
}

export function AutoAdRenderer({ ad, layout }: AutoAdRendererProps) {
  const spec = layoutRegistry.getSpec(layout);

  if (spec.template === "hero") return <HeroLayout ad={ad} layout={layout} />;
  if (spec.template === "ticker") return <TickerLayout ad={ad} layout={layout} />;
  if (spec.template === "ribbon") return <RibbonLayout ad={ad} layout={layout} />;
  if (spec.template === "story") return <StoryLayout ad={ad} layout={layout} />;
  if (spec.template === "video") return <VideoLayout ad={ad} layout={layout} />;
  if (spec.template === "carousel") return <CarouselLayout ad={ad} layout={layout} />;
  if (spec.template === "interactive") return <InteractiveLayout ad={ad} layout={layout} />;
  if (spec.template === "split") return <SplitLayout ad={ad} layout={layout} />;
  return <PosterLayout ad={ad} layout={layout} />;
}

export default AutoAdRenderer;

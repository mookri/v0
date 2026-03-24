//components/seo/ArticlePage.tsx
"use client";

import {
  useEffect,
  useState,
  useRef,
  useMemo,
  useCallback,
  Component,
  type ErrorInfo,
  type ReactNode,
} from "react";
import { ArrowRight, ArrowUp } from "lucide-react";
import type { SEOPage, ClusterMeta } from "@/lib/content/types";
import { BreadcrumbNav } from "./BreadcrumbNav";
import { SchemaScript } from "./SchemaScript";
import { InternalLinks } from "./InternalLinks";
import { generateSchemas } from "@/lib/schema/generator";
import type { InternalLink } from "@/lib/linking/graph";
import { CORE_ENTITY_LINKS } from "@/lib/seo/entity";
import HeroImage from "./HeroImage";
import dynamic from "next/dynamic";
import { getHeroImage, resolveCategory } from "@/lib/Heroimagepool";

/*
 * AdSlot must be loaded with { ssr: false }.
 * The ad-network module calls `new Image()` inside AdLoader at module
 * evaluation time. Next.js SSR-renders client components to produce initial
 * HTML, so the server evaluates this module and crashes with:
 *   "ReferenceError: Image is not defined"
 * Wrapping in dynamic() with ssr:false prevents the module from ever being
 * evaluated on the server. Ad slots render null during SSR and fill in on
 * the client after hydration — no crash, no layout shift.
 */
const AdSlot = dynamic(
  () => import("@/components/ad-network/AdSlot"),
  { ssr: false, loading: () => null },
);

// ---------------------------------------------------------------------------
// Error Boundary — prevents a JS error inside the article from wiping the page
// ---------------------------------------------------------------------------

interface ErrorBoundaryState { hasError: boolean; message: string }

class ArticleErrorBoundary extends Component<
  { children: ReactNode; fallback?: ReactNode },
  ErrorBoundaryState
> {
  state: ErrorBoundaryState = { hasError: false, message: "" };

  static getDerivedStateFromError(err: Error): ErrorBoundaryState {
    return { hasError: true, message: err.message };
  }

  componentDidCatch(err: Error, info: ErrorInfo) {
    console.error("[ArticlePage] Render error:", err, info);
  }

  render() {
    if (this.state.hasError) {
      return this.props.fallback ?? (
        <div className="p-10 text-center text-muted-foreground text-sm">
          <p>Something went wrong loading this article.</p>
          <button
            onClick={() => this.setState({ hasError: false, message: "" })}
            className="mt-4 text-[#0E3F2E] underline"
          >
            Try again
          </button>
        </div>
      );
    }
    return this.props.children;
  }
}

// ---------------------------------------------------------------------------
// Content processing
// ---------------------------------------------------------------------------

type ContentNode =
  | { type: "heading"; html: string }
  | { type: "paragraph"; html: string };

type TocItem = { level: number; text: string; id: string };

function processContent(html: string): {
  nodes: ContentNode[];
  toc: TocItem[];
} {
  const toc: TocItem[] = [];

  // Inject id attributes into h2/h3 and collect TOC entries
  const withIds = html.replace(
    /<h([23])([^>]*)>([\s\S]*?)<\/h\1>/gi,
    (_, level, attrs, inner) => {
      const text = inner.replace(/<[^>]+>/g, "").trim();
      const id = text
        .toLowerCase()
        .replace(/[^\w\s-]/g, "")
        .replace(/\s+/g, "-")
        .slice(0, 60);
      toc.push({ level: parseInt(level, 10), text, id });
      return `<h${level}${attrs} id="${id}">${inner}</h${level}>`;
    },
  );

  const nodes: ContentNode[] = [];
  const tokenRe = /(<h[23][^>]*>[\s\S]*?<\/h[23]>)|(<p[\s\S]*?<\/p>)/gi;
  let match: RegExpExecArray | null;

  while ((match = tokenRe.exec(withIds)) !== null) {
    const [, headingHtml, paraHtml] = match;
    if (headingHtml) {
      nodes.push({ type: "heading", html: headingHtml });
    } else if (paraHtml && paraHtml.replace(/<[^>]+>/g, "").trim()) {
      nodes.push({ type: "paragraph", html: paraHtml });
    }
  }

  return { nodes, toc };
}

// ---------------------------------------------------------------------------
// Ad placement
// ---------------------------------------------------------------------------

function resolveAdIndices(nodes: ContentNode[]): {
  first: number;
  second: number;
} {
  const paraIndices = nodes.reduce<number[]>((acc, node, i) => {
    if (node.type === "paragraph") acc.push(i);
    return acc;
  }, []);

  const total = paraIndices.length;
  if (total < 3) return { first: -1, second: -1 };

  if (total <= 5) {
    return {
      first: paraIndices[1],
      second: paraIndices[total - 1],
    };
  }

  return {
    first: paraIndices[Math.floor(total / 3)],
    second: paraIndices[Math.floor((2 * total) / 3)],
  };
}

// ---------------------------------------------------------------------------
// findLast polyfill
// BUG FIX: Array.prototype.findLast is ES2023 and may not be available in
// older Node/browser targets. Use a manual right-scan instead.
// ---------------------------------------------------------------------------
function findLast<T>(arr: T[], predicate: (item: T) => boolean): T | undefined {
  for (let i = arr.length - 1; i >= 0; i--) {
    if (predicate(arr[i])) return arr[i];
  }
  return undefined;
}

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

interface ArticlePageProps {
  page: SEOPage;
  clusterMeta: ClusterMeta;
  internalLinks: {
    related: InternalLink[];
    core: typeof CORE_ENTITY_LINKS[number][];
  };
  onBookRitual?: () => void;
}

function ArticlePageInner({
  page,
  clusterMeta,
  internalLinks,
  onBookRitual,
}: ArticlePageProps) {
  // generateSchemas already handles noindex guard and null filtering internally
  const schemas = useMemo(
    () => generateSchemas(page, clusterMeta.basePath, clusterMeta.label),
    [page, clusterMeta],
  );

  const [progress, setProgress] = useState(0);
  const [showTop, setShowTop] = useState(false);
  const [activeId, setActiveId] = useState("");
  const bodyRef = useRef<HTMLDivElement>(null);

  const { nodes, toc } = useMemo(() => processContent(page.content), [page.content]);
  const { first: firstAdIndex, second: secondAdIndex } = useMemo(
    () => resolveAdIndices(nodes),
    [nodes],
  );

  // ── Scroll tracking ─────────────────────────────────────────────────────
  useEffect(() => {
    let ticking = false;

    const update = () => {
      const el = bodyRef.current;
      if (el) {
        const rect = el.getBoundingClientRect();
        const pct =
          ((window.innerHeight - rect.top) / (rect.height + window.innerHeight)) * 100;
        setProgress(Math.min(100, Math.max(0, pct)));
      }

      setShowTop(window.scrollY > 600);

      if (toc.length) {
        const headings = toc
          .map((t) => document.getElementById(t.id))
          .filter((el): el is HTMLElement => el !== null);

        // BUG FIX: replaced Array.findLast (ES2023) with our own findLast helper
        const current = findLast(headings, (h) => h.getBoundingClientRect().top <= 120);
        if (current) setActiveId(current.id);
      }

      ticking = false;
    };

    const onScroll = () => {
      if (!ticking) {
        requestAnimationFrame(update);
        ticking = true;
      }
    };

    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, [toc]);

  // ── CTA handler ─────────────────────────────────────────────────────────
  const handleBook = useCallback(() => {
    if (onBookRitual) {
      onBookRitual();
      return;
    }
    /*
     * BUG FIX: page.title was embedded directly in the URL without encoding.
     * If the title contains &, #, or non-ASCII chars the URL was malformed.
     * encodeURIComponent ensures safe encoding.
     */
    const message = encodeURIComponent(`Book ${page.title}`);
    window.open(`https://wa.me/?text=${message}`, "_blank", "noopener,noreferrer");
  }, [onBookRitual, page.title]);

  const publishedDate = page.lastUpdated
    ? new Date(page.lastUpdated).toLocaleDateString("en-IN", {
      year: "numeric",
      month: "long",
      day: "numeric",
    })
    : "";

  return (
    /*
     * BUG FIX: removed the duplicate <AdEngineProvider engine={engine}> wrapper
     * that was here. The engine is now provided once by AdProvider in app/providers.tsx.
     * Wrapping again here created two nested providers with competing engine instances.
     */
    <>
      <SchemaScript schemas={schemas} />

      {/* Reading progress bar */}
      <div
        aria-hidden="true"
        className="fixed top-0 left-0 h-[2px] bg-[#0E3F2E] z-50 transition-[width] duration-100"
        style={{ width: `${progress}%` }}
      />

      {/* ── Sticky header ─────────────────────────────────────────────── */}
      <header className="sticky top-0 z-40 bg-[#0E3F2E] border-b border-white/10 backdrop-blur-md">
        <div className="max-w-[1400px] mx-auto px-6 h-14 flex items-center justify-between text-white">
          <div className="flex items-center min-w-0">
            <div className="max-w-[180px]">
              <BreadcrumbNav
                clusterLabel={clusterMeta.label}
                basePath={clusterMeta.basePath}
                pageTitle={page.title}
                isDark
              />
            </div>
          </div>

          <button
            onClick={handleBook}
            aria-label={`Book ${page.title}`}
            className="relative group h-9 px-6 rounded-md flex items-center gap-3 text-[12px] uppercase tracking-[0.28em] font-semibold text-[#0E3F2E] bg-white border border-black/10 shadow-[0_1px_2px_rgba(0,0,0,0.06),0_3px_8px_rgba(0,0,0,0.10)] hover:shadow-[0_2px_4px_rgba(0,0,0,0.10),0_6px_16px_rgba(0,0,0,0.16)] transition-all duration-200 ease-[cubic-bezier(0.16,1,0.3,1)] active:translate-y-[1px] active:shadow-[0_1px_2px_rgba(0,0,0,0.08)] whitespace-nowrap"
          >
            <span
              aria-hidden="true"
              className="absolute inset-0 pointer-events-none opacity-50"
              style={{ background: "linear-gradient(to bottom, rgba(255,255,255,0.85), rgba(255,255,255,0))" }}
            />
            <span
              aria-hidden="true"
              className="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"
              style={{ background: "linear-gradient(110deg, transparent 40%, rgba(255,255,255,0.5), transparent 70%)" }}
            />
            <span className="relative z-10 flex items-center gap-2">
              <span>Book Puja</span>
              <ArrowRight size={14} aria-hidden="true" className="transition-transform duration-200 group-hover:translate-x-1" />
            </span>
          </button>
        </div>
      </header>

      {/* ── Hero section ──────────────────────────────────────────────── */}
      <section className="relative bg-[#0E3F2E] text-white border-b border-white/10 overflow-hidden">
        <div aria-hidden="true" className="absolute inset-0 bg-gradient-to-b from-transparent via-black/10 to-black/20" />
        <div className="relative max-w-[1200px] mx-auto px-6 py-14">
          <span className="text-[11px] opacity-70 uppercase tracking-[0.25em]" aria-label={`Category: ${clusterMeta.label}`}>
            {clusterMeta.label}
          </span>
          <h1 className="mt-4 text-[clamp(2.4rem,6vw,4.2rem)] font-serif leading-[1.02] tracking-tight">
            {page.title}
          </h1>
          {page.metaDescription && (
            <p className="mt-6 max-w-[720px] text-white/80 text-[16px] leading-relaxed">
              {page.metaDescription}
            </p>
          )}
          <div className="mt-6 text-xs opacity-70 flex items-center gap-3">
            <time dateTime={page.lastUpdated}>{publishedDate}</time>
            <span aria-hidden="true">•</span>
            <span>{page.readingTime} min read</span>
          </div>
        </div>
      </section>

      {/* ── Body ──────────────────────────────────────────────────────── */}
      <div className="max-w-[1400px] mx-auto px-6 py-20 grid lg:grid-cols-[1fr_300px] gap-20">

        {/* Main content column */}
        <main ref={bodyRef} className="max-w-[760px]" id="article-content">

          {/* Hero image */}
          <div className="mb-16">
            <div className="relative overflow-hidden border border-border/10 bg-muted/20 shadow-[0_30px_80px_-20px_rgba(0,0,0,0.15)]">
              <div className="relative w-full h-[220px] sm:h-[300px] md:h-[380px] lg:h-[460px] xl:h-[520px]">
                {(() => {
                  const category = resolveCategory(page.secondaryKeywords || []);
                  const hero = page.heroImage 
                    ? { src: page.heroImage, blurDataURL: undefined }
                    : getHeroImage(page.slug, { category });
                  
                  return (
                    <HeroImage
                      src={hero.src}
                      alt={page.title}
                      blurDataURL={hero.blurDataURL}
                      priority
                    />
                  );
                })()}
              </div>
              <div aria-hidden="true" className="absolute inset-0 bg-gradient-to-b from-black/10 via-transparent to-transparent pointer-events-none" />
              <div aria-hidden="true" className="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent pointer-events-none" />
            </div>
          </div>

          {/* Content nodes + ads */}
          <div className="prose prose-lg max-w-none prose-p:text-[17px] prose-p:leading-[1.85]">
            {nodes.map((node, i) => (
              <div key={i}>
                <div dangerouslySetInnerHTML={{ __html: node.html }} />

                {i === firstAdIndex && (
                  <div className="not-prose my-8 sm:my-10 w-full overflow-hidden flex justify-center">
                    <AdSlot placement="article_inline_1" layout="native_in_content" />
                  </div>
                )}

                {i === secondAdIndex && (
                  <div className="not-prose my-10 sm:my-12 w-full overflow-hidden flex justify-center">
                    <AdSlot placement="article_inline_2" layout="inline_text" />
                  </div>
                )}
              </div>
            ))}
          </div>

          <InternalLinks related={internalLinks.related} core={internalLinks.core} />
        </main>

        {/* Sidebar */}
        <aside className="hidden lg:block" aria-label="Table of contents and sidebar">
          <div className="sticky top-24 space-y-10">

            {toc.length > 0 && (
              <nav aria-label="Table of contents" className="text-sm border-l border-border/20 pl-5">
                <p className="text-[10px] uppercase tracking-[0.18em] text-muted-foreground mb-3">
                  On this page
                </p>
                {toc.map((item) => (
                  <a
                    key={item.id}
                    href={`#${item.id}`}
                    className={`
                      block py-1.5 transition-colors
                      ${item.level === 3 ? "pl-3 text-[12px]" : ""}
                      ${activeId === item.id
                        ? "text-[#0E3F2E] font-medium"
                        : "text-muted-foreground hover:text-foreground"
                      }
                    `}
                  >
                    {item.text}
                  </a>
                ))}
              </nav>
            )}

            {/* Sidebar ad */}
            <AdSlot placement="sidebar_top" layout="medium_rectangle" />

            {/* Book CTA card */}
            <div className="p-6 border border-border/20 bg-muted/20 rounded-sm">
              <p className="text-sm font-medium">Book your ritual today</p>
              <p className="mt-1 text-xs text-muted-foreground">
                Verified priests, authentic ceremonies.
              </p>
              <button
                onClick={handleBook}
                className="mt-4 w-full h-11 bg-[#0E3F2E] text-white rounded-sm text-sm font-medium hover:bg-[#0a2e21] transition-colors"
              >
                Book Puja
              </button>
            </div>

          </div>
        </aside>
      </div>

      {/* Scroll-to-top */}
      {showTop && (
        <button
          onClick={() => window.scrollTo({ top: 0, behavior: "smooth" })}
          aria-label="Scroll to top"
          className="fixed bottom-6 right-6 w-10 h-10 bg-[#0E3F2E] text-white flex items-center justify-center rounded-full shadow-lg hover:bg-[#0a2e21] transition-colors z-40"
        >
          <ArrowUp size={16} aria-hidden="true" />
        </button>
      )}
    </>
  );
}

/**
 * ArticlePage — wrapped in an error boundary so a runtime error in the article
 * body never crashes the entire page layout.
 */
export function ArticlePage(props: ArticlePageProps) {
  return (
    <ArticleErrorBoundary>
      <ArticlePageInner {...props} />
    </ArticleErrorBoundary>
  );
}
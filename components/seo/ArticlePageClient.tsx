"use client";

import {
  useCallback,
  useEffect,
  useMemo,
  useRef,
  useState,
  Component,
  type ErrorInfo,
  type ReactNode,
} from "react";
import { ArrowRight, ArrowUp } from "lucide-react";
import type { SEOPage, ClusterMeta } from "@/lib/content/types";
import type { DecisionResponse, PlacementPlan } from "@/lib/ad-orchestration";
import type { InternalLink } from "@/lib/linking/graph";
import { BreadcrumbNav } from "./BreadcrumbNav";
import { InternalLinks } from "./InternalLinks";
import HeroImage from "./HeroImage";
import AdSlot from "@/components/ad-network/AdSlot";
import { AdRuntimeProvider, useAdRuntime } from "@/components/ad-network/runtime/AdRuntimeProvider";
import type { ArticleAdBaseRequest } from "./article-ad-experience";

type CoreEntityLink = (typeof import("@/lib/seo/entity").CORE_ENTITY_LINKS)[number];

interface ErrorBoundaryState {
  hasError: boolean;
  message: string;
}

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
        </div>
      );
    }
    return this.props.children;
  }
}

function findLast<T>(arr: T[], predicate: (item: T) => boolean): T | undefined {
  for (let index = arr.length - 1; index >= 0; index -= 1) {
    if (predicate(arr[index])) return arr[index];
  }
  return undefined;
}

function ArticleAdBootstrap({
  opportunities,
  prefetchedSlotIds = [],
}: {
  opportunities: PlacementPlan["opportunities"];
  prefetchedSlotIds?: readonly string[];
}) {
  const runtime = useAdRuntime();

  useEffect(() => {
    const prefetched = new Set(prefetchedSlotIds);
    runtime.prefetch(
      opportunities.filter(
        (opportunity) =>
          (!opportunity.lazy || opportunity.depthBucket === "above_fold") &&
          !prefetched.has(opportunity.slotId),
      ),
    );
  }, [opportunities, prefetchedSlotIds, runtime]);

  return null;
}

interface ArticlePageBodyProps {
  page: SEOPage;
  clusterMeta: ClusterMeta;
  internalLinks: {
    related: InternalLink[];
    core: readonly CoreEntityLink[];
  };
  contentPlan: PlacementPlan;
  prefetchedSlotIds?: readonly string[];
}

function ArticlePageBody({ page, clusterMeta, internalLinks, contentPlan, prefetchedSlotIds = [] }: ArticlePageBodyProps) {
  const [progress, setProgress] = useState(0);
  const [showTop, setShowTop] = useState(false);
  const [activeId, setActiveId] = useState("");
  const bodyRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    let ticking = false;

    const update = () => {
      const element = bodyRef.current;
      if (element) {
        const rect = element.getBoundingClientRect();
        const pct = ((window.innerHeight - rect.top) / (rect.height + window.innerHeight)) * 100;
        setProgress(Math.min(100, Math.max(0, pct)));
      }

      setShowTop(window.scrollY > 600);

      if (contentPlan.toc.length > 0) {
        const headings = contentPlan.toc
          .map((item) => document.getElementById(item.id))
          .filter((node): node is HTMLElement => Boolean(node));
        const current = findLast(headings, (heading) => heading.getBoundingClientRect().top <= 120);
        if (current) setActiveId(current.id);
      }

      ticking = false;
    };

    const onScroll = () => {
      if (!ticking) {
        ticking = true;
        requestAnimationFrame(update);
      }
    };

    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, [contentPlan.toc]);

  const handleBook = useCallback(() => {
    const message = encodeURIComponent(`Book ${page.title}`);
    window.open(`https://wa.me/?text=${message}`, "_blank", "noopener,noreferrer");
  }, [page.title]);

  const publishedDate = page.lastUpdated
    ? new Date(page.lastUpdated).toLocaleDateString("en-IN", {
      year: "numeric",
      month: "long",
      day: "numeric",
    })
    : "";

  const opportunitiesByAnchor = useMemo(() => {
    const grouped = new Map<string, PlacementPlan["opportunities"]>();
    for (const opportunity of contentPlan.opportunities.filter((entry) => entry.surface === "article")) {
      const list = grouped.get(opportunity.anchorId) ?? [];
      list.push(opportunity);
      grouped.set(opportunity.anchorId, list);
    }
    return grouped;
  }, [contentPlan.opportunities]);

  const sidebarOpportunities = contentPlan.opportunities.filter((opportunity) => opportunity.surface === "sidebar");

  return (
    <>
      <ArticleAdBootstrap opportunities={contentPlan.opportunities} prefetchedSlotIds={prefetchedSlotIds} />

      <div
        aria-hidden="true"
        className="fixed top-0 left-0 h-[2px] bg-[#0E3F2E] z-50 transition-[width] duration-100"
        style={{ width: `${progress}%` }}
      />

      <header className="sticky top-0 z-40 bg-[#0E3F2E] border-b border-white/10 backdrop-blur-md">
        <div className="max-w-[1400px] mx-auto px-4 sm:px-6 h-14 flex items-center justify-between text-white min-w-0">
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

      <section className="relative bg-[#0E3F2E] text-white border-b border-white/10 overflow-hidden">
        <div aria-hidden="true" className="absolute inset-0 bg-gradient-to-b from-transparent via-black/10 to-black/20" />
        <div className="relative max-w-[1200px] mx-auto px-4 sm:px-6 py-12 sm:py-14 min-w-0">
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
            <span aria-hidden="true">.</span>
            <span>{page.readingTime} min read</span>
          </div>
        </div>
      </section>

      <div className="max-w-[1400px] mx-auto px-4 sm:px-6 py-12 sm:py-20 grid lg:grid-cols-[1fr_300px] gap-12 lg:gap-20 overflow-x-clip">
        <main ref={bodyRef} className="max-w-[760px] w-full min-w-0" id="article-content">
          <div className="mb-16">
            <div className="relative w-full max-w-full overflow-hidden border border-border/10 bg-muted/20 shadow-[0_30px_80px_-20px_rgba(0,0,0,0.15)]">
              <div className="relative w-full h-[220px] sm:h-[300px] md:h-[380px] lg:h-[460px] xl:h-[520px]">
                <HeroImage
                  src={page.heroImage || "https://picsum.photos/1200/500"}
                  alt={page.title}
                  priority
                />
              </div>
              <div aria-hidden="true" className="absolute inset-0 bg-gradient-to-b from-black/10 via-transparent to-transparent pointer-events-none" />
              <div aria-hidden="true" className="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent pointer-events-none" />
            </div>
          </div>

          <div className="prose prose-lg max-w-none w-full min-w-0 overflow-x-hidden break-words prose-p:text-[17px] prose-p:leading-[1.85] [&_a]:break-words [&_h2]:break-words [&_h3]:break-words [&_img]:max-w-full [&_img]:h-auto [&_pre]:max-w-full [&_pre]:overflow-x-auto [&_pre]:whitespace-pre-wrap [&_table]:block [&_table]:max-w-full [&_table]:overflow-x-auto [&_td]:break-words [&_th]:break-words">
            {contentPlan.blocks.map((block) => (
              <div key={block.id} className="min-w-0">
                <div dangerouslySetInnerHTML={{ __html: block.html }} />
                {opportunitiesByAnchor.get(block.id)?.map((opportunity) => (
                  <div key={opportunity.slotId} className="not-prose my-8 sm:my-10 min-w-0">
                    <AdSlot opportunity={opportunity} />
                  </div>
                ))}
              </div>
            ))}
          </div>

          <InternalLinks related={internalLinks.related} core={internalLinks.core} />
        </main>

        <aside className="hidden lg:block min-w-0" aria-label="Table of contents and sidebar">
          <div className="sticky top-24 space-y-10">
            {contentPlan.toc.length > 0 && (
              <nav aria-label="Table of contents" className="text-sm border-l border-border/20 pl-5">
                <p className="text-[10px] uppercase tracking-[0.18em] text-muted-foreground mb-3">
                  On this page
                </p>
                {contentPlan.toc.map((item) => (
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

            {sidebarOpportunities.map((opportunity) => (
              <AdSlot key={opportunity.slotId} opportunity={opportunity} />
            ))}

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

interface ArticlePageClientProps extends ArticlePageBodyProps {
  initialDecisions?: readonly DecisionResponse[];
  baseRequest?: ArticleAdBaseRequest;
}

export function ArticlePageClient({
  page,
  clusterMeta,
  internalLinks,
  contentPlan,
  initialDecisions = [],
  baseRequest,
  prefetchedSlotIds = [],
}: ArticlePageClientProps) {
  const resolvedBaseRequest = useMemo(() => baseRequest ?? ({
    page: {
      title: page.title,
      keywords: [page.primaryKeyword, ...page.secondaryKeywords],
      categories: [page.cluster],
      language: "en-IN",
      url: `${clusterMeta.basePath}/${page.slug}`,
      scrollDepth: 0,
    },
    user: {
      sessionId: `article-${page.id}`,
    },
    client: {
      language: "en-IN",
      timezone: "Asia/Kolkata",
    },
    session: {
      id: `article-${page.id}`,
      engagementScore: 0.35,
      scrollDepth: 0,
    },
  }), [baseRequest, clusterMeta.basePath, page.cluster, page.id, page.primaryKeyword, page.secondaryKeywords, page.slug, page.title]);

  return (
    <ArticleErrorBoundary>
      <AdRuntimeProvider baseRequest={resolvedBaseRequest} initialDecisions={[...initialDecisions]}>
        <ArticlePageBody
          page={page}
          clusterMeta={clusterMeta}
          internalLinks={internalLinks}
          contentPlan={contentPlan}
          prefetchedSlotIds={prefetchedSlotIds}
        />
      </AdRuntimeProvider>
    </ArticleErrorBoundary>
  );
}

"use client";

import React, {
  useCallback,
  useEffect,
  useId,
  useMemo,
  useRef,
  useState,
  type CSSProperties,
  type ReactNode,
} from "react";
import type {
  Ad,
  AdLayout,
  AdPlacement,
  AdRequest,
  AdResolution,
} from "./core/types";
import type { DecisionResponse, PlacementOpportunity } from "@/lib/ad-orchestration";
import { createCompatibilityOpportunity } from "@/lib/ad-orchestration/PlacementEngine";
import { layoutRegistry } from "@/lib/ad-orchestration/LayoutRegistry";
import { observeElement } from "./runtime/observer";
import { AdRuntimeProvider, useAdRuntime, type AdRuntimeProviderProps } from "./runtime/AdRuntimeProvider";
import AutoAdRenderer from "./AutoAdRenderer";

export type AdSlotState = "idle" | "loading" | "loaded" | "empty" | "error";

export interface AdSlotProps {
  opportunity?: PlacementOpportunity;
  decision?: DecisionResponse | null;
  placement?: AdPlacement;
  layout?: AdLayout;
  resolve?: (
    request: AdRequest,
  ) => Promise<AdResolution | null> | AdResolution | null;
  request?: Partial<AdRequest>;
  className?: string;
  style?: CSSProperties;
  fallback?: ReactNode;
  skeleton?: ReactNode;
  onLoad?: (resolution: AdResolution) => void;
  onEmpty?: () => void;
  onError?: (error: Error) => void;
  onImpression?: (resolution: AdResolution) => void;
  onClick?: (resolution: AdResolution, event: React.MouseEvent) => void;
  onViewable?: (resolution: AdResolution) => void;
  eager?: boolean;
  rootMargin?: string;
  maxWidth?: number | string;
  height?: number | string;
  debug?: boolean;
  ariaLabel?: string;
  testId?: string;
  noAnimation?: boolean;
  renderAd?: (resolution: AdResolution) => ReactNode;
}

export type AdEngineProviderProps = AdRuntimeProviderProps;

function toLegacyResolution(decision: DecisionResponse): AdResolution {
  return {
    ad: decision.ad,
    adId: decision.ad?.id,
    requestId: decision.requestId,
    resolvedAt: Date.now(),
    latencyMs: 0,
    stagesRun: [],
    eligibleCount: decision.ad ? 1 : 0,
    filteredOut: [],
    winningBid: decision.clearingPrice,
    qualityScore: decision.rankScore,
    fallback: !decision.ad,
  };
}

function renderDecisionAd(ad: Ad, layout: AdLayout) {
  return <AutoAdRenderer ad={ad} layout={layout} />;
}

function DefaultSkeleton({ height }: { height: number | string }) {
  return (
    <div
      aria-hidden="true"
      style={{
        width: "100%",
        height: typeof height === "number" ? `${height}px` : height,
        background: "linear-gradient(90deg, rgba(148,163,184,0.08) 0%, rgba(148,163,184,0.18) 50%, rgba(148,163,184,0.08) 100%)",
        backgroundSize: "200% 100%",
        animation: "ad-shimmer 1.2s linear infinite",
        borderRadius: 8,
      }}
    />
  );
}

export function AdEngineProvider(props: AdEngineProviderProps) {
  return <AdRuntimeProvider {...props} />;
}

export function useAdEngine() {
  return useAdRuntime();
}

export function AdSlot({
  opportunity,
  decision: initialDecision,
  placement,
  layout,
  resolve,
  request,
  className,
  style,
  fallback = null,
  skeleton,
  onLoad,
  onEmpty,
  onError,
  onImpression,
  onClick,
  onViewable,
  eager = false,
  rootMargin = "320px",
  maxWidth,
  height,
  debug = false,
  ariaLabel,
  testId,
  noAnimation = false,
  renderAd: customRenderAd,
}: AdSlotProps) {
  const runtime = useAdRuntime();
  const slotInstanceId = useId();
  const slotOpportunity = useMemo<PlacementOpportunity>(() => {
    if (opportunity) return opportunity;
    if (placement) return createCompatibilityOpportunity(`${placement}-${slotInstanceId}`, placement);
    throw new Error("AdSlot requires either an opportunity or a placement");
  }, [opportunity, placement, slotInstanceId]);

  const seedDecision = initialDecision ?? runtime.getCachedDecision(slotOpportunity.slotId);
  const [state, setState] = useState<AdSlotState>(seedDecision?.ad ? "loaded" : seedDecision ? "empty" : "idle");
  const [legacyResolution, setLegacyResolution] = useState<AdResolution | null>(
    seedDecision ? toLegacyResolution(seedDecision) : null,
  );
  const [decision, setDecision] = useState<DecisionResponse | null>(seedDecision);
  const [error, setError] = useState<Error | null>(null);
  const containerRef = useRef<HTMLDivElement>(null);
  const impressionRef = useRef(false);
  const viewableRef = useRef(false);
  const viewabilityTimerRef = useRef<number | null>(null);
  const retryTimerRef = useRef<number | null>(null);
  const loadDecisionRef = useRef<(signal?: AbortSignal) => Promise<void>>(async () => undefined);
  const loadingRef = useRef(false);
  const decisionRef = useRef<DecisionResponse | null>(seedDecision);
  const canLoadRef = useRef(eager);
  const retryAttemptRef = useRef(0);
  const lastDeliveredDecisionIdRef = useRef<string | null>(seedDecision?.decisionId ?? null);

  const spec = layoutRegistry.getSpec(decision?.layout ?? layout ?? (slotOpportunity.surface === "sidebar" ? "medium_rectangle" : "inline_text"));
  const reservedHeight = height ?? decision?.reservedSize.height ?? slotOpportunity.reservedSize.height ?? spec.reservedSize.height;
  const reservedWidth = maxWidth ?? decision?.reservedSize.width ?? slotOpportunity.reservedSize.width ?? spec.reservedSize.width;

  const clearRetryTimer = useCallback(() => {
    if (retryTimerRef.current) {
      window.clearTimeout(retryTimerRef.current);
      retryTimerRef.current = null;
    }
  }, []);

  const applyDecision = useCallback((nextDecision: DecisionResponse | null) => {
    decisionRef.current = nextDecision;
    loadingRef.current = false;
    retryAttemptRef.current = 0;
    clearRetryTimer();
    setError(null);
    setDecision(nextDecision);

    if (nextDecision?.ad) {
      const resolution = toLegacyResolution(nextDecision);
      setLegacyResolution(resolution);
      setState("loaded");
      if (lastDeliveredDecisionIdRef.current !== nextDecision.decisionId) {
        lastDeliveredDecisionIdRef.current = nextDecision.decisionId;
        onLoad?.(resolution);
      }
      return;
    }

    setLegacyResolution(nextDecision ? toLegacyResolution(nextDecision) : null);
    setState("empty");
    if (nextDecision && lastDeliveredDecisionIdRef.current !== nextDecision.decisionId) {
      lastDeliveredDecisionIdRef.current = nextDecision.decisionId;
      onEmpty?.();
    }
  }, [clearRetryTimer, onEmpty, onLoad]);

  const scheduleRetry = useCallback((nextError: Error) => {
    const delays = [300, 900, 1800];
    if (!canLoadRef.current || retryAttemptRef.current >= delays.length) {
      loadingRef.current = false;
      setError(nextError);
      setState("error");
      onError?.(nextError);
      return;
    }

    const delay = delays[retryAttemptRef.current];
    retryAttemptRef.current += 1;
    loadingRef.current = false;
    setState("loading");
    clearRetryTimer();
    retryTimerRef.current = window.setTimeout(() => {
      retryTimerRef.current = null;
      void loadDecisionRef.current();
    }, delay);
  }, [clearRetryTimer, onError]);

  const loadDecision = useCallback(async (signal?: AbortSignal) => {
    if (loadingRef.current || decisionRef.current) return;
    loadingRef.current = true;
    setState("loading");

    try {
      if (resolve && placement) {
        const resolved = await resolve({ placement, layout, ...request } as AdRequest);
        loadingRef.current = false;
        if (resolved?.ad) {
          setLegacyResolution(resolved);
          setState("loaded");
          onLoad?.(resolved);
        } else {
          setState("empty");
          onEmpty?.();
        }
        return;
      }

      const nextDecision = await runtime.getDecision(slotOpportunity, signal);
      applyDecision(nextDecision);
    } catch (caughtError) {
      if (signal?.aborted) {
        loadingRef.current = false;
        const currentAd = (decisionRef.current as DecisionResponse | null)?.ad;
        setState(currentAd ? "loaded" : "idle");
        return;
      }

      const nextError = caughtError instanceof Error ? caughtError : new Error("Failed to load ad decision");
      if (nextError.message === "Decision request aborted") {
        loadingRef.current = false;
        const currentAd = (decisionRef.current as DecisionResponse | null)?.ad;
        setState(currentAd ? "loaded" : "idle");
        return;
      }

      scheduleRetry(nextError);
    }
  }, [applyDecision, layout, onEmpty, onLoad, placement, request, resolve, runtime, scheduleRetry, slotOpportunity]);

  loadDecisionRef.current = loadDecision;

  useEffect(() => {
    decisionRef.current = decision;
  }, [decision]);

  useEffect(() => {
    return runtime.subscribeDecision(slotOpportunity.slotId, (nextDecision) => {
      if (nextDecision) {
        applyDecision(nextDecision);
      }
    });
  }, [applyDecision, runtime, slotOpportunity.slotId]);

  useEffect(() => {
    if (decision?.ad) {
      runtime.track.record(decision, "render");
    }
  }, [decision, runtime.track]);

  useEffect(() => {
    if (eager) {
      canLoadRef.current = true;
      const controller = new AbortController();
      void loadDecision(controller.signal);
      return () => {
        controller.abort();
        clearRetryTimer();
      };
    }

    const element = containerRef.current;
    if (!element) return;
    let controller: AbortController | null = null;
    const cleanupObserver = observeElement(
      element,
      { rootMargin, threshold: 0.01 },
      (entry) => {
        canLoadRef.current = entry.isIntersecting;
        if (entry.isIntersecting) {
          controller ??= new AbortController();
          void loadDecision(controller.signal);
        }
      },
    );
    return () => {
      cleanupObserver();
      controller?.abort();
      clearRetryTimer();
    };
  }, [clearRetryTimer, eager, loadDecision, rootMargin]);

  useEffect(() => {
    const element = containerRef.current;
    if (!element || !decision?.ad || !legacyResolution) return;

    const cleanup = observeElement(
      element,
      { threshold: [0, 0.5, 1] },
      (entry) => {
        const ad = decision?.ad;
        if (!ad) return;

        if (entry.isIntersecting && !impressionRef.current) {
          impressionRef.current = true;
          runtime.track.record(decision!, "impression");
          onImpression?.(legacyResolution);
        }

        if (entry.intersectionRatio >= 0.5 && !viewableRef.current) {
          const visibleFor = ad.type === "video" ? 2000 : 1000;
          const startedAt = Date.now();
          if (viewabilityTimerRef.current) return;
          viewabilityTimerRef.current = window.setTimeout(() => {
            if (!viewableRef.current) {
              viewableRef.current = true;
              runtime.track.record(decision, "viewable", {
                viewabilityRatio: entry.intersectionRatio,
                viewabilityMs: Date.now() - startedAt,
              });
              onViewable?.(legacyResolution);
            }
            viewabilityTimerRef.current = null;
          }, visibleFor);
        } else if (entry.intersectionRatio < 0.5 && viewabilityTimerRef.current) {
          window.clearTimeout(viewabilityTimerRef.current);
          viewabilityTimerRef.current = null;
        }
      },
    );
    return () => {
      cleanup();
      if (viewabilityTimerRef.current) {
        window.clearTimeout(viewabilityTimerRef.current);
        viewabilityTimerRef.current = null;
      }
    };
  }, [decision, legacyResolution, onImpression, onViewable, runtime.track]);

  const handleClickCapture = useCallback((event: React.MouseEvent) => {
    if (!decision || !legacyResolution) return;
    runtime.track.record(decision, "click", {
      scrollDepth: window.scrollY,
    });
    onClick?.(legacyResolution, event);
  }, [decision, legacyResolution, onClick, runtime.track]);

  const containerStyle: CSSProperties = {
    position: "relative",
    display: "block",
    width: reservedWidth === "fluid" ? "100%" : typeof reservedWidth === "number" ? `${reservedWidth}px` : reservedWidth,
    maxWidth: reservedWidth === "fluid" ? "100%" : typeof reservedWidth === "number" ? `${reservedWidth}px` : reservedWidth,
    minHeight: typeof reservedHeight === "number" ? `${reservedHeight}px` : reservedHeight,
    overflow: "hidden",
    transition: noAnimation ? undefined : "opacity 180ms ease",
    opacity: state === "loading" ? 0.82 : 1,
    ...style,
  };

  return (
    <div
      ref={containerRef}
      className={className}
      style={containerStyle}
      onClickCapture={handleClickCapture}
      role="complementary"
      aria-label={ariaLabel ?? `Advertisement: ${decision?.placement ?? slotOpportunity.placementHints[0]}`}
      data-testid={testId ?? `adslot-${slotOpportunity.slotId}`}
      data-ad-state={state}
      data-ad-slot={slotOpportunity.slotId}
    >
      {state === "loading" && (skeleton ?? <DefaultSkeleton height={reservedHeight} />)}
      {state === "idle" && (skeleton ?? <DefaultSkeleton height={reservedHeight} />)}

      {state === "loaded" && decision?.ad && legacyResolution && (
        customRenderAd
          ? customRenderAd(legacyResolution)
          : renderDecisionAd(decision.ad, decision.layout)
      )}

      {state === "empty" && fallback}

      {state === "error" && (
        debug ? (
          <div style={{ fontSize: 12, color: "#b91c1c", padding: 8 }}>
            {error?.message ?? "Ad error"}
          </div>
        ) : (
          fallback
        )
      )}
    </div>
  );
}

export interface AdGridProps {
  placement: AdPlacement;
  count: number;
  columns?: number;
  gap?: number;
  itemProps?: Partial<AdSlotProps>;
}

export function AdGrid({ placement, count, columns = 3, gap = 16, itemProps }: AdGridProps) {
  return (
    <div style={{ display: "grid", gridTemplateColumns: `repeat(${columns}, minmax(0, 1fr))`, gap }}>
      {Array.from({ length: count }, (_, index) => (
        <AdSlot key={`${placement}-${index}`} placement={placement} {...itemProps} />
      ))}
    </div>
  );
}

export interface AdStripProps {
  placement: AdPlacement;
  layout?: AdLayout;
  sticky?: boolean;
  position?: "top" | "bottom";
  zIndex?: number;
}

export function AdStrip({ placement, layout, sticky = false, position = "top", zIndex = 1000 }: AdStripProps) {
  return (
    <div
      style={{
        width: "100%",
        display: "flex",
        justifyContent: "center",
        ...(sticky ? { position: "sticky", [position]: 0, zIndex } : null),
      }}
    >
      <AdSlot placement={placement} layout={layout} eager={sticky} />
    </div>
  );
}

export interface AdInterstitialProps {
  onClose: () => void;
  closeAfterSec?: number;
  zIndex?: number;
}

export function AdInterstitial({ onClose, closeAfterSec = 5, zIndex = 99999 }: AdInterstitialProps) {
  const [countdown, setCountdown] = useState(closeAfterSec);

  useEffect(() => {
    if (countdown <= 0) return undefined;
    const timer = window.setInterval(() => setCountdown((current) => current - 1), 1000);
    return () => window.clearInterval(timer);
  }, [countdown]);

  return (
    <div
      style={{
        position: "fixed",
        inset: 0,
        zIndex,
        background: "rgba(0,0,0,0.85)",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
      }}
    >
      <div style={{ position: "relative", width: "90vw", maxWidth: 640 }}>
        <button
          onClick={onClose}
          disabled={countdown > 0}
          style={{ position: "absolute", top: -40, right: 0, zIndex: 1 }}
        >
          {countdown > 0 ? `Close in ${countdown}s` : "Close"}
        </button>
        <AdSlot placement="interstitial" layout="interstitial_full" eager />
      </div>
    </div>
  );
}

export default AdSlot;

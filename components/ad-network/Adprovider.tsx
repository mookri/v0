/* ═══════════════════════════════════════════════════════════════════════════
   AD ENGINE v2  ·  AdProvider.tsx
   
   Provides:
   1. CSS variable injection   → every var(--ad-*) resolves to current theme
   2. AdThemeContext            → read current theme in any component
   3. useAdLazy()              → THE ONE shared hook (fixes BUG-01/02/03)
   4. AdProvider component     → wrap once at app root

   USAGE:
     <AdProvider theme={purpleTheme}>
       <AdSlot placement="article_mid" />   ← picks up purple theme
     </AdProvider>
   ═══════════════════════════════════════════════════════════════════════════ */
"use client";

import {
    createContext,
    useContext,
    useEffect,
    useRef,
    useState,
    useId,
    type ReactNode,
    type MutableRefObject,
} from "react";
import { darkTheme, AD_KEYFRAMES, type AdTheme } from "./Adtokens";
import { observeElement } from "./runtime/observer";

// ─────────────────────────────────────────────────────────────────────────────
// Context
// ─────────────────────────────────────────────────────────────────────────────

const AdThemeContext = createContext<AdTheme>(darkTheme);

export function useAdTheme(): AdTheme {
    return useContext(AdThemeContext);
}

// ─────────────────────────────────────────────────────────────────────────────
// AdProvider
// ─────────────────────────────────────────────────────────────────────────────

export interface AdProviderProps {
    /** Theme token map — defaults to darkTheme */
    theme?: AdTheme;
    /** Scope CSS vars to this element only (default true — recommended) */
    scoped?: boolean;
    children: ReactNode;
}

let _keyframesInjected = false;

export function AdProvider({
    theme = darkTheme,
    scoped = true,
    children,
}: AdProviderProps) {
    const scopeId = useId().replace(/:/g, "");

    // Build the CSS variable block from the theme map
    const cssBlock = Object.entries(theme)
        .map(([k, v]) => `  ${k}: ${v};`)
        .join("\n");

    const selector = scoped ? `[data-ad-scope="${scopeId}"]` : ":root";
    const styleBlock = `${selector} {\n${cssBlock}\n}`;

    // Inject keyframes once globally
    const keyframeStyle = _keyframesInjected ? "" : AD_KEYFRAMES;
    if (!_keyframesInjected) _keyframesInjected = true;

    return (
        <AdThemeContext.Provider value={theme}>
            <style
                // eslint-disable-next-line react/no-danger
                dangerouslySetInnerHTML={{ __html: keyframeStyle + "\n" + styleBlock }}
            />
            <div data-ad-scope={scopeId}>
                {children}
            </div>
        </AdThemeContext.Provider>
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// useAdLazy  — THE ONE shared hook for all layout components
//
// Fixes:
//   BUG-01: onImpression stale closure → included in deps
//   BUG-02: no-image ads never fire    → fires when !hasImage
//   BUG-03: InlineTextAd [] dep array  → same fix applied everywhere
// ─────────────────────────────────────────────────────────────────────────────

export interface AdLazyResult {
    /** Attach to the outermost wrapper div */
    ref: MutableRefObject<HTMLDivElement | null>;
    /** true once element enters viewport */
    visible: boolean;
    /** true once image onLoad fires */
    loaded: boolean;
    /** true if image failed to load */
    imgError: boolean;
    /** Call in <img onLoad> */
    onImgLoad: () => void;
    /** Call in <img onError> */
    onImgError: () => void;
}

/**
 * Single lazy-load + impression hook used by every ad layout component.
 *
 * @param imageUrl   The image src (or undefined for text-only ads)
 * @param onImpression  Callback fired once when ad enters viewport
 * @param rootMargin How far ahead to start loading (default "300px")
 */
export function useAdLazy(
    imageUrl: string | undefined,
    onImpression: (() => void) | undefined,
    rootMargin = "300px"
): AdLazyResult {
    const ref = useRef<HTMLDivElement>(null);
    const [visible, setVisible] = useState(false);
    const [loaded, setLoaded] = useState(false);
    const [imgError, setImgError] = useState(false);
    const firedRef = useRef(false);
    const hasImage = Boolean(imageUrl);

    // ── Visibility observer ──────────────────────────────────────────────────
    useEffect(() => {
        const el = ref.current;
        if (!el) return;
        return observeElement(
            el,
            { rootMargin, threshold: 0.01 },
            (entry) => {
                if (entry.isIntersecting) {
                    setVisible(true);
                }
            }
        );
    }, [rootMargin]);

    // ── Impression fire ───────────────────────────────────────────────────────
    // FIX BUG-01: onImpression is in deps — no stale closure
    // FIX BUG-02: fires on visible alone when !hasImage
    useEffect(() => {
        if (!visible) return;
        if (firedRef.current) return;
        // Fire immediately for text-only ads; wait for image load otherwise
        if (!hasImage || loaded || imgError) {
            firedRef.current = true;
            onImpression?.();
        }
    }, [visible, loaded, imgError, hasImage, onImpression]);

    return {
        ref,
        visible,
        loaded,
        imgError,
        onImgLoad: () => setLoaded(true),
        onImgError: () => setImgError(true),
    };
}

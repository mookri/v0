// components/search/search-box.tsx
// Header/page search box with grouped autocomplete — S64.
//
// Talks only to the BFF's public /api/search/suggest (never Laravel
// directly) and navigates client-side to /search?q=… on submit. Debounced
// at 150 ms, cancels stale responses via AbortController, shows recent
// searches + trending queries as chips, and records click analytics.

"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { useRouter } from "next/navigation";
import { Search, X } from "lucide-react";
import {
  MAX_SUGGEST_QUERY_LENGTH,
  buildSearchHref,
  flattenSuggestions,
  type SuggestionGroup,
  type SuggestionItem,
} from "@/lib/search/params";

const DEBOUNCE_MS = 150;
const RECENT_KEY = "navdhya.recentSearches";
const RECENT_MAX = 5;

export interface SearchBoxProps {
  variant?: "header" | "page";
  initialQuery?: string;
  autoFocus?: boolean;
}

function loadRecent(): string[] {
  if (typeof window === "undefined") return [];
  try {
    const raw = window.localStorage.getItem(RECENT_KEY);
    const parsed = raw ? JSON.parse(raw) : [];
    return Array.isArray(parsed) ? parsed.filter((x) => typeof x === "string").slice(0, RECENT_MAX) : [];
  } catch {
    return [];
  }
}

function saveRecent(q: string) {
  if (typeof window === "undefined") return;
  const trimmed = q.trim();
  if (trimmed.length < 2) return;
  try {
    const current = loadRecent().filter((x) => x !== trimmed);
    const next = [trimmed, ...current].slice(0, RECENT_MAX);
    window.localStorage.setItem(RECENT_KEY, JSON.stringify(next));
  } catch {
    // ignore
  }
}

interface ImpressionItem {
  trace_id: string;
  entity_type: string;
  entity_id: string;
  position: number;
  dwell_ms: number;
  viewport_ratio: number;
}

interface SelectionItem {
  trace_id: string;
  entity_type: string;
  entity_id: string;
  position: number;
  action: string;
  dwell_before_click_ms?: number;
}

async function recordAnalytics(
  q: string,
  results: number,
  impressions?: ImpressionItem[],
  selections?: SelectionItem[],
) {
  try {
    await fetch("/api/search/analytics", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        queries: q ? [{ q, results }] : [],
        impressions: impressions && impressions.length > 0 ? impressions : undefined,
        selections: selections && selections.length > 0 ? selections : undefined,
      }),
      keepalive: true,
    });
  } catch {
    // fire-and-forget
  }
}

export function SearchBox({
  variant = "header",
  initialQuery = "",
  autoFocus = false,
}: SearchBoxProps) {
  const router = useRouter();
  const [query, setQuery] = useState(initialQuery);
  const [open, setOpen] = useState(false);
  const [groups, setGroups] = useState<SuggestionGroup[]>([]);
  const [activeIndex, setActiveIndex] = useState(-1);
  const [recent, setRecent] = useState<string[]>([]);
  const [trending, setTrending] = useState<string[]>([]);

  const seqRef = useRef(0);
  const abortRef = useRef<AbortController | null>(null);
  const rootRef = useRef<HTMLDivElement>(null);
  const listRef = useRef<HTMLUListElement>(null);
  const traceIdRef = useRef<string>("");
  const pendingImpressionsRef = useRef<Map<string, ImpressionItem>>(new Map());
  const openTimeRef = useRef<number>(0);

  const items = flattenSuggestions(groups);
  const trimmed = query.trim();

  // Load recent + trending once.
  useEffect(() => {
    setRecent(loadRecent());
    (async () => {
      try {
        const res = await fetch("/api/search/trending");
        if (!res.ok) return;
        const json = (await res.json()) as { data?: { trending?: Array<{ query: string }> } };
        const qs = (json?.data?.trending ?? []).map((t) => t.query).filter(Boolean).slice(0, 6);
        setTrending(qs);
      } catch {
        // ignore
      }
    })();
  }, []);

  const flushImpressions = useCallback(() => {
    if (pendingImpressionsRef.current.size > 0) {
      const imps = Array.from(pendingImpressionsRef.current.values());
      pendingImpressionsRef.current.clear();
      void recordAnalytics("", 0, imps);
    }
  }, []);

  // Flush impressions on page hide or tab switch
  useEffect(() => {
    const onVisibilityChange = () => {
      if (document.visibilityState === "hidden") {
        flushImpressions();
      }
    };
    document.addEventListener("visibilitychange", onVisibilityChange);
    return () => {
      document.removeEventListener("visibilitychange", onVisibilityChange);
      flushImpressions();
    };
  }, [flushImpressions]);

  // Impression tracking via IntersectionObserver: 50% visibility for >= 300ms
  useEffect(() => {
    if (!open || groups.length === 0 || typeof IntersectionObserver === "undefined") return;

    const timers = new Map<Element, ReturnType<typeof setTimeout>>();
    const startTimes = new Map<Element, number>();

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          const el = entry.target as HTMLElement;
          const entityType = el.getAttribute("data-entity-type") || "suggestion";
          const entityId = el.getAttribute("data-entity-id") || el.id;
          const position = Number(el.getAttribute("data-position") || 0);

          if (entry.isIntersecting && entry.intersectionRatio >= 0.5) {
            startTimes.set(el, Date.now());
            const timer = setTimeout(() => {
              const dwell = Date.now() - (startTimes.get(el) || Date.now());
              const key = `${entityType}:${entityId}`;
              if (!pendingImpressionsRef.current.has(key)) {
                pendingImpressionsRef.current.set(key, {
                  trace_id: traceIdRef.current || "tr_anon",
                  entity_type: entityType,
                  entity_id: entityId,
                  position,
                  dwell_ms: dwell >= 300 ? dwell : 300,
                  viewport_ratio: Math.round(entry.intersectionRatio * 100) / 100,
                });
              }
            }, 300);
            timers.set(el, timer);
          } else {
            const timer = timers.get(el);
            if (timer) {
              clearTimeout(timer);
              timers.delete(el);
            }
          }
        });
      },
      { threshold: 0.5 },
    );

    const optionEls = listRef.current?.querySelectorAll('[role="option"]');
    optionEls?.forEach((el) => observer.observe(el));

    return () => {
      timers.forEach((t) => clearTimeout(t));
      observer.disconnect();
    };
  }, [open, groups]);

  useEffect(() => {
    seqRef.current += 1;
    const seq = seqRef.current;

    if (abortRef.current) {
      abortRef.current.abort();
      abortRef.current = null;
    }

    if (trimmed.length < 2 || trimmed.length > MAX_SUGGEST_QUERY_LENGTH) {
      setGroups([]);
      setActiveIndex(-1);
      return;
    }

    const controller = new AbortController();
    abortRef.current = controller;

    const timer = setTimeout(async () => {
      try {
        const res = await fetch(
          `/api/search/suggest?q=${encodeURIComponent(trimmed)}&limit=8`,
          { signal: controller.signal },
        );
        if (seq !== seqRef.current) return;
        if (!res.ok) {
          setGroups([]);
          return;
        }
        const json = (await res.json()) as {
          data?: { suggestions?: SuggestionGroup[] };
        };
        const suggestions = json?.data?.suggestions ?? [];
        setGroups(suggestions);
        traceIdRef.current = "tr_" + Math.random().toString(36).slice(2, 10);
        openTimeRef.current = Date.now();
        setActiveIndex(-1);
      } catch (e) {
        if ((e as { name?: string })?.name === "AbortError") return;
        if (seq === seqRef.current) setGroups([]);
      }
    }, DEBOUNCE_MS);

    return () => {
      clearTimeout(timer);
      controller.abort();
    };
  }, [trimmed]);

  // Close dropdown on outside press / Escape.
  useEffect(() => {
    if (!open) return;

    const onPointerDown = (event: MouseEvent | TouchEvent) => {
      if (!rootRef.current?.contains(event.target as Node)) {
        flushImpressions();
        setOpen(false);
      }
    };
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") {
        flushImpressions();
        setOpen(false);
      }
    };

    document.addEventListener("mousedown", onPointerDown);
    document.addEventListener("touchstart", onPointerDown);
    document.addEventListener("keydown", onKeyDown);
    return () => {
      document.removeEventListener("mousedown", onPointerDown);
      document.removeEventListener("touchstart", onPointerDown);
      document.removeEventListener("keydown", onKeyDown);
    };
  }, [open, flushImpressions]);

  const goTo = (
    href: string,
    clickedItem?: {
      title: string;
      entityType?: string;
      entityId?: string;
      position?: number;
    },
  ) => {
    if (clickedItem) {
      saveRecent(clickedItem.title);
      setRecent(loadRecent());
      const dwellBeforeClick = openTimeRef.current ? Date.now() - openTimeRef.current : 0;
      const selection: SelectionItem = {
        trace_id: traceIdRef.current || "tr_anon",
        entity_type: clickedItem.entityType || "suggestion",
        entity_id: clickedItem.entityId || clickedItem.title,
        position: clickedItem.position ?? 0,
        action: "click",
        dwell_before_click_ms: dwellBeforeClick,
      };
      flushImpressions();
      void recordAnalytics(trimmed, 1, undefined, [selection]);
    } else if (trimmed) {
      saveRecent(trimmed);
      setRecent(loadRecent());
      flushImpressions();
    }
    setOpen(false);
    router.push(href);
  };

  const runSearch = () => {
    if (!trimmed) return;
    const active = activeIndex >= 0 ? items[activeIndex] : undefined;
    goTo(
      active ? active.url : buildSearchHref({ q: trimmed }),
      active ? { title: active.title, entityId: active.url, position: activeIndex } : undefined,
    );
  };

  const submit = (event: React.FormEvent) => {
    event.preventDefault();
    runSearch();
  };

  const onInputKeyDown = (event: React.KeyboardEvent<HTMLInputElement>) => {
    if ((event.nativeEvent as { isComposing?: boolean }).isComposing) return;

    if (event.key === "Enter") {
      event.preventDefault();
      runSearch();
      return;
    }

    if (items.length === 0 || !open) return;

    if (event.key === "ArrowDown") {
      event.preventDefault();
      setActiveIndex((index) => (index + 1) % items.length);
    } else if (event.key === "ArrowUp") {
      event.preventDefault();
      setActiveIndex((index) => (index <= 0 ? items.length - 1 : index - 1));
    }
  };

  const clearRecent = () => {
    try {
      window.localStorage.removeItem(RECENT_KEY);
    } catch {
      // ignore
    }
    setRecent([]);
  };

  const inputClasses =
    variant === "header"
      ? "w-full rounded-sm border border-border bg-transparent pl-9 pr-4 py-2 text-[13px] focus:border-[#FF4500] focus:outline-none focus:ring-1 focus:ring-[#FF4500]"
      : "w-full rounded-sm border border-border bg-background pl-11 pr-4 py-3.5 text-base focus:border-[#FF4500] focus:outline-none focus:ring-1 focus:ring-[#FF4500]";

  const iconClasses =
    variant === "header"
      ? "absolute left-3 h-4 w-4 text-muted-foreground"
      : "absolute left-4 h-5 w-5 text-muted-foreground";

  const showChips = open && trimmed.length < 2 && (recent.length > 0 || trending.length > 0);

  return (
    <div ref={rootRef} className="relative w-full">
      <form role="search" onSubmit={submit} className="relative flex items-center w-full">
        <Search className={iconClasses} aria-hidden="true" />
        <input
          type="search"
          role="combobox"
          aria-label="Search"
          aria-expanded={open && items.length > 0}
          aria-controls="search-suggest-list"
          aria-autocomplete="list"
          aria-activedescendant={
            activeIndex >= 0 ? `search-suggest-option-${activeIndex}` : undefined
          }
          placeholder="Search for puja, pandit, temple or festival..."
          value={query}
          autoFocus={autoFocus}
          onChange={(event) => {
            setQuery(event.target.value);
            setOpen(true);
          }}
          onFocus={() => setOpen(true)}
          onKeyDown={onInputKeyDown}
          className={inputClasses}
        />
      </form>

      {showChips && (
        <div className="absolute z-50 mt-1 w-full rounded-sm border border-border bg-card p-3 shadow-lg">
          {recent.length > 0 && (
            <div className="mb-2">
              <div className="mb-1 flex items-center justify-between">
                <span className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                  Recent
                </span>
                <button
                  type="button"
                  onClick={clearRecent}
                  className="text-[11px] text-muted-foreground hover:text-foreground"
                >
                  Clear
                </button>
              </div>
              <div className="flex flex-wrap gap-1.5">
                {recent.map((r) => (
                  <button
                    key={r}
                    type="button"
                    onClick={() => {
                      setQuery(r);
                      setOpen(true);
                    }}
                    className="rounded-full border border-border px-2.5 py-0.5 text-xs text-foreground hover:bg-muted"
                  >
                    {r}
                  </button>
                ))}
              </div>
            </div>
          )}
          {trending.length > 0 && (
            <div>
              <span className="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                Trending
              </span>
              <div className="flex flex-wrap gap-1.5">
                {trending.map((t) => (
                  <button
                    key={t}
                    type="button"
                    onClick={() => {
                      setQuery(t);
                      setOpen(true);
                    }}
                    className="rounded-full border border-border px-2.5 py-0.5 text-xs text-foreground hover:bg-muted"
                  >
                    {t}
                  </button>
                ))}
              </div>
            </div>
          )}
        </div>
      )}

      {open && items.length > 0 && (
        <ul
          ref={listRef}
          id="search-suggest-list"
          role="listbox"
          aria-label="Search suggestions"
          className="absolute z-50 mt-1 w-full overflow-hidden rounded-sm border border-border bg-card shadow-lg"
        >
          {groups.map((group) => (
            <li key={group.type} role="presentation">
              <p
                role="presentation"
                className="px-4 pt-3 pb-1 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground"
              >
                {group.label}
              </p>
              <ul role="group" aria-label={group.label}>
                {group.items.map((item) => {
                  const index = items.indexOf(item);
                  const isActive = index === activeIndex;
                  return (
                    <li
                      key={item.url}
                      id={`search-suggest-option-${index}`}
                      role="option"
                      aria-selected={isActive}
                      data-entity-type={group.type}
                      data-entity-id={item.url}
                      data-position={index}
                      tabIndex={-1}
                      onMouseEnter={() => setActiveIndex(index)}
                      onClick={() =>
                        goTo(item.url, {
                          title: item.title,
                          entityType: group.type,
                          entityId: item.url,
                          position: index,
                        })
                      }
                      className={`flex cursor-pointer items-center justify-between gap-3 px-4 py-2 text-sm ${
                        isActive ? "bg-muted" : ""
                      }`}
                    >
                      <span className="truncate text-foreground">{item.title}</span>
                      {item.city && (
                        <span className="shrink-0 text-xs text-muted-foreground">
                          {item.city}
                        </span>
                      )}
                    </li>
                  );
                })}
              </ul>
            </li>
          ))}
          <li role="presentation" className="border-t border-border">
            <button
              type="button"
              onClick={() => goTo(buildSearchHref({ q: trimmed }))}
              className="w-full px-4 py-2 text-left text-xs font-semibold text-[#F16A24] hover:text-[#B74717] hover:bg-muted"
            >
              See all results for &ldquo;{trimmed}&rdquo;
            </button>
          </li>
        </ul>
      )}
    </div>
  );
}
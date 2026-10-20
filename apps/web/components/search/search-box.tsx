// components/search/search-box.tsx
// Header/page search box with grouped autocomplete — S64.
//
// Talks only to the BFF's public /api/search/suggest (never Laravel
// directly) and navigates client-side to /search?q=… on submit. Debounced
// at 150 ms, cancels stale responses via AbortController, shows recent
// searches + trending queries as chips, and records click analytics.

"use client";

import { useEffect, useRef, useState } from "react";
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

async function recordAnalytics(q: string, results: number) {
  try {
    await fetch("/api/search/analytics", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ queries: [{ q, results }] }),
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
        setOpen(false);
      }
    };
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") setOpen(false);
    };

    document.addEventListener("mousedown", onPointerDown);
    document.addEventListener("touchstart", onPointerDown);
    document.addEventListener("keydown", onKeyDown);
    return () => {
      document.removeEventListener("mousedown", onPointerDown);
      document.removeEventListener("touchstart", onPointerDown);
      document.removeEventListener("keydown", onKeyDown);
    };
  }, [open]);

  const goTo = (href: string, clickedTitle?: string) => {
    if (clickedTitle) {
      saveRecent(clickedTitle);
      setRecent(loadRecent());
      void recordAnalytics(trimmed, 1);
    } else if (trimmed) {
      saveRecent(trimmed);
      setRecent(loadRecent());
    }
    setOpen(false);
    router.push(href);
  };

  const runSearch = () => {
    if (!trimmed) return;
    const active = activeIndex >= 0 ? items[activeIndex] : undefined;
    goTo(
      active ? active.url : buildSearchHref({ q: trimmed }),
      active ? active.title : undefined,
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
                      tabIndex={-1}
                      onMouseEnter={() => setActiveIndex(index)}
                      onClick={() => goTo(item.url, item.title)}
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
              className="w-full px-4 py-2 text-left text-xs font-semibold text-[#FF4500] hover:bg-muted"
            >
              See all results for &ldquo;{trimmed}&rdquo;
            </button>
          </li>
        </ul>
      )}
    </div>
  );
}
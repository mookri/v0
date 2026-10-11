// components/search/search-box.tsx
// Header/page search box with grouped autocomplete — SEO track S40.
//
// Talks only to the BFF's public /api/search/suggest (never Laravel
// directly) and navigates client-side to /search?q=… on submit. Debounced
// at 250 ms, cancels stale responses by sequence number, and degrades to a
// plain search form whenever the suggest endpoint errors — the input must
// keep working even when search itself is degraded.

"use client";

import { useEffect, useRef, useState } from "react";
import { useRouter } from "next/navigation";
import { Search } from "lucide-react";
import {
  MAX_SUGGEST_QUERY_LENGTH,
  buildSearchHref,
  flattenSuggestions,
  type SuggestionGroup,
} from "@/lib/search/params";

const DEBOUNCE_MS = 250;

export interface SearchBoxProps {
  /** Visual size; "header" matches the site header's compact input. */
  variant?: "header" | "page";
  /** Controlled-ish starting value (e.g. current ?q= on /search). */
  initialQuery?: string;
  autoFocus?: boolean;
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

  // Monotonic sequence: the response to an older keystroke must never
  // overwrite the suggestions for a newer one.
  const seqRef = useRef(0);
  const rootRef = useRef<HTMLDivElement>(null);

  const items = flattenSuggestions(groups);
  const trimmed = query.trim();

  useEffect(() => {
    seqRef.current += 1;
    const seq = seqRef.current;

    if (trimmed.length < 2 || trimmed.length > MAX_SUGGEST_QUERY_LENGTH) {
      setGroups([]);
      setActiveIndex(-1);
      return;
    }

    const timer = setTimeout(async () => {
      try {
        const res = await fetch(
          `/api/search/suggest?q=${encodeURIComponent(trimmed)}&limit=8`,
        );
        if (seq !== seqRef.current) return; // stale
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
      } catch {
        if (seq === seqRef.current) setGroups([]);
      }
    }, DEBOUNCE_MS);

    return () => clearTimeout(timer);
  }, [trimmed]);

  // Close the dropdown on outside press / Escape.
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

  const goTo = (href: string) => {
    setOpen(false);
    router.push(href);
  };

  const runSearch = () => {
    if (!trimmed) return;
    const active = activeIndex >= 0 ? items[activeIndex] : undefined;
    goTo(active ? active.url : buildSearchHref({ q: trimmed }));
  };

  const submit = (event: React.FormEvent) => {
    event.preventDefault();
    runSearch();
  };

  const onInputKeyDown = (event: React.KeyboardEvent<HTMLInputElement>) => {
    // Never hijack an in-progress IME composition.
    if ((event.nativeEvent as { isComposing?: boolean }).isComposing) return;

    if (event.key === "Enter") {
      // Handled here (not via implicit form submission) so an active
      // suggestion wins deterministically across browsers.
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

  const inputClasses =
    variant === "header"
      ? "w-full rounded-sm border border-border bg-transparent pl-9 pr-4 py-2 text-[13px] focus:border-[#FF4500] focus:outline-none focus:ring-1 focus:ring-[#FF4500]"
      : "w-full rounded-sm border border-border bg-background pl-11 pr-4 py-3.5 text-base focus:border-[#FF4500] focus:outline-none focus:ring-1 focus:ring-[#FF4500]";

  const iconClasses =
    variant === "header"
      ? "absolute left-3 h-4 w-4 text-muted-foreground"
      : "absolute left-4 h-5 w-5 text-muted-foreground";

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
                      onClick={() => goTo(item.url)}
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

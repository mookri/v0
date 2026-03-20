"use client";

import { useState, useRef, useEffect, useId } from "react";
import Link from "next/link";
import { ChevronDown } from "lucide-react";

interface BreadcrumbNavProps {
  clusterLabel: string;
  basePath: string;
  pageTitle: string;
  isDark?: boolean;
}

export function BreadcrumbNav({
  clusterLabel,
  basePath,
  pageTitle,
  isDark = false,
}: BreadcrumbNavProps) {
  const [open, setOpen] = useState(false);
  const ref = useRef<HTMLDivElement>(null);
  const menuId = useId();

  // Close on outside pointer-down
  useEffect(() => {
    const handler = (e: PointerEvent) => {
      if (ref.current && !ref.current.contains(e.target as Node)) {
        setOpen(false);
      }
    };
    document.addEventListener("pointerdown", handler);
    return () => document.removeEventListener("pointerdown", handler);
  }, []);

  // Close on ESC
  useEffect(() => {
    const handler = (e: KeyboardEvent) => {
      if (e.key === "Escape") setOpen(false);
    };
    document.addEventListener("keydown", handler);
    return () => document.removeEventListener("keydown", handler);
  }, []);

  return (
    <div ref={ref} className="relative inline-block">

      {/* ── TRIGGER ────────────────────────────────────────────────────── */}
      <button
        onClick={() => setOpen((v) => !v)}
        aria-expanded={open}
        aria-haspopup="true"
        aria-controls={menuId}
        className={`
          flex items-center gap-1.5
          h-8 px-3
          rounded-sm
          text-[10px] uppercase tracking-[0.18em] font-medium
          border transition-all duration-150
          focus:outline-none focus-visible:ring-1 focus-visible:ring-white/40
          shadow-[0_1px_2px_rgba(0,0,0,0.06)]
          hover:shadow-[0_2px_4px_rgba(0,0,0,0.10)]
          active:translate-y-[1px]
          ${isDark
            ? "text-white/85 border-white/15 bg-white/[0.02] hover:bg-white/[0.06]"
            : "text-foreground border-border bg-muted/20 hover:bg-muted"
          }
        `}
      >
        <span className="truncate max-w-[120px]">{clusterLabel}</span>
        <ChevronDown
          size={12}
          aria-hidden="true"
          className={`shrink-0 transition-transform duration-150 ${open ? "rotate-180" : ""}`}
        />
      </button>

      {/* ── DROPDOWN ───────────────────────────────────────────────────── */}
      {open && (
        <div
          id={menuId}
          role="navigation"
          aria-label="Page breadcrumb navigation"
          className={`
            absolute left-0 top-full
            min-w-[260px] w-max max-w-[min(90vw,480px)]
            mt-1
            rounded-sm
            border border-white/10
            backdrop-blur-md
            shadow-[0_10px_30px_rgba(0,0,0,0.2)]
            z-50
            /*
             * BUG FIX: The @keyframes was named "fadeIn" but the Tailwind
             * arbitrary animation class used "slideDown", causing the
             * animation to never play.
             * Fix: animate-[fadeIn_...] now matches the defined keyframe name.
             *
             * Also removed w-screen / -ml-6 which broke layouts at any
             * container padding other than exactly 24px.
             */
            animate-[fadeIn_0.2s_cubic-bezier(0.16,1,0.3,1)_both]
            ${isDark ? "bg-[#0E3F2E]" : "bg-background"}
          `}
        >
          <div className="px-5 py-4">

            {/* Breadcrumb row */}
            <div className="flex items-center gap-2 text-[12px]">
              <Link
                href={`/${basePath}`}
                onClick={() => setOpen(false)}
                className={`transition hover:underline ${isDark ? "text-white/70 hover:text-white" : "text-muted-foreground hover:text-foreground"}`}
              >
                {clusterLabel}
              </Link>

              <span className={isDark ? "text-white/30" : "text-muted-foreground/40"}>/</span>

              <span className={`font-medium truncate max-w-[200px] ${isDark ? "text-white" : "text-foreground"}`}>
                {pageTitle}
              </span>
            </div>

            {/* Quick links */}
            <div className={`mt-3 flex items-center gap-4 text-[11px] ${isDark ? "text-white/60" : "text-muted-foreground"}`}>
              <Link
                href={`/${basePath}`}
                onClick={() => setOpen(false)}
                className={`transition hover:underline ${isDark ? "hover:text-white" : "hover:text-foreground"}`}
              >
                View all
              </Link>
            </div>

          </div>
        </div>
      )}

      {/* BUG FIX: keyframe name is "fadeIn" — matches animate-[fadeIn_...] above */}
      <style>{`
        @keyframes fadeIn {
          from { opacity: 0; transform: translateY(-6px); }
          to   { opacity: 1; transform: translateY(0); }
        }
      `}</style>
    </div>
  );
}
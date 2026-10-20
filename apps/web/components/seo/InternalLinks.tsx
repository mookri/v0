"use client";

import Link from "next/link";
import type { InternalLink } from "@/lib/linking/graph";

type CoreEntityLink = (typeof import("@/lib/seo/entity").CORE_ENTITY_LINKS)[number];

interface InternalLinksProps {
  related?: InternalLink[];
  core?: readonly CoreEntityLink[];
}

export function InternalLinks({ related = [], core = [] }: InternalLinksProps) {
  if (!related.length && !core.length) return null;

  return (
    <section className="mt-20 border-t border-border/20 pt-10" aria-label="Related articles">

      {/* ── Related articles ─────────────────────────────────────────── */}
      {related.length > 0 && (
        <>
          <h3 className="text-lg font-semibold mb-6">Continue Reading</h3>

          <div className="grid md:grid-cols-3 gap-5">
            {related.map((link, i) => (
              /*
               * BUG FIX: key was `link.href` which could collide when the same
               * page appears via different cluster paths. Using index+href gives
               * a stable, unique key per render position.
               */
              <Link
                key={`related-${i}-${link.href}`}
                href={link.href}
                className="group flex flex-col gap-2 border border-border/20 p-5 rounded-sm hover:border-[#0E3F2E]/40 hover:bg-[#0E3F2E]/[0.02] transition-all duration-200"
              >
                <span className="text-[10px] uppercase tracking-[0.18em] text-muted-foreground">
                  {link.cluster.replace(/-/g, ' ')}
                </span>
                <h4 className="font-serif text-[17px] leading-snug group-hover:text-[#0E3F2E] transition-colors">
                  {link.label}
                </h4>
                <span className="mt-auto text-sm text-muted-foreground group-hover:text-[#0E3F2E] transition-colors">
                  Explore →
                </span>
              </Link>
            ))}
          </div>
        </>
      )}

      {/* ── Core entity links ────────────────────────────────────────── */}
      {core.length > 0 && (
        <div className="mt-10 pt-6 border-t border-border/10">
          <p className="text-[11px] uppercase tracking-[0.18em] text-muted-foreground mb-4">
            About Navdhya
          </p>
          <div className="flex flex-wrap gap-3">
            {core.map((link) => (
              <Link
                key={link.href}
                href={link.href}
                className="h-8 px-4 inline-flex items-center text-[12px] border border-border/30 rounded-sm text-muted-foreground hover:border-[#0E3F2E]/40 hover:text-[#0E3F2E] transition-all duration-150"
              >
                {link.label}
              </Link>
            ))}
          </div>
        </div>
      )}

    </section>
  );
}

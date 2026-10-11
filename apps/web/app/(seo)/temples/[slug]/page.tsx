// /app/(seo)/temples/[slug]/page.tsx
// Temple detail page — SEO track S40.
//
// Renders the public search projection of one temple
// (GET /api/v1/search/entities/temple/{slug} — S36's entity endpoint), the
// indexable landing surface the federated search results link to.
//
// Backend-dependent by nature: temples live in Laravel, not on disk. When
// the backend is disabled (CUSTOMER_BACKEND unset) or the slug is unknown
// (404 from the projection), the page 404s rather than inventing content —
// a soft-404 on an entity page is worse than a hard one for crawlers.

import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { MapPin, BadgeCheck, BookOpen } from "lucide-react";
import { laravelEnabled, laravelGet } from "@/adapters/backend/laravel-client";
import { CustomerApiError } from "@/adapters/backend/customer-api-client";
import { buildSearchHref } from "@/lib/search/params";

interface TempleCard {
  city?: string | null;
  state?: string | null;
  verified?: boolean | null;
  amenities?: string[] | null;
  type?: string | null;
  trust_name?: string | null;
}

interface TempleProjection {
  entity_type: string;
  slug: string;
  url_path: string;
  title: string;
  subtitle?: string | null;
  body?: string | null;
  image_url?: string | null;
  city?: string | null;
  card_json?: TempleCard | string | null;
  status: string;
  indexed_at?: string | null;
}

interface TemplePageProps {
  params: Promise<{ slug: string }>;
}

function parseCard(card: TempleCard | string | null | undefined): TempleCard {
  if (!card) return {};
  if (typeof card === "string") {
    try {
      return JSON.parse(card) as TempleCard;
    } catch {
      return {};
    }
  }
  return card;
}

async function loadTemple(slug: string): Promise<TempleProjection | null> {
  if (!laravelEnabled()) return null;

  try {
    const { data } = await laravelGet<TempleProjection>(
      null,
      `/search/entities/temple/${encodeURIComponent(slug)}`,
      {},
      {},
      { timeout: "normal" },
    );
    return data;
  } catch (error) {
    // Unknown slug → 404. Anything else (backend down, throttled) also
    // lands here as a CustomerApiError; a temple page has no local data to
    // fall back to, so it degrades to notFound() instead of a 500.
    if (error instanceof CustomerApiError && error.code === "NOT_FOUND") return null;
    return null;
  }
}

export async function generateMetadata({ params }: TemplePageProps): Promise<Metadata> {
  const { slug } = await params;
  const temple = await loadTemple(decodeURIComponent(slug));
  if (!temple) return { title: "Temple Not Found" };

  const description =
    temple.subtitle ??
    (temple.body ? temple.body.slice(0, 155) : `Details for ${temple.title} on Navdhya.`);

  return {
    title: temple.title,
    description,
    openGraph: {
      title: `${temple.title} | Navdhya Temples`,
      description,
      ...(temple.image_url ? { images: [{ url: temple.image_url }] } : {}),
    },
  };
}

export default async function TemplePage({ params }: TemplePageProps) {
  const { slug } = await params;
  const temple = await loadTemple(decodeURIComponent(slug));
  if (!temple) notFound();

  const card = parseCard(temple.card_json);
  const city = temple.city ?? card.city ?? null;
  const state = card.state ?? null;

  return (
    <div className="min-h-[60vh] bg-background">
      <div className="container mx-auto max-w-4xl px-6 py-10 md:px-8">
        <nav aria-label="Breadcrumb" className="mb-6 text-[13px] text-muted-foreground">
          <Link href="/" className="hover:text-foreground">
            Home
          </Link>
          <span className="mx-1">/</span>
          <Link href="/search?type=temple" className="hover:text-foreground">
            Temples
          </Link>
          <span className="mx-1">/</span>
          <span className="text-foreground">{temple.title}</span>
        </nav>

        {temple.image_url && (
          // Plain <img>: temple image URLs come from the backend and may
          // point at hosts outside next/image's remotePatterns.
          <img
            src={temple.image_url}
            alt={temple.title}
            className="mb-8 h-64 w-full rounded-sm object-cover md:h-80"
            loading="lazy"
          />
        )}

        <div className="mb-2 flex flex-wrap items-center gap-3">
          {card.verified && (
            <span className="inline-flex items-center gap-1 rounded-full bg-[#0E3F2E]/10 px-2.5 py-1 text-xs font-semibold text-[#0E3F2E]">
              <BadgeCheck className="h-3.5 w-3.5" aria-hidden="true" />
              Verified
            </span>
          )}
          {card.type && (
            <span className="rounded-full bg-muted px-2.5 py-1 text-xs font-medium text-muted-foreground">
              {card.type}
            </span>
          )}
        </div>

        <h1 className="text-3xl font-serif font-bold text-foreground md:text-4xl">
          {temple.title}
        </h1>

        {temple.subtitle && (
          <p className="mt-2 text-muted-foreground">{temple.subtitle}</p>
        )}

        {(city || state) && (
          <p className="mt-3 inline-flex items-center gap-1.5 text-sm text-muted-foreground">
            <MapPin className="h-4 w-4" aria-hidden="true" />
            {[city, state].filter(Boolean).join(", ")}
          </p>
        )}

        {card.amenities && card.amenities.length > 0 && (
          <section className="mt-8">
            <h2 className="mb-3 text-lg font-semibold text-foreground">Amenities</h2>
            <ul className="flex flex-wrap gap-2">
              {card.amenities.map((amenity) => (
                <li
                  key={amenity}
                  className="rounded-full border border-border px-3 py-1 text-[13px] text-foreground"
                >
                  {amenity}
                </li>
              ))}
            </ul>
          </section>
        )}

        {temple.body && (
          <section className="mt-8">
            <h2 className="mb-3 text-lg font-semibold text-foreground">About</h2>
            <p className="whitespace-pre-line leading-relaxed text-muted-foreground">
              {temple.body}
            </p>
          </section>
        )}

        <div className="mt-10 flex flex-wrap gap-3">
          <Link
            href="/book"
            className="inline-flex items-center gap-2 rounded-sm bg-[#FF4500] px-5 py-2.5 text-[13px] font-semibold text-white transition-colors hover:bg-[#E63E00]"
          >
            <BookOpen className="h-4 w-4" aria-hidden="true" />
            Book a Puja Here
          </Link>
          <Link
            href={buildSearchHref({ type: "temple", city })}
            className="inline-flex items-center rounded-sm border border-border px-5 py-2.5 text-[13px] font-semibold text-foreground transition-colors hover:bg-muted"
          >
            More temples{city ? ` in ${city}` : ""}
          </Link>
        </div>
      </div>
    </div>
  );
}

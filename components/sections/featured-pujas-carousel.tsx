"use client";

import Image from "next/image";
import Link from "next/link";
import {
  ArrowRight,
  Clock3,
  Heart,
  MapPin,
  Star,
  IndianRupee,
  Info,
  ChevronLeft,
  ChevronRight
} from "lucide-react";
import { type PujaCardDTO } from "@/dal/catalog";

const badgeColors: Record<string, string> = {
  green: "bg-[#0c5647]/90 text-white",
  blue: "bg-[#f0f1ff]/90 text-[#3447b7]",
  mint: "bg-[#e6f3ed]/90 text-[#287255]",
  orange: "bg-[#fff0e8]/90 text-[#ca6040]",
};

export function FeaturedPujasCarousel({ pujas }: { pujas: PujaCardDTO[] }) {
  const scrollCarousel = (direction: "prev" | "next") => {
    const carousel = document.getElementById("featured-pujas-carousel");
    if (!carousel) return;

    const card = carousel.querySelector<HTMLElement>("[data-puja-card]");
    if (!card) return;

    const gap = 16;
    const distance = card.offsetWidth + gap;

    carousel.scrollBy({
      left: direction === "next" ? distance : -distance,
      behavior: "smooth",
    });
  };

  return (
    <div className="min-w-0 relative flex-1 w-full">
      {/* NAVIGATION */}
      <div className="flex justify-end items-center gap-3 mb-6 hidden md:flex">
        <button
          type="button"
          aria-label="Previous rituals"
          onClick={() => scrollCarousel("prev")}
          className="w-10 h-10 flex items-center justify-center rounded-full border border-[#ebe7e1] bg-white text-[#727a81] hover:border-[#d5d0c9] hover:bg-[#faf9f7] hover:-translate-y-px transition-all"
        >
          <ChevronLeft className="w-[18px] h-[18px]" />
        </button>

        <button
          type="button"
          aria-label="Next rituals"
          onClick={() => scrollCarousel("next")}
          className="w-10 h-10 flex items-center justify-center rounded-full bg-[#0d4d40] text-white hover:bg-[#093f35] hover:-translate-y-px transition-all"
        >
          <ChevronRight className="w-[18px] h-[18px]" />
        </button>
      </div>

      {/* CAROUSEL */}
      <div
        id="featured-pujas-carousel"
        className="flex gap-4 w-full overflow-x-auto scroll-smooth snap-x snap-mandatory pb-1 no-scrollbar"
      >
        {pujas.map((puja) => (
          <article
            key={puja.id}
            data-puja-card
            className="relative flex-none w-[280px] md:w-[calc(50%-8px)] lg:w-[calc(33.333%-11px)] flex flex-col overflow-hidden snap-start border border-[rgba(235,232,227,0.8)] rounded-xl bg-white shadow-[0_2px_10px_rgba(13,77,64,0.03)] hover:border-[rgba(13,77,64,0.12)] transition-all duration-300 group/card"
          >
            {/* IMAGE */}
            <div className="relative w-full aspect-[1.65/1] overflow-hidden bg-[#e9e6df]">
              <Image
                src={puja.image}
                alt={puja.title}
                fill
                priority={puja.id === "wedding"}
                sizes="(max-width: 767px) 86vw, (max-width: 1199px) 44vw, (max-width: 1599px) 30vw, 330px"
                className="object-cover transition-transform duration-500 group-hover/card:scale-105"
              />

              {puja.badge && (
                <span
                  className={`absolute top-3 left-3 min-h-[26px] flex items-center px-2.5 rounded-md text-[10px] font-bold whitespace-nowrap backdrop-blur-sm ${
                    puja.badgeType && badgeColors[puja.badgeType] ? badgeColors[puja.badgeType] : 'bg-[#0c5647]/90 text-white'
                  }`}
                >
                  {puja.badge}
                </span>
              )}

              <button
                type="button"
                className="absolute top-3 right-3 w-8 h-8 flex items-center justify-center border-none rounded-full bg-black/25 text-white cursor-pointer backdrop-blur-sm transition-all duration-150 hover:bg-black/40 hover:scale-[1.04]"
                aria-label={`Save ${puja.title}`}
              >
                <Heart className="w-[18px] h-[18px]" strokeWidth={1.8} />
              </button>
            </div>

            {/* CARD BODY */}
            <div className="flex-1 flex flex-col p-3 pt-3.5 pb-4 md:px-4">
              <span className="text-[#ca7445] text-[9px] font-bold uppercase tracking-[0.1em] leading-[1.2]">
                {puja.category}
              </span>

              <h3 className="min-h-[22px] mt-1 text-[#122940] text-base md:text-lg font-semibold leading-[1.15] tracking-[-0.01em]">
                {puja.title}
              </h3>

              <p className="text-[#66717d] text-xs leading-[1.5] mt-1.5 line-clamp-2 min-h-[36px]">
                {puja.description}
              </p>

              {/* Rating */}
              <div className="flex items-center gap-1 mt-2.5 text-[#747d85] text-[11px]">
                <Star className="w-3.5 h-3.5 text-[#f5a000] fill-[#f5a000]" />
                <strong className="text-[#273747] text-xs">{puja.rating}</strong>
                <span>({puja.reviews})</span>
              </div>

              {/* META */}
              <div className="flex items-center gap-2 mt-3 pt-2.5 border-t border-[#f0ede9] text-[#6f7880] text-[10px]">
                <div className="min-w-0 flex items-center gap-1">
                  <Clock3 className="w-[13px] h-[13px] flex-none text-[#ca7445]" strokeWidth={2} />
                  <span className="truncate">{puja.duration}</span>
                </div>

                <span className="w-px h-3.5 flex-none bg-[#ded9d2]" />

                <div className="min-w-0 flex items-center gap-1">
                  <MapPin className="w-[13px] h-[13px] flex-none text-[#ca7445]" strokeWidth={2} />
                  <span className="truncate">{puja.location}</span>
                </div>
              </div>

              {/* FOOTER */}
              <div className="flex items-end justify-between gap-1.5 mt-auto pt-3">
                <div className="flex flex-col">
                  <span className="text-[#dc6d3d] text-[9px] font-semibold uppercase tracking-[0.05em] leading-[1.1]">
                    From
                  </span>
                  <div className="flex items-center gap-1 mt-0.5">
                    <strong className="text-[#dc6d3d] text-lg font-bold tracking-[-0.025em]">
                      ₹{puja.price}
                    </strong>
                    <Info className="w-[13px] h-[13px] text-[#ca7445]" />
                  </div>
                </div>

                <Link
                  href={`/pujas/${puja.slug}`}
                  className="min-w-[100px] h-[34px] flex items-center justify-center gap-1.5 px-2.5 rounded-md bg-[#0d4d40] text-white decoration-none text-[11px] font-bold shadow-[0_4px_10px_rgba(13,77,64,0.15)] transition-all duration-200 hover:-translate-y-[2px] hover:shadow-[0_6px_16px_rgba(13,77,64,0.25)] group"
                >
                  <span>View &amp; Book</span>
                  <ArrowRight className="w-3.5 h-3.5 transition-transform duration-250 group-hover:translate-x-[3px]" />
                </Link>
              </div>
            </div>
          </article>
        ))}
      </div>

      {/* PAGINATION */}
      <div className="h-4 flex items-center justify-center gap-1.5 mt-3.5 md:hidden">
        <span className="w-[22px] h-1 rounded-full bg-[#0d4d40]" />
        <span className="w-[18px] h-1 rounded-full bg-[#dedbd6]" />
        <span className="w-[18px] h-1 rounded-full bg-[#dedbd6]" />
        <span className="w-[18px] h-1 rounded-full bg-[#dedbd6]" />
      </div>
    </div>
  );
}

import React from 'react';
import Image from 'next/image';
import { ArrowRight, Heart, Star } from 'lucide-react';
import Link from 'next/link';
import { getFeaturedPujas } from '@/dal/catalog';

const tagStyles: Record<string, string> = {
  green: "bg-[#0c5647] text-white",
  blue: "bg-[#f0f1ff] text-[#3447b7]",
  mint: "bg-[#e6f3ed] text-[#287255]",
  orange: "bg-[#fff0e8] text-[#ca6040]",
};

export default async function MobileFeaturedRituals() {
  const pujas = await getFeaturedPujas();

  return (
    <div className="w-full px-2.5 py-8 bg-[#fcfaf7]">
      <div className="px-4 mb-5 flex items-end justify-between">
        <div className="flex flex-col">
          <h2 className="text-[18px] font-bold text-[#10263f] leading-tight tracking-tight">
            Featured Pujas
          </h2>
        </div>
        <Link href="/pujas" className="flex items-center gap-1 text-[13px] font-bold text-[#0d4d40]">
          <span className="pb-0.5 underline underline-offset-[3px] decoration-[#0d4d40]/40">View all</span>
          <ArrowRight size={16} strokeWidth={2.5} className="mb-0.5" />
        </Link>
      </div>

      {/* Categories Row */}
      <div className="flex gap-2 overflow-x-auto no-scrollbar px-4 mb-5">
        {['All', 'Marriage', 'Business', 'Housewarming', 'Festival'].map((cat, i) => (
          <button
            key={cat}
            className={`px-3.5 py-1.5 rounded-full text-[11px] font-bold flex-shrink-0 shadow-[0_1px_2px_rgba(0,0,0,0.03)] transition-colors ${i === 0
              ? 'bg-[#0c5647] text-white border border-[#0c5647]'
              : 'bg-white text-[#66717d] border border-[#e8e4db] active:bg-gray-50'
              }`}
          >
            {cat}
          </button>
        ))}
      </div>

      <div className="flex gap-4 overflow-x-auto no-scrollbar pb-2 px-4 snap-x snap-mandatory">
        {pujas.map((ritual, index) => {
          const tagStyle = ritual.badgeType && tagStyles[ritual.badgeType] ? tagStyles[ritual.badgeType] : "bg-[#0c5647] text-white";

          return (
            <div key={ritual.id} className="relative min-w-[210px] max-w-[210px] flex-shrink-0 snap-center pb-6">
              {/* Bottom Tab Behind Card */}
              <div className={`absolute bottom-0 left-0 right-0 h-12 rounded-b-[10px] flex items-end justify-center pb-2 text-[10px] font-extrabold uppercase tracking-widest z-0 ${tagStyle}`}>
                {ritual.category || 'FEATURED'}
              </div>

              {/* Main Card */}
              <div className="relative z-10 w-full bg-white rounded-[10px] overflow-hidden shadow-[0_4px_16px_rgba(20,40,30,0.06)] border border-[#e8e4db] transform transition-transform active:scale-[0.98]">
                {/* Image */}
                <div className="relative w-full aspect-[1/1] bg-[#e9e6df] overflow-hidden group">
                  <Image
                    src={ritual.image}
                    alt={ritual.title}
                    fill
                    className="object-cover transition-transform duration-500 ease-out active:scale-110"
                  />

                  {/* Tag */}
                  {ritual.badge && (
                    <div className="absolute top-2.5 left-2.5">
                      <span className={`text-[9px] font-bold px-2 py-0.5 rounded-[4px] tracking-wide backdrop-blur-sm shadow-sm ${tagStyle}`}>
                        {ritual.badge}
                      </span>
                    </div>
                  )}

                  {/* Heart */}
                  <button className="absolute top-2.5 right-2.5 w-7 h-7 flex items-center justify-center rounded-full bg-black/30 text-white backdrop-blur-md transition-transform active:scale-90">
                    <Heart size={14} strokeWidth={2} />
                  </button>
                </div>

                {/* Content */}
                <div className="p-3 flex flex-col flex-1">
                  {/* Rating */}
                  <div className="flex items-center gap-1.5 mb-1.5">
                    <Star size={15} className="fill-[#f5a000] text-[#f5a000]" />
                    <span className="text-[14px] font-bold text-[#273747]">{ritual.rating}</span>
                    <span className="text-[12px] text-[#747d85]">({ritual.reviews})</span>
                  </div>

                  <h3 className="font-semibold text-[15px] text-[#122940] leading-[1.15] mb-1">
                    {ritual.title}
                  </h3>

                  <p className="text-[10px] text-[#66717d] leading-snug line-clamp-2 min-h-[30px]">
                    {ritual.description}
                  </p>

                  {/* Footer */}
                  <div className="flex flex-col mt-auto pt-2">
                    <div className="flex items-baseline gap-1.5 mb-2">
                      <span className="text-[12px] font-bold text-[#dc6d3d] uppercase tracking-wide">From</span>
                      <span className="text-[22px] font-extrabold text-[#dc6d3d] leading-none tracking-tight">₹{ritual.price}</span>
                    </div>

                    <Link href={`/pujas/${ritual.slug}`} className="w-full bg-gradient-to-br from-[#0b3d33] via-[#0d4d40] to-[#12614f] text-white flex items-center justify-center gap-2 py-2.5 rounded-[8px] text-[13px] font-bold transition-transform active:scale-[0.97]">
                      View & Book
                      <ArrowRight size={14} strokeWidth={2.5} className="opacity-90" />
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}

import React from 'react';
import Image from 'next/image';
import Link from 'next/link';
import { ArrowRight } from 'lucide-react';

import { getForYouPujas } from '@/lib/data/pujas';

export default function MobilePujaForYou() {
  const pujas = getForYouPujas();

  return (
    <div className="w-full px-2.5 py-6 bg-[#fcfaf7]">
      <div className="px-5 mb-6 flex items-end justify-between">
        <div className="flex flex-col">
          <h2 className="text-[18px] font-bold text-[#10263f] leading-tight tracking-tight">
            Puja for You
          </h2>
        </div>
        <Link href="/pujas" className="flex items-center gap-1 text-[13px] font-bold text-[#0d4d40]">
          <span className="pb-0.5 underline underline-offset-[3px] decoration-[#0d4d40]/40">View all</span>
          <ArrowRight size={16} strokeWidth={2.5} className="mb-0.5" />
        </Link>
      </div>

      <div className="flex gap-5 overflow-x-auto no-scrollbar pb-6 px-4 snap-x snap-mandatory">
        {pujas.map((puja, index) => (
          <Link href={`/pujas/${puja.slug}`} key={index} className="flex flex-col items-center flex-shrink-0 w-[150px] snap-center">
            {/* Circular Image Container */}
            <div className="relative w-[150px] h-[150px] mb-5">
              {/* Circle Image */}
              <div className="relative w-full h-full rounded-full overflow-hidden border-2 border-white">
                <Image
                  src={puja.image}
                  alt={puja.title}
                  fill
                  className="object-cover"
                />
              </div>

              {/* Badge Overlay */}
              <div className="absolute -bottom-2 left-1/2 -translate-x-1/2 z-10 w-max">
                <span className={`text-[10px] font-bold px-2.5 py-0.5 rounded-md border-2 border-white ${puja.tagStyle}`}>
                  {puja.tag}
                </span>
              </div>
            </div>

            {/* Title & Arrow */}
            <div className="flex items-center justify-center gap-1 w-full px-1 mt-1">
              <h3 className="font-bold text-[15px] text-[#122940] leading-tight text-center">
                {puja.title}
              </h3>
            </div>
          </Link>
        ))}
      </div>
    </div>
  );
}

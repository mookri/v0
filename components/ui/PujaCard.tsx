import React from 'react'
import Image from 'next/image'
import Link from 'next/link'
import { ArrowRight, Heart, Star, Clock, MapPin, Info } from 'lucide-react'

export interface PujaData {
  id?: number | string
  title: string
  category: string
  price: string
  originalPrice?: string
  image: string
  badge?: string
  slug: string
  description: string
  rating?: string
  reviews?: string
  duration?: string
  location?: string
  priceRange?: string
  panditType?: string
  ritualType?: string
  availability?: string
}

interface PujaCardProps {
  puja: PujaData
}

export function PujaCard({ puja }: PujaCardProps) {
  // Use fallbacks to match the new design requirements if data is missing
  const rating = puja.rating || '4.8'
  const reviews = puja.reviews || '1.2K reviews'
  const duration = puja.duration || '3 - 5 hours'
  const location = puja.location || 'At your venue'
  
  // Format price if it contains 'Rs.' to '₹'
  const displayPrice = puja.price.replace('Rs.', '₹')

  return (
    <div className="relative z-10 w-full flex flex-col h-full bg-white rounded-[6px] overflow-hidden border border-[#e8e4db] group hover:shadow-[0_8px_30px_rgba(0,0,0,0.04)] transition-all duration-300">
      {/* Image */}
      <div className="relative w-full aspect-[5/4] bg-[#e9e6df] overflow-hidden">
        <Image
          src={puja.image}
          alt={puja.title}
          fill
          className="object-cover transition-transform duration-700 ease-out group-hover:scale-105"
        />

        {/* Badge */}
        {puja.badge && (
          <div className="absolute top-3 left-3 z-10">
            <span className="text-[11px] font-bold px-3 py-1.5 rounded-[4px] tracking-wide text-white bg-[#12614f] shadow-sm">
              {puja.badge}
            </span>
          </div>
        )}

        {/* Heart */}
        <button className="absolute top-3 right-3 z-10 w-9 h-9 flex items-center justify-center rounded-full bg-black/25 text-white backdrop-blur-md transition-transform hover:scale-110 active:scale-95">
          <Heart size={16} strokeWidth={2.5} className="mt-px" />
        </button>
      </div>

      {/* Content */}
      <div className="p-3 md:p-3.5 flex flex-col flex-1">
        
        {/* Rating (Moved to Top) */}
        <div className="flex items-center gap-1.5 mb-1.5">
          <Star size={14} className="fill-[#f5a000] text-[#f5a000]" />
          <span className="text-[12px] md:text-[13px] font-bold text-[#003049]">{rating}</span>
          <span className="text-[11px] md:text-[12px] text-[#747d85] truncate">({reviews})</span>
        </div>

        {/* Title */}
        <h3 className="font-bold text-[16px] md:text-[18px] text-[#003049] leading-tight mb-1.5 line-clamp-2">
          <Link href={`/pujas/${puja.slug}`} className="hover:text-[#dc6d3d] transition-colors">
            {puja.title}
          </Link>
        </h3>

        {/* Description */}
        <p className="text-[12px] md:text-[13.5px] text-[#6a7582] leading-relaxed mb-2.5 line-clamp-2">
          {puja.description}
        </p>

        {/* Horizontal Divider */}
        <hr className="border-[#eaeaea] mb-2.5" />

        {/* Details (Duration & Location) */}
        <div className="flex items-center gap-1.5 md:gap-3 mb-3 text-[10px] sm:text-[11px] md:text-[13px] text-[#6a7582]">
          <div className="flex items-center gap-1 min-w-0">
            <Clock size={13} className="shrink-0 sm:w-[13px] sm:h-[13px] md:w-[14px] md:h-[14px]" />
            <span className="truncate">{duration}</span>
          </div>
          <div className="w-[1px] h-3.5 bg-[#d4d4d4] shrink-0"></div>
          <div className="flex items-center gap-1 min-w-0">
            <MapPin size={13} className="shrink-0 sm:w-[13px] sm:h-[13px] md:w-[14px] md:h-[14px]" />
            <span className="truncate">{location}</span>
          </div>
        </div>

        {/* Footer */}
        <div className="mt-auto flex items-end justify-between pt-1">
          <div className="flex flex-col">
            <span className="text-[9px] md:text-[10px] font-bold text-[#FF4500] uppercase tracking-[0.05em] mb-0.5">From</span>
            <div className="flex items-center gap-1 sm:gap-1.5">
              <span className="text-[15px] sm:text-[18px] md:text-[22px] font-extrabold text-[#FF4500] leading-none tracking-tight">
                {displayPrice}
              </span>
              <Info size={12} className="text-[#FF4500] mb-0.5 shrink-0 sm:w-[14px] sm:h-[14px]" />
            </div>
          </div>

          <Link 
            href={`/pujas/${puja.slug}`} 
            className="shrink-0 bg-[#12614f] hover:bg-[#0b3d33] text-white flex items-center justify-center gap-1 sm:gap-1.5 px-2.5 py-1.5 sm:px-3 sm:py-2 md:px-4 md:py-2.5 rounded-[6px] text-[11px] sm:text-[13px] md:text-[14px] font-bold transition-all active:scale-[0.97]"
          >
            <span className="hidden sm:inline">View & </span>Book
            <ArrowRight size={13} strokeWidth={2.5} className="opacity-90 sm:w-[15px] sm:h-[15px]" />
          </Link>
        </div>
      </div>
    </div>
  )
}

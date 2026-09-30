"use client";

import Link from "next/link";
import Image from "next/image";
import { ArrowRight, Gift, Users, MapPin, ChevronRight } from "lucide-react";
import { Festival } from "@/types/quickActions";
import { useState } from "react";

interface Props {
    festival: Festival;
}

export default function FestivalCard({ festival }: Props) {
    const [imgSrc, setImgSrc] = useState(festival.image);

    return (
        <aside
            className="
                flex
                flex-col
                rounded-lg
                border
                border-neutral-200
                bg-white
                p-5
            "
        >
            {/* Header: Label + Link */}
            <div className="flex items-center justify-between mb-5">
                <h3 className="text-[15px] font-extrabold tracking-tight text-neutral-900">
                    Upcoming Festival
                </h3>
                <Link href="/calendar" className="text-[12px] font-bold text-orange-600 flex items-center gap-1 transition-colors">
                    View Calendar <ArrowRight className="h-3.5 w-3.5" />
                </Link>
            </div>

            {/* Date and Title */}
            <div className="flex items-start gap-3.5 mb-5">
                {/* Calendar Date Block */}
                <div className="flex flex-col items-center justify-center shrink-0 w-[56px] rounded-lg bg-white overflow-hidden text-center py-1.5 border border-neutral-200">
                    <div className="text-[10px] font-bold text-slate-500">
                        Wed
                    </div>
                    <div className="text-[22px] font-black text-orange-600 leading-none py-0.5">
                        27
                    </div>
                    <div className="text-[9px] font-bold text-slate-700">
                        Aug 2025
                    </div>
                </div>

                {/* Title Info */}
                <div className="flex flex-col min-w-0 pt-0.5">
                    <h4 className="text-[16px] font-bold text-neutral-900 leading-tight">
                        {festival.title}
                    </h4>
                    <p className="text-[12px] text-neutral-500 mt-1 leading-snug">
                        Celebrate the arrival of Bappa with special puja services.
                    </p>
                </div>
            </div>

            {/* Inner Banner */}
            <div className="relative overflow-hidden rounded-lg bg-gradient-to-r from-[#e3efff] to-[#d6e6fd] px-4 py-3 mb-5 flex items-center justify-between group border border-white/50">
                <div className="flex flex-col w-[130px] relative z-10">
                    <p className="text-[11px] font-semibold text-slate-800 leading-tight">
                        May Bappa remove all obstacles from your path.
                    </p>
                </div>
                
                {/* Decorative Image with Fade Mask */}
                <div className="absolute right-0 top-0 bottom-0 w-[120px] opacity-100 transition-transform"
                     style={{ maskImage: 'linear-gradient(to right, transparent, black 40%)', WebkitMaskImage: 'linear-gradient(to right, transparent, black 40%)' }}>
                    <Image
                        src={imgSrc}
                        alt="Ganesha"
                        fill
                        className="object-cover object-right"
                        onError={() => setImgSrc("/placeholder.jpg")}
                    />
                </div>

                <div className="relative z-10 flex h-[22px] w-[22px] shrink-0 items-center justify-center rounded-full bg-white text-slate-700 shadow-sm transition-transform">
                    <ChevronRight className="h-3 w-3" strokeWidth={2.5} />
                </div>
            </div>

            {/* Features List */}
            <div className="flex flex-col gap-2.5 mb-6">
                <div className="flex items-center gap-3 text-[12px] text-slate-600">
                    <Gift className="h-[15px] w-[15px] text-slate-700" strokeWidth={2} />
                    <span className="font-medium">Special Puja Packages</span>
                </div>
                <div className="flex items-center gap-3 text-[12px] text-slate-600">
                    <Users className="h-[15px] w-[15px] text-slate-700" strokeWidth={2} />
                    <span className="font-medium">Home Puja & Temple Puja</span>
                </div>
                <div className="flex items-center gap-3 text-[12px] text-slate-600">
                    <MapPin className="h-[15px] w-[15px] text-slate-700" strokeWidth={2} />
                    <span className="font-medium">Available in Delhi NCR</span>
                </div>
            </div>

            {/* CTA */}
            <Link
                href={festival.href}
                className="
                    flex
                    w-full
                    h-[42px]
                    items-center
                    justify-center
                    gap-2
                    rounded-[8px]
                    bg-orange-600
                    text-[13px]
                    font-bold
                    text-white
                    transition-all
                "
            >
                Book {festival.title} Puja
                <ArrowRight className="h-4 w-4" />
            </Link>
        </aside>
    );
}
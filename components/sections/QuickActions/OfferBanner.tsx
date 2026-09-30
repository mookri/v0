"use client";

import { Gift, ArrowRight } from "lucide-react";
import Link from "next/link";

export default function OfferBanner() {
    return (
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-xl border border-orange-200/60 bg-[#fffbf5] p-3 sm:pr-4">
            <div className="flex items-center gap-3 w-full sm:w-auto">
                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-[8px] bg-white border border-orange-100">
                    <Gift className="h-[18px] w-[18px] text-orange-500" strokeWidth={1.5} />
                </div>
                
                <div className="flex flex-col min-w-0 flex-1 sm:flex-initial">
                    <h3 className="truncate text-[13px] font-bold text-neutral-900 leading-tight">
                        First booking offer!
                    </h3>
                    <p className="truncate text-[11px] font-medium text-neutral-500">
                        Get flat 10% OFF on your first puja
                    </p>
                </div>
                
                <div className="hidden sm:block ml-2">
                    <div className="rounded-[6px] border border-dashed border-orange-300 bg-white px-2 py-1 text-[10px] font-bold tracking-widest text-neutral-800">
                        WELCOME10
                    </div>
                </div>
            </div>
            
            <Link
                href="/offers"
                className="
                    group 
                    flex 
                    shrink-0
                    items-center 
                    gap-1.5 
                    text-[12px] 
                    font-semibold 
                    text-orange-600 
                    transition-colors 
                    hover:text-orange-700
                "
            >
                View all offers
                <ArrowRight className="h-3.5 w-3.5 transition-transform duration-200 group-hover:translate-x-1" />
            </Link>
        </div>
    );
}

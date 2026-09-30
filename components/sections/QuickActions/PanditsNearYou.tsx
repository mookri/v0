"use client";

import { Star, ShieldCheck, MapPin, User } from "lucide-react";
import Link from "next/link";

const pandits = [
    {
        name: "Pandit Ramesh Sharma",
        rating: 4.8,
        reviews: 362,
        experience: "15+ Years Experience",
        distance: "2.1 km • Delhi",
        avatar: "/images/pandits/pandit-1.jpg",
    },
    {
        name: "Pandit Ankit Shastri",
        rating: 4.9,
        reviews: 176,
        experience: "8+ Years Experience",
        distance: "3.2 km • Delhi",
        avatar: "/images/pandits/pandit-2.jpg",
    },
    {
        name: "Pandit Vivek Mishra",
        rating: 4.7,
        reviews: 89,
        experience: "12+ Years Experience",
        distance: "4.5 km • Noida",
        avatar: "/images/pandits/pandit-1.jpg",
    },
    {
        name: "Pandit Suresh Bhatt",
        rating: 4.9,
        reviews: 214,
        experience: "10+ Years Experience",
        distance: "1.8 km • Gurugram",
        avatar: "/images/pandits/pandit-2.jpg",
    },
];

export default function PanditsNearYou() {
    return (
        <div className="flex gap-4 overflow-x-auto pb-4 pt-1 [-ms-overflow-style:none] [scrollbar-width:none]">
            {pandits.map((pandit) => (
                <div
                    key={pandit.name}
                    className="
                        group
                        flex 
                        w-[280px]
                        sm:w-[320px]
                        shrink-0 
                        items-center
                        rounded-lg 
                        border 
                        border-neutral-200 
                        bg-white 
                        p-3.5
                        transition-all
                        duration-200
                        focus-visible:border-orange-400
                        focus-visible:ring-2
                        focus-visible:ring-orange-500/20
                    "
                >
                    {/* Avatar Fallback */}
                    <div className="relative flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full bg-orange-50 ring-1 ring-orange-100">
                        <User className="h-6 w-6 text-orange-500" />
                    </div>
                    
                    {/* Info Wrapper */}
                    <div className="flex flex-col flex-1 w-full min-w-0 ml-3.5">
                        <div className="flex items-center gap-1.5 min-w-0">
                            <span className="text-[14px] sm:text-[15px] font-bold text-neutral-900 leading-snug truncate">
                                {pandit.name}
                            </span>
                            <ShieldCheck className="h-4 w-4 text-emerald-500 shrink-0" strokeWidth={2.5} />
                        </div>
                        
                        <div className="flex items-center gap-1.5 text-[11px] sm:text-[12px] text-neutral-500 mt-0.5">
                            <Star className="h-3 w-3 sm:h-3.5 sm:w-3.5 fill-orange-500 text-orange-500" />
                            <span className="font-bold text-neutral-800">{pandit.rating}</span>
                            <span>({pandit.reviews})</span>
                        </div>

                        <div className="flex items-end justify-between mt-2.5">
                            <div className="flex flex-col text-[11px] sm:text-[12px] text-slate-500 gap-1">
                                <span>{pandit.experience}</span>
                                <div className="flex items-center gap-1">
                                    <MapPin className="h-3 w-3 text-slate-400" strokeWidth={1.75} />
                                    <span>{pandit.distance}</span>
                                </div>
                            </div>

                            {/* Book Now Button */}
                            <Link
                                href="/pandits"
                                className="
                                    flex 
                                    shrink-0
                                    items-center 
                                    justify-center 
                                    rounded-md
                                    bg-orange-500
                                    px-3.5
                                    py-1.5
                                    text-[11px] sm:text-[12px]
                                    font-bold 
                                    text-white 
                                    transition-colors
                                    hover:bg-orange-600
                                "
                            >
                                Book Now
                            </Link>
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
}

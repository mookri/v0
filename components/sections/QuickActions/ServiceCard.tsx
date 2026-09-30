"use client";

import Link from "next/link";
import Image from "next/image";
import { Clock3, Home, Heart } from "lucide-react";
import { Service } from "@/types/quickActions";
import { useState } from "react";

interface Props {
    service: Service;
}

export default function ServiceCard({ service }: Props) {
    const [imgSrc, setImgSrc] = useState(service.image);

    // Determine badge color based on text
    const badgeColor = service.badge?.toLowerCase() === "popular" 
        ? "text-emerald-600 border-emerald-200" 
        : "text-orange-600 border-orange-200";

    return (
        <Link
            href={service.href}
            className="group block w-[220px] sm:w-[260px] xl:w-[calc(25%-12px)] shrink-0 outline-none"
        >
            <article
                className="
                    overflow-hidden
                    rounded-lg
                    border
                    border-neutral-200
                    bg-white
                    transition-all
                    duration-200
                    focus-visible:border-orange-400
                    focus-visible:ring-2
                    focus-visible:ring-orange-500/20
                "
            >
                {/* ---------------------------------------------
                    IMAGE
                --------------------------------------------- */}
                <div className="relative aspect-[16/10] overflow-hidden bg-neutral-100">
                    <Image
                        src={imgSrc}
                        alt={service.title}
                        fill
                        sizes="(max-width: 640px) 220px, 260px"
                        className="
                            object-cover
                            transition-transform
                            duration-500
                        "
                        onError={() => setImgSrc("/placeholder.jpg")}
                    />

                    {/* Gradient Overlay for Top Icons */}
                    <div className="absolute inset-x-0 top-0 h-16 bg-gradient-to-b from-black/40 to-transparent pointer-events-none" />

                    {/* Heart Icon (Top Right) */}
                    <button 
                        type="button"
                        aria-label="Save puja"
                        className="
                            absolute 
                            right-3 
                            top-3
                            transition-transform
                            z-10
                        "
                        onClick={(e) => e.preventDefault()}
                    >
                        <Heart className="h-[22px] w-[22px] text-white drop-shadow-md" strokeWidth={1.5} />
                    </button>

                    {/* Badge (Top Left) */}
                    {service.badge && (
                        <span
                            className={`
                                absolute
                                left-3
                                top-3
                                rounded-md
                                bg-white
                                px-2.5
                                py-1
                                text-[11px]
                                font-bold
                                leading-none
                                shadow-sm
                                z-10
                                ${badgeColor}
                            `}
                        >
                            {service.badge}
                        </span>
                    )}
                </div>

                {/* ---------------------------------------------
                    CONTENT
                --------------------------------------------- */}
                <div className="p-3.5">
                    <div className="min-w-0">
                        <h3 className="truncate text-[14px] sm:text-[15px] font-bold leading-tight text-neutral-900">
                            {service.title}
                        </h3>
                        <p className="mt-1 truncate text-[11px] text-neutral-500">
                            {service.subtitle}
                        </p>
                    </div>

                    {/* Metadata */}
                    <div className="mt-2.5 flex items-center gap-2.5 text-[11px] text-neutral-500 font-medium">
                        <div className="flex items-center gap-1.5">
                            <Clock3 className="h-3.5 w-3.5 text-neutral-400" strokeWidth={1.75} />
                            <span>{service.duration ?? "60 mins"}</span>
                        </div>
                        <span className="text-neutral-300">|</span>
                        <div className="flex items-center gap-1.5">
                            <Home className="h-3.5 w-3.5 text-neutral-400" strokeWidth={1.75} />
                            <span>At Home</span>
                        </div>
                    </div>

                    {/* Bottom row */}
                    <div className="mt-3.5 flex items-center justify-between">
                        <span className="text-[16px] font-bold tracking-tight text-neutral-900">
                            ₹{service.price ?? "1,499"}
                        </span>

                        <span
                            className="
                                inline-flex
                                items-center
                                justify-center
                                h-[32px]
                                px-4
                                rounded-md
                                border
                                border-orange-500
                                bg-white
                                text-[11px]
                                font-bold
                                text-orange-600
                                transition-colors
                            "
                        >
                            Book Now
                        </span>
                    </div>
                </div>
            </article>
        </Link>
    );
}
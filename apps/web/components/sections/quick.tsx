"use client";

import { useRef } from "react";
import { ArrowRight } from "lucide-react";
import Link from "next/link";

import { services, festival } from "@/lib/data/QuickAction/data";
import ServiceCard from "./QuickActions/ServiceCard";
import FestivalCard from "./QuickActions/FestivalCard";
import CarouselControls from "./QuickActions/CarouselControls";
import QuickLinks from "./QuickActions/QuickLinks";
import SacredSpaces from "./QuickActions/SacredSpaces";
import PanditsNearYou from "./QuickActions/PanditsNearYou";
import OfferBanner from "./QuickActions/OfferBanner";

export function QuickActions() {
    const scrollContainerRef = useRef<HTMLDivElement>(null);

    const scroll = (direction: number) => {
        const container = scrollContainerRef.current;

        if (!container) return;

        container.scrollBy({
            left: direction * 400, // Adjusted scroll step
            behavior: "smooth",
        });
    };

    return (
        <section className="border-b border-neutral-200 bg-[#fafafa]">
            <div className="mx-auto max-w-[1440px] px-3 py-6 sm:px-4 lg:px-6">

                {/* 1. TOP QUICK LINKS */}
                <QuickLinks />

                {/* 2. MAIN CONTENT AREA */}
                <div className="flex flex-col">
                    
                    {/* TOP SECTION: Trending Pujas & Festival Card */}
                    <div className="grid grid-cols-1 gap-x-6 lg:grid-cols-[minmax(0,1fr)_340px] xl:grid-cols-[minmax(0,1fr)_360px] mb-8">
                        {/* LEFT COLUMN: Trending Header & Carousel */}
                        <div className="flex flex-col min-w-0">
                            {/* Trending Pujas Header */}
                            <div className="flex items-center justify-between mb-4">
                                <div className="flex flex-col min-w-0">
                                    <h2 className="text-[20px] sm:text-[22px] lg:text-[24px] font-bold tracking-tight text-neutral-900">
                                        Trending Pujas
                                    </h2>
                                    <p className="text-[13px] sm:text-[14px] text-neutral-500 mt-0.5 hidden sm:block">
                                        Popular rituals for peace, prosperity and wellbeing
                                    </p>
                                </div>

                                <div className="flex items-center gap-4">
                                    <Link
                                        href="/pujas"
                                        className="hidden items-center gap-1 text-[12px] font-bold text-orange-600 transition-colors hover:text-orange-700 sm:flex group"
                                    >
                                        View all
                                        <ArrowRight className="h-3.5 w-3.5 transition-transform duration-200 group-hover:translate-x-1" />
                                    </Link>

                                    <CarouselControls
                                        onPrev={() => scroll(-1)}
                                        onNext={() => scroll(1)}
                                    />
                                </div>
                            </div>
                            
                            {/* Trending Pujas Carousel */}
                            <div
                                ref={scrollContainerRef}
                                className="flex gap-4 overflow-x-auto pb-2 pt-1 [-ms-overflow-style:none] [scrollbar-width:none]"
                            >
                                {services.map((service) => (
                                    <ServiceCard
                                        key={service.title}
                                        service={service}
                                    />
                                ))}
                            </div>
                        </div>

                        {/* RIGHT COLUMN: Festival Card */}
                        <div className="hidden lg:flex flex-col h-full pb-2">
                            <FestivalCard festival={festival} />
                        </div>
                        
                        {/* Mobile Only: Festival Card */}
                        <div className="flex lg:hidden flex-col mt-6">
                            <FestivalCard festival={festival} />
                        </div>
                    </div>

                    {/* BOTTOM SECTION: Pandits & Sacred Spaces */}
                    <div className="grid grid-cols-1 gap-x-6 lg:grid-cols-[minmax(0,1fr)_340px] xl:grid-cols-[minmax(0,1fr)_360px]">
                        {/* LEFT COLUMN: Pandits Header & Carousel */}
                        <div className="flex flex-col min-w-0">
                            {/* Pandits Near You Header */}
                            <div className="flex items-center justify-between mb-4">
                                <div className="flex flex-col min-w-0">
                                    <h2 className="text-[20px] sm:text-[22px] lg:text-[24px] font-bold tracking-tight text-neutral-900">
                                        Verified Pandits Near You
                                    </h2>
                                    <p className="text-[13px] sm:text-[14px] text-neutral-500 mt-0.5 hidden sm:block">
                                        Experienced and background-verified pandits for authentic rituals
                                    </p>
                                </div>

                                <div className="flex items-center gap-4">
                                    <Link
                                        href="/pandits"
                                        className="hidden items-center gap-1 text-[13px] font-bold text-orange-600 transition-colors hover:text-orange-700 sm:flex group"
                                    >
                                        View all Pandits
                                        <ArrowRight className="h-3.5 w-3.5 transition-transform duration-200 group-hover:translate-x-1" />
                                    </Link>

                                    <CarouselControls
                                        onPrev={() => { }}
                                        onNext={() => { }}
                                    />
                                </div>
                            </div>
                            
                            {/* Pandits Near You Carousel */}
                            <div className="flex flex-col min-w-0">
                                <PanditsNearYou />
                            </div>
                        </div>

                        {/* RIGHT COLUMN: Sacred Spaces */}
                        <div className="hidden lg:flex flex-col h-full pb-4">
                            <SacredSpaces />
                        </div>
                    </div>

                    {/* BOTTOM: Offer Banner */}
                    <div className="mt-8">
                        <OfferBanner />
                    </div>

                </div>
            </div>
        </section>
    );
}
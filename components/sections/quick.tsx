"use client";

import { useRef } from "react";
import { services, festival } from "@/lib/data/QuickAction/data";
import ServiceCard from "./QuickActions/ServiceCard";
import FestivalCard from "./QuickActions/FestivalCard";
import CarouselControls from "./QuickActions/CarouselControls";

export function QuickActions() {
    const scrollContainerRef = useRef<HTMLDivElement>(null);

    const scroll = (direction: number) => {
        if (scrollContainerRef.current) {
            const scrollAmount = 300;
            scrollContainerRef.current.scrollBy({ left: direction * scrollAmount, behavior: "smooth" });
        }
    };

    return (
        <section className="py-14">
            <div className="mx-auto max-w-7xl px-4 lg:px-8">
                <div className="grid xl:grid-cols-12 gap-10">
                    {/* Services */}
                    <div className="xl:col-span-9">
                        <div className="mb-8 flex items-end justify-between">
                            <div>
                                <h2 className="text-3xl font-bold">
                                    Featured Services
                                </h2>
                                <p className="mt-2 text-neutral-500">
                                    Explore rituals, astrology and verified spiritual services.
                                </p>
                            </div>

                            <CarouselControls
                                onPrev={() => scroll(-1)}
                                onNext={() => scroll(1)}
                            />
                        </div>

                        <div
                            ref={scrollContainerRef}
                            className="
                                flex
                                gap-5
                                overflow-x-auto
                                snap-x
                                snap-mandatory
                                scroll-smooth
                                no-scrollbar
                                pb-4
                            "
                            style={{ scrollbarWidth: 'none', msOverflowStyle: 'none' }}
                        >
                            {services.map((service) => (
                                <ServiceCard
                                    key={service.title}
                                    service={service}
                                />
                            ))}
                        </div>
                    </div>

                    {/* Festival */}
                    <div className="xl:col-span-3">
                        <FestivalCard festival={festival} />
                    </div>
                </div>
            </div>
        </section>
    );
}
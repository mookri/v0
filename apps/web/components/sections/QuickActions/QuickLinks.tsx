"use client";

import Link from "next/link";
import { Flame, User, CalendarDays, Compass, Landmark, LayoutGrid } from "lucide-react";

const links = [
    {
        title: "Book a Puja",
        icon: Flame,
        href: "/book",
        colorClass: "text-orange-600",
        bgClass: "bg-orange-50",
    },
    {
        title: "Find a Pandit",
        icon: User,
        href: "/pandits",
        colorClass: "text-emerald-600",
        bgClass: "bg-emerald-50",
    },
    {
        title: "Festivals",
        icon: CalendarDays,
        href: "/festivals",
        colorClass: "text-rose-600",
        bgClass: "bg-rose-50",
    },
    {
        title: "Astrology",
        icon: Compass,
        href: "/astrology",
        colorClass: "text-indigo-600",
        bgClass: "bg-indigo-50",
    },
    {
        title: "Temple Services",
        icon: Landmark,
        href: "/temples",
        colorClass: "text-sky-600",
        bgClass: "bg-sky-50",
    },
    {
        title: "View All",
        icon: LayoutGrid,
        href: "/services",
        colorClass: "text-purple-600",
        bgClass: "bg-purple-50",
    },
];

export default function QuickLinks() {
    return (
        <div className="mt-6 mb-10 sm:mb-12 lg:mb-14 w-full">
            <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-10 lg:gap-14">

                {/* Left Side: Title */}
                <div className="shrink-0 text-center lg:text-left lg:w-[260px] xl:w-[320px]">
                    <h2 className="text-[22px] sm:text-[26px] lg:text-[30px] font-bold tracking-tight text-neutral-900 leading-none">
                        Our<br />Services
                    </h2>
                    <p className="text-[13px] text-neutral-500 mt-0.5 max-w-sm mx-auto lg:mx-0">
                        Everything you need for your spiritual journey, beautifully organized and just a click away.
                    </p>
                </div>

                {/* Vertical Divider */}
                <div className="hidden lg:block w-px h-12 bg-neutral-200 shrink-0" />

                {/* Right Side: Fit For All Links */}
                <div className="flex-1 w-full">
                    <div className="grid grid-cols-3 lg:grid-cols-3 xl:grid-cols-6 gap-y-12 gap-x-4 sm:gap-x-8">
                        {links.map((link) => {
                            const Icon = link.icon;
                            return (
                                <Link
                                    key={link.title}
                                    href={link.href}
                                    className="group flex flex-col items-center justify-start gap-4 sm:gap-5 outline-none"
                                >
                                    {/* Center Decorative Circle */}
                                    <div className={`
                                        h-14 w-14 sm:h-16 sm:w-16 lg:h-[72px] lg:w-[72px] rounded-full shrink-0
                                        ${link.bgClass} 
                                        flex items-center justify-center
                                        transition-transform duration-500 ease-out group-hover:scale-110
                                    `}>
                                        <Icon
                                            className={`h-6 w-6 sm:h-7 sm:w-7 lg:h-8 lg:w-8 ${link.colorClass}`}
                                            strokeWidth={1.5}
                                        />
                                    </div>

                                    {/* Title */}
                                    <span className="text-[12px] sm:text-[13px] lg:text-[14px] font-semibold text-neutral-700 text-center leading-tight transition-colors duration-300 group-hover:text-neutral-900">
                                        {link.title}
                                    </span>
                                </Link>
                            );
                        })}
                    </div>
                </div>

            </div>
        </div>
    );
}

"use client";

import Link from "next/link";
import { ArrowRight, CalendarDays, Flower2, Users } from "lucide-react";
import { Festival } from "@/types/quickActions";

interface Props {
    festival: Festival;
}

export default function FestivalCard({ festival }: Props) {
    return (
        <aside
            className="
      group
      relative
      overflow-hidden
      rounded-3xl
      border
      border-neutral-200/80
      dark:border-white/10
      bg-white
      dark:bg-neutral-900
      shadow-[0_1px_2px_rgba(15,23,42,.04),0_12px_30px_rgba(15,23,42,.08)]
      transition-all
      duration-300
      hover:-translate-y-1
      hover:shadow-[0_20px_45px_rgba(15,23,42,.12)]
    "
        >
            {/* Background Glow */}

            <div
                className="
        absolute
        right-[-70px]
        top-[-70px]
        h-48
        w-48
        rounded-full
        bg-orange-100/60
        blur-3xl
        dark:bg-orange-500/10
      "
            />

            <div className="relative p-5">

                {/* Header */}

                <div className="flex items-center justify-between">

                    <div className="flex items-center gap-2">

                        <div
                            className="
              flex
              h-10
              w-10
              items-center
              justify-center
              rounded-2xl
              bg-orange-100
              text-orange-600
              dark:bg-orange-500/10
            "
                        >
                            <Flower2 className="h-5 w-5" />
                        </div>

                        <div>

                            <p
                                className="
                text-xs
                font-semibold
                uppercase
                tracking-[0.18em]
                text-orange-600
              "
                            >
                                Upcoming Festival
                            </p>

                            <h2
                                className="
                mt-1
                text-xl
                font-bold
                text-neutral-900
                dark:text-white
              "
                            >
                                {festival.title}
                            </h2>

                        </div>

                    </div>

                    <span
                        className="
            rounded-full
            bg-orange-50
            px-3
            py-1
            text-xs
            font-semibold
            text-orange-600
            dark:bg-orange-500/10
          "
                    >
                        {festival.status}
                    </span>

                </div>

                {/* Image */}

                <div
                    className="
          mt-5
          overflow-hidden
          rounded-2xl
          bg-gradient-to-br
          from-orange-50
          to-amber-100
        "
                >
                    <img
                        src={festival.image}
                        alt={festival.title}
                        draggable={false}
                        className="
              aspect-[4/3]
              w-full
              object-cover
              transition-transform
              duration-700
              group-hover:scale-105
            "
                    />
                </div>

                {/* Info */}

                <div className="mt-5 space-y-3">

                    <div className="flex items-center gap-2">

                        <CalendarDays className="h-4 w-4 text-orange-500" />

                        <span className="text-sm text-neutral-600 dark:text-neutral-300">
                            {festival.date}
                        </span>

                    </div>

                    <div className="flex items-center gap-2">

                        <Users className="h-4 w-4 text-orange-500" />

                        <span className="text-sm text-neutral-600 dark:text-neutral-300">
                            {festival.bookings}
                        </span>

                    </div>

                </div>

                {/* CTA */}

                <Link
                    href={festival.href}
                    className="
          mt-6
          flex
          items-center
          justify-center
          gap-2
          rounded-2xl
          bg-orange-500
          px-4
          py-3
          text-sm
          font-semibold
          text-white
          transition-all
          duration-300
          hover:bg-orange-600
        "
                >
                    Explore Pujas

                    <ArrowRight
                        className="
            h-4
            w-4
            transition-transform
            duration-300
            group-hover:translate-x-1
          "
                    />

                </Link>

            </div>
        </aside>
    );
}
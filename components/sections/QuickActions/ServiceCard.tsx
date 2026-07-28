"use client";

import Link from "next/link";
import { ArrowRight } from "lucide-react";
import { Service } from "@/types/quickActions";

interface Props {
    service: Service;
}

export default function ServiceCard({ service }: Props) {
    return (
        <Link
            href={service.href}
            className="group shrink-0 w-[156px] outline-none"
        >
            <article
                className="
        rounded-3xl
        border
        border-neutral-200/80
        dark:border-white/10
        bg-white
        dark:bg-neutral-900
        p-3
        transition-all
        duration-300
        hover:-translate-y-1
        hover:border-orange-200
        hover:shadow-xl
      "
            >
                {/* Image */}

                <div
                    className={`
          aspect-[4/3]
          rounded-2xl
          overflow-hidden
          bg-gradient-to-br
          ${service.gradient}
        `}
                >
                    <img
                        src={`/.jpg?key=s7bpr&height=350&width=350&query=${encodeURIComponent(
                            service.imgQuery
                        )}`}
                        alt={service.title}
                        draggable={false}
                        className="
              h-full
              w-full
              object-cover
              transition-transform
              duration-500
              group-hover:scale-105
            "
                    />
                </div>

                {/* Bottom */}

                <div className="mt-4 flex items-start justify-between">
                    <div>
                        <h3
                            className="
              text-[14px]
              font-semibold
              text-neutral-900
              dark:text-white
              leading-tight
            "
                        >
                            {service.title}
                        </h3>

                        <p
                            className="
              mt-1
              text-[11px]
              text-neutral-500
            "
                        >
                            {service.subtitle}
                        </p>
                    </div>

                    <ArrowRight
                        className="
            h-4
            w-4
            text-neutral-400
            transition-all
            duration-300
            group-hover:text-orange-500
            group-hover:translate-x-1
          "
                    />
                </div>
            </article>
        </Link>
    );
}
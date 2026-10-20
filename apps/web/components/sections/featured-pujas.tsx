import Link from "next/link";
import { ArrowRight, CalendarDays, ShieldCheck, UsersRound, IndianRupee } from "lucide-react";
import { getFeaturedPujas } from "@/dal/catalog";
import { FeaturedPujasCarousel } from "./featured-pujas-carousel";

const trustPoints = [
  {
    icon: ShieldCheck,
    title: "Verified Pandits",
    description: "Identity & experience verified",
  },
  {
    icon: IndianRupee,
    title: "Transparent Pricing",
    description: "Clear packages, no hidden charges",
  },
  {
    icon: CalendarDays,
    title: "Flexible Scheduling",
    description: "At your home, temple or venue",
  },
  {
    icon: UsersRound,
    title: "Personalized Support",
    description: "From planning to completion",
  },
];

export async function FeaturedPujas() {
  // Fetch data using the cached DAL (Rule #05 & #24)
  const pujas = await getFeaturedPujas();

  return (
    <section className="w-full bg-[#fcfaf7] py-9 md:py-11 overflow-hidden">
      <div className="w-full max-w-[1720px] mx-auto px-4 md:px-10 xl:px-20 flex flex-col md:flex-row gap-8 md:gap-12 xl:gap-[140px] items-start">
        {/* LEFT CONTENT (Static Server Rendered) */}
        <aside className="min-w-0 md:w-[260px] xl:w-[340px] flex-shrink-0">
          <h2 className="text-[#10263f] text-3xl md:text-4xl lg:text-[42px] font-semibold leading-[1.05] tracking-[-0.025em]">
            Featured Pujas
          </h2>

          <p className="max-w-[320px] mt-4 text-[#66717d] text-sm leading-relaxed">
            Explore our most popular rituals performed by verified pandits. Authentic Vedic ceremonies for life's important moments.
          </p>

          <div className="flex flex-col gap-4 mt-6">
            {trustPoints.map((item, idx) => {
              const Icon = item.icon;
              return (
                <div
                  className={`flex items-center gap-3 group ${idx > 2 ? "hidden md:flex" : ""}`}
                  key={item.title}
                >
                  <div className="w-[34px] h-[34px] flex-none flex items-center justify-center rounded-full bg-[#faeedc] text-[#a96634] group-hover:scale-[1.15] group-hover:bg-[#f0e1c6] transition-all duration-250">
                    <Icon className="w-4 h-4" strokeWidth={1.9} />
                  </div>

                  <div>
                    <h3 className="text-[#1d2e3f] text-[13px] font-bold leading-tight m-0">{item.title}</h3>
                    <p className="text-[#7a838c] text-[11px] leading-[1.3] mt-0.5 m-0">{item.description}</p>
                  </div>
                </div>
              );
            })}
          </div>

          <Link
            href="/pujas"
            className="w-[180px] h-11 mt-6 flex items-center justify-center gap-2.5 rounded-md bg-[#0d4d40] text-white text-[13px] font-bold shadow-[0_4px_12px_rgba(13,77,64,0.2)] hover:-translate-y-[2px] hover:shadow-[0_8px_20px_rgba(13,77,64,0.3)] transition-all duration-200 group"
          >
            <span>View All Rituals</span>
            <ArrowRight className="w-4 h-4 transition-transform duration-250 group-hover:translate-x-1" />
          </Link>
        </aside>

        {/* RIGHT CONTENT (Interactive Client Component) */}
        <FeaturedPujasCarousel pujas={pujas} />
      </div>
    </section>
  );
}
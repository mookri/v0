// app/components/mobile/home/MobileHome.tsx

import MobileTopBar from "./MobileTopBar";
import MobileHero from "./MobileHero";
import MobileCategories from "./MobileCategories";
import MobileFeaturedRituals from "./MobileFeaturedRituals";
import MobilePujaForYou from "./MobilePujaForYou";
import MobileBottomNav from "./MobileBottomNav";

export default function MobileHome() {
  return (
    <main className="relative min-h-screen bg-[#023020] pb-24 text-white">

      <MobileTopBar />
      <MobileHero />

      {/* White content */}
      <section
        className="
          relative
          z-10
          rounded-t-[28px]
          bg-[#fcfaf7]
          pt-6
          text-gray-900
          shadow-[0_-5px_18px_rgba(0,0,0,0.06)]
        "
      >
        <div className="flex flex-col">
          <MobileCategories />
          <MobileFeaturedRituals />
          <MobilePujaForYou />
        </div>
      </section>

      <MobileBottomNav />
    </main>
  );
}
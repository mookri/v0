import { Award, Package, Tent, Calendar, IndianRupee, Languages } from "lucide-react";
import Image from "next/image";

export function WhyChoose() {
  return (
    <section aria-labelledby="why-choose" className="w-full py-16 px-4 sm:px-6 lg:px-8 max-w-[1400px] mx-auto">
      <div className="flex flex-col lg:flex-row gap-10 lg:gap-12">

        {/* Left Side: Heading */}
        <div className="w-full lg:w-[30%] xl:w-[28%] flex flex-col justify-start pt-2 lg:pt-8">
          <div className="sticky top-24">
            <h2 id="why-choose" className="text-[28px] sm:text-[32px] lg:text-[36px] font-bold text-[#153431] tracking-tight mb-4 leading-[1.15]">
              Why Choose<br className="hidden lg:block"/> Navdhya
            </h2>
            <p className="text-[#6a7582] text-[14px] sm:text-[15px] leading-relaxed">
              Verified priests, complete rituals, transparent pricing — Navdhya brings authentic Vedic puja services to your home with the convenience modern families deserve.
            </p>
          </div>
        </div>

        {/* Right Side: Small Cards Grid */}
        <div className="w-full lg:w-[70%] xl:w-[72%]">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5 auto-rows-[180px] sm:auto-rows-[200px]">

            {/* Item 1: Pandits (Image) */}
            <div className="group relative overflow-hidden rounded-xl bg-white cursor-pointer transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
              <Image
                src="https://images.unsplash.com/photo-1604085572504-a392ddf0d86a?q=80&w=800"
                alt="Vedic Pandits"
                fill
                className="object-cover transition-transform duration-700 group-hover:scale-110"
              />
              <div className="absolute inset-0 bg-black/40 transition-colors duration-300 group-hover:bg-black/50" />
              <div className="absolute inset-0 p-5 flex flex-col justify-between">
                <div className="text-white relative z-10 transition-transform duration-300 group-hover:translate-x-1">
                  <h3 className="font-bold text-[16px] mb-1.5 leading-tight tracking-tight">Vedic Certified Pandits</h3>
                  <p className="text-[12px] sm:text-[13px] text-white/90 line-clamp-2 leading-relaxed">Scholars trained in authentic Vedic traditions with years of experience.</p>
                </div>
                <div className="self-end bg-white w-8 h-8 rounded-full flex items-center justify-center text-[#153431] transition-transform duration-300 group-hover:scale-110 group-hover:bg-[#f8f5ee]">
                  <Award className="w-4 h-4" />
                </div>
              </div>
            </div>

            {/* Item 2: Samagri (Orange) */}
            <div className="group bg-[#ca7445] rounded-xl p-5 text-white flex flex-col justify-between cursor-pointer transition-all duration-300 hover:shadow-xl hover:-translate-y-1 hover:bg-[#d87c4a]">
              <div className="transition-transform duration-300 group-hover:translate-x-1">
                <h3 className="font-bold text-[16px] mb-1.5 leading-tight tracking-tight">Complete Puja Samagri</h3>
                <p className="text-[12px] sm:text-[13px] text-white/90 line-clamp-2 leading-relaxed">Premium-quality ritual materials sourced directly from trusted suppliers.</p>
              </div>
              <div className="self-end bg-white w-8 h-8 rounded-full flex items-center justify-center text-[#ca7445] transition-transform duration-300 group-hover:scale-110 group-hover:bg-[#f8f5ee]">
                <Package className="w-4 h-4" />
              </div>
            </div>

            {/* Item 3: Pricing (White) */}
            <div className="group bg-white rounded-xl p-5 text-[#153431] flex flex-col justify-between border border-gray-200 cursor-pointer transition-all duration-300 hover:shadow-xl hover:-translate-y-1 hover:border-[#ca7445]/30">
              <div className="transition-transform duration-300 group-hover:translate-x-1">
                <h3 className="font-bold text-[16px] mb-1.5 leading-tight tracking-tight">Transparent Pricing</h3>
                <p className="text-[12px] sm:text-[13px] text-[#6a7582] line-clamp-2 leading-relaxed">No hidden costs, clear packages with guarantees.</p>
              </div>
              <div className="self-end bg-[#f8f5ee] w-8 h-8 rounded-full flex items-center justify-center text-[#ca7445] transition-transform duration-300 group-hover:scale-110 group-hover:bg-[#ca7445] group-hover:text-white">
                <IndianRupee className="w-4 h-4" />
              </div>
            </div>

            {/* Item 4: Mandap (Image) */}
            <div className="group relative overflow-hidden rounded-xl bg-white cursor-pointer transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
              <Image
                src="https://images.unsplash.com/photo-1583156340160-785660689bce?q=80&w=800"
                alt="Mandap Setup"
                fill
                className="object-cover transition-transform duration-700 group-hover:scale-110"
              />
              <div className="absolute inset-0 bg-black/40 transition-colors duration-300 group-hover:bg-black/50" />
              <div className="absolute inset-0 p-5 flex flex-col justify-between">
                <div className="text-white relative z-10 transition-transform duration-300 group-hover:translate-x-1">
                  <h3 className="font-bold text-[16px] mb-1.5 leading-tight tracking-tight">Professional Mandap Setup</h3>
                  <p className="text-[12px] sm:text-[13px] text-white/90 line-clamp-2 leading-relaxed">Beautiful, traditional decorations arranged by expert decorators.</p>
                </div>
                <div className="self-end bg-white w-8 h-8 rounded-full flex items-center justify-center text-[#153431] transition-transform duration-300 group-hover:scale-110 group-hover:bg-[#f8f5ee]">
                  <Tent className="w-4 h-4" />
                </div>
              </div>
            </div>

            {/* Item 5: Scheduling (Green) */}
            <div className="group bg-[#153431] rounded-xl p-5 text-white flex flex-col justify-between cursor-pointer transition-all duration-300 hover:shadow-xl hover:-translate-y-1 hover:bg-[#1a403d]">
              <div className="transition-transform duration-300 group-hover:translate-x-1">
                <h3 className="font-bold text-[16px] mb-1.5 leading-tight tracking-tight">Flexible Scheduling</h3>
                <p className="text-[12px] sm:text-[13px] text-white/80 line-clamp-2 leading-relaxed">Book 365 days a year with customizable timings.</p>
              </div>
              <div className="self-end bg-white/10 w-8 h-8 rounded-full flex items-center justify-center text-white transition-transform duration-300 group-hover:scale-110 group-hover:bg-white/20">
                <Calendar className="w-4 h-4" />
              </div>
            </div>

            {/* Item 6: Multilingual (White) */}
            <div className="group bg-white rounded-xl p-5 text-[#153431] flex flex-col justify-between border border-gray-200 cursor-pointer transition-all duration-300 hover:shadow-xl hover:-translate-y-1 hover:border-[#ca7445]/30">
              <div className="transition-transform duration-300 group-hover:translate-x-1">
                <h3 className="font-bold text-[16px] mb-1.5 leading-tight tracking-tight">Multilingual Pandits</h3>
                <p className="text-[12px] sm:text-[13px] text-[#6a7582] line-clamp-2 leading-relaxed">Hindi, English, and regional language pandits.</p>
              </div>
              <div className="self-end bg-[#f8f5ee] w-8 h-8 rounded-full flex items-center justify-center text-[#153431] transition-transform duration-300 group-hover:scale-110 group-hover:bg-[#153431] group-hover:text-white">
                <Languages className="w-4 h-4" />
              </div>
            </div>

          </div>
        </div>

      </div>
    </section>
  );
}
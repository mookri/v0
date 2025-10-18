"use client";

import { useState, useEffect, useRef, useCallback, useMemo } from "react";
import { Star, CheckCircle } from "lucide-react";

interface Testimonial {
  id: number;
  title: string;
  content: string;
  author: string;
  timeAgo: string;
  rating: number;
}

interface TestimonialCarouselProps {
  autoPlayInterval?: number;
  testimonials?: Testimonial[];
}

export function Testimonials({
  autoPlayInterval = 5000,
  testimonials: externalTestimonials,
}: TestimonialCarouselProps) {
  const [currentIndex, setCurrentIndex] = useState(0);
  const [isHovered, setIsHovered] = useState(false);
  const autoplayRef = useRef<NodeJS.Timeout | null>(null);

  const testimonials: Testimonial[] = useMemo(
    () =>
      externalTestimonials || [
        {
          id: 1,
          title: "Exceptional Quality and Fast Delivery",
          content:
            "The attention to detail and craftsmanship exceeded our expectations. Delivery was prompt and the product arrived in perfect condition.",
          author: "Viktoria Blomqvist",
          timeAgo: "3 days ago",
          rating: 5,
        },
        {
          id: 2,
          title: "Outstanding Service Experience",
          content:
            "From consultation to delivery, the team provided exceptional service. They understood our needs and delivered beyond expectations.",
          author: "Jessica Lindström",
          timeAgo: "5 days ago",
          rating: 5,
        },
        {
          id: 3,
          title: "Highly Recommend Their Services",
          content:
            "Professional, reliable, and high-quality work. They made our ceremony truly special with their attention to every detail.",
          author: "John Malkovich",
          timeAgo: "1 week ago",
          rating: 5,
        },
        {
          id: 4,
          title: "Superior Customer Support",
          content:
            "The support team was incredibly helpful throughout the process. They answered all our questions and provided valuable guidance.",
          author: "Teresa Holland",
          timeAgo: "2 weeks ago",
          rating: 5,
        },
        {
          id: 5,
          title: "Flawless Purchase Experience",
          content:
            "Every aspect of the process was seamless. The quality, timing, and communication were all exceptional.",
          author: "Michael Anderson",
          timeAgo: "3 weeks ago",
          rating: 5,
        },
      ],
    [externalTestimonials]
  );

  const navigateSlide = useCallback(
    (direction: "next" | "prev" | number) => {
      if (typeof direction === "number") {
        setCurrentIndex(direction);
      } else if (direction === "next") {
        setCurrentIndex((prev) => (prev + 1) % testimonials.length);
      } else if (direction === "prev") {
        setCurrentIndex((prev) =>
          prev === 0 ? testimonials.length - 1 : prev - 1
        );
      }
    },
    [testimonials.length]
  );

  // ✅ Fixed autoplay logic
  useEffect(() => {
    if (!isHovered) {
      autoplayRef.current = setInterval(() => {
        setCurrentIndex((prev) => (prev + 1) % testimonials.length);
      }, autoPlayInterval);
    }

    return () => {
      if (autoplayRef.current) {
        clearInterval(autoplayRef.current);
      }
    };
  }, [isHovered, autoPlayInterval, testimonials.length]);

  const current = testimonials[currentIndex];

  const getInitials = (name: string) => {
    return name
      .split(" ")
      .map((n) => n[0])
      .join("")
      .toUpperCase()
      .slice(0, 5);
  };

  return (
    <section className="w-full py-16 bg-gradient-to-b from-slate-50 to-white" aria-label="Customer testimonials">
      <div className="container mx-auto px-4">
        {/* Header */}
        <div className="text-center mb-16">
          <h2 className="text-4xl md:text-5xl font-bold text-slate-900 font-serif mb-6">
            Loved by Families
          </h2>
          <p className="text-xl text-slate-600 max-w-3xl mx-auto leading-relaxed">
            Join thousands of families who have made their ceremonies memorable
            with our trusted services
          </p>
        </div>

        <div className="flex flex-col lg:flex-row gap-8 items-start max-w-7xl mx-auto">
          {/* Rating Summary Card */}
          <div className="w-full lg:w-96 flex-shrink-0 bg-white rounded-3xl shadow-xs p-6 lg:p-10">
            <div className="text-center lg:text-left">
              <div className="inline-flex items-center gap-2 bg-slate-50 text-slate-700 px-3 py-1 rounded-full text-xs font-medium mb-4">
                ⭐ 5.0 Rating Average
              </div>
              <h3 className="text-4xl font-bold mb-4 text-slate-900 font-serif">
                Excellent
              </h3>
              {/* <div className="flex gap-1 mb-4 justify-center lg:justify-start">
                {[...Array(5)].map((_, i) => (
                  <div
                    key={i}
                    className="w-6 h-6 bg-gradient-to-br from-amber-400 to-amber-500 rounded-xl flex items-center justify-center shadow-md hover:scale-110 transition-transform"
                  >
                    <Star className="w-4 h-4 fill-white text-white" />
                  </div>
                ))}
              </div> */}
              <div className="space-y-3 mb-6 text-sm">
                <div className="flex justify-between items-center text-slate-600">
                  <span>Based on</span>
                  <span className="font-bold text-emerald-600">
                    2,147 reviews
                  </span>
                </div>
                <div className="flex justify-between items-center text-slate-600">
                  <span>Response time</span>
                  <span className="font-semibold text-slate-900">
                    &lt; 2 hours
                  </span>
                </div>
                <div className="flex justify-between items-center text-slate-600">
                  <span>Satisfaction</span>
                  <span className="font-semibold text-slate-900">99%</span>
                </div>
              </div>
              <div className="w-full h-px bg-gradient-to-r from-transparent via-slate-200 to-transparent my-6"></div>
              <div className="flex items-start gap-3 text-sm text-slate-500">
                <CheckCircle className="w-5 h-5 text-emerald-500 flex-shrink-0 mt-0.5" />
                <p className="leading-relaxed">
                  All reviews are verified from genuine customers who have used
                  our services
                </p>
              </div>
            </div>
          </div>

          <div className="flex-grow relative">
            {/* Carousel */}
            <div
              className="bg-white rounded-2xl shadow-xs overflow-hidden"
              onMouseEnter={() => setIsHovered(true)}
              onMouseLeave={() => setIsHovered(false)}
            >
              <div className="p-6 lg:p-10 transition-all duration-300 min-h-[340px] flex flex-col justify-between">
                {/* Rating & Title */}
                <div>
                  <div className="flex items-center gap-2 mb-6">
                    <div className="flex gap-1">
                      {[...Array(current.rating)].map((_, i) => (
                        <div
                          key={i}
                          className="w-6 h-6 bg-gradient-to-br from-amber-400 to-amber-500 rounded-lg flex items-center justify-center"
                        >
                          <Star className="w-3.5 h-3.5 fill-white text-white" />
                        </div>
                      ))}
                    </div>
                    <div className="ml-auto flex items-center gap-1.5 text-xs text-emerald-600 bg-emerald-50 px-2 py-1 rounded-full font-medium border border-emerald-100">
                      <CheckCircle className="w-3.5 h-3.5" />
                      Verified
                    </div>
                  </div>

                  <h3 className="text-2xl font-bold mb-4 text-slate-900 font-serif leading-snug">
                    {current.title}
                  </h3>
                  <blockquote className="text-slate-600 leading-relaxed text-lg italic">
                    "{current.content}"
                  </blockquote>
                </div>

                {/* Author */}
                <div className="flex items-center justify-between pt-6 border-t border-slate-100 mt-6">
                  <div>
                    <p className="font-semibold text-slate-900">
                      {current.author}
                    </p>
                    <p className="text-slate-500 text-sm mt-1">
                      {current.timeAgo}
                    </p>
                  </div>
                  <div
                    className="w-12 h-12 bg-gradient-to-br from-slate-100 to-slate-200 rounded-full flex items-center justify-center text-slate-600 font-bold text-sm border border-slate-200"
                    aria-label={`Avatar of ${current.author}`}
                  >
                    {getInitials(current.author)}
                  </div>
                </div>
              </div>
            </div>

            {/* ✅ Fixed Dots Indicator */}
            <div className="flex gap-3 justify-center mt-12">
              {testimonials.map((_, index) => (
                <button
                  key={index}
                  onClick={() => navigateSlide(index)}
                  className={`rounded-full transition-all duration-300 ${
                    index === currentIndex
                      ? "bg-gradient-to-r from-amber-400 to-amber-500 w-8 h-3 shadow-md"
                      : "bg-slate-300 w-3 h-3 hover:bg-slate-400 hover:scale-110"
                  }`}
                  aria-label={`Go to testimonial ${index + 1}`}
                />
              ))}
            </div>

            {/* Progress Indicator */}
            <div className="mt-6 text-center">
              <span className="text-sm text-slate-500">
                {currentIndex + 1} of {testimonials.length}
              </span>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

"use client";

import { useState } from 'react';

interface Testimonial {
  id: number;
  name: string;
  location: string;
  rating: number;
  title: string;
  content: string;
  timeAgo: string;
}

const testimonials: Testimonial[] = [
  {
    id: 1,
    name: "Priya Sharma",
    location: "Mumbai",
    rating: 5,
    title: "Divine Blessings for Our New Home",
    content: "The Grihapravesh ceremony conducted by Navdhara was truly divine. The priests were knowledgeable and guided us through every ritual with utmost care. Our new home is filled with positive energy.",
    timeAgo: "2 weeks ago"
  },
  {
    id: 2,
    name: "Rahul & Neha Patel",
    location: "Delhi",
    rating: 5,
    title: "Perfect Wedding Ceremony",
    content: "Our wedding ceremony was a beautiful blend of traditions and spirituality. The Navdhara team ensured every detail was perfect, allowing us to focus on our special day without any stress.",
    timeAgo: "1 month ago"
  },
  {
    id: 3,
    name: "Ankit Verma",
    location: "Bangalore",
    rating: 5,
    title: "Ganesh Chaturthi Celebration",
    content: "The Ganesh Chaturthi celebration organized by Navdhara brought our community together. The arrangements were impeccable and the divine atmosphere was exactly what we hoped for.",
    timeAgo: "3 months ago"
  },
  {
    id: 4,
    name: "Sunita & Rajesh Gupta",
    location: "Chennai",
    rating: 5,
    title: "Baby Naming Ceremony",
    content: "The Namkaran ceremony for our daughter was conducted with such grace and authenticity. The priests explained the significance of each ritual, making it a memorable experience for our entire family.",
    timeAgo: "4 months ago"
  },
  {
    id: 5,
    name: "Vikram Singh",
    location: "Hyderabad",
    rating: 5,
    title: "Navagraha Puja for Prosperity",
    content: "After the Navagraha puja, we noticed a positive change in our family's fortune. The rituals were performed with precision and devotion, exactly as per Vedic traditions.",
    timeAgo: "5 months ago"
  },
  {
    id: 6,
    name: "Meera Reddy",
    location: "Kolkata",
    rating: 5,
    title: "Satyanarayan Puja for Family Peace",
    content: "The Satyanarayan puja brought harmony to our family. The priests were respectful and explained each step clearly. We felt the divine presence throughout the ceremony.",
    timeAgo: "6 months ago"
  }
];

const StarRating = ({ rating }: { rating: number }) => {
  return (
    <div className="flex text-amber-400 text-sm">
      {[...Array(5)].map((_, i) => (
        <svg
          key={i}
          className="w-3.5 h-3.5 mx-0.5 fill-current"
          viewBox="0 0 20 20"
        >
          <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
        </svg>
      ))}
    </div>
  );
};

const ArrowIcon = ({ direction }: { direction: 'left' | 'right' }) => (
  <svg
    className="w-7 h-7 fill-[#0E3F2E] transition-all duration-300 ease-in-out cursor-pointer hover:fill-[#155d42] hover:scale-110"
    viewBox="0 0 64 64"
  >
    {direction === 'left' ? (
      <g transform="matrix(-1,0,0,1,64,0)">
        <path d="M51.93 33 1 32.959a1 1 0 0 1 0-2L51.931 31a1 1 0 0 1 0 2z" opacity="1" />
        <path d="M64 32.012c-5.681 2.1-12.731 5.692-17.1 9.5L50.346 32l-3.433-9.513C51.278 26.3 58.322 29.9 64 32.012z" opacity="1" />
      </g>
    ) : (
      <>
        <path d="m51.93 33-50.93-.041a1 1 0 0 1 0-2l50.931.041a1 1 0 0 1 0 2z" />
        <path d="m64 32.012c-5.681 2.1-12.731 5.692-17.1 9.5l3.446-9.512-3.433-9.513c4.365 3.813 11.409 7.413 17.087 9.525z" />
      </>
    )}
  </svg>
);

export function Testimonials() {
  const [currentSlide, setCurrentSlide] = useState(0);

  const nextSlide = () => {
    setCurrentSlide((prev) => 
      prev >= testimonials.length - 1 ? 0 : prev + 1
    );
  };

  const prevSlide = () => {
    setCurrentSlide((prev) => 
      prev <= 0 ? testimonials.length - 1 : prev - 1
    );
  };

  const goToSlide = (index: number) => {
    setCurrentSlide(index);
  };

  // For mobile, show 1 slide at a time; for desktop, show 2
  const getSlidesToShow = () => {
    if (typeof window !== 'undefined' && window.innerWidth < 768) {
      return 1;
    }
    return 2;
  };

  const slidesToShow = getSlidesToShow();

  return (
    <div className="py-12 transition-all duration-300 bg-white">
      <div className="overflow-hidden">
        <div className="px-4 w-full mx-auto max-w-6xl">
          {/* Header */}
          <div className="mb-8">
            <div className="text-center">
              <div className="inline-flex items-center gap-2 bg-gradient-to-r from-[#0E3F2E] to-[#155d42] text-white px-4 py-1.5 rounded-lg text-xs font-medium mb-3 shadow-lg">
                <span className="flex items-center">
                  ⭐ 5.0
                </span>
                <span className="text-white/80">from 1230+ reviews</span>
              </div>
              <h4 className="font-extrabold text-[#181818] font-['DM_Sans'] text-xl leading-7 m-0 font-serif">
                WHAT CUSTOMERS SAY
              </h4>
            </div>
          </div>

          {/* Slider */}
          <div className="mb-6 relative">
            <div className="relative overflow-hidden">
              <div 
                className="flex transition-transform duration-500 ease-in-out"
                style={{ 
                  transform: `translateX(-${currentSlide * (100 / slidesToShow)}%)` 
                }}
              >
                {testimonials.map((testimonial) => (
                  <div
                    key={testimonial.id}
                    className="flex-shrink-0 w-full md:w-1/2 px-3"
                  >
                    <div className="max-w-[670px]">
                      <StarRating rating={testimonial.rating} />
                      
                      <div className="text-left mb-4">
                        <h5 className="text-sm font-bold mb-2 text-[#181818] font-['DM_Sans'] leading-5 m-0">
                          {testimonial.title}
                        </h5>
                        <p className="text-gray-600 text-xs leading-relaxed line-clamp-3">
                          {testimonial.content}
                        </p>
                      </div>

                      <div className="flex items-center relative">
                        <div className="rounded-full overflow-hidden w-10 h-10 bg-gradient-to-br from-[#0E3F2E] to-[#155d42] flex items-center justify-center text-white font-bold text-xs shadow-md">
                          {testimonial.name.charAt(0)}
                        </div>
                        <div className="text-left flex items-center ml-3">
                          <div className="text-[#222] font-medium text-xs">
                            {testimonial.name} -
                          </div>
                          <div className="text-[#888] uppercase font-normal text-[10px] ml-1">
                            {testimonial.location}
                          </div>
                        </div>
                      </div>
                      
                      <div className="text-[#b7b7b7] text-[10px] mt-1">
                        {testimonial.timeAgo}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>

          {/* Controls */}
          <div className="flex justify-center items-center">
            <button 
              onClick={prevSlide}
              className="mr-1"
              aria-label="Previous slide"
            >
              <ArrowIcon direction="left" />
            </button>
            
            <div>
              <ul className="relative bottom-0 block w-full p-0 m-0 list-none text-center flex gap-1">
                {testimonials.map((_, index) => (
                  <li
                    key={index}
                    className="relative inline-block w-2 h-2 p-0 cursor-pointer"
                    role="presentation"
                  >
                    <button
                      type="button"
                      role="tab"
                      onClick={() => goToSlide(index)}
                      className={`font-size-0 line-height-0 block w-2 h-2 p-0 cursor-pointer border outline-none bg-transparent rounded-full transition-all duration-300 ${
                        currentSlide === index
                          ? 'border-[#0E3F2E] bg-[#0E3F2E]'
                          : 'border-[#e8e8e8]'
                      }`}
                      aria-label={`Go to testimonial ${index + 1}`}
                    />
                  </li>
                ))}
              </ul>
            </div>

            <button 
              onClick={nextSlide}
              className="ml-1"
              aria-label="Next slide"
            >
              <ArrowIcon direction="right" />
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
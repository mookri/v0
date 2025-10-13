"use client";

import { useState, useEffect, useRef } from "react";
import { Star, CheckCircle } from "lucide-react";

interface Testimonial {
  id: number;
  title: string;
  content: string;
  author: string;
  timeAgo: string;
  rating: number;
}

export function TestimonialCarousel() {
  const [currentIndex, setCurrentIndex] = useState(0);
  const [itemsPerView, setItemsPerView] = useState(4);
  const trackRef = useRef<HTMLDivElement>(null);
  const autoplayRef = useRef<NodeJS.Timeout | null>(null);
  
  const testimonials: Testimonial[] = [
    {
      id: 1,
      title: "Great quality fast delivery and well priced",
      content: "Proin a accumsan mauris. Quisque laoreet consequat risus eget accumsan. Mauris interdum tortor turpis, vitae consequat dui iaculis id.",
      author: "Viktoria Blomqvist",
      timeAgo: "3 days ago",
      rating: 5
    },
    {
      id: 2,
      title: "Excellent service and product quality",
      content: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.",
      author: "Jessica Lindström",
      timeAgo: "5 days ago",
      rating: 5
    },
    {
      id: 3,
      title: "Highly recommend this product",
      content: "Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.",
      author: "John Malkovich",
      timeAgo: "1 week ago",
      rating: 5
    },
    {
      id: 4,
      title: "Outstanding customer support",
      content: "Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.",
      author: "Teresa Holland",
      timeAgo: "2 weeks ago",
      rating: 5
    },
    {
      id: 5,
      title: "Perfect purchase experience",
      content: "Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.",
      author: "Michael Anderson",
      timeAgo: "3 weeks ago",
      rating: 5
    },
    {
      id: 6,
      title: "Impressive attention to detail",
      content: "The craftsmanship on this item exceeded my expectations. Every detail was carefully considered and executed perfectly.",
      author: "Sarah Johnson",
      timeAgo: "1 month ago",
      rating: 5
    },
    {
      id: 7,
      title: "Fast shipping and great packaging",
      content: "My order arrived earlier than expected and was packaged with great care. The presentation was beautiful.",
      author: "Robert Chen",
      timeAgo: "1 month ago",
      rating: 5
    },
    {
      id: 8,
      title: "Outstanding quality for the price",
      content: "I've purchased similar items elsewhere for much more money. This is an incredible value for such high quality.",
      author: "Emma Rodriguez",
      timeAgo: "2 months ago",
      rating: 5
    },
    {
      id: 9,
      title: "Customer service beyond expectations",
      content: "When I had a question about my order, the support team went above and beyond to help me resolve it quickly.",
      author: "David Kim",
      timeAgo: "2 months ago",
      rating: 5
    },
    {
      id: 10,
      title: "Beautiful design and finish",
      content: "The aesthetic appeal of this product is exactly what I was looking for. It complements my space perfectly.",
      author: "Lisa Thompson",
      timeAgo: "3 months ago",
      rating: 5
    },
    {
      id: 11,
      title: "Reliable and durable product",
      content: "After months of regular use, this item continues to perform flawlessly. The build quality is exceptional.",
      author: "Mark Wilson",
      timeAgo: "3 months ago",
      rating: 5
    },
    {
      id: 12,
      title: "Easy to assemble and use",
      content: "The instructions were clear and the assembly process was straightforward. I had it set up in no time.",
      author: "Jennifer Lee",
      timeAgo: "4 months ago",
      rating: 5
    },
    {
      id: 13,
      title: "Eco-friendly and sustainable",
      content: "I appreciate that this product is made with sustainable materials. It's great to see companies taking environmental responsibility seriously.",
      author: "Thomas Brown",
      timeAgo: "4 months ago",
      rating: 5
    },
    {
      id: 14,
      title: "Perfect gift for any occasion",
      content: "I've purchased this as a gift multiple times. Everyone loves it and always asks where I got it.",
      author: "Anna Martinez",
      timeAgo: "5 months ago",
      rating: 5
    },
    {
      id: 15,
      title: "Exceeded all my expectations",
      content: "From the ordering process to delivery and product quality, everything was seamless and impressive.",
      author: "Christopher Taylor",
      timeAgo: "5 months ago",
      rating: 5
    }
  ];

  // Calculate items per view based on screen size
  const updateItemsPerView = () => {
    if (typeof window !== 'undefined') {
      const width = window.innerWidth;
      if (width < 640) {
        setItemsPerView(1.2);
      } else if (width < 1024) {
        setItemsPerView(2);
      } else {
        setItemsPerView(4);
      }
    }
  };

  // Infinite loop implementation
  const nextSlide = () => {
    setCurrentIndex(prev => (prev + 1) % testimonials.length);
  };

  const prevSlide = () => {
    setCurrentIndex(prev => (prev - 1 + testimonials.length) % testimonials.length);
  };

  // Go to specific slide
  const goToSlide = (index: number) => {
    setCurrentIndex(index);
    resetAutoplay();
  };

  // Autoplay functions
  const startAutoplay = () => {
    if (autoplayRef.current) {
      clearInterval(autoplayRef.current);
    }
    autoplayRef.current = setInterval(nextSlide, 3000);
  };

  const stopAutoplay = () => {
    if (autoplayRef.current) {
      clearInterval(autoplayRef.current);
      autoplayRef.current = null;
    }
  };

  const resetAutoplay = () => {
    stopAutoplay();
    startAutoplay();
  };

  // Initialize and handle resize
  useEffect(() => {
    updateItemsPerView();
    startAutoplay();

    const handleResize = () => {
      updateItemsPerView();
    };

    window.addEventListener('resize', handleResize);
    
    return () => {
      window.removeEventListener('resize', handleResize);
      stopAutoplay();
    };
  }, []);

  // Update carousel position for infinite loop
  useEffect(() => {
    if (trackRef.current) {
      const cardElements = trackRef.current.querySelectorAll('.testimonial-card');
      if (cardElements.length > 0) {
        const cardWidth = cardElements[0].clientWidth;
        const gap = parseFloat(getComputedStyle(trackRef.current).gap || "28");
        const offset = -(currentIndex * (cardWidth + gap));
        trackRef.current.style.transform = `translateX(${offset}px)`;
      }
    }
  }, [currentIndex, itemsPerView]);

  return (
    <div className="container mx-auto px-4 py-8">
      {/* Title and Subtitle */}
      <div className="text-center mb-8">
        <h2 className="text-2xl md:text-3xl font-bold text-foreground mb-2">
          What Families Say About Us
        </h2>
        <p className="text-muted-foreground max-w-2xl mx-auto">
          Real experiences from our valued customers who have trusted us with their spiritual ceremonies
        </p>
      </div>

      <div className="flex flex-col lg:flex-row gap-6">
        {/* Profile Section */}
        <div className="w-full lg:w-60 flex-shrink-0 text-center lg:text-left">
          <h3 className="text-xl font-semibold mb-2 text-foreground">Excellent</h3>
          <div className="inline-flex gap-0.5 mb-1.5 justify-center lg:justify-start">
            {[...Array(5)].map((_, i) => (
              <div key={i} className="w-6 h-6 bg-emerald-600 rounded-sm flex items-center justify-center">
                <Star className="w-4 h-4 fill-white text-white" />
              </div>
            ))}
          </div>
          <div className="text-xs text-muted-foreground mb-1.5">
            Based on <span className="font-bold underline underline-offset-2">2,147 reviews</span>
          </div>
          <p className="text-xs text-muted-foreground mt-2.5">
            All comments are from real users who have made purchases before.
          </p>
        </div>

        {/* Carousel Section */}
        <div className="flex-grow min-w-0 overflow-hidden relative">
          <div className="relative">
            <div 
              ref={trackRef}
              className="flex gap-7 transition-transform duration-500 ease-out"
              style={{ transition: 'transform 0.5s ease' }}
            >
              {/* Render testimonials multiple times for infinite scroll effect */}
              {Array(5).fill(0).map((_, cycle) => (
                testimonials.map((testimonial) => (
                  <div 
                    key={`${testimonial.id}-${cycle}`} 
                    className="testimonial-card min-w-[231px] flex-shrink-0 bg-background rounded-md p-3.5 shadow-sm border border-border box-border"
                  >
                    <div className="flex items-center gap-2 mb-2.5">
                      <div className="flex gap-0.5">
                        {[...Array(testimonial.rating)].map((_, i) => (
                          <div key={i} className="w-4 h-4 bg-emerald-600 rounded-sm flex items-center justify-center">
                            <Star className="w-3 h-3 fill-white text-white" />
                          </div>
                        ))}
                      </div>
                      <div className="flex items-center gap-1 text-xs text-muted-foreground font-medium">
                        <CheckCircle className="w-2.5 h-2.5" />
                        <span>Purchased user</span>
                      </div>
                    </div>
                    <div className="mb-2.5">
                      <h4 className="text-sm font-semibold mb-0.5 text-foreground leading-tight max-w-[20ch] line-clamp-1">
                        {testimonial.title}
                      </h4>
                      <p className="text-xs text-muted-foreground leading-relaxed max-w-[35ch] line-clamp-3">
                        {testimonial.content}
                      </p>
                    </div>
                    <div className="flex items-center gap-2 text-xs text-muted-foreground">
                      <p className="font-semibold text-foreground">{testimonial.author}</p>
                      <span>{testimonial.timeAgo}</span>
                    </div>
                  </div>
                ))
              ))}
            </div>

            {/* Navigation Buttons - Hidden on mobile */}
            <div className="absolute top-1/2 -translate-y-1/2 w-[calc(100%+72px)] -left-9 pointer-events-none flex justify-between hidden sm:flex">
              <button 
                className="w-6 h-6 bg-transparent border border-border rounded-full flex items-center justify-center cursor-pointer transition-all pointer-events-auto hover:border-muted-foreground"
                onClick={() => {
                  prevSlide();
                  resetAutoplay();
                }}
                aria-label="Previous"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="w-2.5 h-2.5">
                  <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
              </button>
              <button 
                className="w-6 h-6 bg-transparent border border-border rounded-full flex items-center justify-center cursor-pointer transition-all pointer-events-auto hover:border-muted-foreground"
                onClick={() => {
                  nextSlide();
                  resetAutoplay();
                }}
                aria-label="Next"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="w-2.5 h-2.5">
                  <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
              </button>
            </div>
          </div>

          {/* Dots */}
          <div className="flex gap-1 justify-center mt-6">
            {testimonials.map((_, index) => (
              <span
                key={index}
                className={`w-1.5 h-1.5 rounded-full cursor-pointer transition-opacity ${
                  index === currentIndex % testimonials.length ? 'bg-blue-600 opacity-100' : 'bg-blue-600 opacity-30'
                }`}
                onClick={() => goToSlide(index)}
              />
            ))}
          </div>

          <div className="text-xs font-medium text-muted-foreground mt-5 text-left">
            Showing our 5 star reviews.
          </div>
        </div>
      </div>
    </div>
  );
}
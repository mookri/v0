'use client'
import { Button } from "@/components/ui/button"
import Link from "next/link"
import Image from "next/image"
import { useState, useEffect, useRef } from "react"

const items = [
  {
    slug: "griha-pravesh",
    title: "Grihapravesh Puja",
    desc: "Begin your new chapter with divine blessings",
    price: "₹4,999",
    tag: "Most Booked",
    imgQuery: "griha%20pravesh%20home%20ritual%20mandap",
    imageUrl: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_12d1d962f45c56a89444ef5b3cc2f267dr_fzoivd.jpg"
  },
  {
    slug: "lakshmi-puja",
    title: "Lakshmi Puja",
    desc: "Invoke Goddess Lakshmi for wealth and prosperity",
    price: "₹2,999",
    tag: "Popular",
    imgQuery: "lakshmi%20puja%20ritual",
    imageUrl: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358803/Whisk_0f5f669b41ae7caa0134b73a006a3c5edr_1_sb2pdr.jpg"
  },
  {
    slug: "satyanarayan-katha",
    title: "Satyanarayan Puja",
    desc: "Seek Lord Vishnu's grace and prosperity",
    price: "₹3,999",
    tag: undefined,
    imgQuery: "satyanarayan%20katha%20ritual",
    imageUrl: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358798/Whisk_7a093de03fbc3bab93949b075483d783dr_zwnihl.jpg"
  },
  {
    slug: "ganesh-puja",
    title: "Ganesh Puja",
    desc: "Remove obstacles and invite success",
    price: "₹1,999",
    tag: "Quick Setup",
    imgQuery: "ganesh%20puja%20traditional%20setup",
    imageUrl: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358805/Whisk_3a4f5d6e7c8b9a0c1d2e3f4a5b6c7d8e_ganesh_puja.jpg"
  },
  {
    slug: "durga-puja",
    title: "Durga Puja",
    desc: "Celebrate the victory of good over evil",
    price: "₹5,999",
    tag: "Festival Special",
    imgQuery: "durga%20puja%20elaborate%20setup",
    imageUrl: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358807/Whisk_durga_puja_festival.jpg"
  },
  {
    slug: "navratri-puja",
    title: "Navratri Puja",
    desc: "Nine nights of divine feminine energy",
    price: "₹7,999",
    tag: "Extended Ritual",
    imgQuery: "navratri%20puja%20garba%20celebration",
    imageUrl: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358809/Whisk_navratri_puja_celebration.jpg"
  },
]

// Skeleton loader component
function SkeletonCard() {
  return (
    <div className="flex flex-col items-center animate-pulse">
      <div className="relative w-full aspect-square max-w-[140px] mx-auto">
        <div className="w-full h-full rounded-full bg-muted overflow-hidden border border-primary/10"></div>
      </div>
      <div className="mt-2 flex items-center justify-center gap-1 w-full">
        <div className="h-3 bg-muted rounded-full w-24"></div>
        <div className="h-3 w-3 bg-muted rounded-full"></div>
      </div>
      <div className="mt-1 text-center">
        <div className="h-2.5 bg-muted rounded-full w-32 mx-auto"></div>
        <div className="mt-2 h-6 bg-muted rounded-md w-20 mx-auto"></div>
      </div>
    </div>
  )
}

// Desktop skeleton loader
function DesktopSkeletonCard() {
  return (
    <div className="group overflow-hidden rounded-lg border bg-card animate-pulse flex-shrink-0 shadow-sm" style={{ width: "220px" }}>
      <div className="relative w-full aspect-[4/3] bg-muted"></div>
      <div className="p-2 flex flex-col h-full">
        <div className="h-3 bg-muted rounded w-3/4 mb-1"></div>
        <div className="h-2 bg-muted rounded w-full mb-1"></div>
        <div className="h-2 bg-muted rounded w-2/3 mb-1"></div>
        <div className="h-2.5 bg-muted rounded w-1/2 mt-1"></div>
      </div>
      <div className="flex items-center gap-1 px-2 pb-2">
        <div className="h-6 bg-muted rounded w-full"></div>
      </div>
    </div>
  )
}

export function FeaturedPujas() {
  const [isLoading, setIsLoading] = useState(true)
  const [activeSlideIndex, setActiveSlideIndex] = useState(0)
  const scrollContainerRef = useRef<HTMLDivElement>(null)
  
  useEffect(() => {
    // Simulate loading time
    const timer = setTimeout(() => {
      setIsLoading(false)
    }, 1500)
    
    return () => clearTimeout(timer)
  }, [])

  // Update active slide index when scrolling
  useEffect(() => {
    const handleScroll = () => {
      if (scrollContainerRef.current) {
        const container = scrollContainerRef.current
        const scrollLeft = container.scrollLeft
        const cardWidth = 220 + 16 // width + gap
        const newIndex = Math.round(scrollLeft / cardWidth)
        setActiveSlideIndex(newIndex)
      }
    }

    const container = scrollContainerRef.current
    if (container) {
      container.addEventListener('scroll', handleScroll)
      return () => container.removeEventListener('scroll', handleScroll)
    }
  }, [])

  const scroll = (direction: 'left' | 'right') => {
    if (scrollContainerRef.current) {
      const { current: container } = scrollContainerRef
      const scrollAmount = container.offsetWidth * 0.8
      container.scrollBy({
        left: direction === 'right' ? scrollAmount : -scrollAmount,
        behavior: 'smooth'
      })
    }
  }

  // Get current scroll position to determine active slide
  const getActiveSlideIndex = () => {
    if (!scrollContainerRef.current) return 0
    const container = scrollContainerRef.current
    const scrollLeft = container.scrollLeft
    const cardWidth = 220 + 16 // width + gap
    return Math.round(scrollLeft / cardWidth)
  }

  return (
    <section aria-labelledby="featured-pujas" className="bg-muted/50">
      <div className="mx-auto max-w-7xl px-4 py-12 overflow-x-hidden w-full">
        <div className="text-center mb-6">
          <h2 id="featured-pujas" className="font-bold text-2xl md:text-3xl">  
            Featured Pujas
          </h2>
          <p className="text-muted-foreground mt-2.5 text-sm md:text-base max-w-2xl mx-auto font-sans">
            Explore our most popular rituals performed by certified pandits
          </p>
        </div>
        
        {/* Mobile layout - 2x2 grid with circular cards and row dividers */}
        <div className="mt-5 sm:hidden">
          {isLoading ? (
            // Skeleton loaders with row dividers
            <div className="space-y-6">
              {Array.from({ length: 3 }).map((_, rowIndex) => (
                <div key={rowIndex}>
                  <div className="grid grid-cols-2 gap-5">
                    <SkeletonCard />
                    <SkeletonCard />
                  </div>
                  {rowIndex < 2 && (
                    <div className="border-t border-muted mt-6 mb-2 mx-auto w-16"></div>
                  )}
                </div>
              ))}
            </div>
          ) : (
            // Actual content with row dividers
            <div className="space-y-6">
              {Array.from({ length: Math.ceil(items.length / 2) }).map((_, rowIndex) => (
                <div key={rowIndex}>
                  <div className="grid grid-cols-2 gap-5">
                    {items.slice(rowIndex * 2, rowIndex * 2 + 2).map((it) => (
                      <div
                        key={it.title}
                        className="group flex flex-col items-center"
                      >
                        <Link href={`/pujas/${it.slug}`} className="block w-full">
                          <div className="relative w-full aspect-square max-w-[140px] mx-auto">
                            <div className="w-full h-full rounded-full overflow-hidden border border-primary/20 flex items-center justify-center">
                              {it.imageUrl ? (
                                <div className="relative w-full h-full">
                                  <Image
                                    src={it.imageUrl}
                                    alt={`${it.title} representative image`}
                                    fill
                                    className="object-cover"
                                    sizes="(max-width: 640px) 50vw"
                                    priority={it.tag === "Most Booked"}
                                  />
                                </div>
                              ) : (
                                <div className="relative w-full h-full">
                                  <Image
                                    src={`/.jpg?key=s7bpr&height=320&width=480&query=${it.imgQuery}`}
                                    alt={`${it.title} representative image`}
                                    fill
                                    className="object-cover"
                                    sizes="(max-width: 640px) 50vw"
                                    priority={it.tag === "Most Booked"}
                                  />
                                </div>
                              )}
                            </div>
                            {it.tag ? (
                              <span className="absolute left-1.5 top-1.5 rounded-[3px] bg-[#0E3F2E] px-1 py-0.5 text-[7px] font-semibold text-white max-w-[calc(100%-0.75rem)] overflow-hidden z-10 shadow-xs">
                                {it.tag}
                              </span>
                            ) : null}
                          </div>
                          <div className="mt-2 flex items-center justify-center gap-1 w-full">
                            <h3 className="font-sans text-sm font-semibold leading-tight text-center">{it.title}</h3>
                            <svg 
                              xmlns="http://www.w3.org/2000/svg" 
                              width="10" 
                              height="10" 
                              viewBox="0 0 24 24" 
                              fill="none" 
                              stroke="currentColor" 
                              strokeWidth="2" 
                              strokeLinecap="round" 
                              strokeLinejoin="round" 
                              className="text-primary"
                            >
                              <line x1="5" y1="12" x2="19" y2="12"></line>
                              <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                          </div>
                          <div className="mt-1 text-center">
                            <p className="text-xs text-muted-foreground font-sans line-clamp-2 leading-relaxed">{it.desc}</p>
                            {/* Price information hidden on mobile as per requirements */}
                          </div>
                        </Link>
                        <div className="mt-2.5">
                          <Link href="#booking">
                            <Button className="bg-[#0E3F2E] text-white font-sans text-sm h-10 hover:bg-[#0E3F2E]/90 border-2 border-[#0E3F2E] hover:scale-[1.02] transition-transform duration-200 shadow-xs px-6" size="sm">
                              Book Now
                            </Button>
                          </Link>
                        </div>
                      </div>
                    ))}
                  </div>
                  {rowIndex < Math.ceil(items.length / 2) - 1 && (
                    <div className="border-t border-muted mt-6 mb-2 mx-auto w-16"></div>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>
        
        {/* Desktop layout - horizontal row with rectangular cards and slide indicators */}
        <div className="mt-5 w-full hidden sm:block relative px-4">
          {isLoading ? (
            // Desktop skeleton loaders in horizontal row
            <div 
              ref={scrollContainerRef}
              className="flex gap-4 pb-8"
            >
              {Array.from({ length: 6 }).map((_, index) => (
                <DesktopSkeletonCard key={index} />
              ))}
            </div>
          ) : (
            <>
              {/* Card carousel */}
              <div 
                ref={scrollContainerRef}
                className="flex gap-4 pb-8"
                onScroll={() => {
                  // Force re-render to update indicators
                  if (typeof window !== 'undefined') {
                    window.dispatchEvent(new CustomEvent('scroll-indicator-update'))
                  }
                }}
              >
                {items.map((it) => (
                  <div
                    key={it.title}
                    className="group overflow-hidden rounded-lg border bg-card transition-all duration-300 hover:-translate-y-0.5 hover:shadow-sm flex-shrink-0 shadow-xs"
                    style={{ width: "220px" }}
                  >
                    <Link href={`/pujas/${it.slug}`} className="block">
                      <div className="relative w-full aspect-[4/3]">
                        {it.imageUrl ? (
                          <Image
                            src={it.imageUrl}
                            alt={`${it.title} representative image`}
                            fill
                            className="object-cover"
                            sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
                            priority={it.tag === "Most Booked"}
                          />
                        ) : (
                          <Image
                            src={`/.jpg?key=s7bpr&height=320&width=480&query=${it.imgQuery}`}
                            alt={`${it.title} representative image`}
                            fill
                            className="object-cover"
                            sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
                            priority={it.tag === "Most Booked"}
                          />
                        )}
                        {it.tag ? (
                          <span className="absolute left-2 top-2 rounded-[3px] bg-[#0E3F2E] px-1.5 py-0.5 text-[8px] font-semibold text-white max-w-[calc(100%-1rem)] overflow-hidden z-10 shadow-xs">
                            {it.tag}
                          </span>
                        ) : null}
                      </div>
                      <div className="p-2 flex flex-col h-full">
                        <h3 className="font-sans text-sm font-bold">{it.title}</h3>
                        <p className="mt-1 text-xs text-muted-foreground flex-grow font-sans line-clamp-2">{it.desc}</p>
                        <p className="mt-1.5 text-xs font-sans">
                          Starting from <span className="font-bold text-primary">{it.price}</span>
                        </p>
                      </div>
                    </Link>
                    <div className="flex items-center gap-1 px-2 pb-2">
                      <Link href="#booking" className="flex-1">
                        <Button className="w-full bg-[#0E3F2E] text-white font-sans text-xs hover:bg-[#0E3F2E]/90 border-2 border-[#0E3F2E] hover:scale-[1.02] transition-transform duration-200 shadow-xs h-6" size="sm">
                          Book Now
                        </Button>
                      </Link>
                      <Link href={`/pujas/${it.slug}`} className="shrink-0 text-xs hover:text-primary font-sans">
                        Learn More →
                      </Link>
                    </div>
                  </div>
                ))}
              </div>
              
              {/* MNC Grade slide indicators */}
              <div className="flex justify-center mt-4 items-center gap-6">
                <div className="flex gap-1.5 bg-muted/50 px-2.5 py-1.5 rounded-full">
                  {items.map((_, index) => (
                    <button
                      key={index}
                      onClick={() => {
                        if (scrollContainerRef.current) {
                          const cardWidth = 220 + 16 // width + gap
                          scrollContainerRef.current.scrollTo({
                            left: index * cardWidth,
                            behavior: 'smooth'
                          })
                          setActiveSlideIndex(index)
                        }
                      }}
                      className={`w-6 h-0.5 transition-all duration-300 rounded-full ${index === activeSlideIndex ? 'bg-primary' : 'bg-muted-foreground/30'}`}
                      aria-label={`Go to slide ${index + 1}`}
                    />
                  ))}
                </div>
              </div>
              
              {/* View All arrow button for desktop - repositioned to top right */}
              <Link href="#booking" className="absolute top-0 right-0 flex items-center justify-center w-10 h-10 rounded-full bg-primary/10 hover:bg-primary/20 transition-all duration-300 group">
                <svg 
                  xmlns="http://www.w3.org/2000/svg" 
                  width="20" 
                  height="20" 
                  viewBox="0 0 24 24" 
                  fill="none" 
                  stroke="currentColor" 
                  strokeWidth="2" 
                  strokeLinecap="round" 
                  strokeLinejoin="round"
                  className="text-primary transform -rotate-45 group-hover:translate-x-1 group-hover:-translate-y-1 transition-transform duration-300"
                >
                  <line x1="7" y1="17" x2="17" y2="7"></line>
                  <polyline points="7 7 17 7 17 17"></polyline>
                </svg>
              </Link>
            </>
          )}
        </div>
        {/* View All button for mobile - enhanced with reduced height */}
        <div className="mt-8 text-center sm:hidden">
       <Link
  href="#booking"
  className="
    inline-block px-6 py-2
    bg-[#0E3F2E] 
    text-white font-sans font-semibold text-base
    rounded-md border-2 border-[#0E3F2E]
    shadow-md shadow-[#0E3F2E]/30
    hover:shadow-lg hover:shadow-[#0E3F2E]/50
    transition-all duration-300 transform hover:-translate-y-0.5 hover:scale-105
    focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]/40
  "
>
  View All 25+ Rituals
</Link>


        </div>
      </div>
    </section>
  )
}
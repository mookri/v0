'use client'
import { Button } from "@/components/ui/button"
import Link from "next/link"
import Image from "next/image"
import { useState, useEffect, useRef } from "react"

const items = [
  {
    slug: "griha-pravesh",
    title: "Grihapravesh Puja",
    desc: "Traditional housewarming ceremony for new homes with complete rituals and blessings for prosperity, harmony, and divine protection in your new dwelling.",
    price: "₹4,999",
    tag: "Most Booked",
    imgQuery: "griha%20pravesh%20home%20ritual%20mandap",
    imageUrl: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_12d1d962f45c56a89444ef5b3cc2f267dr_fzoivd.jpg"
  },
  {
    slug: "lakshmi-ganesh-puja",
    title: "Lakshmi Ganesh Puja",
    desc: "Powerful combination ritual to invoke blessings from Lord Ganesha and Goddess Lakshmi for removal of obstacles, wealth, and overall prosperity in life.",
    price: "₹2,999",
    tag: "Popular",
    imgQuery: "lakshmi%20ganesh%20puja%20ritual",
    imageUrl: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg"
  },
  {
    slug: "satyanarayan-katha",
    title: "Satyanarayan Puja",
    desc: "Devotional ritual dedicated to Lord Vishnu for prosperity, peace, and fulfillment of sincere wishes with complete katha recitation and aarti.",
    price: "₹3,999",
    tag: undefined,
    imgQuery: "satyanarayan%20katha%20ritual",
    imageUrl: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358798/Whisk_7a093de03fbc3bab93949b075483d783dr_zwnihl.jpg"
  },
  {
    slug: "hindu-wedding",
    title: "Wedding Ceremony",
    desc: "Complete traditional Hindu wedding rituals with experienced Vedic pandits, mandap setup, and all essential samagri for your special day.",
    price: "₹24,999",
    tag: "Life Event",
    imgQuery: "hindu%20wedding%20ceremony%20mandap",
    imageUrl: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg"
  },
  {
    slug: "office-opening",
    title: "Office Opening Puja",
    desc: "Bless your new business space with divine energy for success, prosperity, and protection. Includes Ganesha Puja, Lakshmi Puja, and space sanctification.",
    price: "₹3,500",
    tag: "Business Special",
    imgQuery: "office%20opening%20puja%20business%20blessing",
    imageUrl: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg"
  },
  {
    slug: "baby-naming",
    title: "Baby Naming Ceremony",
    desc: "Traditional Namkaran ceremony with auspicious name selection based on nakshatra and divine blessings for your child's health and future.",
    price: "₹2,500",
    tag: "Life Milestone",
    imgQuery: "baby%20naming%20ceremony%20hindu",
    imageUrl: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg"
  },
  {
    slug: "annaprashan",
    title: "Annaprashan Ceremony",
    desc: "The first feeding of solid food to your child, marking an important milestone in their growth and development with divine blessings.",
    price: "₹2,000",
    tag: "Life Milestone",
    imgQuery: "annaprashan%20ceremony%20baby%20first%20food",
    imageUrl: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg"
  },
  {
    slug: "vastu-shanti",
    title: "Vastu Shanti Puja",
    desc: "Harmonize your living or working space with Vastu principles for positive energy flow and removal of negative influences.",
    price: "₹3,000",
    tag: "Business Special",
    imgQuery: "vastu%20shanti%20puja%20ritual",
    imageUrl: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg"
  }
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
  const [isDragging, setIsDragging] = useState(false)
  const scrollContainerRef = useRef<HTMLDivElement>(null)
  const autoPlayRef = useRef<NodeJS.Timeout | null>(null)
  const dragStartX = useRef(0)
  const scrollStartLeft = useRef(0)
  
  useEffect(() => {
    // Simulate loading time
    const timer = setTimeout(() => {
      setIsLoading(false)
    }, 1500)
    
    return () => clearTimeout(timer)
  }, [])

  // Update active slide index based on scroll position
  useEffect(() => {
    const handleScroll = () => {
      if (scrollContainerRef.current && !isDragging) {
        const container = scrollContainerRef.current
        const scrollLeft = container.scrollLeft
        const cardWidth = 220 + 16 // width + gap
        const newIndex = Math.round(scrollLeft / cardWidth)
        setActiveSlideIndex(Math.min(newIndex, items.length - 1))
      }
    }

    const container = scrollContainerRef.current
    if (container) {
      container.addEventListener('scroll', handleScroll, { passive: true })
      return () => container.removeEventListener('scroll', handleScroll)
    }
  }, [isDragging])

  // Auto-play functionality
  useEffect(() => {
    if (!isLoading && scrollContainerRef.current) {
      const startAutoPlay = () => {
        autoPlayRef.current = setInterval(() => {
          setActiveSlideIndex(prevIndex => {
            const nextIndex = (prevIndex + 1) % items.length
            scrollToSlide(nextIndex)
            return nextIndex
          })
        }, 5000)
      }

      const stopAutoPlay = () => {
        if (autoPlayRef.current) {
          clearInterval(autoPlayRef.current)
          autoPlayRef.current = null
        }
      }

      startAutoPlay()

      const container = scrollContainerRef.current
      container.addEventListener('mouseenter', stopAutoPlay)
      container.addEventListener('mouseleave', startAutoPlay)

      return () => {
        stopAutoPlay()
        container.removeEventListener('mouseenter', stopAutoPlay)
        container.removeEventListener('mouseleave', startAutoPlay)
      }
    }
  }, [isLoading])

  const scrollToSlide = (index: number) => {
    if (scrollContainerRef.current) {
      const cardWidth = 220 + 16
      scrollContainerRef.current.scrollTo({
        left: index * cardWidth,
        behavior: 'smooth'
      })
    }
  }

  // Mouse drag handlers
  const handleMouseDown = (e: React.MouseEvent) => {
    if (!scrollContainerRef.current) return
    setIsDragging(true)
    dragStartX.current = e.pageX - scrollContainerRef.current.offsetLeft
    scrollStartLeft.current = scrollContainerRef.current.scrollLeft
    scrollContainerRef.current.style.cursor = 'grabbing'
  }

  const handleMouseMove = (e: React.MouseEvent) => {
    if (!isDragging || !scrollContainerRef.current) return
    e.preventDefault()
    const x = e.pageX - scrollContainerRef.current.offsetLeft
    const walk = (x - dragStartX.current) * 2
    scrollContainerRef.current.scrollLeft = scrollStartLeft.current - walk
  }

  const handleMouseUp = () => {
    setIsDragging(false)
    if (scrollContainerRef.current) {
      scrollContainerRef.current.style.cursor = 'grab'
    }
  }

  const handleMouseLeave = () => {
    if (isDragging) {
      setIsDragging(false)
      if (scrollContainerRef.current) {
        scrollContainerRef.current.style.cursor = 'grab'
      }
    }
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
        
        {/* View All arrow button for desktop */}
        <Link href="/pujas" className="absolute top-4 right-4 flex items-center justify-center w-10 h-10 rounded-full bg-primary/10 hover:bg-primary/20 transition-all duration-300 group z-10">
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
        
        {/* Mobile layout - 2x2 grid with circular cards and row dividers */}
        <div className="mt-5 sm:hidden">
          {isLoading ? (
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
                          </div>
                        </Link>
                        <div className="mt-2.5">
                          <Link href={`/pujas/${it.slug}`}>
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
        
        {/* Desktop layout - horizontal slider with drag support */}
        <div className="mt-5 w-full hidden sm:block relative">
          {isLoading ? (
            <div 
              className="flex gap-4 pb-8 overflow-x-auto scrollbar-hide"
              style={{ scrollbarWidth: 'none', msOverflowStyle: 'none' }}
            >
              {Array.from({ length: 6 }).map((_, index) => (
                <DesktopSkeletonCard key={index} />
              ))}
            </div>
          ) : (
            <>
              {/* Draggable card carousel */}
              <div 
                ref={scrollContainerRef}
                onMouseDown={handleMouseDown}
                onMouseMove={handleMouseMove}
                onMouseUp={handleMouseUp}
                onMouseLeave={handleMouseLeave}
                className="flex gap-4 pb-8 overflow-x-auto scrollbar-hide select-none"
                style={{ 
                  scrollbarWidth: 'none', 
                  msOverflowStyle: 'none',
                  cursor: isDragging ? 'grabbing' : 'grab'
                }}
              >
                {items.map((it) => (
                  <div
                    key={it.title}
                    className="group overflow-hidden rounded-lg border bg-card transition-all duration-300 hover:-translate-y-0.5 hover:shadow-sm flex-shrink-0 shadow-xs flex flex-col h-full"
                    style={{ width: "220px" }}
                  >
                    <Link href={`/pujas/${it.slug}`} className="block pointer-events-none flex-grow flex flex-col">
                      <div className="relative w-full aspect-[4/3] flex-shrink-0">
                        {it.imageUrl ? (
                          <Image
                            src={it.imageUrl}
                            alt={`${it.title} representative image`}
                            fill
                            className="object-cover"
                            sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
                            priority={it.tag === "Most Booked"}
                            draggable={false}
                          />
                        ) : (
                          <Image
                            src={`/.jpg?key=s7bpr&height=320&width=480&query=${it.imgQuery}`}
                            alt={`${it.title} representative image`}
                            fill
                            className="object-cover"
                            sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
                            priority={it.tag === "Most Booked"}
                            draggable={false}
                          />
                        )}
                        {it.tag ? (
                          <span className="absolute left-2 top-2 rounded-[3px] bg-[#0E3F2E] px-1.5 py-0.5 text-[8px] font-semibold text-white max-w-[calc(100%-1rem)] overflow-hidden z-10 shadow-xs">
                            {it.tag}
                          </span>
                        ) : null}
                      </div>
                      <div className="p-2 flex flex-col flex-grow">
                        <h3 className="font-sans text-sm font-bold">{it.title}</h3>
                        <p className="mt-1 text-xs text-muted-foreground flex-grow font-sans line-clamp-2">{it.desc}</p>
                        <p className="mt-1.5 text-xs font-sans">
                          Starting from <span className="font-bold text-primary">{it.price}</span>
                        </p>
                      </div>
                    </Link>
                    <div className="flex items-center gap-1 px-2 pb-2 pointer-events-auto mt-auto">
                      <Link href={`/pujas/${it.slug}`} className="flex-1" onClick={(e) => e.stopPropagation()}>
                        <Button className="w-full bg-[#0E3F2E] text-white font-sans text-xs hover:bg-[#0E3F2E]/90 border-2 border-[#0E3F2E] hover:scale-[1.02] transition-transform duration-200 shadow-xs h-6" size="sm">
                          Book Now
                        </Button>
                      </Link>
                      <Link href={`/pujas/${it.slug}`} className="shrink-0 text-xs hover:text-primary font-sans" onClick={(e) => e.stopPropagation()}>
                        Learn More →
                      </Link>
                    </div>
                  </div>
                ))}
              </div>
              
              {/* Slide indicators */}
              <div className="flex justify-center mt-4 items-center gap-6">
                <div className="flex gap-1.5 bg-muted/50 px-2.5 py-1.5 rounded-full">
                  {items.map((_, index) => (
                    <button
                      key={index}
                      onClick={() => {
                        setActiveSlideIndex(index)
                        scrollToSlide(index)
                      }}
                      className={`w-6 h-0.5 transition-all duration-300 rounded-full ${index === activeSlideIndex ? 'bg-primary' : 'bg-muted-foreground/30'}`}
                      aria-label={`Go to slide ${index + 1}`}
                    />
                  ))}
                </div>
              </div>
            </>
          )}
        </div>

        {/* View All button for mobile */}
        <div className="mt-8 text-center sm:hidden">
          <Link
            href="#booking"
            className="inline-block px-6 py-2 bg-[#0E3F2E] text-white font-sans font-semibold text-base rounded-md border-2 border-[#0E3F2E] shadow-md shadow-[#0E3F2E]/30 hover:shadow-lg hover:shadow-[#0E3F2E]/50 transition-all duration-300 transform hover:-translate-y-0.5 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]/40"
          >
            View All 25+ Rituals
          </Link>
        </div>
      </div>
      
      <style jsx global>{`
        .scrollbar-hide::-webkit-scrollbar {
          display: none;
        }
      `}</style>
    </section>
  )
}
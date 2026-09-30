'use client'

import React, { useEffect, useMemo, useState } from 'react'
import Link from "next/link"
import { ArrowRight, Sparkles, Star, X, Flower2, Grid, User, Calendar, IndianRupee, Search, Coins, ShieldCheck, Laptop, BookOpen, Compass, Home } from "lucide-react"
import { CatalogFilterBar } from "@/components/sections/CatalogFilterBar"
import { FilterPanelModal } from "@/components/sections/FilterPanelModal"
import { CategoryHeroBanner } from "@/components/sections/CategoryHeroBanner"
import { PujaCard } from "@/components/ui/PujaCard"
import { getAllPujas, Puja } from "@/lib/data/pujas"

const allPujas = getAllPujas();



const filters = {
  category: ["Home & Family", "Life Events", "Business & Prosperity", "Special Rituals"],
  priceRange: ["Under Rs. 1,000", "Rs. 1,000 - Rs. 5,000", "Rs. 5,000 - Rs. 10,000", "Above Rs. 10,000"],
  pandit: ["Certified Pandits", "Experienced Pandits", "Senior Pandits"],
  ritualType: ["Vedic", "Tantric", "Puranic", "Custom"],
  availability: ["Immediate", "Within 3 Days", "Within Week"],
}

const comparisonGuides = [
  {
    title: "Navdhya vs 99Pandit",
    href: "/compare/navdhya-vs-99pandit",
    description: "Compare Delhi NCR fit, local pricing signals, and service support.",
  },
  {
    title: "Best Pandit Booking Services in Delhi NCR",
    href: "/compare/best-pandit-booking-delhi",
    description: "A shortlist for users who are still deciding between platforms.",
  },
  {
    title: "Navdhya vs VidhiPujan",
    href: "/compare/navdhya-vs-vidhipujan",
    description: "Useful for users comparing catalog breadth with local service clarity.",
  },
  {
    title: "Navdhya vs SmartPuja",
    href: "/compare/navdhya-vs-smartpuja",
    description: "Best for users comparing value against a more premium package style.",
  },
  {
    title: "Pandit Booking Pricing in Delhi",
    href: "/compare/pandit-booking-pricing-delhi",
    description: "See what families actually compare before booking.",
  },
  {
    title: "99Pandit Alternatives in Delhi",
    href: "/compare/99pandit-alternatives-delhi",
    description: "Useful when you want a more local and price-aware option.",
  },
  {
    title: "Navdhya vs BookMyPooja",
    href: "/compare/navdhya-vs-bookmypooja",
    description: "Compare a Delhi-first service path against a broader generalist option.",
  },
  {
    title: "Navdhya vs Harivara",
    href: "/compare/navdhya-vs-harivara",
    description: "Best for users comparing local fit with more regional ritual preferences.",
  },
]

const planningGuides = [
  { 
    title: "Costs", 
    href: "/blog/puja-cost-guide-delhi",
    description: "Understand pricing, inclusions and factors that affect the cost",
    icon: Coins,
    count: 19
  },
  { 
    title: "Verify", 
    href: "/blog/how-to-verify-pandit",
    description: "Simple tips to choose a genuine and experienced pandit",
    icon: ShieldCheck,
    count: 20
  },
  { 
    title: "Online", 
    href: "/blog/online-puja-vs-home-puja",
    description: "Compare benefits, process and which option is right for you",
    icon: Laptop,
    count: 16
  },
  { 
    title: "Same-Day", 
    href: "/blog/same-day-pandit-booking-delhi",
    description: "Quick and easy booking for urgent puja requirements",
    icon: Calendar,
    count: 16
  },
  { 
    title: "Clean-Up", 
    href: "/blog/post-puja-clean-up-after-home-puja",
    description: "What happens after the puja and how to manage clean-up",
    icon: Sparkles,
    count: 15
  },
]

const cityPages = [
  { title: "Delhi", href: "/centers/delhi" },
  { title: "Gurgaon", href: "/centers/gurgaon" },
  { title: "Noida", href: "/centers/noida" },
  { title: "Ghaziabad", href: "/centers/ghaziabad" },
]

const authorityPages = [
  { 
    title: "Sanskars", 
    href: "/ceremonies/16-sanskars",
    description: "Learn about the essential life rituals from birth to marriage",
    icon: BookOpen,
    count: 16
  },
  { 
    title: "Muhurat", 
    href: "/astrology/muhurat-2026",
    description: "Auspicious dates and timings for moving into your new home",
    icon: Calendar,
    count: 12
  },
  { 
    title: "Kundli", 
    href: "/astrology/kundli",
    description: "Get personalized astrological insights for important life decisions",
    icon: Compass,
    count: 8
  },
  { 
    title: "Vastu", 
    href: "/vastu/home-guide",
    description: "Create harmony and positive energy in every corner of your home",
    icon: Home,
    count: 9
  },
]

function parsePrice(value: string) {
  return Number(value.replace(/[^0-9]/g, "")) || 0
}

function LinkTile({ title, href, description }: { title: string; href: string; description?: string }) {
  return (
    <Link
      href={href}
      className="group rounded-2xl border border-border bg-card p-4 transition-colors hover:border-[#0E3F2E]/30 hover:bg-[#0E3F2E]/[0.03]"
    >
      <div className="flex items-start justify-between gap-3">
        <div>
          <h3 className="text-sm font-semibold text-foreground">{title}</h3>
          {description ? <p className="mt-2 text-sm leading-6 text-muted-foreground">{description}</p> : null}
        </div>
        <ArrowRight className="mt-0.5 h-4 w-4 shrink-0 text-[#0E3F2E] transition-transform group-hover:translate-x-1" />
      </div>
    </Link>
  )
}



export default function PujaCatalogClient() {
  const [showOnSale, setShowOnSale] = useState(false)
  const [sortBy, setSortBy] = useState("price-desc")
  const [showFilters, setShowFilters] = useState(false)
  const [expandedFilter, setExpandedFilter] = useState<string | null>(null)
  const [selectedFilters, setSelectedFilters] = useState({
    category: [] as string[],
    priceRange: [] as string[],
    pandit: [] as string[],
    ritualType: [] as string[],
    availability: [] as string[],
  })
  const [filteredPujas, setFilteredPujas] = useState<Puja[]>(allPujas)
  const [searchQuery, setSearchQuery] = useState("")

  useEffect(() => {
    let result = [...allPujas]

    if (showOnSale) {
      result = result.filter((puja) => puja.badge)
    }

    if (searchQuery.trim() !== "") {
      const lowerQ = searchQuery.toLowerCase()
      result = result.filter(
        (puja) => 
          puja.title.toLowerCase().includes(lowerQ) || 
          puja.description.toLowerCase().includes(lowerQ) ||
          puja.category.toLowerCase().includes(lowerQ)
      )
    }

    if (selectedFilters.category.length > 0) {
      result = result.filter((puja) => selectedFilters.category.includes(puja.category))
    }

    if (selectedFilters.priceRange.length > 0) {
      result = result.filter((puja) => selectedFilters.priceRange.some((range) => puja.priceRange === range))
    }

    if (selectedFilters.pandit.length > 0) {
      result = result.filter((puja) => selectedFilters.pandit.includes(puja.panditType || ""))
    }

    if (selectedFilters.ritualType.length > 0) {
      result = result.filter((puja) => selectedFilters.ritualType.includes(puja.ritualType || ""))
    }

    if (selectedFilters.availability.length > 0) {
      result = result.filter((puja) => selectedFilters.availability.includes(puja.availability || ""))
    }

    switch (sortBy) {
      case "price":
        result.sort((a, b) => parsePrice(a.price) - parsePrice(b.price))
        break
      case "price-desc":
        result.sort((a, b) => parsePrice(b.price) - parsePrice(a.price))
        break
      case "popularity":
      case "rating":
      case "date":
        result.sort((a, b) => a.id.localeCompare(b.id))
        break
      default:
        break
    }

    setFilteredPujas(result)
  }, [selectedFilters, showOnSale, sortBy, searchQuery])

  const toggleFilter = (filterType: keyof typeof selectedFilters, value: string) => {
    setSelectedFilters((prev) => {
      const currentValues = prev[filterType]
      const nextValues = currentValues.includes(value)
        ? currentValues.filter((item) => item !== value)
        : [...currentValues, value]

      return {
        ...prev,
        [filterType]: nextValues,
      }
    })
  }

  const clearAllFilters = () => {
    setSelectedFilters({
      category: [],
      priceRange: [],
      pandit: [],
      ritualType: [],
      availability: [],
    })
    setShowOnSale(false)
    setSortBy("price-desc")
    setExpandedFilter(null)
  }




  return (
    <div className="min-h-screen bg-background" onClick={() => setExpandedFilter(null)}>
      <CategoryHeroBanner
        breadcrumbs={[
          { label: "Home", href: "/" },
          { label: "Puja Services", href: "/pujas" },
          { label: "Occasion Pujas" },
        ]}
        title="Occasion Pujas"
        description="Special puja services for life's important moments. Celebrate weddings, baby naming, annaprashan, and other significant occasions with authentic rituals performed by certified Vedic pandits."
        imageUrl="https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_db05ba7c437e48abe6440798d2a33b09dr_bokdku.jpg"
      />
      <CatalogFilterBar
        selectedFilters={selectedFilters}
        showOnSale={showOnSale}
        setShowFilters={setShowFilters}
        sortBy={sortBy}
        setSortBy={setSortBy}
        searchQuery={searchQuery}
        setSearchQuery={setSearchQuery}
        totalResults={filteredPujas.length}
      />
      <section className="mx-auto max-w-7xl px-2.5 sm:px-4 pb-8 pt-4 sm:px-6">
        <div className="grid grid-cols-2 gap-3 sm:gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          {filteredPujas.map((puja) => (
            <PujaCard key={puja.id} puja={puja} />
          ))}
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 pb-12 sm:px-6">
        <div className="mb-6">
          <h2 className="text-[24px] sm:text-[28px] font-bold text-[#10263f] leading-tight">Planning Guides</h2>
          <p className="mt-2 text-[15px] text-[#6a7582]">Essential information to help you plan your puja, understand costs, and choose the right service.</p>
        </div>
        
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-5">
          {planningGuides.map((guide) => (
            <Link 
              key={guide.href} 
              href={guide.href} 
              className="relative overflow-hidden rounded-[16px] sm:rounded-[20px] bg-[#ffab4a] p-5 sm:p-6 aspect-square sm:aspect-[4/4.5] flex flex-col group transition-all hover:-translate-y-1.5 duration-300"
            >
              
              <div className="flex items-center gap-2.5 relative z-10">
                <h3 className="text-[20px] sm:text-[24px] font-extrabold text-[#2a3038] tracking-tight">{guide.title}</h3>
                <span className="bg-white text-[11px] sm:text-[12px] font-bold px-2.5 py-0.5 rounded-full text-[#2a3038]">{guide.count}</span>
              </div>
              
              <div className="mt-auto self-end relative z-10 w-full h-[65%] flex items-end justify-center">
                <guide.icon className="relative z-10 w-[90px] h-[90px] sm:w-[110px] sm:h-[110px] text-[#2a3038]/80 group-hover:scale-110 group-hover:-translate-y-2 group-hover:rotate-6 transition-all duration-500" strokeWidth={2.2} />
              </div>
            </Link>
          ))}
        </div>

        <div className="mt-12 mb-6">
          <h2 className="text-[24px] sm:text-[28px] font-bold text-[#10263f] leading-tight">Astrology & Vastu</h2>
          <p className="mt-2 text-[15px] text-[#6a7582]">Deepen your understanding of Vedic traditions, auspicious timings, and space harmony.</p>
        </div>
        
        <div className="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-5">
          {authorityPages.map((page) => (
            <Link 
              key={page.href} 
              href={page.href} 
              className="relative overflow-hidden rounded-[16px] sm:rounded-[20px] bg-[#6db3ad] p-5 sm:p-6 aspect-square sm:aspect-[4/4.5] flex flex-col group transition-all hover:-translate-y-1.5 duration-300"
            >
              
              <div className="flex items-center gap-2.5 relative z-10">
                <h3 className="text-[20px] sm:text-[24px] font-extrabold text-[#153431] tracking-tight">{page.title}</h3>
                <span className="bg-white text-[11px] sm:text-[12px] font-bold px-2.5 py-0.5 rounded-full text-[#153431]">{page.count}</span>
              </div>
              
              <div className="mt-auto self-end relative z-10 w-full h-[65%] flex items-end justify-center">
                <page.icon className="relative z-10 w-[90px] h-[90px] sm:w-[110px] sm:h-[110px] text-[#153431]/80 group-hover:scale-110 group-hover:-translate-y-2 group-hover:rotate-6 transition-all duration-500" strokeWidth={2.2} />
              </div>
            </Link>
          ))}
        </div>
      </section>

      {showFilters ? (
        <FilterPanelModal
          filters={filters}
          selectedFilters={selectedFilters}
          toggleFilter={toggleFilter}
          showOnSale={showOnSale}
          setShowOnSale={setShowOnSale}
          clearAllFilters={clearAllFilters}
          setShowFilters={setShowFilters}
          filteredPujasCount={filteredPujas.length}
        />
      ) : null}
    </div>
  )
}

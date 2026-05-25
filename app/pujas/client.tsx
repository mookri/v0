'use client'

import React, { useEffect, useMemo, useState } from 'react'
import Link from "next/link"
import { ArrowRight, ChevronDown, Filter, MapPin, ShieldCheck, Sparkles, Star, X } from "lucide-react"

interface Puja {
  id: number
  name: string
  category: string
  price: string
  originalPrice?: string
  image: string
  badge?: string
  slug: string
  description: string
  priceRange?: string
  panditType?: string
  ritualType?: string
  availability?: string
}

const allPujas: Puja[] = [
  {
    id: 1,
    name: "Griha Pravesh Puja",
    category: "Home & Family",
    price: "Rs. 2,500",
    originalPrice: "Rs. 5,500",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    badge: "-11%",
    slug: "griha-pravesh",
    description: "Move-in ceremony planning with muhurat guidance, samagri support, and verified pandits in Delhi NCR.",
    priceRange: "Rs. 1,000 - Rs. 5,000",
    panditType: "Certified Pandits",
    ritualType: "Vedic",
    availability: "Immediate",
  },
  {
    id: 2,
    name: "Satyanarayan Katha",
    category: "Home & Family",
    price: "Rs. 2,000",
    originalPrice: "Rs. 4,500",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    badge: "-34%",
    slug: "satyanarayan-katha",
    description: "Devotional home puja with katha recitation, family participation, and transparent Delhi pricing.",
    priceRange: "Rs. 1,000 - Rs. 5,000",
    panditType: "Experienced Pandits",
    ritualType: "Puranic",
    availability: "Within 3 Days",
  },
  {
    id: 3,
    name: "Lakshmi Ganesh Puja",
    category: "Home & Family",
    price: "Rs. 1,800",
    originalPrice: "Rs. 3,800",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg",
    badge: "-24%",
    slug: "lakshmi-ganesh-puja",
    description: "A practical home blessing service for prosperity, festive worship, and new beginnings.",
    priceRange: "Rs. 1,000 - Rs. 5,000",
    panditType: "Certified Pandits",
    ritualType: "Vedic",
    availability: "Within Week",
  },
  {
    id: 4,
    name: "Hindu Wedding Ceremony",
    category: "Life Events",
    price: "Rs. 24,999",
    originalPrice: "Rs. 75,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    badge: "Hot",
    slug: "hindu-wedding",
    description: "Delhi NCR wedding ritual support with Vedic vidhi, planning help, and ceremony-day coordination.",
    priceRange: "Above Rs. 10,000",
    panditType: "Senior Pandits",
    ritualType: "Vedic",
    availability: "Within 3 Days",
  },
  {
    id: 5,
    name: "Baby Naming (Namkaran)",
    category: "Life Events",
    price: "Rs. 2,500",
    originalPrice: "Rs. 4,500",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    badge: "-18%",
    slug: "baby-naming",
    description: "A home-friendly naming ceremony with family blessings and practical ceremony guidance.",
    priceRange: "Rs. 1,000 - Rs. 5,000",
    panditType: "Experienced Pandits",
    ritualType: "Custom",
    availability: "Immediate",
  },
  {
    id: 6,
    name: "Annaprashan Ceremony",
    category: "Life Events",
    price: "Rs. 2,000",
    originalPrice: "Rs. 4,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg",
    slug: "annaprashan",
    description: "First-food ceremony booking for families who want a clear ritual flow and practical support.",
    priceRange: "Rs. 1,000 - Rs. 5,000",
    panditType: "Certified Pandits",
    ritualType: "Puranic",
    availability: "Within Week",
  },
  {
    id: 7,
    name: "Office Opening Puja",
    category: "Business & Prosperity",
    price: "Rs. 3,500",
    originalPrice: "Rs. 7,500",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    badge: "-15%",
    slug: "office-opening",
    description: "New office or shop blessing for Delhi NCR businesses that want an efficient ceremony setup.",
    priceRange: "Rs. 1,000 - Rs. 5,000",
    panditType: "Senior Pandits",
    ritualType: "Tantric",
    availability: "Immediate",
  },
  {
    id: 8,
    name: "Vastu Shanti Puja",
    category: "Business & Prosperity",
    price: "Rs. 3,000",
    originalPrice: "Rs. 6,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    badge: "-22%",
    slug: "vastu-shanti",
    description: "Space-harmony ceremony for homes and offices with a strong Delhi NCR move-in use case.",
    priceRange: "Rs. 1,000 - Rs. 5,000",
    panditType: "Experienced Pandits",
    ritualType: "Vedic",
    availability: "Within 3 Days",
  },
  {
    id: 9,
    name: "Havan and Yagya",
    category: "Home & Family",
    price: "Rs. 2,200",
    originalPrice: "Rs. 4,800",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_db05ba7c437e48abe6440798d2a33b09dr_bokdku.jpg",
    badge: "-20%",
    slug: "havan",
    description: "Home havan booking for purification, peace, family milestones, and apartment-friendly ritual planning.",
    priceRange: "Rs. 1,000 - Rs. 5,000",
    panditType: "Experienced Pandits",
    ritualType: "Vedic",
    availability: "Within 3 Days",
  },
  {
    id: 10,
    name: "Navagraha Puja",
    category: "Special Rituals",
    price: "Rs. 4,500",
    originalPrice: "Rs. 6,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg",
    badge: "-25%",
    slug: "navagraha-puja",
    description: "Planetary harmony puja with astrology-led guidance and ceremony planning support.",
    priceRange: "Rs. 1,000 - Rs. 5,000",
    panditType: "Certified Pandits",
    ritualType: "Vedic",
    availability: "Within Week",
  },
  {
    id: 11,
    name: "Mundan Sanskar",
    category: "Life Events",
    price: "Rs. 2,800",
    originalPrice: "Rs. 5,200",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    badge: "-18%",
    slug: "mundan",
    description: "First-haircut ceremony booking with practical support for family participation and venue planning.",
    priceRange: "Rs. 1,000 - Rs. 5,000",
    panditType: "Experienced Pandits",
    ritualType: "Custom",
    availability: "Within Week",
  },
  {
    id: 12,
    name: "Bhoomi Pujan",
    category: "Business & Prosperity",
    price: "Rs. 3,200",
    originalPrice: "Rs. 6,200",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg",
    badge: "-19%",
    slug: "bhoomi-pujan",
    description: "Land and foundation blessing for plots, renovations, commercial spaces, and new construction.",
    priceRange: "Rs. 1,000 - Rs. 5,000",
    panditType: "Senior Pandits",
    ritualType: "Vedic",
    availability: "Within 3 Days",
  },
]

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
  { title: "Puja Cost Guide", href: "/blog/puja-cost-guide-delhi" },
  { title: "How to Verify a Pandit", href: "/blog/how-to-verify-pandit" },
  { title: "Online Puja vs Home Puja", href: "/blog/online-puja-vs-home-puja" },
  { title: "Same-Day Pandit Booking", href: "/blog/same-day-pandit-booking-delhi" },
  { title: "Post-Puja Clean-Up Guide", href: "/blog/post-puja-clean-up-after-home-puja" },
]

const cityPages = [
  { title: "Delhi", href: "/centers/delhi" },
  { title: "Gurgaon", href: "/centers/gurgaon" },
  { title: "Noida", href: "/centers/noida" },
  { title: "Ghaziabad", href: "/centers/ghaziabad" },
]

const authorityPages = [
  { title: "16 Hindu Sanskars", href: "/ceremonies/16-sanskars" },
  { title: "Griha Pravesh Muhurat 2026", href: "/astrology/muhurat-2026" },
  { title: "Kundli Analysis", href: "/astrology/kundli" },
  { title: "Room-by-Room Vastu Guide", href: "/vastu/home-guide" },
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

function PujaCard({ puja }: { puja: Puja }) {
  return (
    <div className="group flex h-full flex-col overflow-hidden rounded-2xl border border-border bg-card">
      <div className="relative">
        <img src={puja.image} alt={puja.name} className="h-44 w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]" />
        <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent" />
        {puja.badge ? (
          <span className="absolute right-3 top-3 rounded-full bg-[#0E3F2E] px-2.5 py-1 text-[10px] font-semibold tracking-[0.18em] text-white">
            {puja.badge}
          </span>
        ) : null}
        <div className="absolute bottom-3 left-3 right-3 flex items-end justify-between gap-3">
          <div>
            <p className="text-[11px] uppercase tracking-[0.18em] text-white/75">{puja.category}</p>
            <h3 className="mt-1 text-sm font-semibold text-white">
              <Link href={`/pujas/${puja.slug}`}>{puja.name}</Link>
            </h3>
          </div>
          <div className="rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-[#0E3F2E]">{puja.price}</div>
        </div>
      </div>

      <div className="flex flex-1 flex-col p-4">
        <p className="text-sm leading-6 text-muted-foreground">{puja.description}</p>
        <div className="mt-4 flex flex-wrap gap-2 text-[11px] text-muted-foreground">
          {puja.availability ? <span className="rounded-full bg-muted px-2.5 py-1">{puja.availability}</span> : null}
          {puja.ritualType ? <span className="rounded-full bg-muted px-2.5 py-1">{puja.ritualType}</span> : null}
          {puja.panditType ? <span className="rounded-full bg-muted px-2.5 py-1">{puja.panditType}</span> : null}
        </div>
        <Link
          href={`/pujas/${puja.slug}`}
          className="mt-5 inline-flex items-center justify-center gap-2 rounded-xl bg-[#0E3F2E] px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-[#0b3024]"
        >
          <span>View Service</span>
          <ArrowRight className="h-4 w-4" />
        </Link>
      </div>
    </div>
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

  useEffect(() => {
    let result = [...allPujas]

    if (showOnSale) {
      result = result.filter((puja) => puja.badge)
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
        result.sort((a, b) => b.id - a.id)
        break
      default:
        break
    }

    setFilteredPujas(result)
  }, [selectedFilters, showOnSale, sortBy])

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

  const hasActiveFilters = useMemo(() => {
    return (
      selectedFilters.category.length > 0 ||
      selectedFilters.priceRange.length > 0 ||
      selectedFilters.pandit.length > 0 ||
      selectedFilters.ritualType.length > 0 ||
      selectedFilters.availability.length > 0 ||
      showOnSale
    )
  }, [selectedFilters, showOnSale])

  const FilterDropdown = ({
    title,
    items,
    filterType,
  }: {
    title: string
    items: string[]
    filterType: keyof typeof selectedFilters
  }) => {
    const isExpanded = expandedFilter === title
    const activeCount = selectedFilters[filterType].length

    return (
      <div className="relative">
        <button
          onClick={(event) => {
            event.stopPropagation()
            setExpandedFilter(isExpanded ? null : title)
          }}
          className={`inline-flex items-center gap-2 rounded-full border px-3 py-2 text-xs transition-colors ${
            activeCount > 0 ? 'border-[#0E3F2E] bg-[#0E3F2E] text-white' : 'border-border bg-background'
          }`}
        >
          <span>{title}</span>
          <ChevronDown className={`h-3.5 w-3.5 transition-transform ${isExpanded ? 'rotate-180' : ''}`} />
          {activeCount > 0 ? (
            <span className="rounded-full bg-white px-1.5 py-0.5 text-[10px] font-semibold text-[#0E3F2E]">{activeCount}</span>
          ) : null}
        </button>

        {isExpanded ? (
          <div className="absolute left-0 top-full z-10 mt-2 w-56 rounded-2xl border border-border bg-background p-2 shadow-xl">
            {items.map((item) => (
              <label key={item} className="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2 hover:bg-muted">
                <input
                  type="checkbox"
                  checked={selectedFilters[filterType].includes(item)}
                  onChange={() => toggleFilter(filterType, item)}
                  className="h-4 w-4 rounded border-border"
                />
                <span className="text-xs">{item}</span>
              </label>
            ))}
          </div>
        ) : null}
      </div>
    )
  }

  const FilterSheet = () => (
    <div className="fixed inset-0 z-50 bg-background">
      <div className="flex h-full flex-col">
        <div className="flex items-center justify-between border-b border-border px-4 py-4">
          <h2 className="text-lg font-semibold">Filters</h2>
          <button onClick={() => setShowFilters(false)} className="rounded-full p-2 hover:bg-muted">
            <X className="h-5 w-5" />
          </button>
        </div>

        <div className="flex-1 space-y-6 overflow-y-auto px-4 py-5">
          <div>
            <h3 className="mb-3 text-sm font-semibold">Category</h3>
            <div className="space-y-2">
              {filters.category.map((value) => (
                <label key={value} className="flex items-center gap-3">
                  <input type="checkbox" checked={selectedFilters.category.includes(value)} onChange={() => toggleFilter("category", value)} className="h-4 w-4 rounded border-border" />
                  <span className="text-sm">{value}</span>
                </label>
              ))}
            </div>
          </div>

          <div>
            <h3 className="mb-3 text-sm font-semibold">Price Range</h3>
            <div className="space-y-2">
              {filters.priceRange.map((value) => (
                <label key={value} className="flex items-center gap-3">
                  <input type="checkbox" checked={selectedFilters.priceRange.includes(value)} onChange={() => toggleFilter("priceRange", value)} className="h-4 w-4 rounded border-border" />
                  <span className="text-sm">{value}</span>
                </label>
              ))}
            </div>
          </div>

          <div>
            <h3 className="mb-3 text-sm font-semibold">Pandit Type</h3>
            <div className="space-y-2">
              {filters.pandit.map((value) => (
                <label key={value} className="flex items-center gap-3">
                  <input type="checkbox" checked={selectedFilters.pandit.includes(value)} onChange={() => toggleFilter("pandit", value)} className="h-4 w-4 rounded border-border" />
                  <span className="text-sm">{value}</span>
                </label>
              ))}
            </div>
          </div>

          <div>
            <h3 className="mb-3 text-sm font-semibold">Ritual Type</h3>
            <div className="space-y-2">
              {filters.ritualType.map((value) => (
                <label key={value} className="flex items-center gap-3">
                  <input type="checkbox" checked={selectedFilters.ritualType.includes(value)} onChange={() => toggleFilter("ritualType", value)} className="h-4 w-4 rounded border-border" />
                  <span className="text-sm">{value}</span>
                </label>
              ))}
            </div>
          </div>

          <div>
            <h3 className="mb-3 text-sm font-semibold">Availability</h3>
            <div className="space-y-2">
              {filters.availability.map((value) => (
                <label key={value} className="flex items-center gap-3">
                  <input type="checkbox" checked={selectedFilters.availability.includes(value)} onChange={() => toggleFilter("availability", value)} className="h-4 w-4 rounded border-border" />
                  <span className="text-sm">{value}</span>
                </label>
              ))}
            </div>
          </div>

          <label className="flex items-center gap-3 rounded-2xl border border-border p-4">
            <input type="checkbox" checked={showOnSale} onChange={(event) => setShowOnSale(event.target.checked)} className="h-4 w-4 rounded border-border" />
            <span className="text-sm">Show only services with visible offers</span>
          </label>
        </div>

        <div className="grid grid-cols-2 gap-3 border-t border-border px-4 py-4">
          <button onClick={clearAllFilters} className="rounded-xl border border-border px-4 py-3 text-sm font-medium hover:bg-muted">
            Clear All
          </button>
          <button onClick={() => setShowFilters(false)} className="rounded-xl bg-[#0E3F2E] px-4 py-3 text-sm font-semibold text-white hover:bg-[#0b3024]">
            Apply Filters
          </button>
        </div>
      </div>
    </div>
  )

  return (
    <div className="min-h-screen bg-background" onClick={() => setExpandedFilter(null)}>
      <div className="mx-auto mt-4 max-w-7xl px-4 sm:px-6">
        <nav className="text-xs text-muted-foreground">
          <Link href="/" className="hover:text-foreground">Home</Link>
          <span className="mx-1.5">/</span>
          <span className="text-foreground">Puja Services</span>
        </nav>
      </div>

      <section className="mx-auto mt-4 max-w-7xl px-4 sm:px-6">
        <div
          className="relative overflow-hidden rounded-[28px] border border-[#0E3F2E]/10 bg-[#0E3F2E] text-white"
          style={{
            backgroundImage: "linear-gradient(135deg, rgba(14,63,46,0.96), rgba(14,63,46,0.82)), url('https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg')",
            backgroundSize: "cover",
            backgroundPosition: "center",
          }}
        >
          <div className="grid gap-8 px-6 py-8 sm:px-8 lg:grid-cols-[1.3fr_0.9fr] lg:px-10 lg:py-10">
            <div>
              <div className="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-[11px] uppercase tracking-[0.22em] text-white/80">
                <Sparkles className="h-3.5 w-3.5" />
                Delhi NCR Puja Booking
              </div>
              <h1 className="mt-5 max-w-3xl font-serif text-[clamp(2.1rem,5vw,4rem)] leading-[1.04] tracking-tight">
                Book Pandit Online in Delhi NCR for Home, Family, and Business Ceremonies
              </h1>
              <p className="mt-5 max-w-2xl text-sm leading-7 text-white/82 sm:text-base">
                This page is the main Delhi NCR booking hub for Griha Pravesh, Havan, Satyanarayan Katha, weddings, baby ceremonies,
                office opening, Bhoomi Pujan, and Vastu-linked rituals. Use it to compare services, prices, local city pages, and the
                best next guide for your ceremony.
              </p>
              <div className="mt-6 flex flex-wrap gap-3 text-xs text-white/85">
                <span className="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-2"><MapPin className="h-3.5 w-3.5" /> Delhi, Gurgaon, Noida, Ghaziabad</span>
                <span className="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-2"><ShieldCheck className="h-3.5 w-3.5" /> Verified pandits</span>
                <span className="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-2"><Star className="h-3.5 w-3.5" /> Transparent starting prices</span>
              </div>
            </div>

            <div className="rounded-[24px] border border-white/10 bg-white/8 p-5 backdrop-blur">
              <p className="text-[11px] uppercase tracking-[0.22em] text-white/70">Why Delhi families choose Navdhya</p>
              <div className="mt-4 space-y-3 text-sm leading-6 text-white/85">
                <p>Post-puja clean-up is part of the service story, which matters for apartment move-ins and family events.</p>
                <p>Visible Delhi NCR starting prices help users compare before they call or request a quote.</p>
                <p>Vedic University-certified pandit positioning makes the trust story more concrete than a generic listing page.</p>
                <p>Puja, astrology, and Vastu pages live in one connected system, so research intent can move into booking intent quickly.</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        <div className="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
          <div className="rounded-[24px] border border-border bg-card p-6">
            <h2 className="font-serif text-2xl text-foreground">The main keyword path this page owns</h2>
            <p className="mt-3 text-sm leading-7 text-muted-foreground">
              The primary target is <strong>book pandit online delhi</strong>, but this page also supports pandit booking Delhi NCR,
              home puja services Delhi, and ceremony-led searches like Griha Pravesh, Havan, wedding pandit Delhi, and Satyanarayan
              puja cost Delhi. Every supporting page below routes back here so this page stays the main commercial pillar.
            </p>
          </div>

          <div className="rounded-[24px] border border-border bg-card p-6">
            <h2 className="font-serif text-2xl text-foreground">Best first clicks by intent</h2>
            <div className="mt-4 grid gap-3 text-sm">
              <LinkTile title="Need pricing clarity" href="/compare/pandit-booking-pricing-delhi" description="Use this if the first question is cost, not ritual depth." />
              <LinkTile title="Still comparing providers" href="/compare/best-pandit-booking-delhi" description="Use this if you have not chosen a booking platform yet." />
              <LinkTile title="Need a local city page" href="/centers/delhi" description="Use this if locality and logistics matter first." />
            </div>
          </div>
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 pb-8 sm:px-6">
        <div className="grid gap-6 lg:grid-cols-3">
          <div className="rounded-[24px] border border-border bg-card p-6 lg:col-span-2">
            <h2 className="font-serif text-2xl text-foreground">Comparison pages for high-intent Delhi searchers</h2>
            <p className="mt-3 text-sm leading-7 text-muted-foreground">
              These pages target competitor and pricing queries where users are close to booking and need a practical Delhi NCR comparison.
            </p>
            <div className="mt-5 grid gap-4 md:grid-cols-2">
              {comparisonGuides.map((guide) => (
                <LinkTile key={guide.href} {...guide} />
              ))}
            </div>
          </div>

          <div className="rounded-[24px] border border-border bg-card p-6">
            <h2 className="font-serif text-2xl text-foreground">City coverage</h2>
            <p className="mt-3 text-sm leading-7 text-muted-foreground">
              These local pages support Delhi, Gurgaon, Noida, and Ghaziabad search intent without forcing users into generic city swaps.
            </p>
            <div className="mt-5 space-y-3">
              {cityPages.map((page) => (
                <Link key={page.href} href={page.href} className="flex items-center justify-between rounded-2xl border border-border px-4 py-3 text-sm font-medium hover:border-[#0E3F2E]/30 hover:bg-[#0E3F2E]/[0.03]">
                  <span>{page.title}</span>
                  <ArrowRight className="h-4 w-4 text-[#0E3F2E]" />
                </Link>
              ))}
            </div>
          </div>
        </div>
      </section>

      <div className="mx-auto max-w-7xl px-4 sm:px-6 md:hidden">
        <button
          onClick={(event) => {
            event.stopPropagation()
            setShowFilters(true)
          }}
          className={`mb-4 flex w-full items-center justify-center gap-2 rounded-2xl border px-4 py-3 ${
            hasActiveFilters ? 'border-[#0E3F2E] bg-[#0E3F2E] text-white' : 'border-border bg-background'
          }`}
        >
          <Filter className="h-4 w-4" />
          <span>Filters</span>
          {hasActiveFilters ? (
            <span className="rounded-full bg-white px-2 py-0.5 text-[10px] font-semibold text-[#0E3F2E]">
              {selectedFilters.category.length + selectedFilters.priceRange.length + selectedFilters.pandit.length + selectedFilters.ritualType.length + selectedFilters.availability.length + (showOnSale ? 1 : 0)}
            </span>
          ) : null}
        </button>
      </div>

      <section className="mx-auto max-w-7xl px-4 sm:px-6">
        <div className="hidden rounded-[24px] border border-border bg-card p-5 md:block">
          <div className="flex flex-wrap items-center gap-2">
            <span className="inline-flex items-center gap-2 text-xs font-medium text-muted-foreground">
              <Filter className="h-3.5 w-3.5" />
              Filters
            </span>
            <FilterDropdown title="Category" items={filters.category} filterType="category" />
            <FilterDropdown title="Price" items={filters.priceRange} filterType="priceRange" />
            <FilterDropdown title="Pandit" items={filters.pandit} filterType="pandit" />
            <FilterDropdown title="Ritual" items={filters.ritualType} filterType="ritualType" />
            <FilterDropdown title="Availability" items={filters.availability} filterType="availability" />

            <div className="ml-auto flex flex-wrap items-center gap-3">
              <label className="flex items-center gap-2 text-xs">
                <input type="checkbox" checked={showOnSale} onChange={(event) => setShowOnSale(event.target.checked)} className="h-4 w-4 rounded border-border" />
                <span>Visible offers only</span>
              </label>

              <select
                value={sortBy}
                onChange={(event) => setSortBy(event.target.value)}
                className="rounded-full border border-border bg-background px-3 py-2 text-xs"
              >
                <option value="price-desc">Price: High to Low</option>
                <option value="price">Price: Low to High</option>
                <option value="popularity">Popularity</option>
                <option value="rating">Rating</option>
                <option value="date">Latest</option>
              </select>
            </div>
          </div>
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        <div className="flex items-end justify-between gap-4">
          <div>
            <h2 className="font-serif text-2xl text-foreground">Delhi NCR puja services</h2>
            <p className="mt-2 text-sm leading-7 text-muted-foreground">
              Every service page links back to this pillar so users can move between ceremony, comparison, city, and pricing pages in two clicks or less.
            </p>
          </div>
          <div className="hidden text-sm text-muted-foreground md:block">{filteredPujas.length} services shown</div>
        </div>

        <div className="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          {filteredPujas.map((puja) => (
            <PujaCard key={puja.id} puja={puja} />
          ))}
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 pb-8 sm:px-6">
        <div className="grid gap-6 lg:grid-cols-2">
          <div className="rounded-[24px] border border-border bg-card p-6">
            <h2 className="font-serif text-2xl text-foreground">Planning guides that support booking</h2>
            <p className="mt-3 text-sm leading-7 text-muted-foreground">
              These pages answer the questions that usually block conversion: price, trust, urgency, and whether home puja or online puja is the better fit.
            </p>
            <div className="mt-5 grid gap-3">
              {planningGuides.map((guide) => (
                <Link key={guide.href} href={guide.href} className="flex items-center justify-between rounded-2xl border border-border px-4 py-3 text-sm font-medium hover:border-[#0E3F2E]/30 hover:bg-[#0E3F2E]/[0.03]">
                  <span>{guide.title}</span>
                  <ArrowRight className="h-4 w-4 text-[#0E3F2E]" />
                </Link>
              ))}
            </div>
          </div>

          <div className="rounded-[24px] border border-border bg-card p-6">
            <h2 className="font-serif text-2xl text-foreground">Authority and timing pages</h2>
            <p className="mt-3 text-sm leading-7 text-muted-foreground">
              These pages broaden topical authority without losing commercial relevance. They also create strong internal links back to the main pillar.
            </p>
            <div className="mt-5 grid gap-3">
              {authorityPages.map((page) => (
                <Link key={page.href} href={page.href} className="flex items-center justify-between rounded-2xl border border-border px-4 py-3 text-sm font-medium hover:border-[#0E3F2E]/30 hover:bg-[#0E3F2E]/[0.03]">
                  <span>{page.title}</span>
                  <ArrowRight className="h-4 w-4 text-[#0E3F2E]" />
                </Link>
              ))}
            </div>
          </div>
        </div>
      </section>

      {showFilters ? <FilterSheet /> : null}
    </div>
  )
}

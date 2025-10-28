'use client'

import { useState } from "react"
import { Button } from "@/components/ui/button"
import Link from "next/link"
import Image from "next/image"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { ArrowRight } from "lucide-react"

export function Catalog() {
  const [activeCategory, setActiveCategory] = useState("All")

  const categories = [
    { value: "All", label: "All Services" },
    { value: "Home Rituals", label: "Home Rituals" },
    { value: "Life Milestones", label: "Life Milestones" },
    { value: "Festival", label: "Festival" },
    { value: "Corporate", label: "Corporate" },
    { value: "Special", label: "Special" }
  ]

  const items = [
    {
      slug: "griha-pravesh",
      title: "Grihapravesh Puja",
      desc: "Traditional housewarming ceremony for new homes with complete rituals and blessings.",
      price: "₹3,500",
      tag: "Most Booked",
      imgQuery: "griha%20pravesh%20home%20ritual%20mandap",
      category: "Home Rituals",
    },
    {
      slug: "satyanarayan-puja",
      title: "Satyanarayan Puja",
      desc: "Sacred worship ceremony for prosperity, peace, and divine blessings for the family.",
      price: "₹2,500",
      tag: "Popular",
      imgQuery: "satyanarayan%20katha%20ritual",
      category: "Home Rituals",
    },
    {
      slug: "wedding-ceremony",
      title: "Wedding Ceremony",
      desc: "Complete Hindu wedding rituals with experienced priests for your special day.",
      price: "₹15,000",
      tag: undefined,
      imgQuery: "hindu%20wedding%20ceremony%20mandap",
      category: "Life Milestones",
    },
    {
      slug: "ganesh-chaturthi",
      title: "Ganesh Chaturthi",
      desc: "Festival celebration with traditional Ganesh installation and daily prayers.",
      price: "₹5,000",
      tag: undefined,
      imgQuery: "ganesh%20chaturthi%20festival%20celebration",
      category: "Festival",
    },
    {
      slug: "navagraha-puja",
      title: "Navagraha Puja",
      desc: "Nine planetary worship for removing obstacles and bringing positive energy.",
      price: "₹4,500",
      tag: "Popular",
      imgQuery: "navagraha%20puja%20ritual",
      category: "Special",
    },
    {
      slug: "baby-naming",
      title: "Baby Naming Ceremony",
      desc: "Traditional Namkaran ceremony with auspicious name selection and blessings.",
      price: "₹3,000",
      tag: undefined,
      imgQuery: "baby%20naming%20ceremony%20hindu",
      category: "Life Milestones",
    },
    {
      slug: "shraddh-ceremony",
      title: "Shraddh Ceremony",
      desc: "Honor your ancestors with authentic Vedic rituals and offerings.",
      price: "₹6,500",
      tag: undefined,
      imgQuery: "shraddh%20ceremony%20ritual",
      category: "Special",
    },
    {
      slug: "rudrabhishek",
      title: "Rudrabhishek",
      desc: "Sacred Shiva worship for peace, prosperity and removal of obstacles.",
      price: "₹4,000",
      tag: undefined,
      imgQuery: "rudrabhishek%20shiva%20ritual",
      category: "Special",
    },
  ]

  // Filter items based on active category
  const filteredItems = activeCategory === "All" 
    ? items 
    : items.filter(item => item.category === activeCategory)

  return (
    <section id="catalog" aria-labelledby="catalog-title" className="bg-background">
      <div className="mx-auto max-w-6xl px-4 py-12 overflow-x-hidden w-full">
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
           <h2
            id="catalog-title"
            className="font-serif text-3xl font-bold text-foreground text-center"
          >
            Our Services
          </h2>
         <div className="w-full sm:w-48">
            <Select value={activeCategory} onValueChange={setActiveCategory}>
              <SelectTrigger className="w-full border border-slate-300 rounded-md bg-white px-3 py-2 focus:ring-2 focus:ring-[#0E3F2E]/20 focus:border-[#0E3F2E] transition-all duration-300">
                <SelectValue placeholder="Select category" />
              </SelectTrigger>
              <SelectContent>
                {categories.map((category) => (
                  <SelectItem key={category.value} value={category.value}>
                    {category.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        </div>
        <div className="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 w-full">
          {filteredItems.length > 0 ? (
            filteredItems.map((it) => (
              <div
                key={it.title}
                className="group overflow-hidden rounded-xl bg-white border border-slate-200 transition-all duration-500 ease-in-out hover:shadow-xl hover:-translate-y-2 mx-4 sm:mx-0"
              >
                <Link href={`/pujas/${it.slug}`} className="block">
                  <div className="relative h-40 w-full overflow-hidden">
                    <Image
                      src={`/.jpg?key=s7bpr&height=320&width=480&query=${it.imgQuery}`}
                      alt={`${it.title} representative image`}
                      fill
                      className="object-cover transition-transform duration-700 ease-in-out group-hover:scale-110"
                      sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 25vw"
                    />
                    {it.tag ? (
                      <span className="absolute left-3 top-3 rounded-full bg-[#0E3F2E] px-3 py-1 text-xs text-white font-medium animate-pulse">
                        {it.tag}
                      </span>
                    ) : null}
                  </div>
                  <div className="p-4 flex flex-col h-full">
                    <h3 className="font-serif text-lg font-semibold text-slate-800 line-clamp-1">{it.title}</h3>
                    <p className="mt-2 text-sm text-slate-600 flex-grow line-clamp-2">{it.desc}</p>
                    <div className="mt-4 flex items-center justify-between">
                      <p className="text-sm font-medium text-[#0E3F2E]">
                        {it.price}
                      </p>
                    </div>
                  </div>
                </Link>
                <div className="px-4 pb-4">
                  <Link href="#booking" className="block">
                    <Button className="w-full bg-[#0E3F2E] text-white hover:bg-[#0E3F2E]/90 text-sm h-11 font-medium rounded-lg transition-all duration-300 ease-in-out transform hover:scale-[1.02] hover:shadow-md flex items-center justify-center gap-2 group/button shadow-sm">
                      <span className="flex items-center gap-2">
                        Book Now <ArrowRight className="w-4 h-4 transition-transform duration-500 ease-in-out group-hover/button:translate-x-1" />
                      </span>
                    </Button>
                  </Link>
                </div>
              </div>
            ))
          ) : (
            <div className="col-span-full text-center py-12">
              <p className="text-slate-500 text-sm">No services found in this category.</p>
            </div>
          )}
        </div>
        <div className="mt-8 text-center">
          <Button variant="outline" className="border-[#0E3F2E] text-[#0E3F2E] hover:bg-[#0E3F2E] hover:text-white px-6 py-2.5 h-11 text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105">
            View All 25+ Rituals
          </Button>
        </div>
      </div>
    </section>
  )
}
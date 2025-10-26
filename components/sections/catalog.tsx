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
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
          <h2 id="catalog-title" className="font-serif text-2xl md:text-3xl">
            Our Puja Services
          </h2>
          <div className="w-full sm:w-48">
            <Select value={activeCategory} onValueChange={setActiveCategory}>
              <SelectTrigger className="w-full">
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
        <div className="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4 w-full">
          {filteredItems.length > 0 ? (
            filteredItems.map((it) => (
              <div
                key={it.title}
                className="group overflow-hidden rounded-lg border bg-card transition-transform hover:-translate-y-1"
              >
                <Link href={`/pujas/${it.slug}`} className="block">
                  <div className="relative h-48 w-full">
                    <Image
                      src={`/.jpg?key=s7bpr&height=320&width=480&query=${it.imgQuery}`}
                      alt={`${it.title} representative image`}
                      fill
                      className="object-cover"
                      sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 25vw"
                    />
                    {it.tag ? (
                      <span className="absolute left-2 top-2 rounded bg-secondary px-2 py-1 text-xs text-secondary-foreground max-w-[calc(100%-1rem)] overflow-hidden">
                        {it.tag}
                      </span>
                    ) : null}
                  </div>
                  <div className="p-4 flex flex-col h-full">
                    <h3 className="font-serif text-lg">{it.title}</h3>
                    <p className="mt-1 text-sm text-muted-foreground flex-grow">{it.desc}</p>
                    <p className="mt-2 text-sm">
                      Starting from <span className="font-medium">{it.price}</span>
                    </p>
                  </div>
                </Link>
                <div className="flex items-center gap-3 px-4 pb-4">
                  <Link href="#booking" className="flex-1">
                    <Button className="w-full bg-[#0E3F2E] text-white hover:bg-[#0E3F2E]/90 border-2 border-[#0E3F2E]" size="sm">
                      Book Now
                    </Button>
                  </Link>
                  <Link href={`/pujas/${it.slug}`} className="shrink-0 text-sm hover:text-primary">
                    Learn More →
                  </Link>
                </div>
              </div>
            ))
          ) : (
            <div className="col-span-full text-center py-12">
              <p className="text-muted-foreground">No services found in this category.</p>
            </div>
          )}
        </div>
        <div className="mt-6 text-center">
          <Button variant="link">View All 25+ Rituals</Button>
        </div>
      </div>
    </section>
  )
}
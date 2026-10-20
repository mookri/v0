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
    { value: "Business & Prosperity", label: "Business & Prosperity" },
    { value: "Festival", label: "Festivals" },
    { value: "Special", label: "Special Rituals" }
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
      imageUrl: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_12d1d962f45c56a89444ef5b3cc2f267dr_fzoivd.jpg"
    },
    {
      slug: "satyanarayan-katha",
      title: "Satyanarayan Puja",
      desc: "Sacred worship ceremony for prosperity, peace, and divine blessings for the family.",
      price: "₹2,500",
      tag: "Popular",
      imgQuery: "satyanarayan%20katha%20ritual",
      category: "Home Rituals",
      imageUrl: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358798/Whisk_7a093de03fbc3bab93949b075483d783dr_zwnihl.jpg"
    },
    {
      slug: "lakshmi-ganesh-puja",
      title: "Lakshmi Ganesh Puja",
      desc: "Performed to seek blessings from Lord Ganesh and Goddess Lakshmi for removal of obstacles and prosperity.",
      price: "₹1,800",
      tag: "Popular",
      imgQuery: "lakshmi%20ganesh%20puja%20ritual",
      category: "Home Rituals",
      imageUrl: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg"
    },
    {
      slug: "hindu-wedding",
      title: "Wedding Ceremony",
      desc: "Complete Hindu wedding rituals with experienced priests for your special day.",
      price: "₹24,999",
      tag: "Life Event",
      imgQuery: "hindu%20wedding%20ceremony%20mandap",
      category: "Life Milestones",
      imageUrl: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg"
    },
    {
      slug: "baby-naming",
      title: "Baby Naming Ceremony",
      desc: "Traditional Namkaran ceremony with auspicious name selection and blessings.",
      price: "₹2,500",
      tag: "Life Milestone",
      imgQuery: "baby%20naming%20ceremony%20hindu",
      category: "Life Milestones",
      imageUrl: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg"
    },
    {
      slug: "annaprashan",
      title: "Annaprashan Ceremony",
      desc: "The first feeding of solid food to your child, marking an important milestone in their growth.",
      price: "₹2,000",
      tag: "Life Milestone",
      imgQuery: "annaprashan%20ceremony%20baby%20first%20food",
      category: "Life Milestones",
      imageUrl: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg"
    },
    {
      slug: "office-opening",
      title: "Office Opening Puja",
      desc: "Bless your new business space with divine energy for success, prosperity, and protection.",
      price: "₹3,500",
      tag: "Business Special",
      imgQuery: "office%20opening%20puja%20business%20blessing",
      category: "Business & Prosperity",
      imageUrl: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg"
    },
    {
      slug: "vastu-shanti",
      title: "Vastu Shanti Puja",
      desc: "Harmonize your living or working space with Vastu principles for positive energy flow.",
      price: "₹3,000",
      tag: "Business Special",
      imgQuery: "vastu%20shanti%20puja%20ritual",
      category: "Business & Prosperity",
      imageUrl: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg"
    },
    {
      slug: "ganesh-chaturthi",
      title: "Ganesh Chaturthi",
      desc: "Festival celebration with traditional Ganesh installation and daily prayers.",
      price: "₹5,000",
      tag: "Festival Special",
      imgQuery: "ganesh%20chaturthi%20festival%20celebration",
      category: "Festival",
      imageUrl: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_db05ba7c437e48abe6440798d2a33b09dr_bokdku.jpg"
    },
    {
      slug: "navagraha-puja",
      title: "Navagraha Puja",
      desc: "Nine planetary worship for removing obstacles and bringing positive energy.",
      price: "₹4,500",
      tag: "Popular",
      imgQuery: "navagraha%20puja%20ritual",
      category: "Special",
      imageUrl: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg"
    }
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
              <SelectTrigger className="w-full border border-slate-300 rounded-md bg-white px-3 py-2 focus:ring-2 focus:ring-[#0E3F2E]/20 focus:border-[#0E3F2E]">
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
                className="group overflow-hidden rounded-xl bg-white border border-slate-200 transition-all duration-500 ease-in-out hover:shadow-xl hover:-translate-y-2 mx-0 sm:mx-0 flex flex-col h-full"
              >
                <Link href={`/pujas/${it.slug}`} className="block flex-grow flex flex-col">
                  <div className="relative h-48 w-full overflow-hidden">
                    {it.imageUrl ? (
                      <Image
                        src={it.imageUrl}
                        alt={`${it.title} representative image`}
                        fill
                        className="object-cover transition-transform duration-700 ease-in-out group-hover:scale-110"
                        sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 25vw"
                      />
                    ) : (
                      <Image
                        src={`/.jpg?key=s7bpr&height=320&width=480&query=${it.imgQuery}`}
                        alt={`${it.title} representative image`}
                        fill
                        className="object-cover transition-transform duration-700 ease-in-out group-hover:scale-110"
                        sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 25vw"
                      />
                    )}
                    {it.tag ? (
                      <span className="absolute left-3 top-3 rounded-full bg-[#0E3F2E] px-3 py-1 text-xs text-white font-medium">
                        {it.tag}
                      </span>
                    ) : null}
                  </div>
                  <div className="p-4 flex flex-col flex-grow">
                    <h3 className="font-serif text-base font-semibold text-slate-800 line-clamp-1">{it.title}</h3>
                    <p className="mt-2 text-xs text-slate-600 flex-grow line-clamp-2">{it.desc}</p>
                    <div className="mt-3 flex items-center justify-between">
                      <p className="text-sm font-medium text-[#0E3F2E]">
                        {it.price}
                      </p>
                    </div>
                  </div>
                </Link>
                <div className="px-4 pb-4">
                  <Link href={`/pujas/${it.slug}`} className="block">
                    <Button className="w-full bg-[#0E3F2E] text-white hover:bg-[#0E3F2E]/90 text-xs h-10 font-medium rounded-lg transition-all duration-300 ease-in-out transform hover:scale-[1.02] hover:shadow-md flex items-center justify-center gap-2 group/button shadow-sm">
                      <span className="flex items-center gap-2">
                        Book Now <ArrowRight className="w-4 h-4 animate-looping-arrow" />
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
          <Link href="/pujas">
            <Button variant="outline" className="border-[#0E3F2E] text-[#0E3F2E] hover:bg-[#0E3F2E] hover:text-white px-6 py-2.5 h-11 text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105">
              View All 25+ Rituals
            </Button>
          </Link>
        </div>
      </div>
    </section>
  )
}
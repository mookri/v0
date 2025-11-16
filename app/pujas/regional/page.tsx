'use client'
import React from 'react';
import Link from "next/link"
import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight } from "lucide-react"

// Schema markup for the regional pujas page
const REGIONAL_PUJAS_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Regional Pujas - Navdhya",
  description: "Authentic regional puja traditions from across India - South Indian, North Indian, Bengali, Tamil, and more. Experience diverse cultural rituals performed by pandits specialized in regional traditions in Delhi NCR.",
  path: "/pujas/regional",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Puja Services", url: "https://www.navdhya.com/pujas" },
    { name: "Regional Pujas", url: "https://www.navdhya.com/pujas/regional" }
  ]
})

interface Puja {
  id: number;
  name: string;
  category: string;
  price: string;
  originalPrice?: string;
  image: string;
  badge?: string;
  slug: string;
  description: string;
  priceRange?: string;
  panditType?: string;
  ritualType?: string;
  availability?: string;
}

// Regional puja data
const regionalPujas: Puja[] = [
  {
    id: 1,
    name: "Tamil Pongal Celebration",
    category: "South Indian",
    price: "₹3,500",
    originalPrice: "₹5,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    badge: "-30%",
    slug: "tamil-pongal",
    description: "Traditional Tamil harvest festival with authentic rituals, kolam decorations, and pongal preparation.",
    priceRange: "₹1,000 - ₹5,000",
    panditType: "South Indian Specialists",
    ritualType: "Agamic",
    availability: "Within 3 Days"
  },
  {
    id: 2,
    name: "Bengali Durga Puja",
    category: "East Indian",
    price: "₹15,000",
    originalPrice: "₹20,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    badge: "-25%",
    slug: "bengali-durga-puja",
    description: "Elaborate Durga Puja with traditional idol installation, dhunuchi dance, and cultural programs.",
    priceRange: "₹10,000 - ₹20,000",
    panditType: "Bengali Pandits",
    ritualType: "Tantric",
    availability: "Within Week"
  },
  {
    id: 3,
    name: "Punjabi Lohri Puja",
    category: "North Indian",
    price: "₹2,500",
    originalPrice: "₹3,500",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg",
    badge: "-28%",
    slug: "punjabi-lohri",
    description: "Traditional Lohri celebration with bonfire, sesame sweets, and folk songs for newborns and harvest.",
    priceRange: "₹1,000 - ₹5,000",
    panditType: "North Indian Specialists",
    ritualType: "Folk",
    availability: "Immediate"
  },
  {
    id: 4,
    name: "Kerala Onam Pujas",
    category: "South Indian",
    price: "₹4,000",
    originalPrice: "₹6,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_db05ba7c437e48abe6440798d2a33b09dr_bokdku.jpg",
    badge: "-33%",
    slug: "kerala-onam",
    description: "Traditional Onam celebration with pookalam, sadhya feast, and cultural performances.",
    priceRange: "₹1,000 - ₹5,000",
    panditType: "South Indian Specialists",
    ritualType: "Agamic",
    availability: "Within 3 Days"
  },
  {
    id: 5,
    name: "Gujarati Navratri",
    category: "West Indian",
    price: "₹8,000",
    originalPrice: "₹12,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg",
    badge: "-33%",
    slug: "gujarati-navratri",
    description: "Nine nights of traditional Navratri with garba, dandiya, and authentic Gujarati rituals.",
    priceRange: "₹5,000 - ₹10,000",
    panditType: "Gujarati Pandits",
    ritualType: "Devotional",
    availability: "Within Week"
  },
  {
    id: 6,
    name: "Maharashtrian Gudi Padwa",
    category: "West Indian",
    price: "₹3,000",
    originalPrice: "₹4,500",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    badge: "-33%",
    slug: "maharashtrian-gudi-padwa",
    description: "Traditional Marathi New Year celebration with gudi installation and customary rituals.",
    priceRange: "₹1,000 - ₹5,000",
    panditType: "Marathi Pandits",
    ritualType: "Custom",
    availability: "Within 3 Days"
  }
];

const filters = {
  category: ["South Indian", "North Indian", "East Indian", "West Indian"],
  priceRange: ["Under ₹1,000", "₹1,000 - ₹5,000", "₹5,000 - ₹10,000", "Above ₹10,000"],
  pandit: ["South Indian Specialists", "North Indian Specialists", "Bengali Pandits", "Gujarati Pandits", "Marathi Pandits"],
  ritualType: ["Agamic", "Tantric", "Folk", "Devotional", "Custom"],
  availability: ["Immediate", "Within 3 Days", "Within Week"]
};

export default function RegionalPujasPage() {
  const pujaSchema = JSON.stringify(REGIONAL_PUJAS_SCHEMA)
  
  const PujaCard = ({ puja }: { puja: Puja }) => {
    const [isHovered, setIsHovered] = React.useState(false);
    
    return (
      <div 
        className="group relative border border-border rounded-lg overflow-hidden bg-card flex flex-col h-full"
        onMouseEnter={() => setIsHovered(true)}
        onMouseLeave={() => setIsHovered(false)}
      >
        <div className="relative bg-muted flex-shrink-0">
          <img 
            src={puja.image} 
            alt={puja.name}
            className="w-full h-40 object-cover group-hover:scale-105 transition-transform duration-300"
          />
          
          <div className="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
          
          {puja.badge && (
            <div className="absolute top-2 right-2 z-10">
              <span className={`w-8 h-8 rounded-full flex items-center justify-center text-white text-[10px] font-bold ${
                puja.badge === 'Hot' ? 'bg-green-600' : 'bg-red-600'
              }`}>
                {puja.badge}
              </span>
            </div>
          )}
          
          <div className={`absolute top-2 left-2 flex flex-col gap-1 transition-opacity duration-300 ${
            isHovered ? 'opacity-100' : 'opacity-0'
          }`}>
            <button className="w-7 h-7 bg-background/80 rounded-full flex items-center justify-center hover:bg-[#0E3F2E] hover:text-white transition-colors shadow-sm">
              <Heart className="w-3 h-3" />
            </button>
            <button className="w-7 h-7 bg-background/80 rounded-full flex items-center justify-center hover:bg-[#0E3F2E] hover:text-white transition-colors shadow-sm">
              <Eye className="w-3 h-3" />
            </button>
          </div>
        </div>
        
        <div className="p-3 space-y-1.5 flex flex-col flex-grow">
          <div className="flex justify-between items-start">
            <p className="text-[10px] text-muted-foreground">{puja.category}</p>
            <div className="flex items-center gap-1">
              {puja.originalPrice && (
                <span className="text-[10px] text-muted-foreground line-through">{puja.originalPrice}</span>
              )}
              <span className={`font-medium text-xs ${puja.originalPrice ? 'text-red-600' : 'text-foreground'}`}>
                {puja.price}
              </span>
            </div>
          </div>
          
          <h3 className="font-medium hover:text-[#0E3F2E] transition-colors line-clamp-2 text-xs flex-grow">
            <a href="#">{puja.name}</a>
          </h3>
          
          <p className="text-[10px] text-muted-foreground line-clamp-2">
            {puja.description}
          </p>
          
          <Link 
            href={`/pujas/${puja.slug}`}
            className="mt-2 inline-flex items-center justify-center gap-2 w-full bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white font-medium py-2 px-4 rounded-md transition-all duration-300 shadow-sm hover:shadow-md text-center text-xs"
          >
            <span>Book Now</span>
            <ArrowRight className="w-4 h-4 animate-looping-arrow" />
          </Link>
        </div>
      </div>
    );
  };

  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: pujaSchema }}
      />
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <Link href="/" className="hover:text-foreground">Home</Link>
            <span className="mx-1.5">/</span>
            <Link href="/pujas" className="hover:text-foreground">Puja Services</Link>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">Regional Pujas</span>
          </nav>
        </div>

        {/* Hero Banner */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div 
            className="relative h-48 rounded-lg overflow-hidden bg-cover bg-center flex items-end"
            style={{
              backgroundImage: "url('https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg')"
            }}
          >
            <div className="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
            <div className="relative z-10 text-white p-4 max-w-2xl">
              <h1 className="text-2xl font-serif font-semibold">Regional Pujas</h1>
              <p className="text-sm mt-1">
                Experience authentic regional puja traditions from across India. Celebrate diverse cultural rituals with pandits specialized in regional traditions.
              </p>
            </div>
          </div>
        </div>

        {/* Desktop Filters - Hidden on mobile */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 pb-6 hidden md:block">
          <div className="border-b border-border pb-4 mb-6">
            <div className="flex flex-wrap items-center gap-2">
              <span className="font-medium text-xs flex items-center gap-1 text-muted-foreground">
                <Filter className="w-3 h-3" />
                Filters:
              </span>
              <div className="flex items-center gap-2 text-xs">
                <span className="px-2 py-1 border border-border rounded-full bg-background">All Regions</span>
                <span className="px-2 py-1 border border-border rounded-full bg-background">Any Price</span>
                <span className="px-2 py-1 border border-border rounded-full bg-background">All Pandits</span>
              </div>
              
              <div className="flex items-center gap-3 ml-auto">
                <div className="flex items-center gap-1">
                  <span className="text-xs text-muted-foreground">Sort:</span>
                  <select
                    className="px-2 py-1 text-xs border border-border rounded-full bg-background cursor-pointer hover:bg-[#0E3F2E] hover:text-white transition-colors"
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
          </div>
        </div>

        {/* Product Grid */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 pb-10">
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            {regionalPujas.map(puja => (
              <div key={puja.id} className="flex">
                <PujaCard puja={puja} />
              </div>
            ))}
          </div>
        </div>
      </div>
    </>
  )
}
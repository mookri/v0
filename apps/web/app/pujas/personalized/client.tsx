'use client'

import React from 'react';
import Link from "next/link"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight } from "lucide-react"

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

// Personalized puja data
const personalizedPujas: Puja[] = [
  {
    id: 1,
    name: "Custom Health Puja",
    category: "Personalized Health",
    price: "₹4,500",
    originalPrice: "₹6,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    badge: "-25%",
    slug: "custom-health-puja",
    description: "Personalized puja for health and wellness with specific mantras and offerings based on your birth chart.",
    priceRange: "₹1,000 - ₹5,000",
    panditType: "Health Specialists",
    ritualType: "Vedic",
    availability: "Within 3 Days"
  },
  {
    id: 2,
    name: "Family Prosperity Ritual",
    category: "Business & Prosperity",
    price: "₹7,500",
    originalPrice: "₹10,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    badge: "-25%",
    slug: "family-prosperity-ritual",
    description: "Custom ritual designed to bring prosperity and success to your family and business ventures.",
    priceRange: "₹5,000 - ₹10,000",
    panditType: "Prosperity Specialists",
    ritualType: "Tantric",
    availability: "Within Week"
  },
  {
    id: 3,
    name: "Ancestral Peace Ceremony",
    category: "Ancestral Rituals",
    price: "₹6,000",
    originalPrice: "₹8,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg",
    badge: "-25%",
    slug: "ancestral-peace-ceremony",
    description: "Special ceremony to honor ancestors and seek their blessings for family harmony and peace.",
    priceRange: "₹5,000 - ₹10,000",
    panditType: "Ancestral Specialists",
    ritualType: "Purānic",
    availability: "Within 3 Days"
  },
  {
    id: 4,
    name: "Student Success Puja",
    category: "Education & Learning",
    price: "₹3,500",
    originalPrice: "₹5,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_db05ba7c437e48abe6440798d2a33b09dr_bokdku.jpg",
    badge: "-30%",
    slug: "student-success-puja",
    description: "Personalized puja to enhance focus, memory, and academic performance for students of all ages.",
    priceRange: "₹1,000 - ₹5,000",
    panditType: "Education Specialists",
    ritualType: "Vedic",
    availability: "Immediate"
  },
  {
    id: 5,
    name: "Relationship Harmony Ritual",
    category: "Personal Relationships",
    price: "₹5,000",
    originalPrice: "₹7,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg",
    badge: "-28%",
    slug: "relationship-harmony-ritual",
    description: "Custom ritual to strengthen bonds, resolve conflicts, and bring harmony to personal relationships.",
    priceRange: "₹1,000 - ₹5,000",
    panditType: "Relationship Specialists",
    ritualType: "Tantric",
    availability: "Within 3 Days"
  },
  {
    id: 6,
    name: "Career Guidance Puja",
    category: "Professional Growth",
    price: "₹4,000",
    originalPrice: "₹6,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    badge: "-33%",
    slug: "career-guidance-puja",
    description: "Specialized puja for career advancement, job change, or business expansion with personalized mantras.",
    priceRange: "₹1,000 - ₹5,000",
    panditType: "Career Specialists",
    ritualType: "Vedic",
    availability: "Within Week"
  }
];

const filters = {
  category: ["Personalized Health", "Business & Prosperity", "Ancestral Rituals", "Education & Learning", "Personal Relationships", "Professional Growth"],
  priceRange: ["Under ₹1,000", "₹1,000 - ₹5,000", "₹5,000 - ₹10,000", "Above ₹10,000"],
  pandit: ["Health Specialists", "Prosperity Specialists", "Ancestral Specialists", "Education Specialists", "Relationship Specialists", "Career Specialists"],
  ritualType: ["Vedic", "Tantric", "Purānic", "Custom"],
  availability: ["Immediate", "Within 3 Days", "Within Week"]
};

export default function PersonalizedRitualsClient() {
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
            <Link href={`/pujas/${puja.slug}`}>{puja.name}</Link>
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
    <div className="min-h-screen bg-background">
      {/* Breadcrumb */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
        <nav className="text-xs text-muted-foreground">
          <Link href="/" className="hover:text-foreground">Home</Link>
          <span className="mx-1.5">/</span>
          <Link href="/pujas" className="hover:text-foreground">Puja Services</Link>
          <span className="mx-1.5">/</span>
          <span className="text-foreground">Personalized Rituals</span>
        </nav>
      </div>

      {/* Hero Banner */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
        <div 
          className="relative h-48 rounded-lg overflow-hidden bg-cover bg-center flex items-end"
          style={{
            backgroundImage: "url('https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg')"
          }}
        >
          <div className="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
          <div className="relative z-10 text-white p-4 max-w-2xl">
            <h1 className="text-2xl font-serif font-semibold">Personalized Rituals</h1>
            <p className="text-sm mt-1">
              Tailored rituals for individual or family needs. Custom puja services designed specifically for your unique requirements and spiritual goals.
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
              <span className="px-2 py-1 border border-border rounded-full bg-background">All Categories</span>
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
          {personalizedPujas.map(puja => (
            <div key={puja.id} className="flex">
              <PujaCard puja={puja} />
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}

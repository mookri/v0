'use client'

import React, { useState, useEffect } from 'react';
import Link from "next/link"
import { ChevronDown, Heart, Eye, BarChart2, ShoppingCart, Filter, X, ArrowRight } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Separator } from "@/components/ui/separator"

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

// Extended puja data with additional filter properties
const allPujas: Puja[] = [
  {
    id: 1,
    name: "Griha Pravesh Puja",
    category: "Home & Family",
    price: "₹2,500",
    originalPrice: "₹5,500",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    badge: "-11%",
    slug: "griha-pravesh",
    description: "An auspicious ceremony performed before entering a new home to invoke blessings, prosperity, and harmony.",
    priceRange: "₹1,000 - ₹5,000",
    panditType: "Certified Pandits",
    ritualType: "Vedic",
    availability: "Immediate"
  },
  {
    id: 2,
    name: "Satyanarayan Katha",
    category: "Home & Family",
    price: "₹2,000",
    originalPrice: "₹4,500",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    badge: "-34%",
    slug: "satyanarayan-katha",
    description: "A devotional ritual dedicated to Lord Vishnu for prosperity, peace, and fulfillment of wishes.",
    priceRange: "₹1,000 - ₹5,000",
    panditType: "Experienced Pandits",
    ritualType: "Purānic",
    availability: "Within 3 Days"
  },
  {
    id: 3,
    name: "Lakshmi Ganesh Puja",
    category: "Home & Family",
    price: "₹1,800",
    originalPrice: "₹3,800",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg",
    badge: "-24%",
    slug: "lakshmi-ganesh-puja",
    description: "Performed to seek blessings from Lord Ganesh and Goddess Lakshmi for removal of obstacles and prosperity.",
    priceRange: "₹1,000 - ₹5,000",
    panditType: "Certified Pandits",
    ritualType: "Vedic",
    availability: "Within Week"
  },
  {
    id: 4,
    name: "Hindu Wedding Ceremony",
    category: "Life Events",
    price: "₹24,999",
    originalPrice: "₹75,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    badge: "Hot",
    slug: "hindu-wedding",
    description: "Complete traditional wedding rituals with Vedic mantras, mandap, and proper vidhi performed by experienced Pandits.",
    priceRange: "Above ₹10,000",
    panditType: "Senior Pandits",
    ritualType: "Vedic",
    availability: "Within 3 Days"
  },
  {
    id: 5,
    name: "Baby Naming (Namkaran)",
    category: "Life Events",
    price: "₹2,500",
    originalPrice: "₹4,500",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    badge: "-18%",
    slug: "baby-naming",
    description: "A sacred ceremony to give your child a divine name and seek blessings for their future.",
    priceRange: "₹1,000 - ₹5,000",
    panditType: "Experienced Pandits",
    ritualType: "Custom",
    availability: "Immediate"
  },
  {
    id: 6,
    name: "Annaprashan Ceremony",
    category: "Life Events",
    price: "₹2,000",
    originalPrice: "₹4,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg",
    slug: "annaprashan",
    description: "The first feeding of solid food to your child, marking an important milestone in their growth.",
    priceRange: "₹1,000 - ₹5,000",
    panditType: "Certified Pandits",
    ritualType: "Purānic",
    availability: "Within Week"
  },
  {
    id: 7,
    name: "Office/Shop Opening Puja",
    category: "Business & Prosperity",
    price: "₹3,500",
    originalPrice: "₹7,500",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    badge: "-15%",
    slug: "office-opening",
    description: "Bless your new business space with divine energy for success, prosperity, and protection.",
    priceRange: "₹1,000 - ₹5,000",
    panditType: "Senior Pandits",
    ritualType: "Tantric",
    availability: "Immediate"
  },
  {
    id: 8,
    name: "Vastu Shanti Puja",
    category: "Business & Prosperity",
    price: "₹3,000",
    originalPrice: "₹6,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    badge: "-22%",
    slug: "vastu-shanti",
    description: "Harmonize your living or working space with Vastu principles for positive energy flow.",
    priceRange: "₹1,000 - ₹5,000",
    panditType: "Experienced Pandits",
    ritualType: "Vedic",
    availability: "Within 3 Days"
  },
  {
    id: 9,
    name: "Ganesh Chaturthi",
    category: "Festivals",
    price: "₹5,000",
    originalPrice: "₹7,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_db05ba7c437e48abe6440798d2a33b09dr_bokdku.jpg",
    badge: "-15%",
    slug: "ganesh-chaturthi",
    description: "Festival celebration with traditional Ganesh installation and daily prayers.",
    priceRange: "₹1,000 - ₹5,000",
    panditType: "Experienced Pandits",
    ritualType: "Purānic",
    availability: "Within 3 Days"
  },
  {
    id: 10,
    name: "Navagraha Puja",
    category: "Special Rituals",
    price: "₹4,500",
    originalPrice: "₹6,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg",
    badge: "-25%",
    slug: "navagraha-puja",
    description: "Nine planetary worship for removing obstacles and bringing positive energy.",
    priceRange: "₹1,000 - ₹5,000",
    panditType: "Certified Pandits",
    ritualType: "Vedic",
    availability: "Within Week"
  }
];

const filters = {
  category: ["Home & Family", "Life Events", "Business & Prosperity", "Festivals", "Special Rituals", "Online Services"],
  priceRange: ["Under ₹1,000", "₹1,000 - ₹5,000", "₹5,000 - ₹10,000", "Above ₹10,000"],
  pandit: ["Certified Pandits", "Experienced Pandits", "Senior Pandits"],
  ritualType: ["Vedic", "Tantric", "Purānic", "Custom"],
  availability: ["Immediate", "Within 3 Days", "Within Week"]
};

export default function PujaCatalogClient() {
  const [showOnSale, setShowOnSale] = useState(false);
  const [sortBy, setSortBy] = useState("price-desc");
  const [showFilters, setShowFilters] = useState(false);
  const [selectedFilters, setSelectedFilters] = useState({
    category: [] as string[],
    priceRange: [] as string[],
    pandit: [] as string[],
    ritualType: [] as string[],
    availability: [] as string[]
  });
  const [filteredPujas, setFilteredPujas] = useState<Puja[]>(allPujas);
  const [expandedFilter, setExpandedFilter] = useState<string | null>(null);
  
  // Apply filters and sorting whenever selected filters, showOnSale, or sortBy changes
  useEffect(() => {
    let result = [...allPujas];
    
    // Apply discount filter
    if (showOnSale) {
      result = result.filter(puja => puja.badge);
    }
    
    // Apply category filter
    if (selectedFilters.category.length > 0) {
      result = result.filter(puja => 
        selectedFilters.category.includes(puja.category)
      );
    }
    
    // Apply price range filter
    if (selectedFilters.priceRange.length > 0) {
      result = result.filter(puja => 
        selectedFilters.priceRange.some(range => puja.priceRange === range)
      );
    }
    
    // Apply pandit filter
    if (selectedFilters.pandit.length > 0) {
      result = result.filter(puja => 
        selectedFilters.pandit.includes(puja.panditType || '')
      );
    }
    
    // Apply ritual type filter
    if (selectedFilters.ritualType.length > 0) {
      result = result.filter(puja => 
        selectedFilters.ritualType.includes(puja.ritualType || '')
      );
    }
    
    // Apply availability filter
    if (selectedFilters.availability.length > 0) {
      result = result.filter(puja => 
        selectedFilters.availability.includes(puja.availability || '')
      );
    }
    
    // Apply sorting
    switch (sortBy) {
      case "price-desc":
        result.sort((a, b) => {
          const priceA = parseInt(a.price.replace(/[₹,]/g, ""));
          const priceB = parseInt(b.price.replace(/[₹,]/g, ""));
          return priceB - priceA;
        });
        break;
      case "price":
        result.sort((a, b) => {
          const priceA = parseInt(a.price.replace(/[₹,]/g, ""));
          const priceB = parseInt(b.price.replace(/[₹,]/g, ""));
          return priceA - priceB;
        });
        break;
      case "popularity":
        // For demo purposes, we'll sort by ID as a proxy for popularity
        result.sort((a, b) => b.id - a.id);
        break;
      case "rating":
        // For demo purposes, we'll sort by ID as a proxy for rating
        result.sort((a, b) => b.id - a.id);
        break;
      case "date":
        // For demo purposes, we'll sort by ID as a proxy for date (newer first)
        result.sort((a, b) => b.id - a.id);
        break;
      default:
        break;
    }
    
    setFilteredPujas(result);
  }, [selectedFilters, showOnSale, sortBy]);
  
  const toggleFilter = (filterType: string, value: string) => {
    setSelectedFilters(prev => {
      const currentFilters = prev[filterType as keyof typeof prev] || [];
      const updatedFilters = currentFilters.includes(value)
        ? currentFilters.filter(item => item !== value)
        : [...currentFilters, value];
      
      return {
        ...prev,
        [filterType]: updatedFilters
      };
    });
  };
  
  const clearAllFilters = () => {
    setSelectedFilters({
      category: [],
      priceRange: [],
      pandit: [],
      ritualType: [],
      availability: []
    });
    setShowOnSale(false);
    setExpandedFilter(null);
    setSortBy("price-desc");
  };
  
  const hasActiveFilters = () => {
    return (
      selectedFilters.category.length > 0 || 
      selectedFilters.priceRange.length > 0 || 
      selectedFilters.pandit.length > 0 || 
      selectedFilters.ritualType.length > 0 || 
      selectedFilters.availability.length > 0 || 
      showOnSale
    );
  };
  
  const FilterDropdown = ({ title, items, filterType }: { title: string; items: string[]; filterType: string }) => {
    const isExpanded = expandedFilter === title;
    
    return (
      <div className="relative">
        <button
          onClick={(e) => {
            e.stopPropagation();
            setExpandedFilter(isExpanded ? null : title);
          }}
          className={`px-3 py-1.5 text-xs border rounded-full transition-colors flex items-center gap-1 ${
            selectedFilters[filterType as keyof typeof selectedFilters]?.length > 0
              ? 'border-[#0E3F2E] bg-[#0E3F2E] text-white'
              : 'border-border bg-background'
          }`}
        >
          {title}
          <ChevronDown className={`w-3 h-3 transition-transform ${isExpanded ? 'rotate-180' : ''}`} />
          {selectedFilters[filterType as keyof typeof selectedFilters]?.length > 0 && (
            <span className="ml-1 bg-white text-[#0E3F2E] text-[8px] rounded-full w-4 h-4 flex items-center justify-center">
              {selectedFilters[filterType as keyof typeof selectedFilters]?.length}
            </span>
          )}
        </button>
        
        {isExpanded && (
          <div 
            className="absolute top-full mt-1 bg-background border border-border rounded-md shadow-lg z-10 w-48 max-h-[300px] overflow-y-auto"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="p-2 space-y-1">
              {items.map((item) => (
                <label key={item} className="flex items-center p-2 hover:bg-muted rounded cursor-pointer">
                  <input
                    type="checkbox"
                    checked={selectedFilters[filterType as keyof typeof selectedFilters]?.includes(item) || false}
                    onChange={() => toggleFilter(filterType, item)}
                    className="w-4 h-4 rounded border-border text-[#0E3F2E] focus:ring-[#0E3F2E]"
                  />
                  <span className="ml-2 text-xs">{item}</span>
                </label>
              ))}
            </div>
          </div>
        )}
      </div>
    );
  };
  
  const FilterPopup = () => (
    <div 
      className="fixed inset-0 z-50 bg-background"
      onClick={() => setExpandedFilter(null)}
    >
      <div className="flex flex-col h-full">
        {/* Header */}

        <div className="flex items-center justify-between p-4 border-b border-border">
          <h2 className="text-lg font-semibold">Filters</h2>
          <button 
            onClick={() => {
              setShowFilters(false);
              setExpandedFilter(null);
            }}
            className="p-2 rounded-full hover:bg-muted"
          >
            <X className="w-5 h-5" />
          </button>
        </div>
        
        {/* Filters Content - 2 Column Layout */}
        <div className="flex-1 overflow-hidden">
          <div className="flex h-full">
            {/* Filter Categories - Narrow Column */}
            <div className="w-1/3 border-r border-border overflow-y-auto bg-muted/30">
              <div className="p-4 space-y-1">
                <h3 className="font-medium text-sm py-2 px-3 rounded-md bg-background border border-border">Categories</h3>
                <h3 className="font-medium text-sm py-2 px-3 rounded-md hover:bg-muted cursor-pointer">Price Range</h3>
                <h3 className="font-medium text-sm py-2 px-3 rounded-md hover:bg-muted cursor-pointer">Pandit Type</h3>
                <h3 className="font-medium text-sm py-2 px-3 rounded-md hover:bg-muted cursor-pointer">Ritual Type</h3>
                <h3 className="font-medium text-sm py-2 px-3 rounded-md hover:bg-muted cursor-pointer">Availability</h3>
              </div>
            </div>
            
            {/* Filter Options - Wide Column */}
            <div className="w-2/3 overflow-y-auto">
              <div className="p-4">
                <div className="space-y-6">
                  <div className="pb-4">
                    <h3 className="font-medium mb-3 text-sm">Category</h3>
                    <div className="space-y-2">
                      {filters.category.map((value) => (
                        <label key={value} className="flex items-center">
                          <input
                            type="checkbox"
                            checked={selectedFilters.category.includes(value)}
                            onChange={() => toggleFilter('category', value)}
                            className="w-4 h-4 rounded border-border text-[#0E3F2E] focus:ring-[#0E3F2E]"
                          />
                          <span className="ml-2 text-sm">{value}</span>
                        </label>
                      ))}
                    </div>
                  </div>
                  
                  <div className="pb-4">
                    <h3 className="font-medium mb-3 text-sm">Price Range</h3>
                    <div className="space-y-2">
                      {filters.priceRange.map((value) => (
                        <label key={value} className="flex items-center">
                          <input
                            type="checkbox"
                            checked={selectedFilters.priceRange.includes(value)}
                            onChange={() => toggleFilter('priceRange', value)}
                            className="w-4 h-4 rounded border-border text-[#0E3F2E] focus:ring-[#0E3F2E]"
                          />
                          <span className="ml-2 text-sm">{value}</span>
                        </label>
                      ))}
                    </div>
                  </div>
                  
                  <div className="pb-4">
                    <h3 className="font-medium mb-3 text-sm">Pandit Type</h3>
                    <div className="space-y-2">
                      {filters.pandit.map((value) => (
                        <label key={value} className="flex items-center">
                          <input
                            type="checkbox"
                            checked={selectedFilters.pandit.includes(value)}
                            onChange={() => toggleFilter('pandit', value)}
                            className="w-4 h-4 rounded border-border text-[#0E3F2E] focus:ring-[#0E3F2E]"
                          />
                          <span className="ml-2 text-sm">{value}</span>
                        </label>
                      ))}
                    </div>
                  </div>
                  
                  <div className="pb-4">
                    <h3 className="font-medium mb-3 text-sm">Ritual Type</h3>
                    <div className="space-y-2">
                      {filters.ritualType.map((value) => (
                        <label key={value} className="flex items-center">
                          <input
                            type="checkbox"
                            checked={selectedFilters.ritualType.includes(value)}
                            onChange={() => toggleFilter('ritualType', value)}
                            className="w-4 h-4 rounded border-border text-[#0E3F2E] focus:ring-[#0E3F2E]"
                          />
                          <span className="ml-2 text-sm">{value}</span>
                        </label>
                      ))}
                    </div>
                  </div>
                  
                  <div className="border-t border-border pt-4">
                    <h3 className="font-medium mb-3 text-sm">Availability</h3>
                    <div className="space-y-2">
                      {filters.availability.map((value) => (
                        <label key={value} className="flex items-center">
                          <input
                            type="checkbox"
                            checked={selectedFilters.availability.includes(value)}
                            onChange={() => toggleFilter('availability', value)}
                            className="w-4 h-4 rounded border-border text-[#0E3F2E] focus:ring-[#0E3F2E]"
                          />
                          <span className="ml-2 text-sm">{value}</span>
                        </label>
                      ))}
                    </div>
                  </div>
                  
                  <div className="border-t border-border pt-4">
                    <h3 className="font-medium mb-3 text-sm">Special Offers</h3>
                    <label className="flex items-center">
                      <input
                        type="checkbox"
                        checked={showOnSale}
                        onChange={(e) => setShowOnSale(e.target.checked)}
                        className="w-4 h-4 rounded border-border text-[#0E3F2E] focus:ring-[#0E3F2E]"
                      />
                      <span className="ml-2 text-sm">Show only discounted pujas</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        {/* Apply Button */}
        <div className="p-4 border-t border-border flex gap-3">
          <button 
            onClick={clearAllFilters}
            className="flex-1 border border-border rounded-md py-3 font-medium text-foreground hover:bg-muted transition-colors"
          >
            Clear All
          </button>
          <button 
            onClick={() => {
              setShowFilters(false);
              setExpandedFilter(null);
            }}
            className="flex-1 bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white font-medium py-3 rounded-md transition-all duration-300 shadow-sm hover:shadow-md"
          >
            Apply Filters
          </button>
        </div>
      </div>
    </div>
  );

  const PujaCard = ({ puja }: { puja: Puja }) => {
    const [isHovered, setIsHovered] = useState(false);
    
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
    <div 
      className="min-h-screen bg-background"
      onClick={() => setExpandedFilter(null)}
    >
      {/* Breadcrumb */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
        <nav className="text-xs text-muted-foreground">
          <Link href="/" className="hover:text-foreground">Home</Link>
          <span className="mx-1.5">/</span>
          <Link href="/pujas" className="hover:text-foreground">Services</Link>
          <span className="mx-1.5">/</span>
          <span className="text-foreground">Puja Services</span>
        </nav>
      </div>

      {/* Hero Banner */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
        <div 
          className="relative h-48 rounded-lg overflow-hidden bg-cover bg-center flex items-end"
          style={{
            backgroundImage: "url('https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg')"
          }}
        >
          <div className="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
          <div className="relative z-10 text-white p-4 max-w-2xl">
            <h1 className="text-2xl font-serif font-semibold">Puja Services</h1>
            <p className="text-sm mt-1">
              Authentic spiritual rituals for every occasion, performed by certified Vedic pandits in Delhi NCR.
            </p>
          </div>
        </div>
      </div>

      {/* Mobile Filter Button */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-4 md:hidden">
        <button
          onClick={(e) => {
            e.stopPropagation();
            setShowFilters(true);
          }}
          className={`w-full flex items-center justify-center gap-2 py-2.5 border rounded-md transition-colors ${
            hasActiveFilters() 
              ? 'border-[#0E3F2E] bg-[#0E3F2E] text-white' 
              : 'border-border bg-background hover:bg-muted'
          }`}
        >
          <Filter className="w-4 h-4" />
          <span>Filters</span>
          {hasActiveFilters() && (
            <span className="ml-1 bg-white text-[#0E3F2E] text-[10px] rounded-full w-5 h-5 flex items-center justify-center">
              {selectedFilters.category.length + 
               selectedFilters.priceRange.length + 
               selectedFilters.pandit.length + 
               selectedFilters.ritualType.length + 
               selectedFilters.availability.length + 
               (showOnSale ? 1 : 0)}
            </span>
          )}
        </button>
      </div>

      {/* Desktop Filters - Hidden on mobile */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 pb-6 hidden md:block">
        <div className="border-b border-border pb-4 mb-6">
          <div className="flex flex-wrap items-center gap-2">
            <span className="font-medium text-xs flex items-center gap-1 text-muted-foreground">
              <Filter className="w-3 h-3" />
              Filters:
            </span>
            <div className="flex flex-wrap items-center gap-2">
              <FilterDropdown title="Category" items={filters.category} filterType="category" />
              <FilterDropdown title="Price" items={filters.priceRange} filterType="priceRange" />
              <FilterDropdown title="Pandit" items={filters.pandit} filterType="pandit" />
              <FilterDropdown title="Ritual" items={filters.ritualType} filterType="ritualType" />
              <FilterDropdown title="Availability" items={filters.availability} filterType="availability" />
            </div>
            
            <div className="flex items-center gap-3 ml-auto">
              <label className="flex items-center gap-1 cursor-pointer text-xs">
                <input
                  type="checkbox"
                  checked={showOnSale}
                  onChange={(e) => setShowOnSale(e.target.checked)}
                  className="w-3 h-3"
                />
                Discounted only
              </label>
              
              <div className="flex items-center gap-1">
                <span className="text-xs text-muted-foreground">Sort:</span>
                <select
                  value={sortBy}
                  onChange={(e) => setSortBy(e.target.value)}
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
          {filteredPujas.map(puja => (
            <div key={puja.id} className="flex">
              <PujaCard puja={puja} />
            </div>
          ))}
        </div>
      </div>
      
      {/* Filter Popup for Mobile */}
      {showFilters && <FilterPopup />}
    </div>
  )
}
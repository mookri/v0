'use client'
import React, { useState, useEffect, useMemo, useCallback } from 'react';
import Link from "next/link"
import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video, Search, SlidersHorizontal } from "lucide-react"

// Schema markup for the main pandits page
const PANDITS_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Pandits & Experts - Navdhya",
  description: "Connect with verified and experienced Vedic pandits in Delhi NCR. Book authentic puja services, astrology consultations, and spiritual guidance from certified experts.",
  path: "/pandits",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Pandits & Experts", url: "https://www.navdhya.com/pandits" }
  ]
})

interface Pandit {
  id: number;
  name: string;
  specialization: string;
  location: string;
  experience: string;
  rating: number;
  image: string;
  skills: string[];
  price: string;
  languages: string[];
}

interface FilterOptions {
  location: string[];
  specialization: string[];
  rating: number | null;
  experience: string[];
  searchQuery: string;
}

// Mock data for pandits
const mockPandits: Pandit[] = [
  {
    id: 1,
    name: "Pandit Rajesh Sharma",
    specialization: "Vedic Rituals & Astrology Specialist",
    location: "Delhi",
    experience: "15+ years",
    rating: 4.9,
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    skills: ["Griha Pravesh", "Kundli Analysis", "Satyanarayan Katha"],
    price: "₹1,500/session",
    languages: ["Hindi", "English"]
  },
  {
    id: 2,
    name: "Pandit Anil Verma",
    specialization: "Business & Prosperity Rituals",
    location: "Gurgaon",
    experience: "18+ years",
    rating: 4.9,
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg",
    skills: ["Lakshmi Puja", "Vastu Shanti", "Navagraha Homa"],
    price: "₹2,000/session",
    languages: ["Hindi", "English", "Punjabi"]
  },
  {
    id: 3,
    name: "Pandit Suresh Iyer",
    specialization: "South Indian Rituals Specialist",
    location: "Noida",
    experience: "12+ years",
    rating: 4.8,
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    skills: ["Pongal", "Ganesh Puja", "Annaprashan"],
    price: "₹1,800/session",
    languages: ["Tamil", "Telugu", "English"]
  },
  {
    id: 4,
    name: "Pandit Ramesh Patel",
    specialization: "Wedding & Life Event Ceremonies",
    location: "Delhi",
    experience: "20+ years",
    rating: 4.7,
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    skills: ["Wedding Rituals", "Upanayana", "Naming Ceremony"],
    price: "₹2,500/session",
    languages: ["Hindi", "Gujarati", "English"]
  },
  {
    id: 5,
    name: "Pandit Kavita Menon",
    specialization: "Women's Wellness & Fertility Rituals",
    location: "Gurgaon",
    experience: "10+ years",
    rating: 4.9,
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    skills: ["Ganesh Homa", "Lakshmi Puja", "Navratri Special"],
    price: "₹1,700/session",
    languages: ["Malayalam", "Tamil", "English"]
  },
  {
    id: 6,
    name: "Pandit Vikram Singh",
    specialization: "Health & Healing Ceremonies",
    location: "Noida",
    experience: "14+ years",
    rating: 4.6,
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg",
    skills: ["Rudra Abhishek", "Maha Mrityunjaya Jaap", "Ayurvedic Rituals"],
    price: "₹2,200/session",
    languages: ["Hindi", "English"]
  }
];

// Unique filter options
const locations = ["Delhi", "Gurgaon", "Noida"];
const specializations = [
  "Vedic Rituals & Astrology Specialist",
  "Business & Prosperity Rituals", 
  "South Indian Rituals Specialist",
  "Wedding & Life Event Ceremonies",
  "Women's Wellness & Fertility Rituals",
  "Health & Healing Ceremonies"
];
const experienceOptions = ["5+ years", "10+ years", "15+ years", "20+ years"];
const skills = [
  "Griha Pravesh", "Kundli Analysis", "Satyanarayan Katha",
  "Lakshmi Puja", "Vastu Shanti", "Navagraha Homa",
  "Pongal", "Ganesh Puja", "Annaprashan",
  "Wedding Rituals", "Upanayana", "Naming Ceremony",
  "Ganesh Homa", "Lakshmi Puja", "Navratri Special",
  "Rudra Abhishek", "Maha Mrityunjaya Jaap", "Ayurvedic Rituals"
];

export default function PanditsPage() {
  const panditsSchema = JSON.stringify(PANDITS_SCHEMA);
  
  // State for filters
  const [filters, setFilters] = useState<FilterOptions>({
    location: [],
    specialization: [],
    rating: null,
    experience: [],
    searchQuery: ""
  });
  
  // State for UI
  const [showFilters, setShowFilters] = useState(false);
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 6;
  
  // Debounced search
  const [debouncedSearchQuery, setDebouncedSearchQuery] = useState("");
  
  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedSearchQuery(filters.searchQuery);
    }, 300);
    
    return () => clearTimeout(timer);
  }, [filters.searchQuery]);
  
  // Apply filters and search
  const filteredPandits = useMemo(() => {
    return mockPandits.filter(pandit => {
      // Search query filter
      if (debouncedSearchQuery && 
          !pandit.name.toLowerCase().includes(debouncedSearchQuery.toLowerCase()) &&
          !pandit.specialization.toLowerCase().includes(debouncedSearchQuery.toLowerCase()) &&
          !pandit.skills.some(skill => skill.toLowerCase().includes(debouncedSearchQuery.toLowerCase()))) {
        return false;
      }
      
      // Location filter
      if (filters.location.length > 0 && !filters.location.includes(pandit.location)) {
        return false;
      }
      
      // Specialization filter
      if (filters.specialization.length > 0 && !filters.specialization.includes(pandit.specialization)) {
        return false;
      }
      
      // Rating filter
      if (filters.rating !== null && pandit.rating < filters.rating) {
        return false;
      }
      
      // Experience filter
      if (filters.experience.length > 0) {
        const panditExp = parseInt(pandit.experience);
        const filterExp = filters.experience.map(exp => parseInt(exp));
        if (!filterExp.some(exp => panditExp >= exp)) {
          return false;
        }
      }
      
      return true;
    });
  }, [filters, debouncedSearchQuery]);
  
  // Pagination
  const totalPages = Math.ceil(filteredPandits.length / itemsPerPage);
  const paginatedPandits = useMemo(() => {
    const startIndex = (currentPage - 1) * itemsPerPage;
    return filteredPandits.slice(startIndex, startIndex + itemsPerPage);
  }, [filteredPandits, currentPage]);
  
  // Reset pagination when filters change
  useEffect(() => {
    setCurrentPage(1);
  }, [filters, debouncedSearchQuery]);
  
  // Handle filter changes
  const handleLocationChange = useCallback((location: string) => {
    setFilters(prev => {
      const newLocations = prev.location.includes(location)
        ? prev.location.filter(l => l !== location)
        : [...prev.location, location];
      return { ...prev, location: newLocations };
    });
  }, []);
  
  const handleSpecializationChange = useCallback((specialization: string) => {
    setFilters(prev => {
      const newSpecializations = prev.specialization.includes(specialization)
        ? prev.specialization.filter(s => s !== specialization)
        : [...prev.specialization, specialization];
      return { ...prev, specialization: newSpecializations };
    });
  }, []);
  
  const handleExperienceChange = useCallback((experience: string) => {
    setFilters(prev => {
      const newExperiences = prev.experience.includes(experience)
        ? prev.experience.filter(e => e !== experience)
        : [...prev.experience, experience];
      return { ...prev, experience: newExperiences };
    });
  }, []);
  
  const handleRatingChange = useCallback((rating: number) => {
    setFilters(prev => ({
      ...prev,
      rating: prev.rating === rating ? null : rating
    }));
  }, []);
  
  const handleSearchChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
    setFilters(prev => ({ ...prev, searchQuery: e.target.value }));
  }, []);
  
  const clearFilters = useCallback(() => {
    setFilters({
      location: [],
      specialization: [],
      rating: null,
      experience: [],
      searchQuery: ""
    });
  }, []);
  
  // Close filter popup when clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      const filterPopup = document.getElementById('filter-popup');
      const filterButton = document.getElementById('filter-button');
      if (showFilters && filterPopup && !filterPopup.contains(event.target as Node) && 
          filterButton && !filterButton.contains(event.target as Node)) {
        setShowFilters(false);
      }
    };
    
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [showFilters]);
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: panditsSchema }}
      />
      <div className="min-h-screen bg-background">
        {/* Pandit Browser Section */}
        <div id="browse-pandits" className="max-w-7xl mx-auto px-4 sm:px-6 mb-16 pt-8">
          <div className="text-center mb-10">
            <h1 className="text-2xl md:text-3xl font-serif font-bold mb-4 text-foreground">Verified Pandits & Experts</h1>
            <p className="text-muted-foreground max-w-2xl mx-auto text-base md:text-lg">
              Find the perfect pandit for your spiritual needs with our advanced filtering options.
            </p>
          </div>
          
          {/* Search and Filter Bar */}
          <div className="bg-card border border-border rounded-xl p-5 mb-8 shadow-sm">
            <div className="flex flex-col md:flex-row gap-4">
              {/* Search Input */}
              <div className="relative flex-grow">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground w-5 h-5" />
                <input
                  type="text"
                  placeholder="Search by name, specialization, or skills..."
                  className="w-full pl-10 pr-4 py-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-[#0E3F2E] focus:border-transparent"
                  value={filters.searchQuery}
                  onChange={handleSearchChange}
                />
              </div>
              
              {/* Filter Button */}
              <button
                id="filter-button"
                onClick={() => setShowFilters(!showFilters)}
                className="flex items-center justify-center gap-2 px-4 py-3 bg-[#0E3F2E] text-white rounded-lg hover:bg-[#0E3F2E]/90 transition-colors duration-300"
              >
                <SlidersHorizontal className="w-5 h-5" />
                <span>Filters</span>
              </button>
            </div>
            
            {/* Active Filters */}
            {(filters.location.length > 0 || 
              filters.specialization.length > 0 || 
              filters.rating !== null || 
              filters.experience.length > 0) && (
              <div className="mt-4 flex flex-wrap gap-2">
                {filters.location.map(location => (
                  <span key={location} className="inline-flex items-center gap-1 px-3 py-1 bg-[#0E3F2E]/10 text-[#0E3F2E] rounded-full text-sm">
                    {location}
                    <X 
                      className="w-4 h-4 cursor-pointer" 
                      onClick={() => handleLocationChange(location)}
                    />
                  </span>
                ))}
                {filters.specialization.map(spec => (
                  <span key={spec} className="inline-flex items-center gap-1 px-3 py-1 bg-[#0E3F2E]/10 text-[#0E3F2E] rounded-full text-sm">
                    {spec}
                    <X 
                      className="w-4 h-4 cursor-pointer" 
                      onClick={() => handleSpecializationChange(spec)}
                    />
                  </span>
                ))}
                {filters.experience.map(exp => (
                  <span key={exp} className="inline-flex items-center gap-1 px-3 py-1 bg-[#0E3F2E]/10 text-[#0E3F2E] rounded-full text-sm">
                    {exp}
                    <X 
                      className="w-4 h-4 cursor-pointer" 
                      onClick={() => handleExperienceChange(exp)}
                    />
                  </span>
                ))}
                {filters.rating !== null && (
                  <span className="inline-flex items-center gap-1 px-3 py-1 bg-[#0E3F2E]/10 text-[#0E3F2E] rounded-full text-sm">
                    {filters.rating}+ Rating
                    <X 
                      className="w-4 h-4 cursor-pointer" 
                      onClick={() => handleRatingChange(filters.rating!)}
                    />
                  </span>
                )}
                <button 
                  onClick={clearFilters}
                  className="text-sm text-muted-foreground hover:text-foreground underline"
                >
                  Clear all
                </button>
              </div>
            )}
          </div>
          
          {/* Filter Popup/Modal */}
          {showFilters && (
            <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
              <div 
                id="filter-popup"
                className="bg-card border border-border rounded-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto shadow-xl transform transition-all duration-300 ease-out"
              >
                <div className="p-6">
                  <div className="flex justify-between items-center mb-6">
                    <h2 className="text-xl font-bold text-foreground">Filter Pandits</h2>
                    <button 
                      onClick={() => setShowFilters(false)}
                      className="p-2 rounded-full hover:bg-muted transition-colors"
                    >
                      <X className="w-5 h-5" />
                    </button>
                  </div>
                  
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                    {/* Location Filter */}
                    <div>
                      <h3 className="font-semibold mb-4 text-foreground border-b border-border pb-2">Location</h3>
                      <div className="space-y-3">
                        {locations.map(location => (
                          <label key={location} className="flex items-center">
                            <input
                              type="checkbox"
                              checked={filters.location.includes(location)}
                              onChange={() => handleLocationChange(location)}
                              className="rounded text-[#0E3F2E] focus:ring-[#0E3F2E] w-5 h-5"
                            />
                            <span className="ml-3 text-muted-foreground">{location}</span>
                          </label>
                        ))}
                      </div>
                    </div>
                    
                    {/* Specialization Filter */}
                    <div>
                      <h3 className="font-semibold mb-4 text-foreground border-b border-border pb-2">Specialization</h3>
                      <div className="space-y-3 max-h-60 overflow-y-auto pr-2">
                        {specializations.map(spec => (
                          <label key={spec} className="flex items-start">
                            <input
                              type="checkbox"
                              checked={filters.specialization.includes(spec)}
                              onChange={() => handleSpecializationChange(spec)}
                              className="rounded text-[#0E3F2E] focus:ring-[#0E3F2E] w-5 h-5 mt-1"
                            />
                            <span className="ml-3 text-muted-foreground text-sm">{spec}</span>
                          </label>
                        ))}
                      </div>
                    </div>
                    
                    {/* Experience Filter */}
                    <div>
                      <h3 className="font-semibold mb-4 text-foreground border-b border-border pb-2">Experience</h3>
                      <div className="space-y-3">
                        {experienceOptions.map(exp => (
                          <label key={exp} className="flex items-center">
                            <input
                              type="checkbox"
                              checked={filters.experience.includes(exp)}
                              onChange={() => handleExperienceChange(exp)}
                              className="rounded text-[#0E3F2E] focus:ring-[#0E3F2E] w-5 h-5"
                            />
                            <span className="ml-3 text-muted-foreground">{exp}</span>
                          </label>
                        ))}
                      </div>
                    </div>
                    
                    {/* Rating Filter */}
                    <div>
                      <h3 className="font-semibold mb-4 text-foreground border-b border-border pb-2">Minimum Rating</h3>
                      <div className="space-y-3">
                        {[4.5, 4.0, 3.5].map(rating => (
                          <label key={rating} className="flex items-center">
                            <input
                              type="radio"
                              name="rating"
                              checked={filters.rating === rating}
                              onChange={() => handleRatingChange(rating)}
                              className="text-[#0E3F2E] focus:ring-[#0E3F2E] w-5 h-5"
                            />
                            <span className="ml-3 text-muted-foreground flex items-center">
                              {rating}+ 
                              <span className="ml-2 flex">
                                {[...Array(5)].map((_, i) => (
                                  <Star 
                                    key={i} 
                                    className={`w-4 h-4 ${i < Math.floor(rating) ? 'text-yellow-500 fill-current' : 'text-gray-300'}`} 
                                  />
                                ))}
                              </span>
                            </span>
                          </label>
                        ))}
                      </div>
                    </div>
                  </div>
                  
                  <div className="flex justify-end gap-3 mt-8 pt-6 border-t border-border">
                    <button
                      onClick={clearFilters}
                      className="px-4 py-2 border border-border rounded-lg text-foreground hover:bg-muted transition-colors"
                    >
                      Clear All
                    </button>
                    <button
                      onClick={() => setShowFilters(false)}
                      className="px-4 py-2 bg-[#0E3F2E] text-white rounded-lg hover:bg-[#0E3F2E]/90 transition-colors"
                    >
                      Apply Filters
                    </button>
                  </div>
                </div>
              </div>
            </div>
          )}
          
          {/* Results Info */}
          <div className="flex justify-between items-center mb-6">
            <p className="text-muted-foreground">
              Showing {paginatedPandits.length} of {filteredPandits.length} pandits
            </p>
          </div>
          
          {/* Pandit Cards Grid */}
          {paginatedPandits.length > 0 ? (
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
              {paginatedPandits.map(pandit => (
                <div 
                  key={pandit.id} 
                  className="border border-border rounded-lg overflow-hidden bg-card shadow-sm hover:shadow-md transition-all duration-300"
                >
                  <div className="flex">
                    {/* Image Section - Circular Profile */}
                    <div className="w-1/4 flex items-center justify-center p-4">
                      <div className="relative w-16 h-16 rounded-full overflow-hidden">
                        <img 
                          src={pandit.image} 
                          alt={pandit.name}
                          className="w-full h-full object-cover"
                        />
                        <div className="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-full"></div>
                      </div>
                    </div>
                    
                    {/* Content Section */}
                    <div className="w-3/4 p-4 flex flex-col">
                      <div className="flex-grow">
                        <h3 className="font-semibold text-base mb-0.5 text-foreground">{pandit.name}</h3>
                        <p className="text-xs text-muted-foreground mb-2 line-clamp-1">{pandit.specialization}</p>
                        
                        <div className="flex items-center gap-2 mb-2">
                          <div className="flex items-center gap-1">
                            <MapPin className="w-3 h-3 text-muted-foreground" />
                            <span className="text-xs text-muted-foreground">{pandit.location}</span>
                          </div>
                          <span className="text-xs text-muted-foreground">•</span>
                          <span className="text-xs text-muted-foreground">{pandit.experience}</span>
                        </div>
                        
                        <div className="flex flex-wrap gap-1.5 mb-3">
                          {pandit.skills.slice(0, 2).map((skill, index) => (
                            <span 
                              key={index} 
                              className="px-1.5 py-0.5 bg-muted rounded-full text-[10px] text-muted-foreground"
                            >
                              {skill}
                            </span>
                          ))}
                          {pandit.skills.length > 2 && (
                            <span className="px-1.5 py-0.5 bg-muted rounded-full text-[10px] text-muted-foreground">
                              +{pandit.skills.length - 2} more
                            </span>
                          )}
                        </div>
                      </div>
                      
                      <div className="flex justify-between items-center mt-auto pt-2 border-t border-border">
                        <span className="font-semibold text-[#0E3F2E] text-sm">{pandit.price}</span>
                        <Link 
                          href={`/pandits/${pandit.id}`}
                          className="inline-flex items-center justify-center gap-1 bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white font-medium py-1.5 px-3 rounded-md transition-all duration-300 text-xs shadow-xs hover:shadow-sm"
                        >
                          <span>View Details</span>
                          <ArrowRight className="w-2.5 h-2.5" />
                        </Link>
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="text-center py-12">
              <div className="text-muted-foreground mb-4">
                <Users className="w-12 h-12 mx-auto" />
              </div>
              <h3 className="text-xl font-semibold mb-2">No pandits found</h3>
              <p className="text-muted-foreground mb-4">
                Try adjusting your filters or search terms to find what you're looking for.
              </p>
              <button 
                onClick={clearFilters}
                className="px-4 py-2 bg-[#0E3F2E] text-white rounded-lg hover:bg-[#0E3F2E]/90 transition-colors duration-300"
              >
                Clear Filters
              </button>
            </div>
          )}
          
          {/* Pagination */}
          {totalPages > 1 && (
            <div className="flex justify-center mt-10">
              <div className="flex items-center gap-2">
                <button
                  onClick={() => setCurrentPage(prev => Math.max(prev - 1, 1))}
                  disabled={currentPage === 1}
                  className={`px-4 py-2 rounded-lg ${currentPage === 1 ? 'bg-muted text-muted-foreground cursor-not-allowed' : 'bg-card border border-border text-foreground hover:bg-muted'}`}
                >
                  Previous
                </button>
                
                {Array.from({ length: Math.min(5, totalPages) }, (_, i) => {
                  let pageNum;
                  if (totalPages <= 5) {
                    pageNum = i + 1;
                  } else if (currentPage <= 3) {
                    pageNum = i + 1;
                  } else if (currentPage >= totalPages - 2) {
                    pageNum = totalPages - 4 + i;
                  } else {
                    pageNum = currentPage - 2 + i;
                  }
                  
                  return (
                    <button
                      key={pageNum}
                      onClick={() => setCurrentPage(pageNum)}
                      className={`w-10 h-10 rounded-lg ${currentPage === pageNum ? 'bg-[#0E3F2E] text-white' : 'bg-card border border-border text-foreground hover:bg-muted'}`}
                    >
                      {pageNum}
                    </button>
                  );
                })}
                
                <button
                  onClick={() => setCurrentPage(prev => Math.min(prev + 1, totalPages))}
                  disabled={currentPage === totalPages}
                  className={`px-4 py-2 rounded-lg ${currentPage === totalPages ? 'bg-muted text-muted-foreground cursor-not-allowed' : 'bg-card border border-border text-foreground hover:bg-muted'}`}
                >
                  Next
                </button>
              </div>
            </div>
          )}
        </div>

        {/* Testimonials */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-16">
          <div className="text-center mb-12">
            <h2 className="text-2xl md:text-3xl font-serif font-bold mb-4 text-foreground">What Our Clients Say</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto text-base md:text-lg">
              Hear from families who have experienced our authentic Vedic services through our verified pandits.
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div className="border border-border rounded-2xl p-7 bg-card shadow-lg hover:shadow-xl transition-shadow duration-300">
              <div className="flex items-center gap-1 mb-5">
                {[...Array(5)].map((_, i) => (
                  <Star key={i} className="w-5 h-5 text-yellow-500 fill-current" />
                ))}
              </div>
              <p className="text-muted-foreground mb-6 leading-relaxed text-base">
                "Pandit Rajesh Sharma conducted our Griha Pravesh ceremony with such precision and devotion. The positive energy in our new home is palpable. Highly recommended!"
              </p>
              <div className="flex items-center gap-4">
                <div className="w-12 h-12 rounded-full bg-muted flex items-center justify-center">
                  <span className="text-sm font-semibold">VS</span>
                </div>
                <div>
                  <div className="font-semibold text-foreground">Vikram Singh</div>
                  <div className="text-sm text-muted-foreground">Gurgaon</div>
                </div>
              </div>
            </div>
            
            <div className="border border-border rounded-2xl p-7 bg-card shadow-lg hover:shadow-xl transition-shadow duration-300">
              <div className="flex items-center gap-1 mb-5">
                {[...Array(5)].map((_, i) => (
                  <Star key={i} className="w-5 h-5 text-yellow-500 fill-current" />
                ))}
              </div>
              <p className="text-muted-foreground mb-6 leading-relaxed text-base">
                "The baby naming ceremony performed by Pandit Suresh Iyer was beautiful and authentic. He took care to explain each step and made our family feel comfortable throughout."
              </p>
              <div className="flex items-center gap-4">
                <div className="w-12 h-12 rounded-full bg-muted flex items-center justify-center">
                  <span className="text-sm font-semibold">PG</span>
                </div>
                <div>
                  <div className="font-semibold text-foreground">Priya Gupta</div>
                  <div className="text-sm text-muted-foreground">Delhi</div>
                </div>
              </div>
            </div>
            
            <div className="border border-border rounded-2xl p-7 bg-card shadow-lg hover:shadow-xl transition-shadow duration-300">
              <div className="flex items-center gap-1 mb-5">
                {[...Array(5)].map((_, i) => (
                  <Star key={i} className="w-5 h-5 text-yellow-500 fill-current" />
                ))}
              </div>
              <p className="text-muted-foreground mb-6 leading-relaxed text-base">
                "Our office puja conducted by Pandit Anil Verma brought tremendous positive energy to our workspace. Business has improved significantly since then. Truly grateful!"
              </p>
              <div className="flex items-center gap-4">
                <div className="w-12 h-12 rounded-full bg-muted flex items-center justify-center">
                  <span className="text-sm font-semibold">RM</span>
                </div>
                <div>
                  <div className="font-semibold text-foreground">Rahul Mehta</div>
                  <div className="text-sm text-muted-foreground">Noida</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  )
}
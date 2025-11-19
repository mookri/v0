'use client'
import React, { useState, useEffect, useMemo } from 'react';
import Link from "next/link"
import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video, Search, SlidersHorizontal, Shield } from "lucide-react"

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

export default function PanditsClient() {
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
    }).sort((a, b) => b.rating - a.rating);
  }, [debouncedSearchQuery, filters]);
  
  // Pagination
  const totalPages = Math.ceil(filteredPandits.length / itemsPerPage);
  const paginatedPandits = filteredPandits.slice(
    (currentPage - 1) * itemsPerPage,
    currentPage * itemsPerPage
  );
  
  // Filter toggle functions
  const toggleLocation = (location: string) => {
    setFilters(prev => ({
      ...prev,
      location: prev.location.includes(location)
        ? prev.location.filter(l => l !== location)
        : [...prev.location, location]
    }));
  };
  
  const toggleSpecialization = (specialization: string) => {
    setFilters(prev => ({
      ...prev,
      specialization: prev.specialization.includes(specialization)
        ? prev.specialization.filter(s => s !== specialization)
        : [...prev.specialization, specialization]
    }));
  };
  
  const toggleExperience = (experience: string) => {
    setFilters(prev => ({
      ...prev,
      experience: prev.experience.includes(experience)
        ? prev.experience.filter(e => e !== experience)
        : [...prev.experience, experience]
    }));
  };
  
  const setRating = (rating: number | null) => {
    setFilters(prev => ({
      ...prev,
      rating
    }));
  };
  
  const resetFilters = () => {
    setFilters({
      location: [],
      specialization: [],
      rating: null,
      experience: [],
      searchQuery: ""
    });
    setDebouncedSearchQuery("");
  };
  
  return (
    <>
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <Link href="/" className="hover:text-foreground">Home</Link>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">Pandits & Experts</span>
          </nav>
        </div>

        {/* Hero Banner */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div 
            className="relative h-64 rounded-lg overflow-hidden bg-cover bg-center flex items-center"
            style={{
              backgroundImage: "url('https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg')"
            }}
          >
            <div className="absolute inset-0 bg-gradient-to-r from-black/80 to-black/40"></div>
            <div className="relative z-10 text-white p-8 max-w-3xl">
              <h1 className="text-3xl font-serif font-semibold mb-4">Connect with Verified Pandits</h1>
              <p className="text-lg mb-6">
                Book authentic puja services, astrology consultations, and spiritual guidance from certified experts in Delhi NCR.
              </p>
              <Link 
                href="#pandits"
                className="inline-flex items-center gap-2 bg-white text-[#0E3F2E] hover:bg-gray-100 font-medium py-2 px-6 rounded-md transition-all duration-300 shadow-sm"
              >
                <span>Find a Pandit</span>
                <ArrowRight className="w-4 h-4" />
              </Link>
            </div>
          </div>
        </div>

        {/* Stats Bar */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-10">
          <div className="bg-card border border-border rounded-lg p-6 grid grid-cols-2 md:grid-cols-4 gap-6">
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">50+</div>
              <div className="text-sm text-muted-foreground">Verified Pandits</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">15K+</div>
              <div className="text-sm text-muted-foreground">Services Booked</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">4.8/5</div>
              <div className="text-sm text-muted-foreground">Average Rating</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">12+</div>
              <div className="text-sm text-muted-foreground">Years Experience</div>
            </div>
          </div>
        </div>

        {/* Search and Filters */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div className="bg-card border border-border rounded-lg p-4">
            <div className="relative mb-4">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground" />
              <input
                type="text"
                placeholder="Search pandits by name, specialization, or skills..."
                className="w-full pl-10 pr-4 py-2 border border-border rounded-md bg-background focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]"
                value={filters.searchQuery}
                onChange={(e) => setFilters(prev => ({ ...prev, searchQuery: e.target.value }))}
              />
            </div>
            
            {/* Mobile Filter Button */}
            <div className="md:hidden">
              <button
                onClick={() => setShowFilters(!showFilters)}
                className="w-full flex items-center justify-center gap-2 py-2 border border-border rounded-md bg-background hover:bg-muted transition-colors"
              >
                <SlidersHorizontal className="w-4 h-4" />
                <span>Filters</span>
                {filters.location.length > 0 || filters.specialization.length > 0 || 
                 filters.rating !== null || filters.experience.length > 0 ? (
                  <span className="ml-2 bg-[#0E3F2E] text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                    {filters.location.length + filters.specialization.length + 
                     (filters.rating !== null ? 1 : 0) + filters.experience.length}
                  </span>
                ) : null}
              </button>
            </div>
            
            {/* Desktop Filters */}
            <div className="hidden md:flex flex-wrap gap-4 items-center">
              <div className="relative">
                <select
                  value=""
                  onChange={(e) => toggleLocation(e.target.value)}
                  className="appearance-none bg-background border border-border rounded-md py-2 pl-3 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]"
                >
                  <option value="">Location</option>
                  {locations.map(location => (
                    <option key={location} value={location}>{location}</option>
                  ))}
                </select>
                <ChevronDown className="absolute right-2 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none" />
                {filters.location.length > 0 && (
                  <div className="absolute -top-2 -right-2 bg-[#0E3F2E] text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                    {filters.location.length}
                  </div>
                )}
              </div>
              
              <div className="relative">
                <select
                  value=""
                  onChange={(e) => toggleSpecialization(e.target.value)}
                  className="appearance-none bg-background border border-border rounded-md py-2 pl-3 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]"
                >
                  <option value="">Specialization</option>
                  {specializations.map(specialization => (
                    <option key={specialization} value={specialization}>{specialization}</option>
                  ))}
                </select>
                <ChevronDown className="absolute right-2 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none" />
                {filters.specialization.length > 0 && (
                  <div className="absolute -top-2 -right-2 bg-[#0E3F2E] text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                    {filters.specialization.length}
                  </div>
                )}
              </div>
              
              <div className="flex items-center gap-1">
                <span className="text-sm text-muted-foreground">Rating:</span>
                {[4, 4.5].map(rating => (
                  <button
                    key={rating}
                    onClick={() => setRating(filters.rating === rating ? null : rating)}
                    className={`px-2 py-1 text-xs rounded-full border ${
                      filters.rating === rating
                        ? 'bg-[#0E3F2E] text-white border-[#0E3F2E]'
                        : 'border-border hover:bg-muted'
                    }`}
                  >
                    {rating}+ ★
                  </button>
                ))}
              </div>
              
              <div className="relative">
                <select
                  value=""
                  onChange={(e) => toggleExperience(e.target.value)}
                  className="appearance-none bg-background border border-border rounded-md py-2 pl-3 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]"
                >
                  <option value="">Experience</option>
                  {experienceOptions.map(exp => (
                    <option key={exp} value={exp}>{exp}</option>
                  ))}
                </select>
                <ChevronDown className="absolute right-2 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none" />
                {filters.experience.length > 0 && (
                  <div className="absolute -top-2 -right-2 bg-[#0E3F2E] text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                    {filters.experience.length}
                  </div>
                )}
              </div>
              
              {(filters.location.length > 0 || filters.specialization.length > 0 || 
                filters.rating !== null || filters.experience.length > 0) && (
                <button
                  onClick={resetFilters}
                  className="text-sm text-[#0E3F2E] hover:underline"
                >
                  Clear all
                </button>
              )}
            </div>
          </div>
        </div>

        {/* Mobile Filter Panel */}
        {showFilters && (
          <div className="md:hidden fixed inset-0 z-50 bg-background">
            <div className="flex flex-col h-full">
              <div className="flex items-center justify-between p-4 border-b border-border">
                <h2 className="text-lg font-semibold">Filters</h2>
                <button 
                  onClick={() => setShowFilters(false)}
                  className="p-2 rounded-full hover:bg-muted"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>
              
              <div className="flex-1 overflow-y-auto p-4 space-y-6">
                {/* Location Filter */}
                <div>
                  <h3 className="font-medium mb-3">Location</h3>
                  <div className="space-y-2">
                    {locations.map(location => (
                      <label key={location} className="flex items-center">
                        <input
                          type="checkbox"
                          checked={filters.location.includes(location)}
                          onChange={() => toggleLocation(location)}
                          className="w-4 h-4 rounded border-border text-[#0E3F2E] focus:ring-[#0E3F2E]"
                        />
                        <span className="ml-2 text-sm">{location}</span>
                      </label>
                    ))}
                  </div>
                </div>
                
                {/* Specialization Filter */}
                <div>
                  <h3 className="font-medium mb-3">Specialization</h3>
                  <div className="space-y-2">
                    {specializations.map(specialization => (
                      <label key={specialization} className="flex items-center">
                        <input
                          type="checkbox"
                          checked={filters.specialization.includes(specialization)}
                          onChange={() => toggleSpecialization(specialization)}
                          className="w-4 h-4 rounded border-border text-[#0E3F2E] focus:ring-[#0E3F2E]"
                        />
                        <span className="ml-2 text-sm">{specialization}</span>
                      </label>
                    ))}
                  </div>
                </div>
                
                {/* Rating Filter */}
                <div>
                  <h3 className="font-medium mb-3">Minimum Rating</h3>
                  <div className="flex gap-2">
                    {[4, 4.5].map(rating => (
                      <button
                        key={rating}
                        onClick={() => setRating(filters.rating === rating ? null : rating)}
                        className={`flex-1 py-2 text-sm rounded-md border ${
                          filters.rating === rating
                            ? 'bg-[#0E3F2E] text-white border-[#0E3F2E]'
                            : 'border-border hover:bg-muted'
                        }`}
                      >
                        {rating}+ ★
                      </button>
                    ))}
                  </div>
                </div>
                
                {/* Experience Filter */}
                <div>
                  <h3 className="font-medium mb-3">Experience</h3>
                  <div className="space-y-2">
                    {experienceOptions.map(exp => (
                      <label key={exp} className="flex items-center">
                        <input
                          type="checkbox"
                          checked={filters.experience.includes(exp)}
                          onChange={() => toggleExperience(exp)}
                          className="w-4 h-4 rounded border-border text-[#0E3F2E] focus:ring-[#0E3F2E]"
                        />
                        <span className="ml-2 text-sm">{exp}</span>
                      </label>
                    ))}
                  </div>
                </div>
              </div>
              
              <div className="p-4 border-t border-border flex gap-3">
                <button 
                  onClick={resetFilters}
                  className="flex-1 border border-border rounded-md py-2 font-medium text-foreground hover:bg-muted transition-colors"
                >
                  Clear All
                </button>
                <button 
                  onClick={() => setShowFilters(false)}
                  className="flex-1 bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white font-medium py-2 rounded-md transition-colors"
                >
                  Apply Filters
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Pandits Grid */}
        <div id="pandits" className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="flex justify-between items-center mb-6">
            <h2 className="text-2xl font-serif font-semibold">
              {filteredPandits.length} Verified Pandits
            </h2>
            <div className="text-sm text-muted-foreground">
              Sorted by Rating
            </div>
          </div>
          
          {filteredPandits.length > 0 ? (
            <>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                {paginatedPandits.map(pandit => (
                  <div key={pandit.id} className="border border-border rounded-lg overflow-hidden bg-card hover:shadow-lg transition-all duration-300">
                    <div className="p-5">
                      <div className="flex items-start gap-4 mb-4">
                        <div className="w-16 h-16 rounded-lg overflow-hidden flex-shrink-0">
                          <img 
                            src={pandit.image} 
                            alt={pandit.name}
                            className="w-full h-full object-cover"
                          />
                        </div>
                        <div>
                          <h3 className="font-medium text-lg">{pandit.name}</h3>
                          <p className="text-sm text-muted-foreground mb-1">{pandit.specialization}</p>
                          <div className="flex items-center gap-1">
                            <div className="flex">
                              {[...Array(5)].map((_, i) => (
                                <Star 
                                  key={i} 
                                  className={`w-4 h-4 ${i < Math.floor(pandit.rating) ? 'fill-yellow-400 text-yellow-400' : 'text-gray-300'}`} 
                                />
                              ))}
                            </div>
                            <span className="text-sm font-medium">{pandit.rating}</span>
                          </div>
                        </div>
                      </div>
                      
                      <div className="space-y-2 mb-4">
                        <div className="flex items-center gap-2 text-sm">
                          <MapPin className="w-4 h-4 text-muted-foreground" />
                          <span>{pandit.location}</span>
                        </div>
                        <div className="flex items-center gap-2 text-sm">
                          <Award className="w-4 h-4 text-muted-foreground" />
                          <span>{pandit.experience} experience</span>
                        </div>
                        <div className="flex items-center gap-2 text-sm">
                          <Clock className="w-4 h-4 text-muted-foreground" />
                          <span>{pandit.price}</span>
                        </div>
                      </div>
                      
                      <div className="mb-4">
                        <div className="text-xs text-muted-foreground mb-1">Skills</div>
                        <div className="flex flex-wrap gap-1">
                          {pandit.skills.slice(0, 3).map((skill, index) => (
                            <span 
                              key={index} 
                              className="text-xs px-2 py-1 bg-muted rounded-full"
                            >
                              {skill}
                            </span>
                          ))}
                          {pandit.skills.length > 3 && (
                            <span className="text-xs px-2 py-1 bg-muted rounded-full">
                              +{pandit.skills.length - 3} more
                            </span>
                          )}
                        </div>
                      </div>
                      
                      <div className="flex gap-2">
                        <Link 
                          href={`/pandits/${pandit.id}`}
                          className="flex-1 text-center py-2 px-4 bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white text-sm font-medium rounded-md transition-colors"
                        >
                          View Profile
                        </Link>
                        <button className="p-2 border border-border rounded-md hover:bg-muted transition-colors">
                          <Heart className="w-4 h-4" />
                        </button>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
              
              {/* Pagination */}
              {totalPages > 1 && (
                <div className="flex justify-center gap-2">
                  <button
                    onClick={() => setCurrentPage(prev => Math.max(prev - 1, 1))}
                    disabled={currentPage === 1}
                    className="px-3 py-1 border border-border rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-muted transition-colors"
                  >
                    Previous
                  </button>
                  
                  {[...Array(totalPages)].map((_, i) => (
                    <button
                      key={i + 1}
                      onClick={() => setCurrentPage(i + 1)}
                      className={`px-3 py-1 rounded-md ${
                        currentPage === i + 1
                          ? 'bg-[#0E3F2E] text-white'
                          : 'border border-border hover:bg-muted transition-colors'
                      }`}
                    >
                      {i + 1}
                    </button>
                  ))}
                  
                  <button
                    onClick={() => setCurrentPage(prev => Math.min(prev + 1, totalPages))}
                    disabled={currentPage === totalPages}
                    className="px-3 py-1 border border-border rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-muted transition-colors"
                  >
                    Next
                  </button>
                </div>
              )}
            </>
          ) : (
            <div className="text-center py-12">
              <div className="mx-auto w-16 h-16 rounded-full bg-muted flex items-center justify-center mb-4">
                <Users className="w-8 h-8 text-muted-foreground" />
              </div>
              <h3 className="text-lg font-medium mb-2">No pandits found</h3>
              <p className="text-muted-foreground mb-4">Try adjusting your search or filter criteria</p>
              <button
                onClick={resetFilters}
                className="px-4 py-2 bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white rounded-md transition-colors"
              >
                Clear all filters
              </button>
            </div>
          )}
        </div>

        {/* Why Choose Our Pandits */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">Why Choose Our Pandits?</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Our pandits are carefully selected and verified to ensure the highest quality of spiritual services.
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="border border-border rounded-lg p-6 bg-card text-center">
              <div className="w-12 h-12 rounded-lg bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
                <Award className="w-6 h-6" />
              </div>
              <h3 className="font-medium text-lg mb-2">Verified Credentials</h3>
              <p className="text-sm text-muted-foreground">
                All our pandits are certified and have verified credentials with years of experience.
              </p>
            </div>
            
            <div className="border border-border rounded-lg p-6 bg-card text-center">
              <div className="w-12 h-12 rounded-lg bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
                <Shield className="w-6 h-6" />
              </div>
              <h3 className="font-medium text-lg mb-2">Secure Booking</h3>
              <p className="text-sm text-muted-foreground">
                Book with confidence through our secure platform with guaranteed service quality.
              </p>
            </div>
            
            <div className="border border-border rounded-lg p-6 bg-card text-center">
              <div className="w-12 h-12 rounded-lg bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
                <Users className="w-6 h-6" />
              </div>
              <h3 className="font-medium text-lg mb-2">Community Rated</h3>
              <p className="text-sm text-muted-foreground">
                Choose from pandits with high ratings and positive reviews from our community.
              </p>
            </div>
          </div>
        </div>

        {/* Testimonials */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">Client Testimonials</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Hear from our clients about their experiences with our pandits.
            </p>
          </div>
          
          <div className="bg-card border border-border rounded-lg p-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="border border-border rounded-lg p-4">
                <div className="flex items-center gap-1 mb-3">
                  {[...Array(5)].map((_, i) => (
                    <Star key={i} className="w-4 h-4 fill-yellow-400 text-yellow-400" />
                  ))}
                </div>
                <p className="text-sm text-muted-foreground mb-4">
                  "Pandit Rajesh Sharma conducted our Griha Pravesh ceremony with such precision and devotion. The positive energy in our new home is palpable. Highly recommend his services!"
                </p>
                <div className="flex items-center gap-4">
                  <div className="w-12 h-12 rounded-full bg-muted flex items-center justify-center">
                    <span className="text-sm font-semibold">SP</span>
                  </div>
                  <div>
                    <div className="font-semibold text-foreground">Shreya Patel</div>
                    <div className="text-sm text-muted-foreground">Delhi</div>
                  </div>
                </div>
              </div>
              
              <div className="border border-border rounded-lg p-4">
                <div className="flex items-center gap-1 mb-3">
                  {[...Array(5)].map((_, i) => (
                    <Star key={i} className="w-4 h-4 fill-yellow-400 text-yellow-400" />
                  ))}
                </div>
                <p className="text-sm text-muted-foreground mb-4">
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
      </div>
    </>
  )
}
'use client'
import React, { useState, useEffect, useMemo } from 'react';
import Link from "next/link"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video, Search, SlidersHorizontal, Shield, User, CheckCircle } from "lucide-react"
import { Button } from "@/components/ui/button"
import {
  MOCK_PANDITS,
  PANDIT_EXPERIENCE_OPTIONS,
  PANDIT_LANGUAGES,
  PANDIT_LOCATIONS,
  PANDIT_SPECIALIZATIONS,
  type Pandit,
  type PanditFilterOptions as FilterOptions,
} from "@/lib/pandits"

// Schema markup for the find pandit page is rendered by
// `app/find-pandit/page.tsx` (server component) so it ships in the
// initial HTML instead of being regenerated client-side. The old
// in-client `seoManager.generateSchema` call was:
//   - Wasted JS in the client bundle
//   - Used `dangerouslySetInnerHTML` (avoidable XSS surface)
//   - Could not be cached by Next's static optimisation since the
//     schema string lived in client component state
// See PR 0.8 audit closure (P1).

// Mock data for pandits is now sourced from @/lib/pandits — single
// source of truth shared with app/pandits. This module is the
// data layer for the directory; the seed data here will become
// dev-only when the real Drizzle/Postgres backend lands (PR 11).
// Cast to Required<...> because find-pandit shows the extended card
// UI (`about`, `certifications`) — all 6 seed pandits have them.
const mockPandits = MOCK_PANDITS as unknown as (Pandit &
  Required<{ about: string; certifications: string[] }>)[];
const locations: readonly string[] = PANDIT_LOCATIONS;
const specializations: readonly string[] = PANDIT_SPECIALIZATIONS;
const experienceOptions: readonly string[] = PANDIT_EXPERIENCE_OPTIONS;
const languages: readonly string[] = PANDIT_LANGUAGES;

export default function FindPanditClient() {
  // State for filters
  const [filters, setFilters] = useState<FilterOptions>({
    location: [],
    specialization: [],
    rating: null,
    experience: [],
    searchQuery: "",
    language: []
  });
  
  // State for UI
  const [showFilters, setShowFilters] = useState(false);
  const [selectedPandit, setSelectedPandit] = useState<
    (Pandit & Required<{ about: string; certifications: string[] }>) | null
  >(null);
  const [showBookingForm, setShowBookingForm] = useState(false);
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    date: '',
    time: '',
    requirements: ''
  });
  
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
      
      // Language filter
      if (filters.language!.length > 0 && !filters.language!.some(lang => pandit.languages.includes(lang))) {
        return false;
      }
      
      return true;
    });
  }, [filters, debouncedSearchQuery]);
  
  // Handle filter changes
  const handleFilterChange = (filterType: keyof FilterOptions, value: string | number) => {
    setFilters(prev => {
      const currentFilters = { ...prev };
      
      if (filterType === 'rating') {
        currentFilters[filterType] = value as number | null;
        return currentFilters;
      }
      
      const currentArray = currentFilters[filterType] as string[];
      const filterArray = [...currentArray];
      const index = filterArray.indexOf(value as string);
      
      if (index > -1) {
        filterArray.splice(index, 1);
      } else {
        filterArray.push(value as string);
      }
      
      // Type assertion to satisfy TypeScript
      currentFilters[filterType] = filterArray as any;
      return currentFilters;
    });
  };
  
  // Clear all filters
  const clearFilters = () => {
    setFilters({
      location: [],
      specialization: [],
      rating: null,
      experience: [],
      searchQuery: "",
      language: []
    });
  };
  
  // Handle booking form changes
  const handleFormChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };
  
  // Handle booking submission
  const handleBookingSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    // Handle form submission
    console.log('Booking submitted:', { ...formData, pandit: selectedPandit });
    alert(`Thank you! Your booking request for ${selectedPandit?.name} has been submitted. We will contact you shortly to confirm details.`);
    setShowBookingForm(false);
    setSelectedPandit(null);
    // Reset form
    setFormData({
      name: '',
      email: '',
      phone: '',
      date: '',
      time: '',
      requirements: ''
    });
  };
  
  // View pandit details
  const viewPanditDetails = (
    pandit: Pandit & Required<{ about: string; certifications: string[] }>,
  ) => {
    setSelectedPandit(pandit);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };
  
  // Book pandit
  const bookPandit = (
    pandit: Pandit & Required<{ about: string; certifications: string[] }>,
  ) => {
    setSelectedPandit(pandit);
    setShowBookingForm(true);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };
  
  return (
    <>
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <Link href="/" className="hover:text-foreground">Home</Link>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">Find Pandit</span>
          </nav>
        </div>

        {/* Hero Banner */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div 
            className="relative h-48 rounded-lg overflow-hidden bg-cover bg-center flex items-end"
            style={{
              backgroundImage: "url('https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg')"
            }}
          >
            <div className="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
            <div className="relative z-10 text-white p-4 max-w-2xl">
              <h1 className="text-2xl font-serif font-semibold">Find Qualified Pandits</h1>
              <p className="text-sm mt-1">
                Browse and book verified Vedic pandits for your spiritual rituals and pujas in Delhi NCR.
              </p>
            </div>
          </div>
        </div>

        <div className="max-w-7xl mx-auto px-4 sm:px-6 pb-10">
          {/* Booking Form Modal */}
          {showBookingForm && selectedPandit && (
            <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
              <div className="bg-card border border-border rounded-lg p-6 max-w-md w-full max-h-[90vh] overflow-y-auto">
                <div className="flex justify-between items-start mb-4">
                  <h2 className="text-xl font-semibold">Book {selectedPandit.name}</h2>
                  <button 
                    onClick={() => setShowBookingForm(false)}
                    className="text-muted-foreground hover:text-foreground"
                  >
                    <X className="h-5 w-5" />
                  </button>
                </div>
                
                <div className="flex items-center mb-4 p-3 bg-muted rounded-lg">
                  <img 
                    src={selectedPandit.image} 
                    alt={selectedPandit.name} 
                    className="w-12 h-12 rounded-full object-cover mr-3"
                  />
                  <div>
                    <p className="font-medium">{selectedPandit.name}</p>
                    <p className="text-sm text-muted-foreground">{selectedPandit.specialization}</p>
                  </div>
                </div>
                
                <form onSubmit={handleBookingSubmit} className="space-y-4">
                  <div>
                    <label htmlFor="name" className="block text-sm font-medium mb-1">Full Name</label>
                    <input
                      type="text"
                      id="name"
                      name="name"
                      value={formData.name}
                      onChange={handleFormChange}
                      className="w-full px-3 py-2 border border-border rounded-md bg-background focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]"
                      required
                    />
                  </div>
                  
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label htmlFor="email" className="block text-sm font-medium mb-1">Email</label>
                      <input
                        type="email"
                        id="email"
                        name="email"
                        value={formData.email}
                        onChange={handleFormChange}
                        className="w-full px-3 py-2 border border-border rounded-md bg-background focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]"
                        required
                      />
                    </div>
                    
                    <div>
                      <label htmlFor="phone" className="block text-sm font-medium mb-1">Phone</label>
                      <input
                        type="tel"
                        id="phone"
                        name="phone"
                        value={formData.phone}
                        onChange={handleFormChange}
                        className="w-full px-3 py-2 border border-border rounded-md bg-background focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]"
                        required
                      />
                    </div>
                  </div>
                  
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label htmlFor="date" className="block text-sm font-medium mb-1">Preferred Date</label>
                      <input
                        type="date"
                        id="date"
                        name="date"
                        value={formData.date}
                        onChange={handleFormChange}
                        className="w-full px-3 py-2 border border-border rounded-md bg-background focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]"
                        required
                      />
                    </div>
                    
                    <div>
                      <label htmlFor="time" className="block text-sm font-medium mb-1">Preferred Time</label>
                      <input
                        type="time"
                        id="time"
                        name="time"
                        value={formData.time}
                        onChange={handleFormChange}
                        className="w-full px-3 py-2 border border-border rounded-md bg-background focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]"
                        required
                      />
                    </div>
                  </div>
                  
                  <div>
                    <label htmlFor="requirements" className="block text-sm font-medium mb-1">Special Requirements</label>
                    <textarea
                      id="requirements"
                      name="requirements"
                      value={formData.requirements}
                      onChange={handleFormChange}
                      rows={3}
                      className="w-full px-3 py-2 border border-border rounded-md bg-background focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]"
                      placeholder="Any specific requirements or customs you'd like us to follow..."
                    ></textarea>
                  </div>
                  
                  <Button 
                    type="submit" 
                    className="w-full bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white font-medium py-3 rounded-md transition-all duration-300 shadow-sm hover:shadow-md"
                  >
                    Submit Booking Request
                  </Button>
                </form>
              </div>
            </div>
          )}

          {/* Pandit Detail View */}
          {selectedPandit && !showBookingForm && (
            <div className="mb-8 bg-card border border-border rounded-lg p-6">
              <button 
                onClick={() => setSelectedPandit(null)}
                className="flex items-center text-muted-foreground hover:text-foreground mb-4"
              >
                <ArrowRight className="h-4 w-4 rotate-180 mr-1" />
                Back to all pandits
              </button>
              
              <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="md:col-span-1">
                  <img 
                    src={selectedPandit.image} 
                    alt={selectedPandit.name} 
                    className="w-full rounded-lg object-cover aspect-square"
                  />
                  
                  <div className="mt-4">
                    <Button 
                      onClick={() => bookPandit(selectedPandit)}
                      className="w-full bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white font-medium py-3 rounded-md transition-all duration-300 shadow-sm hover:shadow-md"
                    >
                      Book This Pandit
                    </Button>
                  </div>
                </div>
                
                <div className="md:col-span-2">
                  <div className="flex flex-wrap items-center gap-2 mb-2">
                    <h1 className="text-2xl font-bold">{selectedPandit.name}</h1>
                    <div className="flex items-center bg-green-100 text-green-800 px-2 py-1 rounded-full text-sm">
                      <CheckCircle className="h-4 w-4 mr-1" />
                      Verified
                    </div>
                  </div>
                  
                  <p className="text-muted-foreground mb-4">{selectedPandit.specialization}</p>
                  
                  <div className="flex flex-wrap items-center gap-4 mb-6">
                    <div className="flex items-center">
                      <Star className="h-5 w-5 text-yellow-500 fill-yellow-500" />
                      <span className="ml-1 font-medium">{selectedPandit.rating}</span>
                    </div>
                    <div className="flex items-center">
                      <Users className="h-5 w-5 text-muted-foreground" />
                      <span className="ml-1">{selectedPandit.experience}</span>
                    </div>
                    <div className="flex items-center">
                      <MapPin className="h-5 w-5 text-muted-foreground" />
                      <span className="ml-1">{selectedPandit.location}</span>
                    </div>
                  </div>
                  
                  <div className="mb-6">
                    <h2 className="text-lg font-semibold mb-2">About</h2>
                    <p className="text-muted-foreground">{selectedPandit.about}</p>
                  </div>
                  
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <h3 className="font-medium mb-2">Skills & Expertise</h3>
                      <div className="flex flex-wrap gap-2">
                        {selectedPandit.skills.map((skill, index) => (
                          <span key={index} className="bg-muted px-3 py-1 rounded-full text-sm">
                            {skill}
                          </span>
                        ))}
                      </div>
                    </div>
                    
                    <div>
                      <h3 className="font-medium mb-2">Languages</h3>
                      <div className="flex flex-wrap gap-2">
                        {selectedPandit.languages.map((lang, index) => (
                          <span key={index} className="bg-muted px-3 py-1 rounded-full text-sm">
                            {lang}
                          </span>
                        ))}
                      </div>
                    </div>
                  </div>
                  
                  <div className="mt-6">
                    <h3 className="font-medium mb-2">Certifications</h3>
                    <ul className="list-disc list-inside text-muted-foreground">
                      {selectedPandit.certifications.map((cert, index) => (
                        <li key={index}>{cert}</li>
                      ))}
                    </ul>
                  </div>
                  
                  <div className="mt-6 p-4 bg-muted rounded-lg">
                    <div className="flex justify-between items-center">
                      <span className="font-medium">Starting at {selectedPandit.price}</span>
                      <Button 
                        onClick={() => bookPandit(selectedPandit)}
                        variant="outline"
                        className="border-[#0E3F2E] text-[#0E3F2E] hover:bg-[#0E3F2E] hover:text-white"
                      >
                        Book Now
                      </Button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          )}
          
          {/* Filters and Search */}
          {!selectedPandit && (
            <div className="mb-6">
              <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <div className="relative flex-1 max-w-md">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <Search className="h-4 w-4 text-muted-foreground" />
                  </div>
                  <input
                    type="text"
                    placeholder="Search pandits, skills, or specializations..."
                    value={filters.searchQuery}
                    onChange={(e) => setFilters(prev => ({ ...prev, searchQuery: e.target.value }))}
                    className="w-full pl-10 pr-4 py-2 border border-border rounded-md bg-background focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]"
                  />
                </div>
                
                <div className="flex gap-2">
                  <button
                    onClick={() => setShowFilters(!showFilters)}
                    className="flex items-center gap-2 px-4 py-2 border border-border rounded-md bg-background hover:bg-muted transition-colors"
                  >
                    <SlidersHorizontal className="h-4 w-4" />
                    Filters
                  </button>
                </div>
              </div>
              
              {/* Filter Panel */}
              {showFilters && (
                <div className="border border-border rounded-lg p-4 mb-6 bg-card">
                  <div className="flex justify-between items-center mb-4">
                    <h3 className="font-medium">Filter Pandits</h3>
                    <button 
                      onClick={clearFilters}
                      className="text-sm text-muted-foreground hover:text-foreground"
                    >
                      Clear all
                    </button>
                  </div>
                  
                  <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {/* Location Filter */}
                    <div>
                      <h4 className="font-medium mb-2">Location</h4>
                      <div className="space-y-2">
                        {locations.map((location) => (
                          <div key={location} className="flex items-center">
                            <input
                              type="checkbox"
                              id={`location-${location}`}
                              checked={filters.location.includes(location)}
                              onChange={() => handleFilterChange('location', location)}
                              className="h-4 w-4 text-[#0E3F2E] border-border rounded focus:ring-[#0E3F2E]"
                            />
                            <label htmlFor={`location-${location}`} className="ml-2 text-sm">
                              {location}
                            </label>
                          </div>
                        ))}
                      </div>
                    </div>
                    
                    {/* Specialization Filter */}
                    <div>
                      <h4 className="font-medium mb-2">Specialization</h4>
                      <div className="space-y-2">
                        {specializations.map((specialization) => (
                          <div key={specialization} className="flex items-center">
                            <input
                              type="checkbox"
                              id={`specialization-${specialization}`}
                              checked={filters.specialization.includes(specialization)}
                              onChange={() => handleFilterChange('specialization', specialization)}
                              className="h-4 w-4 text-[#0E3F2E] border-border rounded focus:ring-[#0E3F2E]"
                            />
                            <label htmlFor={`specialization-${specialization}`} className="ml-2 text-sm">
                              {specialization}
                            </label>
                          </div>
                        ))}
                      </div>
                    </div>
                    
                    {/* Rating Filter */}
                    <div>
                      <h4 className="font-medium mb-2">Minimum Rating</h4>
                      <div className="space-y-2">
                        {[4.5, 4.0, 3.5].map((rating) => (
                          <div key={rating} className="flex items-center">
                            <input
                              type="radio"
                              id={`rating-${rating}`}
                              name="rating"
                              checked={filters.rating === rating}
                              onChange={() => setFilters(prev => ({ ...prev, rating }))}
                              className="h-4 w-4 text-[#0E3F2E] border-border focus:ring-[#0E3F2E]"
                            />
                            <label htmlFor={`rating-${rating}`} className="ml-2 text-sm flex items-center">
                              <Star className="h-4 w-4 text-yellow-500 fill-yellow-500 mr-1" />
                              {rating} & up
                            </label>
                          </div>
                        ))}
                        {filters.rating !== null && (
                          <div className="flex items-center">
                            <input
                              type="radio"
                              id="rating-all"
                              name="rating"
                              checked={filters.rating === null}
                              onChange={() => setFilters(prev => ({ ...prev, rating: null }))}
                              className="h-4 w-4 text-[#0E3F2E] border-border focus:ring-[#0E3F2E]"
                            />
                            <label htmlFor="rating-all" className="ml-2 text-sm">
                              Show all
                            </label>
                          </div>
                        )}
                      </div>
                    </div>
                    
                    {/* Experience Filter */}
                    <div>
                      <h4 className="font-medium mb-2">Experience</h4>
                      <div className="space-y-2">
                        {experienceOptions.map((exp) => (
                          <div key={exp} className="flex items-center">
                            <input
                              type="checkbox"
                              id={`experience-${exp}`}
                              checked={filters.experience.includes(exp)}
                              onChange={() => handleFilterChange('experience', exp)}
                              className="h-4 w-4 text-[#0E3F2E] border-border rounded focus:ring-[#0E3F2E]"
                            />
                            <label htmlFor={`experience-${exp}`} className="ml-2 text-sm">
                              {exp}
                            </label>
                          </div>
                        ))}
                      </div>
                    </div>
                    
                    {/* Language Filter */}
                    <div>
                      <h4 className="font-medium mb-2">Languages</h4>
                      <div className="space-y-2">
                        {languages.map((language) => (
                          <div key={language} className="flex items-center">
                            <input
                              type="checkbox"
                              id={`language-${language}`}
                              checked={filters.language!.includes(language)}
                              onChange={() => handleFilterChange('language', language)}
                              className="h-4 w-4 text-[#0E3F2E] border-border rounded focus:ring-[#0E3F2E]"
                            />
                            <label htmlFor={`language-${language}`} className="ml-2 text-sm">
                              {language}
                            </label>
                          </div>
                        ))}
                      </div>
                    </div>
                  </div>
                </div>
              )}
              
              {/* Active Filters */}
              {(filters.location.length > 0 || 
                filters.specialization.length > 0 || 
                filters.rating !== null || 
                filters.experience.length > 0 ||
                filters.language!.length > 0) && (
                <div className="flex flex-wrap gap-2 mb-4">
                  {filters.location.map(location => (
                    <span key={location} className="flex items-center bg-muted px-3 py-1 rounded-full text-sm">
                      {location}
                      <button 
                        onClick={() => handleFilterChange('location', location)}
                        className="ml-2 text-muted-foreground hover:text-foreground"
                      >
                        <X className="h-3 w-3" />
                      </button>
                    </span>
                  ))}
                  
                  {filters.specialization.map(specialization => (
                    <span key={specialization} className="flex items-center bg-muted px-3 py-1 rounded-full text-sm">
                      {specialization}
                      <button 
                        onClick={() => handleFilterChange('specialization', specialization)}
                        className="ml-2 text-muted-foreground hover:text-foreground"
                      >
                        <X className="h-3 w-3" />
                      </button>
                    </span>
                  ))}
                  
                  {filters.rating !== null && (
                    <span className="flex items-center bg-muted px-3 py-1 rounded-full text-sm">
                      {filters.rating}+ rating
                      <button 
                        onClick={() => setFilters(prev => ({ ...prev, rating: null }))}
                        className="ml-2 text-muted-foreground hover:text-foreground"
                      >
                        <X className="h-3 w-3" />
                      </button>
                    </span>
                  )}
                  
                  {filters.experience.map(exp => (
                    <span key={exp} className="flex items-center bg-muted px-3 py-1 rounded-full text-sm">
                      {exp}
                      <button 
                        onClick={() => handleFilterChange('experience', exp)}
                        className="ml-2 text-muted-foreground hover:text-foreground"
                      >
                        <X className="h-3 w-3" />
                      </button>
                    </span>
                  ))}
                  
                  {filters.language!.map(lang => (
                    <span key={lang} className="flex items-center bg-muted px-3 py-1 rounded-full text-sm">
                      {lang}
                      <button 
                        onClick={() => handleFilterChange('language', lang)}
                        className="ml-2 text-muted-foreground hover:text-foreground"
                      >
                        <X className="h-3 w-3" />
                      </button>
                    </span>
                  ))}
                </div>
              )}
              
              {/* Results Count */}
              <div className="mb-4">
                <p className="text-muted-foreground">
                  Showing {filteredPandits.length} of {mockPandits.length} pandits
                </p>
              </div>
              
              {/* Pandit Cards */}
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {filteredPandits.map((pandit) => (
                  <div key={pandit.id} className="border border-border rounded-lg overflow-hidden bg-card hover:shadow-md transition-shadow">
                    <div className="relative">
                      <img 
                        src={pandit.image} 
                        alt={pandit.name} 
                        className="w-full h-48 object-cover"
                      />
                      <div className="absolute top-2 right-2 bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs flex items-center">
                        <CheckCircle className="h-3 w-3 mr-1" />
                        Verified
                      </div>
                    </div>
                    
                    <div className="p-4">
                      <div className="flex justify-between items-start mb-2">
                        <h3 className="font-semibold">{pandit.name}</h3>
                        <div className="flex items-center">
                          <Star className="h-4 w-4 text-yellow-500 fill-yellow-500" />
                          <span className="ml-1 text-sm">{pandit.rating}</span>
                        </div>
                      </div>
                      
                      <p className="text-sm text-muted-foreground mb-2">{pandit.specialization}</p>
                      
                      <div className="flex flex-wrap gap-1 mb-3">
                        {pandit.skills.slice(0, 3).map((skill, index) => (
                          <span key={index} className="bg-muted px-2 py-1 rounded-full text-xs">
                            {skill}
                          </span>
                        ))}
                        {pandit.skills.length > 3 && (
                          <span className="bg-muted px-2 py-1 rounded-full text-xs">
                            +{pandit.skills.length - 3} more
                          </span>
                        )}
                      </div>
                      
                      <div className="flex justify-between items-center mb-4">
                        <div className="flex items-center text-sm text-muted-foreground">
                          <MapPin className="h-4 w-4 mr-1" />
                          {pandit.location}
                        </div>
                        <div className="text-sm font-medium">{pandit.price}</div>
                      </div>
                      
                      <div className="flex gap-2">
                        <Button 
                          onClick={() => viewPanditDetails(pandit)}
                          variant="outline"
                          className="flex-1 border-border"
                        >
                          View Details
                        </Button>
                        <Button 
                          onClick={() => bookPandit(pandit)}
                          className="flex-1 bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white"
                        >
                          Book
                        </Button>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
              
              {filteredPandits.length === 0 && (
                <div className="text-center py-12">
                  <User className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                  <h3 className="text-lg font-medium mb-2">No pandits found</h3>
                  <p className="text-muted-foreground mb-4">
                    Try adjusting your filters or search query to find more pandits.
                  </p>
                  <Button 
                    onClick={clearFilters}
                    variant="outline"
                  >
                    Clear all filters
                  </Button>
                </div>
              )}
            </div>
          )}
        </div>
      </div>
    </>
  )
}
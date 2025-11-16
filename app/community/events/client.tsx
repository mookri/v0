'use client'
import React, { useState } from 'react';
import Link from "next/link"
import Script from "next/script"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video, Search, MessageCircle, ThumbsUp, Reply, Flag, MoreHorizontal, User, CalendarDays, Ticket, Bell, Share2 } from "lucide-react"

interface Event {
  id: number;
  title: string;
  excerpt: string;
  date: string;
  time: string;
  location: string;
  type: string;
  format: string;
  attendees: number;
  maxAttendees: number;
  price: string;
  image: string;
  category: string;
  isFeatured?: boolean;
  organizer: string;
}

// Events data
const events: Event[] = [
  {
    id: 1,
    title: "Bhagavad Gita Discourse Series",
    excerpt: "Weekly sessions exploring the profound wisdom of the Bhagavad Gita with expert scholars and spiritual leaders.",
    date: "2025-11-20",
    time: "6:00 PM - 7:30 PM",
    location: "Online & Delhi Center",
    type: "Discourse",
    format: "Hybrid",
    attendees: 142,
    maxAttendees: 200,
    price: "Free",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    category: "Philosophy",
    isFeatured: true,
    organizer: "Prof. Ramesh Iyer"
  },
  {
    id: 2,
    title: "International Yoga Day Celebration",
    excerpt: "Join our global community in celebrating International Yoga Day with special sessions and guided practices.",
    date: "2025-11-22",
    time: "5:00 AM - 8:00 AM",
    location: "Multiple Locations",
    type: "Celebration",
    format: "In-person",
    attendees: 285,
    maxAttendees: 500,
    price: "Free",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    category: "Yoga",
    organizer: "Swami Anand"
  },
  {
    id: 3,
    title: "Vedic Rituals Workshop",
    excerpt: "Hands-on workshop to learn proper procedures and significance of various Vedic rituals from certified pandits.",
    date: "2025-11-25",
    time: "10:00 AM - 4:00 PM",
    location: "Mumbai Center",
    type: "Workshop",
    format: "In-person",
    attendees: 42,
    maxAttendees: 50,
    price: "₹1,499",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg",
    category: "Rituals",
    organizer: "Pandit Rajesh Sharma"
  },
  {
    id: 4,
    title: "Navratri Special Pujas",
    excerpt: "Nine days of special pujas and cultural programs celebrating the divine feminine energy.",
    date: "2025-11-28",
    time: "All Day",
    location: "Temple Premises",
    type: "Festival",
    format: "In-person",
    attendees: 500,
    maxAttendees: 1000,
    price: "Free",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_db05ba7c437e48abe6440798d2a33b09dr_bokdku.jpg",
    category: "Festivals",
    organizer: "Navdhya Temple Committee"
  },
  {
    id: 5,
    title: "Astrology & Career Guidance",
    excerpt: "Expert astrologers provide personalized career guidance based on birth charts and planetary positions.",
    date: "2025-12-02",
    time: "9:00 AM - 5:00 PM",
    location: "Online",
    type: "Consultation",
    format: "Online",
    attendees: 78,
    maxAttendees: 150,
    price: "₹499/session",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg",
    category: "Astrology",
    organizer: "Dr. Priya Sharma"
  },
  {
    id: 6,
    title: "Children's Mythology Camp",
    excerpt: "Engaging camp for children to learn about Indian mythology through stories, games, and creative activities.",
    date: "2025-12-05",
    time: "10:00 AM - 1:00 PM",
    location: "Bangalore Center",
    type: "Camp",
    format: "In-person",
    attendees: 35,
    maxAttendees: 40,
    price: "₹799",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    category: "Kids",
    organizer: "Navdhya Kids Team"
  }
];

const categories = [
  "All", "Philosophy", "Yoga", "Rituals", "Festivals", "Astrology", "Kids"
];

const formats = [
  "All Formats", "Online", "In-person", "Hybrid"
];

const months = [
  "November 2025", "December 2025", "January 2026", "February 2026"
];

export default function EventsClient({ eventsSchema }: { eventsSchema: string }) {
  const [searchTerm, setSearchTerm] = useState('')
  const [categoryFilter, setCategoryFilter] = useState("All");
  const [formatFilter, setFormatFilter] = useState("All Formats");
  const [monthFilter, setMonthFilter] = useState("November 2025");
  
  const filteredEvents = events.filter(event => 
    (event.title.toLowerCase().includes(searchTerm.toLowerCase()) || 
     event.excerpt.toLowerCase().includes(searchTerm.toLowerCase()) ||
     event.organizer.toLowerCase().includes(searchTerm.toLowerCase())) &&
    (categoryFilter === "All" || event.category === categoryFilter) &&
    (formatFilter === "All Formats" || event.format === formatFilter) &&
    (new Date(event.date).toLocaleDateString('en-IN', { month: 'long', year: 'numeric' }) === monthFilter)
  );
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: eventsSchema }}
      />
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <Link href="/" className="hover:text-foreground">Home</Link>
            <span className="mx-1.5">/</span>
            <Link href="/community" className="hover:text-foreground">Community</Link>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">Upcoming Events</span>
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
              <h1 className="text-2xl font-serif font-semibold">Upcoming Events</h1>
              <p className="text-sm mt-1">
                Calendar-based spiritual events and gatherings. Join our community for workshops, discourses, and cultural celebrations.
              </p>
            </div>
          </div>
        </div>

        {/* Stats Bar */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div className="bg-card border border-border rounded-lg p-4 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">30+</div>
              <div className="text-xs text-muted-foreground">Events This Month</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">5000+</div>
              <div className="text-xs text-muted-foreground">Community Members</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">4.9/5</div>
              <div className="text-xs text-muted-foreground">Event Rating</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">95%</div>
              <div className="text-xs text-muted-foreground">Attendance Rate</div>
            </div>
          </div>
        </div>

        {/* Search and Filters */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div className="bg-card border border-border rounded-lg p-4">
            <div className="flex flex-col md:flex-row gap-4">
              {/* Search Bar */}
              <div className="relative flex-grow">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                <input
                  type="text"
                  placeholder="Search events..."
                  className="w-full pl-10 pr-4 py-2 border border-border rounded-md bg-background focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                />
              </div>
              
              {/* Action Buttons */}
              <div className="flex gap-2">
                <button className="px-4 py-2 bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white rounded-md text-sm font-medium transition-colors">
                  <div className="flex items-center gap-2">
                    <Bell className="w-4 h-4" />
                    <span>Get Notifications</span>
                  </div>
                </button>
                <button className="px-4 py-2 border border-border rounded-md text-sm font-medium hover:bg-muted transition-colors">
                  <div className="flex items-center gap-2">
                    <CalendarDays className="w-4 h-4" />
                    <span>Add to Calendar</span>
                  </div>
                </button>
              </div>
            </div>
            
            {/* Filters */}
            <div className="mt-4 pt-4 border-t border-border">
              <div className="flex flex-wrap gap-4">
                <div>
                  <div className="text-xs font-medium mb-2">Category</div>
                  <div className="flex flex-wrap gap-2">
                    {categories.map((category, index) => (
                      <button
                        key={index}
                        onClick={() => setCategoryFilter(category)}
                        className={`px-3 py-1.5 text-xs rounded-full transition-colors ${
                          categoryFilter === category
                            ? 'bg-[#0E3F2E] text-white' 
                            : 'bg-background hover:bg-muted border border-border text-foreground'
                        }`}
                      >
                        {category}
                      </button>
                    ))}
                  </div>
                </div>
                
                <div>
                  <div className="text-xs font-medium mb-2">Format</div>
                  <div className="flex flex-wrap gap-2">
                    {formats.map((format, index) => (
                      <button
                        key={index}
                        onClick={() => setFormatFilter(format)}
                        className={`px-3 py-1.5 text-xs rounded-full transition-colors ${
                          formatFilter === format
                            ? 'bg-[#0E3F2E] text-white' 
                            : 'bg-background hover:bg-muted border border-border text-foreground'
                        }`}
                      >
                        {format}
                      </button>
                    ))}
                  </div>
                </div>
                
                <div>
                  <div className="text-xs font-medium mb-2">Month</div>
                  <div className="flex flex-wrap gap-2">
                    {months.map((month, index) => (
                      <button
                        key={index}
                        onClick={() => setMonthFilter(month)}
                        className={`px-3 py-1.5 text-xs rounded-full transition-colors ${
                          monthFilter === month
                            ? 'bg-[#0E3F2E] text-white' 
                            : 'bg-background hover:bg-muted border border-border text-foreground'
                        }`}
                      >
                        {month}
                      </button>
                    ))}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Featured Event */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-8">
          <div className="bg-card border border-border rounded-lg overflow-hidden">
            <div className="border-b border-border p-4">
              <h2 className="text-lg font-semibold">Featured Event</h2>
            </div>
            
            {events.find(event => event.isFeatured) && (
              <div className="p-6">
                <div className="flex flex-col lg:flex-row gap-6">
                  <div className="lg:w-2/3">
                    <div className="flex items-center gap-2 mb-3">
                      <span className="px-2 py-1 bg-yellow-100 text-yellow-800 text-[10px] rounded-full">
                        FEATURED
                      </span>
                      <span className="px-2 py-1 bg-[#0E3F2E]/10 text-[#0E3F2E] text-[10px] rounded-full">
                        {events.find(event => event.isFeatured)?.category}
                      </span>
                    </div>
                    
                    <h3 className="text-xl font-semibold mb-3">
                      {events.find(event => event.isFeatured)?.title}
                    </h3>
                    <p className="text-muted-foreground mb-4">
                      {events.find(event => event.isFeatured)?.excerpt}
                    </p>
                    
                    <div className="grid grid-cols-2 gap-4 mb-4">
                      <div className="flex items-center gap-2 text-sm">
                        <Calendar className="w-4 h-4 text-[#0E3F2E]" />
                        <div>
                          <div className="text-xs text-muted-foreground">Date & Time</div>
                          <div className="font-medium">
                            {new Date(events.find(event => event.isFeatured)?.date || '').toLocaleDateString('en-IN', { day: 'numeric', month: 'long', year: 'numeric' })} at {events.find(event => event.isFeatured)?.time}
                          </div>
                        </div>
                      </div>
                      <div className="flex items-center gap-2 text-sm">
                        <MapPin className="w-4 h-4 text-[#0E3F2E]" />
                        <div>
                          <div className="text-xs text-muted-foreground">Location</div>
                          <div className="font-medium">{events.find(event => event.isFeatured)?.location}</div>
                        </div>
                      </div>
                      <div className="flex items-center gap-2 text-sm">
                        <Users className="w-4 h-4 text-[#0E3F2E]" />
                        <div>
                          <div className="text-xs text-muted-foreground">Attendees</div>
                          <div className="font-medium">{events.find(event => event.isFeatured)?.attendees} registered</div>
                        </div>
                      </div>
                      <div className="flex items-center gap-2 text-sm">
                        <User className="w-4 h-4 text-[#0E3F2E]" />
                        <div>
                          <div className="text-xs text-muted-foreground">Organizer</div>
                          <div className="font-medium">{events.find(event => event.isFeatured)?.organizer}</div>
                        </div>
                      </div>
                    </div>
                    
                    <div className="flex items-center justify-between">
                      <div className="text-lg font-semibold text-[#0E3F2E]">
                        {events.find(event => event.isFeatured)?.price}
                      </div>
                      <div className="flex gap-2">
                        <button className="px-4 py-2 border border-border rounded-md text-sm font-medium hover:bg-muted transition-colors">
                          <div className="flex items-center gap-2">
                            <Share2 className="w-4 h-4" />
                            <span>Share</span>
                          </div>
                        </button>
                        <Link 
                          href={`/community/events/${events.find(event => event.isFeatured)?.id}`}
                          className="px-4 py-2 bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white rounded-md text-sm font-medium transition-colors"
                        >
                          <div className="flex items-center gap-2">
                            <Ticket className="w-4 h-4" />
                            <span>Register Now</span>
                          </div>
                        </Link>
                      </div>
                    </div>
                  </div>
                  
                  <div className="lg:w-1/3">
                    <div className="bg-muted rounded-lg h-full overflow-hidden">
                      <img 
                        src={events.find(event => event.isFeatured)?.image} 
                        alt={events.find(event => event.isFeatured)?.title}
                        className="w-full h-full object-cover"
                      />
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>

        {/* Events Grid */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 pb-10">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {filteredEvents.filter(event => !event.isFeatured).map(event => (
              <div key={event.id} className="border border-border rounded-lg overflow-hidden bg-card flex flex-col h-full">
                <div className="bg-muted flex-shrink-0">
                  <img 
                    src={event.image} 
                    alt={event.title}
                    className="w-full h-40 object-cover"
                  />
                </div>
                
                <div className="p-4 space-y-3 flex flex-col flex-grow">
                  <div>
                    <div className="flex items-center justify-between mb-2">
                      <span className="px-2 py-1 bg-[#0E3F2E]/10 text-[#0E3F2E] text-[10px] rounded-full">
                        {event.category}
                      </span>
                      <span className="px-2 py-1 bg-background text-foreground text-[10px] rounded-full border border-border">
                        {event.format}
                      </span>
                    </div>
                    <h3 className="font-medium hover:text-[#0E3F2E] transition-colors line-clamp-2 text-sm">
                      {event.title}
                    </h3>
                  </div>
                  
                  <p className="text-[11px] text-muted-foreground line-clamp-3 flex-grow">
                    {event.excerpt}
                  </p>
                  
                  <div className="space-y-2 text-[10px] text-muted-foreground">
                    <div className="flex items-center gap-2">
                      <Calendar className="w-3 h-3" />
                      <span>{new Date(event.date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short' })} at {event.time}</span>
                    </div>
                    <div className="flex items-center gap-2">
                      <MapPin className="w-3 h-3" />
                      <span className="truncate">{event.location}</span>
                    </div>
                    <div className="flex items-center gap-2">
                      <User className="w-3 h-3" />
                      <span>{event.organizer}</span>
                    </div>
                    <div className="flex items-center gap-2">
                      <Users className="w-3 h-3" />
                      <span>{event.attendees}/{event.maxAttendees} registered</span>
                    </div>
                  </div>
                  
                  <div className="flex items-center justify-between pt-2">
                    <div className="text-sm font-semibold text-[#0E3F2E]">
                      {event.price}
                    </div>
                    <Link 
                      href={`/community/events/${event.id}`}
                      className="inline-flex items-center justify-center gap-1 bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white font-medium py-1.5 px-3 rounded-md transition-all duration-300 text-center text-[11px]"
                    >
                      <span>Register</span>
                      <ArrowRight className="w-3 h-3" />
                    </Link>
                  </div>
                </div>
              </div>
            ))}
          </div>
          
          {filteredEvents.filter(event => !event.isFeatured).length === 0 && (
            <div className="text-center py-10">
              <CalendarDays className="w-12 h-12 text-muted-foreground mx-auto mb-4" />
              <h3 className="text-lg font-medium mb-2">No events found</h3>
              <p className="text-muted-foreground">Try adjusting your search or filter criteria</p>
            </div>
          )}
        </div>
      </div>
    </>
  )
}
'use client'
import React, { useState, useMemo } from 'react';
import Link from "next/link"
import Script from "next/script"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video, CalendarDays } from "lucide-react"

interface Workshop {
  id: number;
  title: string;
  excerpt: string;
  instructor: string;
  date: string;
  time: string;
  duration: string;
  attendees: number;
  maxAttendees: number;
  price: string;
  originalPrice?: string;
  image: string;
  category: string;
  format: string;
}

// Workshop data
const workshops: Workshop[] = [
  {
    id: 1,
    title: "Meditation for Modern Life",
    excerpt: "Learn practical meditation techniques to manage stress and find inner peace in today's fast-paced world.",
    instructor: "Swami Anand",
    date: "2025-11-20",
    time: "6:00 PM - 7:30 PM",
    duration: "90 mins",
    attendees: 24,
    maxAttendees: 30,
    price: "₹499",
    originalPrice: "₹799",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    category: "Meditation",
    format: "Online"
  },
  {
    id: 2,
    title: "Understanding Your Birth Chart",
    excerpt: "A beginner-friendly introduction to Vedic astrology and how to interpret your Kundli for self-awareness.",
    instructor: "Pandit Ramesh Gupta",
    date: "2025-11-22",
    time: "4:00 PM - 6:00 PM",
    duration: "2 hours",
    attendees: 18,
    maxAttendees: 25,
    price: "₹799",
    originalPrice: "₹1,299",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg",
    category: "Astrology",
    format: "Online"
  },
  {
    id: 3,
    title: "Ganesh Homa Ritual Workshop",
    excerpt: "Participate in a live Ganesh Homa ceremony and learn the significance of each step in this powerful ritual.",
    instructor: "Pandit Suresh Patel",
    date: "2025-11-25",
    time: "9:00 AM - 11:00 AM",
    duration: "2 hours",
    attendees: 12,
    maxAttendees: 20,
    price: "₹1,299",
    originalPrice: "₹1,999",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_db05ba7c437e48abe6440798d2a33b09dr_bokdku.jpg",
    category: "Rituals",
    format: "In-person"
  },
  {
    id: 4,
    title: "Vastu for Home Harmony",
    excerpt: "Discover simple Vastu principles to create a peaceful and prosperous home environment for your family.",
    instructor: "Dr. Priya Sharma",
    date: "2025-11-27",
    time: "5:00 PM - 6:30 PM",
    duration: "90 mins",
    attendees: 28,
    maxAttendees: 30,
    price: "₹599",
    originalPrice: "₹899",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg",
    category: "Vastu",
    format: "Online"
  },
  {
    id: 5,
    title: "Bhagavad Gita Discussion Circle",
    excerpt: "Join fellow seekers in an interactive discussion of key chapters from the Bhagavad Gita and their practical wisdom.",
    instructor: "Prof. Ramesh Iyer",
    date: "2025-11-29",
    time: "7:00 PM - 8:30 PM",
    duration: "90 mins",
    attendees: 35,
    maxAttendees: 40,
    price: "₹399",
    originalPrice: "₹599",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    category: "Philosophy",
    format: "Online"
  },
  {
    id: 6,
    title: "Mantra Chanting Masterclass",
    excerpt: "Learn the art of proper pronunciation, rhythm, and intention in Vedic mantra chanting for maximum spiritual benefit.",
    instructor: "Swami Prema",
    date: "2025-12-02",
    time: "6:00 PM - 7:30 PM",
    duration: "90 mins",
    attendees: 15,
    maxAttendees: 20,
    price: "₹699",
    originalPrice: "₹999",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    category: "Mantras",
    format: "Online"
  }
];

const categories = [
  "All", "Meditation", "Astrology", "Rituals", "Vastu", "Philosophy", "Mantras"
];

const formats = [
  "All Formats", "Online", "In-person"
];

export default function WorkshopsClient({ workshopsSchema }: { workshopsSchema: string }) {
  const [categoryFilter, setCategoryFilter] = useState("All");
  const [formatFilter, setFormatFilter] = useState("All Formats");
  
  const filteredWorkshops = useMemo(() => workshops.filter(workshop => 
    (categoryFilter === "All" || workshop.category === categoryFilter) &&
    (formatFilter === "All Formats" || workshop.format === formatFilter)
  ), [workshops, categoryFilter, formatFilter]);
  
  const WorkshopCard = ({ workshop }: { workshop: Workshop }) => {
    const eventDate = new Date(workshop.date);
    const today = new Date();
    const daysUntil = Math.ceil((eventDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));
    
    return (
      <div className="group border border-border rounded-lg overflow-hidden bg-card flex flex-col h-full">
        <div className="relative bg-muted flex-shrink-0">
          <img 
            src={workshop.image} 
            alt={workshop.title}
            className="w-full h-40 object-cover group-hover:scale-105 transition-transform duration-300"
          />
          <div className="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
          
          {workshop.originalPrice && (
            <div className="absolute top-2 right-2">
              <span className="px-2 py-1 bg-red-600 text-white text-[10px] rounded-full">
                Save ₹{parseInt(workshop.originalPrice.replace(/[₹,]/g, "")) - parseInt(workshop.price.replace(/[₹,]/g, ""))}
              </span>
            </div>
          )}
          
          <div className="absolute top-2 left-2">
            <span className="px-2 py-1 bg-[#0E3F2E]/80 text-white text-[10px] rounded-full">
              {workshop.category}
            </span>
          </div>
          
          <div className="absolute bottom-2 left-2 right-2">
            <div className="flex items-center justify-between">
              <span className="px-2 py-1 bg-background/80 text-foreground text-[10px] rounded-full">
                {workshop.format}
              </span>
              <div className="flex items-center gap-1 text-white text-[10px]">
                <Users className="w-3 h-3" />
                <span>{workshop.attendees}/{workshop.maxAttendees}</span>
              </div>
            </div>
          </div>
        </div>
        
        <div className="p-4 space-y-3 flex flex-col flex-grow">
          <div className="flex justify-between items-start">
            <h3 className="font-medium hover:text-[#0E3F2E] transition-colors line-clamp-2 text-sm flex-grow">
              {workshop.title}
            </h3>
            <div className="flex items-center gap-1 ml-2">
              <CalendarDays className="w-3 h-3 text-muted-foreground" />
              <span className="text-[10px] text-muted-foreground">
                {daysUntil > 0 ? `${daysUntil} days` : 'Today'}
              </span>
            </div>
          </div>
          
          <p className="text-[11px] text-muted-foreground line-clamp-2">
            {workshop.excerpt}
          </p>
          
          <div className="flex items-center gap-2 text-[10px] text-muted-foreground">
            <div className="flex items-center gap-1">
              <Clock className="w-3 h-3" />
              <span>{workshop.time}</span>
            </div>
            <span>•</span>
            <div className="flex items-center gap-1">
              <Play className="w-3 h-3" />
              <span>{workshop.duration}</span>
            </div>
          </div>
          
          <div className="flex items-center justify-between pt-2">
            <div className="flex items-center gap-2">
              <div className="w-6 h-6 rounded-full bg-muted flex items-center justify-center">
                <span className="text-[8px] font-medium">
                  {workshop.instructor.split(' ').map(n => n[0]).join('')}
                </span>
              </div>
              <div className="text-[10px] font-medium">{workshop.instructor}</div>
            </div>
          </div>
          
          <div className="flex items-center justify-between pt-2">
            <div className="flex items-center gap-1">
              {workshop.originalPrice && (
                <span className="text-[10px] text-muted-foreground line-through">{workshop.originalPrice}</span>
              )}
              <span className={`font-medium text-sm ${workshop.originalPrice ? 'text-red-600' : 'text-foreground'}`}>
                {workshop.price}
              </span>
            </div>
            <Link 
              href={`/learn/workshops/${workshop.id}`}
              className="inline-flex items-center justify-center gap-1 bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white font-medium py-1.5 px-3 rounded-md transition-all duration-300 text-center text-[11px]"
            >
              <span>Register</span>
              <ArrowRight className="w-3 h-3" />
            </Link>
          </div>
        </div>
      </div>
    );
  };

  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: workshopsSchema }}
      />
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <Link href="/" className="hover:text-foreground">Home</Link>
            <span className="mx-1.5">/</span>
            <Link href="/learn" className="hover:text-foreground">Learning</Link>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">Workshops</span>
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
              <h1 className="text-2xl font-serif font-semibold">Spiritual Workshops</h1>
              <p className="text-sm mt-1">
                Join our live spiritual workshops with expert mentors. Experience interactive sessions on meditation, rituals, and Vedic wisdom in an engaging group setting.
              </p>
            </div>
          </div>
        </div>

        {/* Stats Bar */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div className="bg-card border border-border rounded-lg p-4 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">30+</div>
              <div className="text-xs text-muted-foreground">Workshops Monthly</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">2K+</div>
              <div className="text-xs text-muted-foreground">Active Participants</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">4.9/5</div>
              <div className="text-xs text-muted-foreground">Average Rating</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">95%</div>
              <div className="text-xs text-muted-foreground">Satisfaction Rate</div>
            </div>
          </div>
        </div>

        {/* Filters */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
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
          </div>
        </div>

        {/* Workshops Grid */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 pb-10">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {filteredWorkshops.map(workshop => (
              <div key={workshop.id} className="flex">
                <WorkshopCard workshop={workshop} />
              </div>
            ))}
          </div>
        </div>
      </div>
    </>
  )
}
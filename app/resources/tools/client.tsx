'use client'
import React, { useState } from 'react';
import Link from "next/link"
import Script from "next/script"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video, Search, MessageCircle, ThumbsUp, Reply, Flag, MoreHorizontal, User, Calculator, CalendarDays, Compass, Target } from "lucide-react"
import { seoManager } from "@/lib/seo-manager"

// Schema markup for the tools page
const TOOLS_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Tools & Calculators - Navdhya",
  description: "Horoscope, Muhurat, and Panchang calculators for determining auspicious timings and astrological insights.",
  path: "/resources/tools",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Resources", url: "https://www.navdhya.com/resources" },
    { name: "Tools & Calculators", url: "https://www.navdhya.com/resources/tools" }
  ]
})

interface Tool {
  id: number;
  title: string;
  description: string;
  icon: React.ReactNode;
  category: string;
  href: string;
  color: string;
}

// Tools data
const tools: Tool[] = [
  {
    id: 1,
    title: "Horoscope Calculator",
    description: "Generate your personalized horoscope based on your birth details for daily, weekly, and monthly predictions.",
    icon: <Compass className="w-6 h-6" />,
    category: "Astrology",
    href: "/resources/tools/horoscope",
    color: "bg-blue-100 text-blue-800"
  },
  {
    id: 2,
    title: "Muhurat Finder",
    description: "Find the most auspicious timings for important events like weddings, business ventures, and religious ceremonies.",
    icon: <Target className="w-6 h-6" />,
    category: "Rituals",
    href: "/resources/tools/muhurat",
    color: "bg-green-100 text-green-800"
  },
  {
    id: 3,
    title: "Panchang Calculator",
    description: "Daily Panchang with Tithi, Nakshatra, Yoga, and Karana details for spiritual planning.",
    icon: <CalendarDays className="w-6 h-6" />,
    category: "Calendar",
    href: "/resources/tools/panchang",
    color: "bg-purple-100 text-purple-800"
  },
  {
    id: 4,
    title: "Kundli Matching",
    description: "Compatibility analysis for marriages based on astrological charts and planetary positions.",
    icon: <Heart className="w-6 h-6" />,
    category: "Astrology",
    href: "/resources/tools/kundli-matching",
    color: "bg-red-100 text-red-800"
  },
  {
    id: 5,
    title: "Gemstone Recommendation",
    description: "Personalized gemstone suggestions based on your birth chart and planetary influences.",
    icon: <Star className="w-6 h-6" />,
    category: "Astrology",
    href: "/resources/tools/gemstones",
    color: "bg-yellow-100 text-yellow-800"
  },
  {
    id: 6,
    title: "Vastu Compass",
    description: "Directional analysis tool to determine the best orientations for your home and workspace.",
    icon: <Compass className="w-6 h-6" />,
    category: "Vastu",
    href: "/resources/tools/vastu",
    color: "bg-indigo-100 text-indigo-800"
  }
];

const categories = [
  "All", "Astrology", "Rituals", "Calendar", "Vastu"
];

export default function ToolsClient() {
  const toolsSchema = JSON.stringify(TOOLS_SCHEMA)
  const [categoryFilter, setCategoryFilter] = useState("All");
  
  const filteredTools = tools.filter(tool => 
    categoryFilter === "All" || tool.category === categoryFilter
  );
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: toolsSchema }}
      />
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <Link href="/" className="hover:text-foreground">Home</Link>
            <span className="mx-1.5">/</span>
            <Link href="/resources" className="hover:text-foreground">Resources</Link>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">Tools & Calculators</span>
          </nav>
        </div>

        {/* Hero Banner */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div 
            className="relative h-48 rounded-lg overflow-hidden bg-cover bg-center flex items-end"
            style={{
              backgroundImage: "url('https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_db05ba7c437e48abe6440798d2a33b09dr_bokdku.jpg')"
            }}
          >
            <div className="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
            <div className="relative z-10 text-white p-4 max-w-2xl">
              <h1 className="text-2xl font-serif font-semibold">Tools & Calculators</h1>
              <p className="text-sm mt-1">
                Horoscope, Muhurat, and Panchang calculators for determining auspicious timings and astrological insights.
              </p>
            </div>
          </div>
        </div>

        {/* Stats Bar */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div className="bg-card border border-border rounded-lg p-4 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">500K+</div>
              <div className="text-xs text-muted-foreground">Calculations Performed</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">98%</div>
              <div className="text-xs text-muted-foreground">Accuracy Rate</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">4.9/5</div>
              <div className="text-xs text-muted-foreground">User Satisfaction</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">6</div>
              <div className="text-xs text-muted-foreground">Specialized Tools</div>
            </div>
          </div>
        </div>

        {/* Category Filter */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div className="bg-card border border-border rounded-lg p-4">
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
            </div>
          </div>
        </div>

        {/* Tools Grid */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 pb-10">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {filteredTools.map(tool => (
              <Link 
                key={tool.id}
                href={tool.href}
                className="group border border-border rounded-lg overflow-hidden bg-card hover:shadow-lg transition-all duration-300 flex flex-col h-full"
              >
                <div className="p-6 flex-grow">
                  <div className={`w-12 h-12 rounded-lg ${tool.color} flex items-center justify-center mb-4`}>
                    {tool.icon}
                  </div>
                  <h3 className="font-medium text-lg mb-3 group-hover:text-[#0E3F2E] transition-colors">
                    {tool.title}
                  </h3>
                  <p className="text-sm text-muted-foreground mb-4">
                    {tool.description}
                  </p>
                  <div className="inline-block px-3 py-1 bg-muted text-foreground text-xs rounded-full">
                    {tool.category}
                  </div>
                </div>
                <div className="px-6 py-4 bg-muted/50 border-t border-border">
                  <div className="flex items-center justify-between">
                    <span className="text-sm font-medium text-[#0E3F2E]">Use Tool</span>
                    <ArrowRight className="w-4 h-4 text-muted-foreground group-hover:translate-x-1 transition-transform" />
                  </div>
                </div>
              </Link>
            ))}
          </div>
          
          {filteredTools.length === 0 && (
            <div className="text-center py-10">
              <Calculator className="w-12 h-12 text-muted-foreground mx-auto mb-4" />
              <h3 className="text-lg font-medium mb-2">No tools found</h3>
              <p className="text-muted-foreground">Try adjusting your filter criteria</p>
            </div>
          )}
        </div>

        {/* How to Use Section */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="bg-card border border-border rounded-lg p-6">
            <h2 className="text-xl font-semibold mb-4">How to Use Our Tools</h2>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="border border-border rounded-lg p-4">
                <div className="text-2xl font-bold text-[#0E3F2E] mb-2">1</div>
                <h3 className="font-medium text-sm mb-2">Enter Your Details</h3>
                <p className="text-xs text-muted-foreground">
                  Provide accurate birth information including date, time, and place for precise calculations.
                </p>
              </div>
              <div className="border border-border rounded-lg p-4">
                <div className="text-2xl font-bold text-[#0E3F2E] mb-2">2</div>
                <h3 className="font-medium text-sm mb-2">Select Your Tool</h3>
                <p className="text-xs text-muted-foreground">
                  Choose from our specialized calculators based on your specific spiritual or ritual needs.
                </p>
              </div>
              <div className="border border-border rounded-lg p-4">
                <div className="text-2xl font-bold text-[#0E3F2E] mb-2">3</div>
                <h3 className="font-medium text-sm mb-2">Get Your Results</h3>
                <p className="text-xs text-muted-foreground">
                  Receive detailed, personalized insights and recommendations for your spiritual journey.
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Testimonials */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 pb-10">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">What Our Users Say</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Hear from members of our community about how our tools have helped their spiritual practices.
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="border border-border rounded-lg p-6 bg-card">
              <div className="flex items-center gap-1 mb-4">
                {[...Array(5)].map((_, i) => (
                  <Star key={i} className="w-4 h-4 text-yellow-500 fill-current" />
                ))}
              </div>
              <p className="text-sm text-muted-foreground mb-4">
                "The Muhurat Finder helped me choose the perfect date for my daughter's wedding. The ceremony was blessed with harmony and joy!"
              </p>
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-full bg-muted flex items-center justify-center">
                  <span className="text-xs font-medium">SP</span>
                </div>
                <div>
                  <div className="text-sm font-medium">Sneha Patel</div>
                  <div className="text-xs text-muted-foreground">Wedding Planning</div>
                </div>
              </div>
            </div>
            
            <div className="border border-border rounded-lg p-6 bg-card">
              <div className="flex items-center gap-1 mb-4">
                {[...Array(5)].map((_, i) => (
                  <Star key={i} className="w-4 h-4 text-yellow-500 fill-current" />
                ))}
              </div>
              <p className="text-sm text-muted-foreground mb-4">
                "The Horoscope Calculator provides incredibly accurate daily predictions. It has become an essential part of my morning routine."
              </p>
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-full bg-muted flex items-center justify-center">
                  <span className="text-xs font-medium">RK</span>
                </div>
                <div>
                  <div className="text-sm font-medium">Rajesh Kumar</div>
                  <div className="text-xs text-muted-foreground">Daily Practice</div>
                </div>
              </div>
            </div>
            
            <div className="border border-border rounded-lg p-6 bg-card">
              <div className="flex items-center gap-1 mb-4">
                {[...Array(5)].map((_, i) => (
                  <Star key={i} className="w-4 h-4 text-yellow-500 fill-current" />
                ))}
              </div>
              <p className="text-sm text-muted-foreground mb-4">
                "The Kundli Matching tool gave us confidence in our marriage decision. The detailed analysis was eye-opening and reassuring."
              </p>
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-full bg-muted flex items-center justify-center">
                  <span className="text-xs font-medium">MP</span>
                </div>
                <div>
                  <div className="text-sm font-medium">Meera Pillai</div>
                  <div className="text-xs text-muted-foreground">Marriage Planning</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  )
}
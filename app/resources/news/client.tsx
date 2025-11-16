'use client'
import React, { useState } from 'react';
import Link from "next/link"
import Script from "next/script"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video, Search, MessageCircle, ThumbsUp, Reply, Flag, MoreHorizontal, User, Bell, Clock as ClockIcon, TrendingUp as TrendingUpIcon } from "lucide-react"
import { seoManager } from "@/lib/seo-manager"

// Schema markup for the news page
const NEWS_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "News & Updates - Navdhya",
  description: "Real-time temple information, spiritual event updates, and latest developments in our community.",
  path: "/resources/news",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Resources", url: "https://www.navdhya.com/resources" },
    { name: "News & Updates", url: "https://www.navdhya.com/resources/news" }
  ]
})

interface NewsItem {
  id: number;
  title: string;
  excerpt: string;
  date: string;
  category: string;
  source: string;
  image: string;
  isBreaking?: boolean;
  views: number;
}

// News data
const newsItems: NewsItem[] = [
  {
    id: 1,
    title: "Kedarnath Temple Reopens for Pilgrims After Winter Closure",
    excerpt: "The revered Kedarnath Temple in Uttarakhand has officially reopened its doors to pilgrims after the annual winter closure, marking the beginning of the summer pilgrimage season.",
    date: "2025-11-10",
    category: "Temple Updates",
    source: "Navdhya News Desk",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg",
    isBreaking: true,
    views: 1250
  },
  {
    id: 2,
    title: "New Online Puja Booking System Launched for Major Temples",
    excerpt: "Our enhanced digital platform now supports real-time booking for pujas at over 200 major temples across India, with instant confirmation and video streaming options.",
    date: "2025-11-08",
    category: "Platform Updates",
    source: "Technology Team",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    views: 890
  },
  {
    id: 3,
    title: "International Yoga Day 2026 Registration Now Open",
    excerpt: "Registrations are now open for our global International Yoga Day celebration on June 21, 2026, featuring renowned yoga masters from around the world.",
    date: "2025-11-05",
    category: "Events",
    source: "Events Team",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    views: 1420
  },
  {
    id: 4,
    title: "Advanced Astrology Certification Program Launching Soon",
    excerpt: "Our new advanced certification program in Vedic astrology will begin in January 2026, offering comprehensive training with industry experts.",
    date: "2025-11-03",
    category: "Education",
    source: "Learning Department",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg",
    views: 760
  },
  {
    id: 5,
    title: "Temple Safety Protocols Enhanced Following Recent Guidelines",
    excerpt: "All our partnered temples have implemented enhanced safety measures in accordance with the latest government guidelines for pilgrim safety.",
    date: "2025-11-01",
    category: "Temple Updates",
    source: "Safety & Compliance",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_db05ba7c437e48abe6440798d2a33b09dr_bokdku.jpg",
    views: 1150
  },
  {
    id: 6,
    title: "Community Volunteer Program Reaches 10,000 Participants",
    excerpt: "Our community volunteer initiative has successfully engaged over 10,000 volunteers in various spiritual and social welfare activities across the country.",
    date: "2025-10-28",
    category: "Community",
    source: "Community Team",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    views: 980
  }
];

const categories = [
  "All", "Temple Updates", "Platform Updates", "Events", "Education", "Community"
];

const sources = [
  "All Sources", "Navdhya News Desk", "Technology Team", "Events Team", "Learning Department", "Safety & Compliance", "Community Team"
];

export default function NewsClient() {
  const newsSchema = JSON.stringify(NEWS_SCHEMA)
  const [searchTerm, setSearchTerm] = useState('')
  const [categoryFilter, setCategoryFilter] = useState("All");
  const [sourceFilter, setSourceFilter] = useState("All Sources");
  
  const filteredNews = newsItems.filter(item => 
    (item.title.toLowerCase().includes(searchTerm.toLowerCase()) || 
     item.excerpt.toLowerCase().includes(searchTerm.toLowerCase()) ||
     item.source.toLowerCase().includes(searchTerm.toLowerCase())) &&
    (categoryFilter === "All" || item.category === categoryFilter) &&
    (sourceFilter === "All Sources" || item.source === sourceFilter)
  );
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: newsSchema }}
      />
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <Link href="/" className="hover:text-foreground">Home</Link>
            <span className="mx-1.5">/</span>
            <Link href="/resources" className="hover:text-foreground">Resources</Link>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">News & Updates</span>
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
              <h1 className="text-2xl font-serif font-semibold">News & Updates</h1>
              <p className="text-sm mt-1">
                Real-time temple information, spiritual event updates, and latest developments in our community.
              </p>
            </div>
          </div>
        </div>

        {/* Stats Bar */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div className="bg-card border border-border rounded-lg p-4 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">500+</div>
              <div className="text-xs text-muted-foreground">News Articles</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">25K+</div>
              <div className="text-xs text-muted-foreground">Daily Readers</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">98%</div>
              <div className="text-xs text-muted-foreground">Update Accuracy</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">12</div>
              <div className="text-xs text-muted-foreground">News Sources</div>
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
                placeholder="Search news..."
                className="w-full pl-10 pr-4 py-2 border border-border rounded-md bg-background focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </div>
            
            {/* Filters */}
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
                <div className="text-xs font-medium mb-2">Source</div>
                <div className="flex flex-wrap gap-2">
                  {sources.map((source, index) => (
                    <button
                      key={index}
                      onClick={() => setSourceFilter(source)}
                      className={`px-3 py-1.5 text-xs rounded-full transition-colors ${
                        sourceFilter === source
                          ? 'bg-[#0E3F2E] text-white' 
                          : 'bg-background hover:bg-muted border border-border text-foreground'
                      }`}
                    >
                      {source}
                    </button>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Breaking News Banner */}
        {newsItems.find(item => item.isBreaking) && (
          <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
            <div className="bg-red-50 border border-red-200 rounded-lg p-4 flex items-start gap-3">
              <Bell className="w-5 h-5 text-red-600 mt-0.5 flex-shrink-0" />
              <div>
                <div className="flex items-center gap-2 mb-1">
                  <span className="px-2 py-1 bg-red-600 text-white text-[10px] rounded-full">BREAKING</span>
                  <span className="text-xs text-red-700 font-medium">
                    {new Date(newsItems.find(item => item.isBreaking)?.date || '').toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}
                  </span>
                </div>
                <h3 className="font-medium text-red-800 mb-1">
                  {newsItems.find(item => item.isBreaking)?.title}
                </h3>
                <p className="text-sm text-red-700">
                  {newsItems.find(item => item.isBreaking)?.excerpt}
                </p>
              </div>
            </div>
          </div>
        )}

        {/* Featured News */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-8">
          <div className="bg-card border border-border rounded-lg overflow-hidden">
            <div className="border-b border-border p-4">
              <h2 className="text-lg font-semibold">Latest Updates</h2>
            </div>
            
            <div className="divide-y divide-border">
              {filteredNews.slice(0, 3).map(item => (
                <div key={item.id} className="p-4 hover:bg-muted/50 transition-colors">
                  <div className="flex gap-4">
                    <div className="bg-muted flex-shrink-0 rounded-lg overflow-hidden w-24 h-24">
                      <img 
                        src={item.image} 
                        alt={item.title}
                        className="w-full h-full object-cover"
                      />
                    </div>
                    
                    <div className="flex-grow">
                      <div className="flex items-center gap-2 mb-2">
                        {item.isBreaking && (
                          <span className="px-2 py-1 bg-red-600 text-white text-[10px] rounded-full">BREAKING</span>
                        )}
                        <span className="px-2 py-1 bg-[#0E3F2E]/10 text-[#0E3F2E] text-[10px] rounded-full">
                          {item.category}
                        </span>
                        <span className="text-[10px] text-muted-foreground">
                          {new Date(item.date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}
                        </span>
                      </div>
                      
                      <h3 className="font-medium hover:text-[#0E3F2E] transition-colors mb-2">
                        <Link href={`/resources/news/${item.id}`}>
                          {item.title}
                        </Link>
                      </h3>
                      
                      <p className="text-sm text-muted-foreground mb-3">
                        {item.excerpt}
                      </p>
                      
                      <div className="flex items-center justify-between">
                        <div className="text-xs text-muted-foreground">
                          By {item.source}
                        </div>
                        <div className="flex items-center gap-3 text-xs text-muted-foreground">
                          <div className="flex items-center gap-1">
                            <Eye className="w-3 h-3" />
                            <span>{item.views}</span>
                          </div>
                          <Link 
                            href={`/resources/news/${item.id}`}
                            className="text-[#0E3F2E] hover:underline"
                          >
                            Read More
                          </Link>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* News Grid */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 pb-10">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {filteredNews.slice(3).map(item => (
              <div key={item.id} className="border border-border rounded-lg overflow-hidden bg-card flex flex-col h-full">
                <div className="bg-muted flex-shrink-0">
                  <img 
                    src={item.image} 
                    alt={item.title}
                    className="w-full h-40 object-cover"
                  />
                </div>
                
                <div className="p-4 space-y-3 flex flex-col flex-grow">
                  <div>
                    <div className="flex items-center justify-between mb-2">
                      {item.isBreaking && (
                        <span className="px-2 py-1 bg-red-600 text-white text-[10px] rounded-full">BREAKING</span>
                      )}
                      <span className="px-2 py-1 bg-[#0E3F2E]/10 text-[#0E3F2E] text-[10px] rounded-full">
                        {item.category}
                      </span>
                    </div>
                    <h3 className="font-medium hover:text-[#0E3F2E] transition-colors line-clamp-2 text-sm">
                      {item.title}
                    </h3>
                  </div>
                  
                  <p className="text-[11px] text-muted-foreground line-clamp-3 flex-grow">
                    {item.excerpt}
                  </p>
                  
                  <div className="flex items-center justify-between text-[10px] text-muted-foreground pt-2">
                    <div>
                      <div className="font-medium">{item.source}</div>
                      <div>{new Date(item.date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}</div>
                    </div>
                    
                    <div className="flex items-center gap-2">
                      <div className="flex items-center gap-1">
                        <Eye className="w-3 h-3" />
                        <span>{item.views}</span>
                      </div>
                    </div>
                  </div>
                  
                  <Link 
                    href={`/resources/news/${item.id}`}
                    className="mt-2 inline-flex items-center justify-center gap-1 w-full bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white font-medium py-2 px-3 rounded-md transition-all duration-300 text-center text-[11px]"
                  >
                    <span>Read Full Story</span>
                    <ArrowRight className="w-3 h-3" />
                  </Link>
                </div>
              </div>
            ))}
          </div>
          
          {filteredNews.length === 0 && (
            <div className="text-center py-10">
              <Bell className="w-12 h-12 text-muted-foreground mx-auto mb-4" />
              <h3 className="text-lg font-medium mb-2">No news found</h3>
              <p className="text-muted-foreground">Try adjusting your search or filter criteria</p>
            </div>
          )}
        </div>
      </div>
    </>
  )
}
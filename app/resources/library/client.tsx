'use client'
import React, { useState } from 'react';
import Link from "next/link"
import Script from "next/script"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video, Search, MessageCircle, ThumbsUp, Reply, Flag, MoreHorizontal, User, Download, BookMarked, AudioLines, File } from "lucide-react"
import { seoManager } from "@/lib/seo-manager"

// Schema markup for the digital library page
const DIGITAL_LIBRARY_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Digital Library - Navdhya",
  description: "Downloadable wisdom including spiritual texts, guided meditations, ritual instructions, and educational resources.",
  path: "/resources/library",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Resources", url: "https://www.navdhya.com/resources" },
    { name: "Digital Library", url: "https://www.navdhya.com/resources/library" }
  ]
})

interface LibraryItem {
  id: number;
  title: string;
  excerpt: string;
  author: string;
  date: string;
  type: string;
  category: string;
  duration?: string;
  pages?: number;
  fileSize?: string;
  image: string;
  downloads: number;
}

// Library items data
const libraryItems: LibraryItem[] = [
  {
    id: 1,
    title: "Complete Guide to Vedic Rituals",
    excerpt: "A comprehensive handbook covering the essential principles and practices of Vedic rituals for beginners and intermediate practitioners.",
    author: "Pandit Rajesh Sharma",
    date: "2025-10-15",
    type: "PDF",
    category: "Rituals",
    pages: 120,
    fileSize: "4.2 MB",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    downloads: 1250
  },
  {
    id: 2,
    title: "Meditation for Inner Peace - Guided Sessions",
    excerpt: "Step-by-step guided meditation practices to achieve mental clarity and emotional balance in daily life.",
    author: "Swami Anand",
    date: "2025-10-10",
    type: "Audio",
    category: "Meditation",
    duration: "60 mins",
    fileSize: "85 MB",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    downloads: 2100
  },
  {
    id: 3,
    title: "Understanding Your Kundli",
    excerpt: "Learn how to read and interpret your birth chart with this detailed guide to Vedic astrology fundamentals.",
    author: "Pandit Ramesh Gupta",
    date: "2025-10-05",
    type: "PDF",
    category: "Astrology",
    pages: 85,
    fileSize: "3.1 MB",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg",
    downloads: 1800
  },
  {
    id: 4,
    title: "Sacred Mantras and Their Meanings",
    excerpt: "A collection of powerful Vedic mantras with proper pronunciation guides and explanations of their spiritual significance.",
    author: "Swami Prema",
    date: "2025-09-28",
    type: "Audio",
    category: "Mantras",
    duration: "45 mins",
    fileSize: "65 MB",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_db05ba7c437e48abe6440798d2a33b09dr_bokdku.jpg",
    downloads: 1500
  },
  {
    id: 5,
    title: "Vastu Principles for Modern Homes",
    excerpt: "Practical Vastu tips and guidelines to create harmonious living spaces that promote health, wealth, and happiness.",
    author: "Dr. Priya Sharma",
    date: "2025-09-20",
    type: "PDF",
    category: "Vastu",
    pages: 68,
    fileSize: "2.8 MB",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg",
    downloads: 1650
  },
  {
    id: 6,
    title: "Bhagavad Gita Chapter Summaries",
    excerpt: "Concise summaries and key teachings from all 18 chapters of the Bhagavad Gita for easy reference and study.",
    author: "Prof. Ramesh Iyer",
    date: "2025-09-15",
    type: "PDF",
    category: "Philosophy",
    pages: 92,
    fileSize: "3.5 MB",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    downloads: 2200
  },
  {
    id: 7,
    title: "Festival Rituals and Traditions",
    excerpt: "Detailed guides to performing rituals for major Hindu festivals including Diwali, Navratri, and Dussehra.",
    author: "Pandit Suresh Patel",
    date: "2025-09-10",
    type: "Video",
    category: "Festivals",
    duration: "75 mins",
    fileSize: "450 MB",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg",
    downloads: 1400
  },
  {
    id: 8,
    title: "Yoga Philosophy and Practice",
    excerpt: "Explore the philosophical foundations of yoga and learn basic asanas for physical and spiritual well-being.",
    author: "Swami Anand",
    date: "2025-09-05",
    type: "Video",
    category: "Yoga",
    duration: "50 mins",
    fileSize: "320 MB",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    downloads: 1300
  }
];

const categories = [
  "All", "Rituals", "Meditation", "Astrology", "Mantras", "Vastu", "Philosophy", "Festivals", "Yoga"
];

const types = [
  "All Types", "PDF", "Video", "Audio"
];

export default function DigitalLibraryClient() {
  const digitalLibrarySchema = JSON.stringify(DIGITAL_LIBRARY_SCHEMA)
  const [searchTerm, setSearchTerm] = useState('')
  const [categoryFilter, setCategoryFilter] = useState("All");
  const [typeFilter, setTypeFilter] = useState("All Types");
  
  const filteredItems = libraryItems.filter(item => 
    (item.title.toLowerCase().includes(searchTerm.toLowerCase()) || 
     item.excerpt.toLowerCase().includes(searchTerm.toLowerCase()) ||
     item.author.toLowerCase().includes(searchTerm.toLowerCase())) &&
    (categoryFilter === "All" || item.category === categoryFilter) &&
    (typeFilter === "All Types" || item.type === typeFilter)
  );
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: digitalLibrarySchema }}
      />
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <Link href="/" className="hover:text-foreground">Home</Link>
            <span className="mx-1.5">/</span>
            <Link href="/resources" className="hover:text-foreground">Resources</Link>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">Digital Library</span>
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
              <h1 className="text-2xl font-serif font-semibold">Digital Library</h1>
              <p className="text-sm mt-1">
                Downloadable wisdom including spiritual texts, guided meditations, ritual instructions, and educational resources.
              </p>
            </div>
          </div>
        </div>

        {/* Stats Bar */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div className="bg-card border border-border rounded-lg p-4 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">200+</div>
              <div className="text-xs text-muted-foreground">Resources Available</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">75K+</div>
              <div className="text-xs text-muted-foreground">Total Downloads</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">4.8/5</div>
              <div className="text-xs text-muted-foreground">Average Rating</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">95%</div>
              <div className="text-xs text-muted-foreground">Satisfaction Rate</div>
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
                placeholder="Search library..."
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
                <div className="text-xs font-medium mb-2">Type</div>
                <div className="flex flex-wrap gap-2">
                  {types.map((type, index) => (
                    <button
                      key={index}
                      onClick={() => setTypeFilter(type)}
                      className={`px-3 py-1.5 text-xs rounded-full transition-colors ${
                        typeFilter === type
                          ? 'bg-[#0E3F2E] text-white' 
                          : 'bg-background hover:bg-muted border border-border text-foreground'
                      }`}
                    >
                      {type}
                    </button>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Library Grid */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 pb-10">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            {filteredItems.map(item => (
              <div key={item.id} className="group border border-border rounded-lg overflow-hidden bg-card flex flex-col h-full">
                <div className="relative bg-muted flex-shrink-0">
                  <img 
                    src={item.image} 
                    alt={item.title}
                    className="w-full h-40 object-cover group-hover:scale-105 transition-transform duration-300"
                  />
                  <div className="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                  
                  <div className="absolute top-2 left-2">
                    <span className="px-2 py-1 bg-[#0E3F2E]/80 text-white text-[10px] rounded-full">
                      {item.category}
                    </span>
                  </div>
                  
                  <div className="absolute top-2 right-2">
                    <span className="px-2 py-1 bg-background/80 text-foreground text-[10px] rounded-full">
                      {item.type}
                    </span>
                  </div>
                </div>
                
                <div className="p-4 space-y-3 flex flex-col flex-grow">
                  <h3 className="font-medium hover:text-[#0E3F2E] transition-colors line-clamp-2 text-sm flex-grow">
                    {item.title}
                  </h3>
                  
                  <p className="text-[11px] text-muted-foreground line-clamp-3">
                    {item.excerpt}
                  </p>
                  
                  <div className="flex items-center justify-between text-[10px] text-muted-foreground">
                    <div className="flex items-center gap-2">
                      <div className="w-6 h-6 rounded-full bg-muted flex items-center justify-center">
                        <span className="text-[8px] font-medium">
                          {item.author.split(' ').map(n => n[0]).join('')}
                        </span>
                      </div>
                      <div>
                        <div className="font-medium">{item.author}</div>
                        <div>{new Date(item.date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}</div>
                      </div>
                    </div>
                  </div>
                  
                  <div className="flex items-center justify-between pt-2">
                    <div className="flex items-center gap-1 text-[10px] text-muted-foreground">
                      {item.duration && (
                        <div className="flex items-center gap-1">
                          <Clock className="w-3 h-3" />
                          <span>{item.duration}</span>
                        </div>
                      )}
                      {item.pages && (
                        <div className="flex items-center gap-1">
                          <FileText className="w-3 h-3" />
                          <span>{item.pages} pages</span>
                        </div>
                      )}
                      {item.fileSize && (
                        <div className="flex items-center gap-1">
                          <Download className="w-3 h-3" />
                          <span>{item.fileSize}</span>
                        </div>
                      )}
                    </div>
                    <div className="flex items-center gap-1 text-[10px] text-muted-foreground">
                      <Download className="w-3 h-3" />
                      <span>{item.downloads}</span>
                    </div>
                  </div>
                  
                  <button 
                    className="mt-2 inline-flex items-center justify-center gap-1 w-full bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white font-medium py-2 px-3 rounded-md transition-all duration-300 text-center text-[11px]"
                  >
                    <Download className="w-3 h-3" />
                    <span>{item.type === 'Video' ? 'Watch' : item.type === 'Audio' ? 'Listen' : 'Download'}</span>
                  </button>
                </div>
              </div>
            ))}
          </div>
          
          {filteredItems.length === 0 && (
            <div className="text-center py-10">
              <BookMarked className="w-12 h-12 text-muted-foreground mx-auto mb-4" />
              <h3 className="text-lg font-medium mb-2">No resources found</h3>
              <p className="text-muted-foreground">Try adjusting your search or filter criteria</p>
            </div>
          )}
        </div>
      </div>
    </>
  )
}
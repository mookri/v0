'use client'

import React, { useState } from 'react';
import Link from "next/link"
import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video, Search, MessageCircle, ThumbsUp, Reply, Flag, MoreHorizontal, User, Image, Camera, Grid, List, Download } from "lucide-react"

// Schema markup for the gallery page
const GALLERY_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Gallery - Navdhya",
  description: "Explore our collection of spiritual events, rituals, and community gatherings through photos and videos.",
  path: "/gallery",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Gallery", url: "https://www.navdhya.com/gallery" }
  ]
})

interface GalleryItem {
  id: number;
  title: string;
  description: string;
  type: 'photo' | 'video';
  category: string;
  date: string;
  image: string;
  likes: number;
  views: number;
}

// Gallery items data
const galleryItems: GalleryItem[] = [
  {
    id: 1,
    title: "Diwali Celebration at Kedarnath Temple",
    description: "Festive lights and decorations during Diwali at one of the holiest Hindu temples.",
    type: 'photo',
    category: 'Festivals',
    date: "2025-10-25",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg",
    likes: 1250,
    views: 3200
  },
  {
    id: 2,
    title: "Vedic Ritual Performance",
    description: "Pandit performing a traditional Vedic ritual with sacred fire.",
    type: 'photo',
    category: 'Rituals',
    date: "2025-10-20",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_db05ba7c437e48abe6440798d2a33b09dr_bokdku.jpg",
    likes: 890,
    views: 2100
  },
  {
    id: 3,
    title: "Community Yoga Session",
    description: "Group yoga practice at our community center in Bangalore.",
    type: 'photo',
    category: 'Community',
    date: "2025-10-15",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    likes: 1120,
    views: 2800
  },
  {
    id: 4,
    title: "Temple Architecture Tour",
    description: "Exploring the intricate carvings and architecture of ancient temples.",
    type: 'video',
    category: 'Temples',
    date: "2025-10-10",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg",
    likes: 1450,
    views: 4200
  },
  {
    id: 5,
    title: "Astrology Workshop",
    description: "Expert session on Vedic astrology and birth chart analysis.",
    type: 'photo',
    category: 'Education',
    date: "2025-10-05",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    likes: 890,
    views: 1900
  },
  {
    id: 6,
    title: "Havan Ceremony",
    description: "Sacred fire ritual performed during a community gathering.",
    type: 'video',
    category: 'Rituals',
    date: "2025-09-30",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    likes: 1320,
    views: 3500
  }
];

const categories = [
  "All", "Festivals", "Rituals", "Community", "Temples", "Education"
];

const types = [
  "All Types", "Photos", "Videos"
];

export default function GalleryPage() {
  const gallerySchema = JSON.stringify(GALLERY_SCHEMA)
  const [searchTerm, setSearchTerm] = useState('')
  const [categoryFilter, setCategoryFilter] = useState("All");
  const [typeFilter, setTypeFilter] = useState("All Types");
  const [viewMode, setViewMode] = useState<'grid' | 'list'>('grid');
  
  const filteredItems = galleryItems.filter(item => 
    (item.title.toLowerCase().includes(searchTerm.toLowerCase()) || 
     item.description.toLowerCase().includes(searchTerm.toLowerCase())) &&
    (categoryFilter === "All" || item.category === categoryFilter) &&
    (typeFilter === "All Types" || (typeFilter === "Photos" && item.type === "photo") || (typeFilter === "Videos" && item.type === "video"))
  );
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: gallerySchema }}
      />
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <Link href="/" className="hover:text-foreground">Home</Link>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">Gallery</span>
          </nav>
        </div>

        {/* Hero Banner */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div 
            className="relative h-64 rounded-lg overflow-hidden bg-cover bg-center flex items-center"
            style={{
              backgroundImage: "url('https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg')"
            }}
          >
            <div className="absolute inset-0 bg-gradient-to-r from-black/80 to-black/40"></div>
            <div className="relative z-10 text-white p-8 max-w-3xl">
              <h1 className="text-3xl font-serif font-semibold mb-4">Gallery</h1>
              <p className="text-lg mb-6">
                Explore our collection of spiritual events, rituals, and community gatherings through photos and videos.
              </p>
            </div>
          </div>
        </div>

        {/* Stats Bar */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div className="bg-card border border-border rounded-lg p-4 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">150+</div>
              <div className="text-xs text-muted-foreground">Media Items</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">50K+</div>
              <div className="text-xs text-muted-foreground">Total Views</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">4.8/5</div>
              <div className="text-xs text-muted-foreground">Average Rating</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">12</div>
              <div className="text-xs text-muted-foreground">Categories</div>
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
                placeholder="Search gallery..."
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
              
              <div className="ml-auto flex items-center gap-2">
                <button
                  onClick={() => setViewMode('grid')}
                  className={`p-2 rounded-md ${
                    viewMode === 'grid' 
                      ? 'bg-[#0E3F2E] text-white' 
                      : 'bg-background hover:bg-muted border border-border text-foreground'
                  }`}
                >
                  <Grid className="w-4 h-4" />
                </button>
                <button
                  onClick={() => setViewMode('list')}
                  className={`p-2 rounded-md ${
                    viewMode === 'list' 
                      ? 'bg-[#0E3F2E] text-white' 
                      : 'bg-background hover:bg-muted border border-border text-foreground'
                  }`}
                >
                  <List className="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>

        {/* Gallery Grid/List */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 pb-10">
          {viewMode === 'grid' ? (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
              {filteredItems.map(item => (
                <div key={item.id} className="group border border-border rounded-lg overflow-hidden bg-card flex flex-col h-full">
                  <div className="relative bg-muted flex-shrink-0">
                    <img 
                      src={item.image} 
                      alt={item.title}
                      className="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300"
                    />
                    <div className="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                    
                    <div className="absolute top-2 left-2">
                      <span className="px-2 py-1 bg-[#0E3F2E]/80 text-white text-[10px] rounded-full">
                        {item.category}
                      </span>
                    </div>
                    
                    <div className="absolute top-2 right-2">
                      <span className="px-2 py-1 bg-background/80 text-foreground text-[10px] rounded-full">
                        {item.type === 'photo' ? 'Photo' : 'Video'}
                      </span>
                    </div>
                    
                    {item.type === 'video' && (
                      <div className="absolute inset-0 flex items-center justify-center">
                        <div className="w-12 h-12 rounded-full bg-[#0E3F2E]/80 flex items-center justify-center">
                          <Play className="w-5 h-5 text-white ml-1" />
                        </div>
                      </div>
                    )}
                  </div>
                  
                  <div className="p-4 space-y-3 flex flex-col flex-grow">
                    <h3 className="font-medium hover:text-[#0E3F2E] transition-colors line-clamp-2 text-sm flex-grow">
                      {item.title}
                    </h3>
                    
                    <p className="text-[11px] text-muted-foreground line-clamp-2">
                      {item.description}
                    </p>
                    
                    <div className="flex items-center justify-between text-[10px] text-muted-foreground">
                      <div>
                        {new Date(item.date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}
                      </div>
                      
                      <div className="flex items-center gap-2">
                        <div className="flex items-center gap-1">
                          <Eye className="w-3 h-3" />
                          <span>{item.views}</span>
                        </div>
                        <div className="flex items-center gap-1">
                          <Heart className="w-3 h-3" />
                          <span>{item.likes}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="border border-border rounded-lg overflow-hidden bg-card">
              {filteredItems.map(item => (
                <div key={item.id} className="border-b border-border last:border-b-0 p-4 hover:bg-muted/50 transition-colors">
                  <div className="flex gap-4">
                    <div className="bg-muted flex-shrink-0 rounded-lg overflow-hidden w-24 h-24 relative">
                      <img 
                        src={item.image} 
                        alt={item.title}
                        className="w-full h-full object-cover"
                      />
                      {item.type === 'video' && (
                        <div className="absolute inset-0 flex items-center justify-center">
                          <div className="w-8 h-8 rounded-full bg-[#0E3F2E]/80 flex items-center justify-center">
                            <Play className="w-3 h-3 text-white ml-0.5" />
                          </div>
                        </div>
                      )}
                    </div>
                    
                    <div className="flex-grow">
                      <div className="flex items-center gap-2 mb-2">
                        <span className="px-2 py-1 bg-[#0E3F2E]/10 text-[#0E3F2E] text-[10px] rounded-full">
                          {item.category}
                        </span>
                        <span className="px-2 py-1 bg-background text-foreground text-[10px] rounded-full border border-border">
                          {item.type === 'photo' ? 'Photo' : 'Video'}
                        </span>
                        <span className="text-[10px] text-muted-foreground">
                          {new Date(item.date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}
                        </span>
                      </div>
                      
                      <h3 className="font-medium hover:text-[#0E3F2E] transition-colors mb-2">
                        {item.title}
                      </h3>
                      
                      <p className="text-sm text-muted-foreground mb-3">
                        {item.description}
                      </p>
                      
                      <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3 text-xs text-muted-foreground">
                          <div className="flex items-center gap-1">
                            <Eye className="w-3 h-3" />
                            <span>{item.views}</span>
                          </div>
                          <div className="flex items-center gap-1">
                            <Heart className="w-3 h-3" />
                            <span>{item.likes}</span>
                          </div>
                        </div>
                        
                        <button className="px-3 py-1.5 bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white text-xs rounded-md transition-colors flex items-center gap-1">
                          <Download className="w-3 h-3" />
                          <span>Download</span>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
          
          {filteredItems.length === 0 && (
            <div className="text-center py-10">
              <Image className="w-12 h-12 text-muted-foreground mx-auto mb-4" />
              <h3 className="text-lg font-medium mb-2">No media found</h3>
              <p className="text-muted-foreground">Try adjusting your search or filter criteria</p>
            </div>
          )}
        </div>
      </div>
    </>
  )
}
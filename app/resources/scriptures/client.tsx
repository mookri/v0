'use client'
import React, { useState } from 'react';
import Link from "next/link"
import Script from "next/script"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video, Search, MessageCircle, ThumbsUp, Reply, Flag, MoreHorizontal, User, BookMarked, FileText as FileTextIcon, Download } from "lucide-react"
import { seoManager } from "@/lib/seo-manager"

// Schema markup for the scriptures page
const SCRIPTURES_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Scriptures & Texts - Navdhya",
  description: "Rich spiritual library of ancient texts, scriptures, and sacred writings with translations and commentary.",
  path: "/resources/scriptures",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Resources", url: "https://www.navdhya.com/resources" },
    { name: "Scriptures & Texts", url: "https://www.navdhya.com/resources/scriptures" }
  ]
})

interface Scripture {
  id: number;
  title: string;
  excerpt: string;
  author: string;
  language: string;
  type: string;
  category: string;
  pages?: number;
  fileSize?: string;
  image: string;
  downloads: number;
}

// Scriptures data
const scriptures: Scripture[] = [
  {
    id: 1,
    title: "Bhagavad Gita - Original Sanskrit with Translations",
    excerpt: "The complete text of the Bhagavad Gita in Sanskrit with word-by-word translations and philosophical commentary.",
    author: "Vyasa",
    language: "Sanskrit/English",
    type: "PDF",
    category: "Vedas & Upanishads",
    pages: 180,
    fileSize: "4.2 MB",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    downloads: 2500
  },
  {
    id: 2,
    title: "Rig Veda - Sacred Hymns and Mantras",
    excerpt: "Collection of the oldest sacred hymns of Hinduism with detailed explanations of their spiritual significance.",
    author: "Multiple Rishis",
    language: "Sanskrit/English",
    type: "PDF",
    category: "Vedas & Upanishads",
    pages: 320,
    fileSize: "6.8 MB",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    downloads: 1800
  },
  {
    id: 3,
    title: "Upanishads - Philosophical Teachings",
    excerpt: "Essential Upanishads with translations and interpretations by renowned scholars and spiritual teachers.",
    author: "Various",
    language: "Sanskrit/English",
    type: "PDF",
    category: "Vedas & Upanishads",
    pages: 240,
    fileSize: "5.1 MB",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg",
    downloads: 2100
  },
  {
    id: 4,
    title: "Ramayana - Epic of Lord Rama",
    excerpt: "Complete retelling of the Ramayana with moral lessons and spiritual insights for modern readers.",
    author: "Valmiki",
    language: "Sanskrit/English",
    type: "PDF",
    category: "Epics",
    pages: 420,
    fileSize: "8.5 MB",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_db05ba7c437e48abe6440798d2a33b09dr_bokdku.jpg",
    downloads: 3200
  },
  {
    id: 5,
    title: "Mahabharata - The Great Epic",
    excerpt: "Selected chapters from the Mahabharata focusing on the Bhagavad Gita and other philosophical teachings.",
    author: "Vyasa",
    language: "Sanskrit/English",
    type: "PDF",
    category: "Epics",
    pages: 580,
    fileSize: "12.3 MB",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg",
    downloads: 2800
  },
  {
    id: 6,
    title: "Yoga Sutras of Patanjali",
    excerpt: "Classical text on yoga philosophy and practice with detailed commentary by modern yoga masters.",
    author: "Patanjali",
    language: "Sanskrit/English",
    type: "PDF",
    category: "Philosophy",
    pages: 120,
    fileSize: "2.9 MB",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    downloads: 1900
  }
];

const categories = [
  "All", "Vedas & Upanishads", "Epics", "Philosophy", "Puranas", "Tantras"
];

const languages = [
  "All Languages", "Sanskrit", "English", "Hindi", "Tamil", "Telugu"
];

export default function ScripturesClient() {
  const [searchTerm, setSearchTerm] = useState('')
  const [categoryFilter, setCategoryFilter] = useState("All");
  const [languageFilter, setLanguageFilter] = useState("All Languages");
  
  const filteredScriptures = scriptures.filter(scripture => 
    (scripture.title.toLowerCase().includes(searchTerm.toLowerCase()) || 
     scripture.excerpt.toLowerCase().includes(searchTerm.toLowerCase()) ||
     scripture.author.toLowerCase().includes(searchTerm.toLowerCase())) &&
    (categoryFilter === "All" || scripture.category === categoryFilter) &&
    (languageFilter === "All Languages" || scripture.language.includes(languageFilter))
  );
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify({
          "@context": "https://schema.org",
          "@type": "CollectionPage",
          "name": "Scriptures & Texts - Navdhya",
          "description": "Rich spiritual library of ancient texts, scriptures, and sacred writings with translations and commentary.",
          "url": "https://www.navdhya.com/resources/scriptures",
          "breadcrumb": {
            "@type": "BreadcrumbList",
            "itemListElement": [
              { "@type": "ListItem", "position": 1, "name": "Home", "item": "https://www.navdhya.com" },
              { "@type": "ListItem", "position": 2, "name": "Resources", "item": "https://www.navdhya.com/resources" },
              { "@type": "ListItem", "position": 3, "name": "Scriptures & Texts", "item": "https://www.navdhya.com/resources/scriptures" }
            ]
          }
        }) }}
      />
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <Link href="/" className="hover:text-foreground">Home</Link>
            <span className="mx-1.5">/</span>
            <Link href="/resources" className="hover:text-foreground">Resources</Link>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">Scriptures & Texts</span>
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
              <h1 className="text-2xl font-serif font-semibold">Scriptures & Texts</h1>
              <p className="text-sm mt-1">
                Rich spiritual library of ancient texts, scriptures, and sacred writings with translations and commentary.
              </p>
            </div>
          </div>
        </div>

        {/* Stats Bar */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div className="bg-card border border-border rounded-lg p-4 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">75+</div>
              <div className="text-xs text-muted-foreground">Sacred Texts</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">15K+</div>
              <div className="text-xs text-muted-foreground">Total Downloads</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">4.9/5</div>
              <div className="text-xs text-muted-foreground">Average Rating</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">12</div>
              <div className="text-xs text-muted-foreground">Languages</div>
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
                placeholder="Search scriptures..."
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
                <div className="text-xs font-medium mb-2">Language</div>
                <div className="flex flex-wrap gap-2">
                  {languages.map((language, index) => (
                    <button
                      key={index}
                      onClick={() => setLanguageFilter(language)}
                      className={`px-3 py-1.5 text-xs rounded-full transition-colors ${
                        languageFilter === language
                          ? 'bg-[#0E3F2E] text-white' 
                          : 'bg-background hover:bg-muted border border-border text-foreground'
                      }`}
                    >
                      {language}
                    </button>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Featured Scripture */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-8">
          <div className="bg-card border border-border rounded-lg overflow-hidden">
            <div className="border-b border-border p-4">
              <h2 className="text-lg font-semibold">Featured Scripture</h2>
            </div>
            
            {scriptures[0] && (
              <div className="p-6">
                <div className="flex flex-col lg:flex-row gap-6">
                  <div className="lg:w-2/3">
                    <div className="flex items-center gap-2 mb-3">
                      <span className="px-2 py-1 bg-[#0E3F2E]/10 text-[#0E3F2E] text-[10px] rounded-full">
                        {scriptures[0].category}
                      </span>
                      <span className="px-2 py-1 bg-background text-foreground text-[10px] rounded-full border border-border">
                        {scriptures[0].language}
                      </span>
                    </div>
                    
                    <h3 className="text-xl font-semibold mb-3">
                      {scriptures[0].title}
                    </h3>
                    <p className="text-muted-foreground mb-4">
                      {scriptures[0].excerpt}
                    </p>
                    
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-full bg-muted flex items-center justify-center">
                          <span className="text-[8px] font-medium">
                            {scriptures[0].author.split(' ').map(n => n[0]).join('')}
                          </span>
                        </div>
                        <div>
                          <div className="font-medium text-sm">{scriptures[0].author}</div>
                        </div>
                      </div>
                      
                      <div className="flex items-center gap-4 text-sm text-muted-foreground">
                        <div className="flex items-center gap-1">
                          <FileTextIcon className="w-4 h-4" />
                          <span>{scriptures[0].pages} pages</span>
                        </div>
                        <div className="flex items-center gap-1">
                          <Download className="w-4 h-4" />
                          <span>{scriptures[0].downloads} downloads</span>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <div className="lg:w-1/3">
                    <div className="bg-muted rounded-lg h-full overflow-hidden">
                      <img 
                        src={scriptures[0].image} 
                        alt={scriptures[0].title}
                        className="w-full h-full object-cover"
                      />
                    </div>
                  </div>
                </div>
                
                <div className="mt-6 flex gap-3">
                  <button className="px-4 py-2 bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white rounded-md text-sm font-medium transition-colors flex items-center gap-2">
                    <Download className="w-4 h-4" />
                    <span>Download PDF</span>
                  </button>
                  <button className="px-4 py-2 border border-border rounded-md text-sm font-medium hover:bg-muted transition-colors flex items-center gap-2">
                    <BookOpen className="w-4 h-4" />
                    <span>Read Online</span>
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>

        {/* Scriptures Grid */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 pb-10">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {filteredScriptures.filter((_, index) => index > 0).map(scripture => (
              <div key={scripture.id} className="border border-border rounded-lg overflow-hidden bg-card flex flex-col h-full">
                <div className="bg-muted flex-shrink-0">
                  <img 
                    src={scripture.image} 
                    alt={scripture.title}
                    className="w-full h-40 object-cover"
                  />
                </div>
                
                <div className="p-4 space-y-3 flex flex-col flex-grow">
                  <div>
                    <div className="flex items-center justify-between mb-2">
                      <span className="px-2 py-1 bg-[#0E3F2E]/10 text-[#0E3F2E] text-[10px] rounded-full">
                        {scripture.category}
                      </span>
                      <span className="px-2 py-1 bg-background text-foreground text-[10px] rounded-full border border-border">
                        {scripture.type}
                      </span>
                    </div>
                    <h3 className="font-medium hover:text-[#0E3F2E] transition-colors line-clamp-2 text-sm">
                      {scripture.title}
                    </h3>
                  </div>
                  
                  <p className="text-[11px] text-muted-foreground line-clamp-3 flex-grow">
                    {scripture.excerpt}
                  </p>
                  
                  <div className="flex items-center justify-between text-[10px] text-muted-foreground">
                    <div className="flex items-center gap-2">
                      <div className="w-6 h-6 rounded-full bg-muted flex items-center justify-center">
                        <span className="text-[8px] font-medium">
                          {scripture.author.split(' ').map(n => n[0]).join('')}
                        </span>
                      </div>
                      <div>
                        <div className="font-medium">{scripture.author}</div>
                        <div>{scripture.language}</div>
                      </div>
                    </div>
                    
                    <div className="flex items-center gap-2">
                      {scripture.pages && (
                        <div className="flex items-center gap-1">
                          <FileTextIcon className="w-3 h-3" />
                          <span>{scripture.pages}p</span>
                        </div>
                      )}
                      <div className="flex items-center gap-1">
                        <Download className="w-3 h-3" />
                        <span>{scripture.downloads}</span>
                      </div>
                    </div>
                  </div>
                  
                  <div className="flex gap-2 pt-2">
                    <Link 
                      href={`/resources/scriptures/${scripture.id}`}
                      className="flex-1 inline-flex items-center justify-center gap-1 bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white font-medium py-1.5 px-3 rounded-md transition-all duration-300 text-center text-[11px]"
                    >
                      <span>Read</span>
                      <ArrowRight className="w-3 h-3" />
                    </Link>
                    <button className="px-3 py-1.5 border border-border rounded-md text-[11px] font-medium hover:bg-muted transition-colors flex items-center gap-1">
                      <Download className="w-3 h-3" />
                      <span>DL</span>
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>
          
          {filteredScriptures.filter((_, index) => index > 0).length === 0 && (
            <div className="text-center py-10">
              <BookMarked className="w-12 h-12 text-muted-foreground mx-auto mb-4" />
              <h3 className="text-lg font-medium mb-2">No scriptures found</h3>
              <p className="text-muted-foreground">Try adjusting your search or filter criteria</p>
            </div>
          )}
        </div>
      </div>
    </>
  )
}
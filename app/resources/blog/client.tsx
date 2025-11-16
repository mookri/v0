'use client'
import React, { useState } from 'react';
import Link from "next/link"
import Script from "next/script"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video, Search, MessageCircle, ThumbsUp, Reply, Flag, MoreHorizontal, User, Tag, Clock as ClockIcon } from "lucide-react"

interface BlogPost {
  id: number;
  title: string;
  excerpt: string;
  author: string;
  date: string;
  readTime: string;
  category: string;
  tags: string[];
  image: string;
  views: number;
  likes: number;
}

// Blog posts data
const blogPosts: BlogPost[] = [
  {
    id: 1,
    title: "Understanding the Significance of Diwali: Beyond the Lights",
    excerpt: "Explore the deeper spiritual meaning behind Diwali celebrations and how this festival of lights can illuminate your inner journey.",
    author: "Dr. Priya Sharma",
    date: "2025-10-25",
    readTime: "8 min read",
    category: "Festivals",
    tags: ["Diwali", "Spirituality", "Traditions"],
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg",
    views: 1250,
    likes: 89
  },
  {
    id: 2,
    title: "The Science Behind Mantra Chanting: Vibration and Consciousness",
    excerpt: "Discover the scientific basis of how sacred sounds affect our mind, body, and spiritual consciousness.",
    author: "Swami Anand",
    date: "2025-10-20",
    readTime: "10 min read",
    category: "Mantras",
    tags: ["Mantras", "Science", "Consciousness"],
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_db05ba7c437e48abe6440798d2a33b09dr_bokdku.jpg",
    views: 980,
    likes: 124
  },
  {
    id: 3,
    title: "Vastu Shastra for Modern Homes: Practical Applications",
    excerpt: "Learn how to apply ancient Vastu principles to contemporary living spaces for harmony and prosperity.",
    author: "Pandit Ramesh Gupta",
    date: "2025-10-15",
    readTime: "12 min read",
    category: "Vastu",
    tags: ["Vastu", "Home", "Harmony"],
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    views: 1120,
    likes: 76
  },
  {
    id: 4,
    title: "The Bhagavad Gita's Teachings on Karma Yoga: Path of Selfless Action",
    excerpt: "Understanding the concept of performing actions without attachment to results as a path to spiritual liberation.",
    author: "Prof. Ramesh Iyer",
    date: "2025-10-10",
    readTime: "15 min read",
    category: "Philosophy",
    tags: ["Bhagavad Gita", "Karma Yoga", "Philosophy"],
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    views: 1450,
    likes: 156
  },
  {
    id: 5,
    title: "Astrology Basics: Reading Your Birth Chart for Self-Awareness",
    excerpt: "A beginner's guide to understanding the key components of a Vedic birth chart and its insights into personality traits.",
    author: "Dr. Mehta",
    date: "2025-10-05",
    readTime: "11 min read",
    category: "Astrology",
    tags: ["Astrology", "Birth Chart", "Self-Awareness"],
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg",
    views: 890,
    likes: 67
  },
  {
    id: 6,
    title: "Meditation Techniques for Stress Relief in Modern Life",
    excerpt: "Simple yet effective meditation practices that can be integrated into busy schedules for mental peace and clarity.",
    author: "Swami Prema",
    date: "2025-09-30",
    readTime: "7 min read",
    category: "Meditation",
    tags: ["Meditation", "Stress Relief", "Mindfulness"],
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    views: 1320,
    likes: 98
  }
];

const categories = [
  "All", "Festivals", "Mantras", "Vastu", "Philosophy", "Astrology", "Meditation", "Rituals"
];

const popularTags = [
  "Spirituality", "Traditions", "Science", "Consciousness", "Home", "Harmony", "Karma Yoga", "Philosophy", "Birth Chart", "Self-Awareness", "Stress Relief", "Mindfulness"
];

export default function BlogClient({ blogSchema }: { blogSchema: string }) {
  const [searchTerm, setSearchTerm] = useState('')
  const [categoryFilter, setCategoryFilter] = useState("All");
  const [tagFilter, setTagFilter] = useState("All Tags");
  
  const filteredPosts = blogPosts.filter(post => 
    (post.title.toLowerCase().includes(searchTerm.toLowerCase()) || 
     post.excerpt.toLowerCase().includes(searchTerm.toLowerCase()) ||
     post.author.toLowerCase().includes(searchTerm.toLowerCase())) &&
    (categoryFilter === "All" || post.category === categoryFilter) &&
    (tagFilter === "All Tags" || post.tags.includes(tagFilter))
  );
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: blogSchema }}
      />
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <Link href="/" className="hover:text-foreground">Home</Link>
            <span className="mx-1.5">/</span>
            <Link href="/resources" className="hover:text-foreground">Resources</Link>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">Articles & Blogs</span>
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
              <h1 className="text-2xl font-serif font-semibold">Articles & Blogs</h1>
              <p className="text-sm mt-1">
                SEO content covering spiritual topics, rituals, philosophy, and practical guidance for your spiritual journey.
              </p>
            </div>
          </div>
        </div>

        {/* Stats Bar */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div className="bg-card border border-border rounded-lg p-4 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">150+</div>
              <div className="text-xs text-muted-foreground">Articles Published</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">50K+</div>
              <div className="text-xs text-muted-foreground">Monthly Readers</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">4.8/5</div>
              <div className="text-xs text-muted-foreground">Average Rating</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">25+</div>
              <div className="text-xs text-muted-foreground">Expert Authors</div>
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
                placeholder="Search articles..."
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
                <div className="text-xs font-medium mb-2">Popular Tags</div>
                <div className="flex flex-wrap gap-2">
                  {popularTags.map((tag, index) => (
                    <button
                      key={index}
                      onClick={() => setTagFilter(tag)}
                      className={`px-3 py-1.5 text-xs rounded-full transition-colors ${
                        tagFilter === tag
                          ? 'bg-[#0E3F2E] text-white' 
                          : 'bg-background hover:bg-muted border border-border text-foreground'
                      }`}
                    >
                      {tag}
                    </button>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Featured Article */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-8">
          <div className="bg-card border border-border rounded-lg overflow-hidden">
            <div className="border-b border-border p-4">
              <h2 className="text-lg font-semibold">Featured Article</h2>
            </div>
            
            {blogPosts[0] && (
              <div className="p-6">
                <div className="flex flex-col lg:flex-row gap-6">
                  <div className="lg:w-2/3">
                    <div className="flex items-center gap-2 mb-3">
                      <span className="px-2 py-1 bg-[#0E3F2E]/10 text-[#0E3F2E] text-[10px] rounded-full">
                        {blogPosts[0].category}
                      </span>
                    </div>
                    
                    <h3 className="text-xl font-semibold mb-3">
                      {blogPosts[0].title}
                    </h3>
                    <p className="text-muted-foreground mb-4">
                      {blogPosts[0].excerpt}
                    </p>
                    
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-full bg-muted flex items-center justify-center">
                          <span className="text-[8px] font-medium">
                            {blogPosts[0].author.split(' ').map(n => n[0]).join('')}
                          </span>
                        </div>
                        <div>
                          <div className="font-medium text-sm">{blogPosts[0].author}</div>
                          <div className="text-xs text-muted-foreground">
                            {new Date(blogPosts[0].date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })} • {blogPosts[0].readTime}
                          </div>
                        </div>
                      </div>
                      
                      <div className="flex items-center gap-4 text-sm text-muted-foreground">
                        <div className="flex items-center gap-1">
                          <Eye className="w-4 h-4" />
                          <span>{blogPosts[0].views}</span>
                        </div>
                        <div className="flex items-center gap-1">
                          <ThumbsUp className="w-4 h-4" />
                          <span>{blogPosts[0].likes}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <div className="lg:w-1/3">
                    <div className="bg-muted rounded-lg h-full overflow-hidden">
                      <img 
                        src={blogPosts[0].image} 
                        alt={blogPosts[0].title}
                        className="w-full h-full object-cover"
                      />
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>

        {/* Articles Grid */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 pb-10">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {filteredPosts.filter((_, index) => index > 0).map(post => (
              <div key={post.id} className="border border-border rounded-lg overflow-hidden bg-card flex flex-col h-full">
                <div className="bg-muted flex-shrink-0">
                  <img 
                    src={post.image} 
                    alt={post.title}
                    className="w-full h-40 object-cover"
                  />
                </div>
                
                <div className="p-4 space-y-3 flex flex-col flex-grow">
                  <div>
                    <div className="flex items-center justify-between mb-2">
                      <span className="px-2 py-1 bg-[#0E3F2E]/10 text-[#0E3F2E] text-[10px] rounded-full">
                        {post.category}
                      </span>
                    </div>
                    <h3 className="font-medium hover:text-[#0E3F2E] transition-colors line-clamp-2 text-sm">
                      {post.title}
                    </h3>
                  </div>
                  
                  <p className="text-[11px] text-muted-foreground line-clamp-3 flex-grow">
                    {post.excerpt}
                  </p>
                  
                  <div className="flex flex-wrap gap-1 mb-3">
                    {post.tags.slice(0, 3).map((tag, index) => (
                      <span key={index} className="px-2 py-1 bg-muted text-foreground text-[10px] rounded-full">
                        {tag}
                      </span>
                    ))}
                  </div>
                  
                  <div className="flex items-center justify-between text-[10px] text-muted-foreground">
                    <div className="flex items-center gap-2">
                      <div className="w-6 h-6 rounded-full bg-muted flex items-center justify-center">
                        <span className="text-[8px] font-medium">
                          {post.author.split(' ').map(n => n[0]).join('')}
                        </span>
                      </div>
                      <div>
                        <div className="font-medium">{post.author}</div>
                        <div>{new Date(post.date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}</div>
                      </div>
                    </div>
                    
                    <div className="flex items-center gap-2">
                      <div className="flex items-center gap-1">
                        <ClockIcon className="w-3 h-3" />
                        <span>{post.readTime}</span>
                      </div>
                    </div>
                  </div>
                  
                  <Link 
                    href={`/resources/blog/${post.id}`}
                    className="mt-2 inline-flex items-center justify-center gap-1 w-full bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white font-medium py-2 px-3 rounded-md transition-all duration-300 text-center text-[11px]"
                  >
                    <span>Read Article</span>
                    <ArrowRight className="w-3 h-3" />
                  </Link>
                </div>
              </div>
            ))}
          </div>
          
          {filteredPosts.filter((_, index) => index > 0).length === 0 && (
            <div className="text-center py-10">
              <BookOpen className="w-12 h-12 text-muted-foreground mx-auto mb-4" />
              <h3 className="text-lg font-medium mb-2">No articles found</h3>
              <p className="text-muted-foreground">Try adjusting your search or filter criteria</p>
            </div>
          )}
        </div>
      </div>
    </>
  )
}
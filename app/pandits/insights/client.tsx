'use client'
import React from 'react';
import Link from "next/link"
import Script from "next/script"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp } from "lucide-react"

interface Insight {
  id: number;
  title: string;
  excerpt: string;
  author: string;
  date: string;
  category: string;
  readTime: string;
  image: string;
}

interface InsightsClientProps {
  insightsSchema: string;
}

// Insight data
const insights: Insight[] = [
  {
    id: 1,
    title: "The Significance of Griha Pravesh in Vastu Shastra",
    excerpt: "Understanding the ancient Vedic rituals and their impact on creating a harmonious living environment for new homeowners.",
    author: "Pandit Rajesh Sharma",
    date: "2025-10-15",
    category: "Vastu",
    readTime: "5 min read",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg"
  },
  {
    id: 2,
    title: "Navagraha Homa: Balancing Planetary Influences",
    excerpt: "How the nine planetary worship rituals can help mitigate negative influences and enhance positive cosmic energies in your life.",
    author: "Pandit Deepak Joshi",
    date: "2025-10-10",
    category: "Rituals",
    readTime: "7 min read",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg"
  },
  {
    id: 3,
    title: "Understanding Your Kundli: A Beginner's Guide",
    excerpt: "Learn how to read your birth chart and understand the cosmic influences that shape your personality and life path.",
    author: "Pandit Suresh Iyer",
    date: "2025-10-05",
    category: "Astrology",
    readTime: "8 min read",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg"
  },
  {
    id: 4,
    title: "The Sacred Science of Rudra Homa",
    excerpt: "Exploring the powerful healing and purification properties of the ancient Rudra Homa ritual for health and wellness.",
    author: "Pandit Anil Verma",
    date: "2025-09-28",
    category: "Healing",
    readTime: "6 min read",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_db05ba7c437e48abe6440798d2a33b09dr_bokdku.jpg"
  },
  {
    id: 5,
    title: "Vastu Tips for Business Prosperity",
    excerpt: "Simple yet effective Vastu principles that can transform your workspace and attract abundance and success in your business.",
    author: "Pandit Mukesh Patel",
    date: "2025-09-20",
    category: "Vastu",
    readTime: "4 min read",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg"
  },
  {
    id: 6,
    title: "The Spiritual Significance of Diwali Celebrations",
    excerpt: "Delving into the deeper spiritual meanings behind the Festival of Lights and how to celebrate it with authenticity.",
    author: "Pandit Ramesh Gupta",
    date: "2025-09-15",
    category: "Festivals",
    readTime: "6 min read",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg"
  }
];

const categories = [
  "All", "Rituals", "Astrology", "Vastu", "Healing", "Festivals", "Mantras", "Philosophy"
];

export default function InsightsClient({ insightsSchema }: InsightsClientProps) {
  const InsightCard = ({ insight }: { insight: Insight }) => {
    return (
      <div className="group border border-border rounded-lg overflow-hidden bg-card flex flex-col h-full">
        <div className="relative bg-muted flex-shrink-0">
          <img 
            src={insight.image} 
            alt={insight.title}
            className="w-full h-40 object-cover group-hover:scale-105 transition-transform duration-300"
          />
          <div className="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
          <div className="absolute top-2 right-2">
            <span className="px-2 py-1 bg-[#0E3F2E]/80 text-white text-[10px] rounded-full">
              {insight.category}
            </span>
          </div>
        </div>
        
        <div className="p-4 space-y-3 flex flex-col flex-grow">
          <h3 className="font-medium hover:text-[#0E3F2E] transition-colors line-clamp-2 text-sm flex-grow">
            {insight.title}
          </h3>
          
          <p className="text-[11px] text-muted-foreground line-clamp-3">
            {insight.excerpt}
          </p>
          
          <div className="flex items-center justify-between pt-2">
            <div className="flex items-center gap-2">
              <div className="w-6 h-6 rounded-full bg-muted flex items-center justify-center">
                <span className="text-[8px] font-medium">P</span>
              </div>
              <div>
                <div className="text-[10px] font-medium">{insight.author}</div>
                <div className="text-[9px] text-muted-foreground">{insight.date}</div>
              </div>
            </div>
            <span className="text-[10px] text-muted-foreground">{insight.readTime}</span>
          </div>
          
          <Link 
            href={`/pandits/insights/${insight.id}`}
            className="mt-2 inline-flex items-center justify-center gap-1 w-full bg-background hover:bg-muted text-foreground border border-border font-medium py-2 px-3 rounded-md transition-all duration-300 text-center text-[11px]"
          >
            <span>Read More</span>
            <ArrowRight className="w-3 h-3" />
          </Link>
        </div>
      </div>
    );
  };

  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: insightsSchema }}
      />
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <Link href="/" className="hover:text-foreground">Home</Link>
            <span className="mx-1.5">/</span>
            <Link href="/pandits" className="hover:text-foreground">Pandits & Experts</Link>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">Insights</span>
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
              <h1 className="text-2xl font-serif font-semibold">Pandit Insights & Resources</h1>
              <p className="text-sm mt-1">
                Expert insights, resources, and knowledge from verified Vedic pandits. Learn about puja rituals, astrology, spiritual practices, and ancient wisdom.
              </p>
            </div>
          </div>
        </div>

        {/* Stats Bar */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div className="bg-card border border-border rounded-lg p-4 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">50+</div>
              <div className="text-xs text-muted-foreground">Expert Articles</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">25+</div>
              <div className="text-xs text-muted-foreground">Video Guides</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">15+</div>
              <div className="text-xs text-muted-foreground">Pandit Experts</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">10K+</div>
              <div className="text-xs text-muted-foreground">Monthly Readers</div>
            </div>
          </div>
        </div>

        {/* Category Filter */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div className="flex flex-wrap gap-2">
            {categories.map((category, index) => (
              <button
                key={index}
                className={`px-3 py-1.5 text-xs rounded-full transition-colors ${
                  category === "All" 
                    ? 'bg-[#0E3F2E] text-white' 
                    : 'bg-background hover:bg-muted border border-border text-foreground'
                }`}
              >
                {category}
              </button>
            ))}
          </div>
        </div>

        {/* Insights Grid */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 pb-10">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {insights.map(insight => (
              <div key={insight.id} className="flex">
                <InsightCard insight={insight} />
              </div>
            ))}
          </div>
        </div>
      </div>
    </>
  )
}
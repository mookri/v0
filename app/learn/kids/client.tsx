'use client'
import React, { useState } from 'react';
import Link from "next/link"
import Script from "next/script"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video, Baby, BookMarked, Sparkles, Gamepad2 } from "lucide-react"

interface KidsResource {
  id: number;
  title: string;
  excerpt: string;
  ageGroup: string;
  type: string;
  duration?: string;
  pages?: number;
  category: string;
  image: string;
  featured?: boolean;
}

// Kids resource data
const kidsResources: KidsResource[] = [
  {
    id: 1,
    title: "Ramayana for Little Heroes",
    excerpt: "An engaging retelling of the epic Ramayana with colorful illustrations and simple language perfect for young minds.",
    ageGroup: "5-8 years",
    type: "Storybook",
    pages: 48,
    category: "Mythology",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    featured: true
  },
  {
    id: 2,
    title: "Mahabharata Adventures",
    excerpt: "Exciting tales from the Mahabharata featuring young characters and moral lessons in an interactive format.",
    ageGroup: "8-12 years",
    type: "Interactive Book",
    pages: 64,
    category: "Mythology",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg"
  },
  {
    id: 3,
    title: "Values Through Stories",
    excerpt: "A collection of traditional stories that teach important values like honesty, kindness, and perseverance.",
    ageGroup: "4-7 years",
    type: "Story Collection",
    pages: 36,
    category: "Values",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg"
  },
  {
    id: 4,
    title: "Festivals Fun Pack",
    excerpt: "Learn about Indian festivals through games, coloring activities, and simple craft projects.",
    ageGroup: "6-10 years",
    type: "Activity Book",
    pages: 52,
    category: "Festivals",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_db05ba7c437e48abe6440798d2a33b09dr_bokdku.jpg"
  },
  {
    id: 5,
    title: "Little Yogis",
    excerpt: "Simple yoga poses and breathing exercises designed specifically for children to improve focus and well-being.",
    ageGroup: "4-8 years",
    type: "Video Series",
    duration: "30 mins",
    category: "Yoga",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg"
  },
  {
    id: 6,
    title: "Temple Tales",
    excerpt: "Discover the stories behind famous temples in India through engaging narratives and beautiful illustrations.",
    ageGroup: "7-12 years",
    type: "Storybook",
    pages: 44,
    category: "Culture",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg"
  },
  {
    id: 7,
    title: "Animal Friends in Mythology",
    excerpt: "Meet the animal characters from Indian mythology and learn about their special qualities and stories.",
    ageGroup: "5-9 years",
    type: "Interactive Book",
    pages: 40,
    category: "Mythology",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg"
  },
  {
    id: 8,
    title: "Good Habits Game",
    excerpt: "Fun games and activities that help children develop positive habits like cleanliness, punctuality, and respect.",
    ageGroup: "4-8 years",
    type: "Game Pack",
    pages: 28,
    category: "Values",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg"
  }
];

const categories = [
  "All", "Mythology", "Values", "Festivals", "Yoga", "Culture"
];

const ageGroups = [
  "All Ages", "2-4 years", "5-8 years", "8-12 years"
];

export default function KidsLearningClient({ kidsSchema }: { kidsSchema: string }) {
  const [categoryFilter, setCategoryFilter] = useState("All");
  const [ageGroupFilter, setAgeGroupFilter] = useState("All Ages");
  
  const filteredResources = kidsResources.filter(resource => 
    (categoryFilter === "All" || resource.category === categoryFilter) &&
    (ageGroupFilter === "All Ages" || resource.ageGroup === ageGroupFilter)
  );
  
  const KidsResourceCard = ({ resource }: { resource: KidsResource }) => {
    return (
      <div className="group border border-border rounded-lg overflow-hidden bg-card flex flex-col h-full">
        <div className="relative bg-muted flex-shrink-0">
          <img 
            src={resource.image} 
            alt={resource.title}
            className="w-full h-40 object-cover group-hover:scale-105 transition-transform duration-300"
          />
          <div className="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
          
          {resource.featured && (
            <div className="absolute top-2 left-2">
              <span className="px-2 py-1 bg-yellow-500 text-white text-[10px] rounded-full flex items-center gap-1">
                <Sparkles className="w-3 h-3" />
                Featured
              </span>
            </div>
          )}
          
          <div className="absolute top-2 right-2">
            <span className="px-2 py-1 bg-[#0E3F2E]/80 text-white text-[10px] rounded-full">
              {resource.type}
            </span>
          </div>
          
          <div className="absolute bottom-2 left-2">
            <span className="px-2 py-1 bg-background/80 text-foreground text-[10px] rounded-full">
              {resource.ageGroup}
            </span>
          </div>
        </div>
        
        <div className="p-4 space-y-3 flex flex-col flex-grow">
          <h3 className="font-medium hover:text-[#0E3F2E] transition-colors line-clamp-2 text-sm flex-grow">
            {resource.title}
          </h3>
          
          <p className="text-[11px] text-muted-foreground line-clamp-3">
            {resource.excerpt}
          </p>
          
          <div className="flex items-center justify-between text-[10px] text-muted-foreground pt-2">
            <div className="flex items-center gap-1">
              {resource.duration && (
                <div className="flex items-center gap-1">
                  <Clock className="w-3 h-3" />
                  <span>{resource.duration}</span>
                </div>
              )}
              {resource.pages && (
                <div className="flex items-center gap-1">
                  <FileText className="w-3 h-3" />
                  <span>{resource.pages} pages</span>
                </div>
              )}
            </div>
            
            <Link 
              href={`/learn/kids/${resource.id}`}
              className="inline-flex items-center gap-1 bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white font-medium py-1 px-2 rounded transition-all duration-300 text-center text-[10px]"
            >
              <span>Explore</span>
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
        dangerouslySetInnerHTML={{ __html: kidsSchema }}
      />
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <Link href="/" className="hover:text-foreground">Home</Link>
            <span className="mx-1.5">/</span>
            <Link href="/learn" className="hover:text-foreground">Learning</Link>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">For Kids</span>
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
              <div className="inline-block bg-white/20 backdrop-blur-sm text-white px-3 py-1 rounded-full text-sm font-medium mb-2">
                <Baby className="w-4 h-4 inline mr-1" />
                For Children
              </div>
              <h1 className="text-2xl font-serif font-semibold">Learning for Kids</h1>
              <p className="text-sm mt-1">
                Mythology and value-based learning modules designed specifically for children. Engaging content that teaches traditional Indian values through stories, games, and interactive activities.
              </p>
            </div>
          </div>
        </div>

        {/* Stats Bar */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div className="bg-card border border-border rounded-lg p-4 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">50+</div>
              <div className="text-xs text-muted-foreground">Learning Resources</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">6</div>
              <div className="text-xs text-muted-foreground">Age Groups</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">4.9/5</div>
              <div className="text-xs text-muted-foreground">Average Rating</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">100%</div>
              <div className="text-xs text-muted-foreground">Parent Satisfaction</div>
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
              <div className="text-xs font-medium mb-2">Age Group</div>
              <div className="flex flex-wrap gap-2">
                {ageGroups.map((ageGroup, index) => (
                  <button
                    key={index}
                    onClick={() => setAgeGroupFilter(ageGroup)}
                    className={`px-3 py-1.5 text-xs rounded-full transition-colors ${
                      ageGroupFilter === ageGroup
                        ? 'bg-[#0E3F2E] text-white' 
                        : 'bg-background hover:bg-muted border border-border text-foreground'
                    }`}
                  >
                    {ageGroup}
                  </button>
                ))}
              </div>
            </div>
          </div>
        </div>

        {/* Resources Grid */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 pb-10">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            {filteredResources.map(resource => (
              <div key={resource.id} className="flex">
                <KidsResourceCard resource={resource} />
              </div>
            ))}
          </div>
        </div>
      </div>
    </>
  )
}
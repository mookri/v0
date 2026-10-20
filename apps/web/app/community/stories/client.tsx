'use client'
import React, { useState } from 'react';
import Link from "next/link"
import Script from "next/script"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video, Search, MessageCircle, ThumbsUp, Reply, Flag, MoreHorizontal, User, Quote, Share, Bookmark } from "lucide-react"

interface UserStory {
  id: number;
  title: string;
  excerpt: string;
  author: {
    name: string;
    avatar: string;
    location: string;
  };
  date: string;
  readTime: string;
  likes: number;
  comments: number;
  category: string;
  isFeatured?: boolean;
  image?: string;
}

// User stories data
const userStories: UserStory[] = [
  {
    id: 1,
    title: "How Daily Rituals Transformed My Family's Life",
    excerpt: "After incorporating daily puja rituals into our routine, I noticed a significant positive change in our family dynamics and overall well-being.",
    author: {
      name: "Priya Sharma",
      avatar: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
      location: "Mumbai, India"
    },
    date: "2025-11-10",
    readTime: "5 min read",
    likes: 124,
    comments: 18,
    category: "Family",
    isFeatured: true,
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg"
  },
  {
    id: 2,
    title: "My Journey from Stress to Inner Peace Through Meditation",
    excerpt: "Discovering Navdhya's meditation courses helped me overcome chronic stress and find lasting inner peace through consistent practice.",
    author: {
      name: "Rajesh Kumar",
      avatar: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
      location: "Delhi, India"
    },
    date: "2025-11-08",
    readTime: "7 min read",
    likes: 89,
    comments: 12,
    category: "Meditation",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg"
  },
  {
    id: 3,
    title: "Finding the Right Pandit Changed Everything",
    excerpt: "My experience with Navdhya's pandit verification process led me to an authentic spiritual guide who transformed my understanding of rituals.",
    author: {
      name: "Anjali Bose",
      avatar: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg",
      location: "Kolkata, India"
    },
    date: "2025-11-05",
    readTime: "6 min read",
    likes: 156,
    comments: 24,
    category: "Pandits",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_db05ba7c437e48abe6440798d2a33b09dr_bokdku.jpg"
  },
  {
    id: 4,
    title: "Vastu Corrections That Brought Prosperity",
    excerpt: "After implementing Vastu recommendations from Navdhya experts, my business saw a remarkable improvement in the following months.",
    author: {
      name: "Vikram Singh",
      avatar: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
      location: "Bangalore, India"
    },
    date: "2025-11-02",
    readTime: "8 min read",
    likes: 92,
    comments: 15,
    category: "Vastu",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg"
  },
  {
    id: 5,
    title: "Understanding My Birth Chart Changed My Perspective",
    excerpt: "A detailed Kundli analysis from Navdhya's astrologers helped me understand my life path and make better decisions.",
    author: {
      name: "Meera Patel",
      avatar: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
      location: "Chennai, India"
    },
    date: "2025-10-28",
    readTime: "9 min read",
    likes: 143,
    comments: 31,
    category: "Astrology",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg"
  },
  {
    id: 6,
    title: "Teaching Spirituality to My Children",
    excerpt: "Using Navdhya's kids learning modules, I've successfully introduced my children to the beautiful world of Indian mythology and values.",
    author: {
      name: "Sneha Gupta",
      avatar: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
      location: "Hyderabad, India"
    },
    date: "2025-10-25",
    readTime: "4 min read",
    likes: 78,
    comments: 9,
    category: "Family",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg"
  }
];

const categories = [
  "All", "Family", "Meditation", "Pandits", "Vastu", "Astrology", "Rituals", "Philosophy"
];

const popularTags = [
  "Transformation", "Family", "Peace", "Growth", "Authentic", "Experience", "Guidance", "Community"
];

export default function StoriesClient({ userStoriesSchema }: { userStoriesSchema: string }) {
  const [searchTerm, setSearchTerm] = useState('')
  const [categoryFilter, setCategoryFilter] = useState("All");
  
  const filteredStories = userStories.filter(story => 
    (story.title.toLowerCase().includes(searchTerm.toLowerCase()) || 
     story.excerpt.toLowerCase().includes(searchTerm.toLowerCase()) ||
     story.author.name.toLowerCase().includes(searchTerm.toLowerCase())) &&
    (categoryFilter === "All" || story.category === categoryFilter)
  );
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: userStoriesSchema }}
      />
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <Link href="/" className="hover:text-foreground">Home</Link>
            <span className="mx-1.5">/</span>
            <Link href="/community" className="hover:text-foreground">Community</Link>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">User Stories</span>
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
              <h1 className="text-2xl font-serif font-semibold">User Stories</h1>
              <p className="text-sm mt-1">
                Authentic engagement through real experiences shared by our community members. Read inspiring stories of spiritual growth and transformation.
              </p>
            </div>
          </div>
        </div>

        {/* Stats Bar */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div className="bg-card border border-border rounded-lg p-4 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">500+</div>
              <div className="text-xs text-muted-foreground">User Stories</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">25K+</div>
              <div className="text-xs text-muted-foreground">Community Members</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">4.9/5</div>
              <div className="text-xs text-muted-foreground">Story Rating</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">98%</div>
              <div className="text-xs text-muted-foreground">Reader Satisfaction</div>
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
                  placeholder="Search stories..."
                  className="w-full pl-10 pr-4 py-2 border border-border rounded-md bg-background focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                />
              </div>
              
              {/* Action Buttons */}
              <button className="px-4 py-2 bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white rounded-md text-sm font-medium transition-colors">
                Share Your Story
              </button>
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
                  <div className="text-xs font-medium mb-2">Popular Tags</div>
                  <div className="flex flex-wrap gap-2">
                    {popularTags.map((tag, index) => (
                      <span 
                        key={index}
                        className="px-3 py-1 bg-muted hover:bg-muted/80 text-foreground text-xs rounded-full cursor-pointer transition-colors"
                      >
                        {tag}
                      </span>
                    ))}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Featured Story */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-8">
          <div className="bg-card border border-border rounded-lg overflow-hidden">
            <div className="border-b border-border p-4">
              <h2 className="text-lg font-semibold">Featured Story</h2>
            </div>
            
            {userStories.find(story => story.isFeatured) && (
              <div className="p-6">
                <div className="flex flex-col lg:flex-row gap-6">
                  <div className="lg:w-2/3">
                    <h3 className="text-xl font-semibold mb-3">
                      {userStories.find(story => story.isFeatured)?.title}
                    </h3>
                    <p className="text-muted-foreground mb-4">
                      {userStories.find(story => story.isFeatured)?.excerpt}
                    </p>
                    
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-full overflow-hidden">
                          <img 
                            src={userStories.find(story => story.isFeatured)?.author.avatar} 
                            alt={userStories.find(story => story.isFeatured)?.author.name}
                            className="w-full h-full object-cover"
                          />
                        </div>
                        <div>
                          <div className="font-medium text-sm">
                            {userStories.find(story => story.isFeatured)?.author.name}
                          </div>
                          <div className="text-xs text-muted-foreground">
                            {userStories.find(story => story.isFeatured)?.author.location} • {userStories.find(story => story.isFeatured)?.readTime}
                          </div>
                        </div>
                      </div>
                      
                      <div className="flex items-center gap-4 text-sm text-muted-foreground">
                        <div className="flex items-center gap-1">
                          <Heart className="w-4 h-4" />
                          <span>{userStories.find(story => story.isFeatured)?.likes}</span>
                        </div>
                        <div className="flex items-center gap-1">
                          <MessageCircle className="w-4 h-4" />
                          <span>{userStories.find(story => story.isFeatured)?.comments}</span>
                        </div>
                        <div className="flex items-center gap-1">
                          <Share className="w-4 h-4" />
                          <span>Share</span>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <div className="lg:w-1/3">
                    <div className="bg-muted rounded-lg h-full overflow-hidden">
                      <img 
                        src={userStories.find(story => story.isFeatured)?.image} 
                        alt={userStories.find(story => story.isFeatured)?.title}
                        className="w-full h-full object-cover"
                      />
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>

        {/* Stories Grid */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 pb-10">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {filteredStories.filter(story => !story.isFeatured).map(story => (
              <div key={story.id} className="border border-border rounded-lg overflow-hidden bg-card flex flex-col h-full">
                {story.image && (
                  <div className="bg-muted flex-shrink-0">
                    <img 
                      src={story.image} 
                      alt={story.title}
                      className="w-full h-40 object-cover"
                    />
                  </div>
                )}
                
                <div className="p-4 space-y-3 flex flex-col flex-grow">
                  <div>
                    <span className="px-2 py-1 bg-[#0E3F2E]/10 text-[#0E3F2E] text-[10px] rounded-full">
                      {story.category}
                    </span>
                    <h3 className="font-medium hover:text-[#0E3F2E] transition-colors line-clamp-2 text-sm mt-2">
                      {story.title}
                    </h3>
                  </div>
                  
                  <p className="text-[11px] text-muted-foreground line-clamp-3 flex-grow">
                    {story.excerpt}
                  </p>
                  
                  <div className="flex items-center gap-2 text-[10px] text-muted-foreground">
                    <div className="w-6 h-6 rounded-full bg-muted flex items-center justify-center overflow-hidden">
                      <img 
                        src={story.author.avatar} 
                        alt={story.author.name}
                        className="w-full h-full object-cover"
                      />
                    </div>
                    <div>
                      <div className="font-medium">{story.author.name}</div>
                      <div>{new Date(story.date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short' })} • {story.readTime}</div>
                    </div>
                  </div>
                  
                  <div className="flex items-center justify-between pt-2">
                    <div className="flex items-center gap-3 text-[10px] text-muted-foreground">
                      <div className="flex items-center gap-1">
                        <Heart className="w-3 h-3" />
                        <span>{story.likes}</span>
                      </div>
                      <div className="flex items-center gap-1">
                        <MessageCircle className="w-3 h-3" />
                        <span>{story.comments}</span>
                      </div>
                    </div>
                    <Link 
                      href={`/community/stories/${story.id}`}
                      className="inline-flex items-center justify-center gap-1 bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white font-medium py-1.5 px-3 rounded-md transition-all duration-300 text-center text-[11px]"
                    >
                      <span>Read Story</span>
                      <ArrowRight className="w-3 h-3" />
                    </Link>
                  </div>
                </div>
              </div>
            ))}
          </div>
          
          {filteredStories.filter(story => !story.isFeatured).length === 0 && (
            <div className="text-center py-10">
              <Quote className="w-12 h-12 text-muted-foreground mx-auto mb-4" />
              <h3 className="text-lg font-medium mb-2">No stories found</h3>
              <p className="text-muted-foreground">Try adjusting your search or filter criteria</p>
            </div>
          )}
        </div>
      </div>
    </>
  )
}
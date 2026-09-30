'use client'
import React, { useState } from 'react';
import Link from "next/link"
import Script from "next/script"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video, Search, MessageCircle, ThumbsUp, Reply, Flag, MoreHorizontal, User, Lock, Globe, Shield } from "lucide-react"

interface ForumCategory {
  id: number;
  name: string;
  description: string;
  topics: number;
  posts: number;
  lastPost?: {
    title: string;
    author: string;
    time: string;
  };
}

interface ForumTopic {
  id: number;
  title: string;
  author: string;
  replies: number;
  views: number;
  lastPost: {
    author: string;
    time: string;
  };
  isPinned?: boolean;
  isLocked?: boolean;
}

// Forum categories data
const forumCategories: ForumCategory[] = [
  {
    id: 1,
    name: "General Discussions",
    description: "Talk about anything related to spirituality, rituals, and personal experiences",
    topics: 124,
    posts: 892,
    lastPost: {
      title: "My journey with daily meditation",
      author: "Swami Anand",
      time: "2 hours ago"
    }
  },
  {
    id: 2,
    name: "Vedic Rituals & Practices",
    description: "Discuss proper procedures, mantras, and experiences with Vedic rituals",
    topics: 89,
    posts: 542,
    lastPost: {
      title: "Ganesh Homa experiences",
      author: "Pandit Ramesh",
      time: "5 hours ago"
    }
  },
  {
    id: 3,
    name: "Astrology & Predictions",
    description: "Share insights about astrology, birth charts, and planetary influences",
    topics: 67,
    posts: 421,
    lastPost: {
      title: "Jupiter's transit effects",
      author: "Dr. Sharma",
      time: "1 day ago"
    }
  },
  {
    id: 4,
    name: "Meditation & Yoga",
    description: "Techniques, experiences, and guidance on meditation and yoga practices",
    topics: 92,
    posts: 618,
    lastPost: {
      title: "Breathing techniques for beginners",
      author: "Meera Patel",
      time: "3 hours ago"
    }
  },
  {
    id: 5,
    name: "Scriptures & Philosophy",
    description: "Discussions on sacred texts, philosophical concepts, and spiritual wisdom",
    topics: 56,
    posts: 324,
    lastPost: {
      title: "Understanding Karma in Bhagavad Gita",
      author: "Prof. Iyer",
      time: "1 day ago"
    }
  },
  {
    id: 6,
    name: "Regional Traditions",
    description: "Explore diverse regional practices and cultural variations",
    topics: 43,
    posts: 276,
    lastPost: {
      title: "Bengali Durga Puja traditions",
      author: "Anjali Bose",
      time: "2 days ago"
    }
  }
];

// Recent topics data
const recentTopics: ForumTopic[] = [
  {
    id: 1,
    title: "Best practices for daily rituals during Navratri",
    author: "Priya Sharma",
    replies: 24,
    views: 187,
    lastPost: {
      author: "Rajesh Kumar",
      time: "15 mins ago"
    },
    isPinned: true
  },
  {
    id: 2,
    title: "Understanding the significance of Rudraksha beads",
    author: "Dr. Mehta",
    replies: 18,
    views: 142,
    lastPost: {
      author: "Swami Prema",
      time: "1 hour ago"
    },
    isPinned: true
  },
  {
    id: 3,
    title: "How to create a proper meditation space at home?",
    author: "Ankit Verma",
    replies: 32,
    views: 256,
    lastPost: {
      author: "Sneha Patel",
      time: "2 hours ago"
    }
  },
  {
    id: 4,
    title: "Experiences with Vastu corrections in home",
    author: "Vikram Singh",
    replies: 15,
    views: 98,
    lastPost: {
      author: "Dr. Priya Sharma",
      time: "3 hours ago"
    }
  },
  {
    id: 5,
    title: "Recommended books for understanding Upanishads",
    author: "Prof. Ramesh Iyer",
    replies: 27,
    views: 201,
    lastPost: {
      author: "Amitabh Choudhury",
      time: "4 hours ago"
    }
  },
  {
    id: 6,
    title: "Dealing with spiritual doubts and questions",
    author: "Neha Gupta",
    replies: 41,
    views: 312,
    lastPost: {
      author: "Swami Anand",
      time: "5 hours ago"
    }
  }
];

const popularTags = [
  "Meditation", "Rituals", "Astrology", "Yoga", "Philosophy", "Mantras", "Vastu", "Karma", "Dharma", "Guru"
];

export default function ForumsClient({ forumsSchema }: { forumsSchema: string }) {
  const [searchTerm, setSearchTerm] = useState('')
  
  return (
    <>
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <Link href="/" className="hover:text-foreground">Home</Link>
            <span className="mx-1.5">/</span>
            <Link href="/community" className="hover:text-foreground">Community</Link>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">Forums</span>
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
              <h1 className="text-2xl font-serif font-semibold">Community Forums</h1>
              <p className="text-sm mt-1">
                Join discussions with fellow spiritual seekers. Share experiences, ask questions, and connect with our community of practitioners and learners.
              </p>
            </div>
          </div>
        </div>

        {/* Stats Bar */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div className="bg-card border border-border rounded-lg p-4 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">25K+</div>
              <div className="text-xs text-muted-foreground">Community Members</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">1.2K+</div>
              <div className="text-xs text-muted-foreground">Active Discussions</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">4.8/5</div>
              <div className="text-xs text-muted-foreground">Community Rating</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">95%</div>
              <div className="text-xs text-muted-foreground">Helpful Responses</div>
            </div>
          </div>
        </div>

        {/* Search and Actions */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div className="bg-card border border-border rounded-lg p-4">
            <div className="flex flex-col md:flex-row gap-4">
              {/* Search Bar */}
              <div className="relative flex-grow">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                <input
                  type="text"
                  placeholder="Search discussions..."
                  className="w-full pl-10 pr-4 py-2 border border-border rounded-md bg-background focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                />
              </div>
              
              {/* Action Buttons */}
              <div className="flex gap-2">
                <button className="px-4 py-2 bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white rounded-md text-sm font-medium transition-colors">
                  Start New Topic
                </button>
                <button className="px-4 py-2 border border-border rounded-md text-sm font-medium hover:bg-muted transition-colors">
                  My Topics
                </button>
              </div>
            </div>
            
            {/* Popular Tags */}
            <div className="mt-4 pt-4 border-t border-border">
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

        {/* Forum Categories */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-8">
          <div className="bg-card border border-border rounded-lg overflow-hidden">
            <div className="border-b border-border p-4">
              <h2 className="text-lg font-semibold">Forum Categories</h2>
            </div>
            
            <div className="divide-y divide-border">
              {forumCategories.map(category => (
                <div key={category.id} className="p-4 hover:bg-muted/50 transition-colors">
                  <Link href={`/community/forums/${category.id}`} className="block">
                    <div className="flex items-start justify-between">
                      <div className="flex-grow">
                        <h3 className="font-medium text-foreground hover:text-[#0E3F2E] transition-colors">
                          {category.name}
                        </h3>
                        <p className="text-sm text-muted-foreground mt-1">
                          {category.description}
                        </p>
                        
                        {category.lastPost && (
                          <div className="mt-2 text-xs text-muted-foreground">
                            Last post: <span className="font-medium">{category.lastPost.title}</span> by {category.lastPost.author} • {category.lastPost.time}
                          </div>
                        )}
                      </div>
                      
                      <div className="text-right text-xs text-muted-foreground ml-4">
                        <div>{category.topics} topics</div>
                        <div>{category.posts} posts</div>
                      </div>
                    </div>
                  </Link>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Recent Topics */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 pb-10">
          <div className="bg-card border border-border rounded-lg overflow-hidden">
            <div className="border-b border-border p-4 flex items-center justify-between">
              <h2 className="text-lg font-semibold">Recent Topics</h2>
              <div className="flex gap-2">
                <button className="text-xs text-muted-foreground hover:text-foreground">Latest</button>
                <button className="text-xs text-muted-foreground hover:text-foreground">Popular</button>
                <button className="text-xs text-muted-foreground hover:text-foreground">Unanswered</button>
              </div>
            </div>
            
            <div className="divide-y divide-border">
              {recentTopics.map(topic => (
                <div key={topic.id} className="p-4 hover:bg-muted/50 transition-colors">
                  <div className="flex items-start">
                    {topic.isPinned && (
                      <div className="mr-3 mt-1">
                        <Shield className="w-4 h-4 text-yellow-500" />
                      </div>
                    )}
                    
                    <div className="flex-grow">
                      <div className="flex items-start justify-between">
                        <div>
                          <Link href={`/community/forums/topics/${topic.id}`} className="font-medium text-foreground hover:text-[#0E3F2E] transition-colors">
                            {topic.title}
                          </Link>
                          <div className="text-xs text-muted-foreground mt-1">
                            Started by <span className="font-medium">{topic.author}</span>
                          </div>
                        </div>
                        
                        <div className="flex items-center gap-2 text-xs text-muted-foreground ml-4">
                          <div className="flex items-center gap-1">
                            <MessageCircle className="w-3 h-3" />
                            <span>{topic.replies}</span>
                          </div>
                          <div className="flex items-center gap-1">
                            <Eye className="w-3 h-3" />
                            <span>{topic.views}</span>
                          </div>
                        </div>
                      </div>
                      
                      <div className="mt-2 text-xs text-muted-foreground">
                        Last reply by <span className="font-medium">{topic.lastPost.author}</span> • {topic.lastPost.time}
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
            
            <div className="p-4 border-t border-border">
              <button className="w-full py-2 text-sm text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors">
                View All Topics
              </button>
            </div>
          </div>
        </div>
      </div>
    </>
  )
}
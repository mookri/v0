'use client'
import React from 'react';
import Link from "next/link"
import Script from "next/script"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video, Search, MessageCircle, ThumbsUp, Reply, Flag, MoreHorizontal, User, Handshake, Target, Globe, CalendarDays, Crown, Zap, Shield } from "lucide-react"
import { seoManager } from "@/lib/seo-manager"

// Schema markup for the main community page
const COMMUNITY_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Community - Navdhya",
  description: "Connect with fellow spiritual seekers, share experiences, and participate in community initiatives. Join our circle of like-minded individuals dedicated to spiritual growth.",
  path: "/community",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Community", url: "https://www.navdhya.com/community" }
  ]
})

interface CommunitySection {
  id: number;
  title: string;
  excerpt: string;
  icon: React.ReactNode;
  href: string;
  color: string;
}

// Community sections data
const communitySections: CommunitySection[] = [
  {
    id: 1,
    title: "Discussions → Forums with moderation",
    excerpt: "Join conversations with fellow seekers, ask questions, and share experiences in our moderated discussion forums.",
    icon: <MessageCircle className="w-6 h-6" />,
    href: "/community/forums",
    color: "bg-blue-100 text-blue-800"
  },
  {
    id: 2,
    title: "User Stories → Authentic engagement",
    excerpt: "Read inspiring stories from our community members and share your own transformative experiences.",
    icon: <BookOpen className="w-6 h-6" />,
    href: "/community/stories",
    color: "bg-green-100 text-green-800"
  },
  {
    id: 3,
    title: "Volunteer Programs → CSR & initiatives",
    excerpt: "Contribute to meaningful causes and community welfare through our volunteer programs and initiatives.",
    icon: <Handshake className="w-6 h-6" />,
    href: "/community/volunteer",
    color: "bg-purple-100 text-purple-800"
  },
  {
    id: 4,
    title: "Upcoming Events → Calendar-based",
    excerpt: "Stay updated with our spiritual events, workshops, and gatherings happening throughout the year.",
    icon: <CalendarDays className="w-6 h-6" />,
    href: "/community/events",
    color: "bg-yellow-100 text-yellow-800"
  },
  {
    id: 5,
    title: "Join Navdhya → Circle Membership gateway",
    excerpt: "Become part of our exclusive community circle with access to premium resources and personalized guidance.",
    icon: <Crown className="w-6 h-6" />,
    href: "/community/join",
    color: "bg-red-100 text-red-800"
  }
];

export default function CommunityClient() {

  return (
    <>
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <Link href="/" className="hover:text-foreground">Home</Link>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">Community</span>
          </nav>
        </div>

        {/* Hero Banner */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div 
            className="relative h-64 rounded-lg overflow-hidden bg-cover bg-center flex items-center"
            style={{
              backgroundImage: "url('https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg')"
            }}
          >
            <div className="absolute inset-0 bg-gradient-to-r from-black/80 to-black/40"></div>
            <div className="relative z-10 text-white p-8 max-w-3xl">
              <h1 className="text-3xl font-serif font-semibold mb-4">Navdhya Community</h1>
              <p className="text-lg mb-6">
                Connect with fellow spiritual seekers, share experiences, and participate in community initiatives. Join our circle of like-minded individuals dedicated to spiritual growth.
              </p>
              <Link 
                href="#sections"
                className="inline-flex items-center gap-2 bg-white text-[#0E3F2E] hover:bg-gray-100 font-medium py-2 px-6 rounded-md transition-all duration-300 shadow-sm"
              >
                <span>Explore Community</span>
                <ArrowRight className="w-4 h-4" />
              </Link>
            </div>
          </div>
        </div>

        {/* Stats Bar */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-10">
          <div className="bg-card border border-border rounded-lg p-6 grid grid-cols-2 md:grid-cols-4 gap-6">
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">25K+</div>
              <div className="text-sm text-muted-foreground">Community Members</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">1.5K+</div>
              <div className="text-sm text-muted-foreground">Active Discussions</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">4.9/5</div>
              <div className="text-sm text-muted-foreground">Community Rating</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">50+</div>
              <div className="text-sm text-muted-foreground">Community Events</div>
            </div>
          </div>
        </div>

        {/* Community Sections */}
        <div id="sections" className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">Community Engagement</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Explore different ways to connect, contribute, and grow within our spiritual community.
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {communitySections.map(section => (
              <Link 
                key={section.id}
                href={section.href}
                className="group border border-border rounded-lg overflow-hidden bg-card hover:shadow-lg transition-all duration-300 flex flex-col h-full"
              >
                <div className="p-6 flex-grow">
                  <div className={`w-12 h-12 rounded-lg ${section.color} flex items-center justify-center mb-4`}>
                    {section.icon}
                  </div>
                  <h3 className="font-medium text-lg mb-3 group-hover:text-[#0E3F2E] transition-colors">
                    {section.title}
                  </h3>
                  <p className="text-sm text-muted-foreground mb-4">
                    {section.excerpt}
                  </p>
                </div>
                <div className="px-6 py-4 bg-muted/50 border-t border-border">
                  <div className="flex items-center justify-between">
                    <span className="text-sm font-medium text-[#0E3F2E]">Explore</span>
                    <ArrowRight className="w-4 h-4 text-muted-foreground group-hover:translate-x-1 transition-transform" />
                  </div>
                </div>
              </Link>
            ))}
          </div>
        </div>

        {/* Featured Discussions */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">Trending Discussions</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Join the conversation in our most active community discussions.
            </p>
          </div>
          
          <div className="bg-card border border-border rounded-lg p-6">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="border border-border rounded-lg p-4">
                <div className="flex items-start gap-3 mb-3">
                  <div className="w-10 h-10 rounded-lg bg-blue-100 text-blue-800 flex items-center justify-center flex-shrink-0">
                    <MessageCircle className="w-5 h-5" />
                  </div>
                  <div>
                    <h3 className="font-medium text-sm">Best meditation techniques for beginners</h3>
                    <p className="text-xs text-muted-foreground">124 replies • 42 participants</p>
                  </div>
                </div>
                <p className="text-xs text-muted-foreground mb-3">
                  A discussion about simple meditation practices that anyone can start with, regardless of their experience level.
                </p>
                <Link href="/community/forums/4" className="text-xs text-blue-600 hover:underline">Join Discussion</Link>
              </div>
              
              <div className="border border-border rounded-lg p-4">
                <div className="flex items-start gap-3 mb-3">
                  <div className="w-10 h-10 rounded-lg bg-green-100 text-green-800 flex items-center justify-center flex-shrink-0">
                    <Users className="w-5 h-5" />
                  </div>
                  <div>
                    <h3 className="font-medium text-sm">Experiences with online pujas</h3>
                    <p className="text-xs text-muted-foreground">89 replies • 35 participants</p>
                  </div>
                </div>
                <p className="text-xs text-muted-foreground mb-3">
                  Share your experiences and insights about participating in online puja ceremonies and their effectiveness.
                </p>
                <Link href="/community/forums/2" className="text-xs text-green-600 hover:underline">Join Discussion</Link>
              </div>
              
              <div className="border border-border rounded-lg p-4">
                <div className="flex items-start gap-3 mb-3">
                  <div className="w-10 h-10 rounded-lg bg-purple-100 text-purple-800 flex items-center justify-center flex-shrink-0">
                    <BookOpen className="w-5 h-5" />
                  </div>
                  <div>
                    <h3 className="font-medium text-sm">Understanding karma in daily life</h3>
                    <p className="text-xs text-muted-foreground">156 replies • 67 participants</p>
                  </div>
                </div>
                <p className="text-xs text-muted-foreground mb-3">
                  Exploring how the concept of karma applies to our everyday actions and decisions.
                </p>
                <Link href="/community/forums/5" className="text-xs text-purple-600 hover:underline">Join Discussion</Link>
              </div>
            </div>
          </div>
        </div>

        {/* Upcoming Events Preview */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">Upcoming Community Events</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Mark your calendars for these exciting spiritual gatherings and workshops.
            </p>
          </div>
          
          <div className="bg-card border border-border rounded-lg p-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="border border-border rounded-lg p-4">
                <div className="flex items-start gap-3 mb-3">
                  <div className="w-10 h-10 rounded-lg bg-yellow-100 text-yellow-800 flex items-center justify-center flex-shrink-0">
                    <Calendar className="w-5 h-5" />
                  </div>
                  <div>
                    <h3 className="font-medium text-sm">Bhagavad Gita Discourse Series</h3>
                    <p className="text-xs text-muted-foreground">Nov 20, 2025 • 6:00 PM</p>
                  </div>
                </div>
                <p className="text-xs text-muted-foreground mb-3">
                  Weekly sessions exploring the profound wisdom of the Bhagavad Gita with expert scholars.
                </p>
                <Link href="/community/events/1" className="text-xs text-yellow-600 hover:underline">Register Now</Link>
              </div>
              
              <div className="border border-border rounded-lg p-4">
                <div className="flex items-start gap-3 mb-3">
                  <div className="w-10 h-10 rounded-lg bg-red-100 text-red-800 flex items-center justify-center flex-shrink-0">
                    <Zap className="w-5 h-5" />
                  </div>
                  <div>
                    <h3 className="font-medium text-sm">International Yoga Day Celebration</h3>
                    <p className="text-xs text-muted-foreground">Nov 22, 2025 • 5:00 AM</p>
                  </div>
                </div>
                <p className="text-xs text-muted-foreground mb-3">
                  Join our global community in celebrating International Yoga Day with special sessions.
                </p>
                <Link href="/community/events/2" className="text-xs text-red-600 hover:underline">Register Now</Link>
              </div>
            </div>
            
            <div className="mt-6 text-center">
              <Link 
                href="/community/events"
                className="inline-flex items-center gap-2 text-sm font-medium text-[#0E3F2E] hover:underline"
              >
                <span>View All Events</span>
                <ArrowRight className="w-4 h-4" />
              </Link>
            </div>
          </div>
        </div>

        {/* Community Benefits */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">Why Join Our Community?</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Being part of the Navdhya community offers unique benefits for your spiritual journey.
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="border border-border rounded-lg p-6 bg-card text-center">
              <div className="w-12 h-12 rounded-lg bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
                <Users className="w-6 h-6" />
              </div>
              <h3 className="font-medium text-lg mb-2">Connect with Like-Minded Seekers</h3>
              <p className="text-sm text-muted-foreground">
                Build meaningful relationships with individuals who share your spiritual interests and values.
              </p>
            </div>
            
            <div className="border border-border rounded-lg p-6 bg-card text-center">
              <div className="w-12 h-12 rounded-lg bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
                <Shield className="w-6 h-6" />
              </div>
              <h3 className="font-medium text-lg mb-2">Verified Spiritual Guidance</h3>
              <p className="text-sm text-muted-foreground">
                Access guidance from certified pandits and spiritual teachers in a supportive environment.
              </p>
            </div>
            
            <div className="border border-border rounded-lg p-6 bg-card text-center">
              <div className="w-12 h-12 rounded-lg bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
                <Globe className="w-6 h-6" />
              </div>
              <h3 className="font-medium text-lg mb-2">Global Community Access</h3>
              <p className="text-sm text-muted-foreground">
                Participate in events and discussions with spiritual seekers from around the world.
              </p>
            </div>
          </div>
        </div>
      </div>
    </>
  )
}
'use client'
import React from 'react';
import Link from "next/link"
import Script from "next/script"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video, Search, MessageCircle, ThumbsUp, Reply, Flag, MoreHorizontal, User, BookMarked, Calculator, Bell, Download, AudioLines } from "lucide-react"
import { seoManager } from "@/lib/seo-manager"

// Schema markup for the main resources page
const RESOURCES_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Resources - Navdhya",
  description: "Comprehensive spiritual resources including articles, scriptures, tools, news, and a digital library for your spiritual journey.",
  path: "/resources",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Resources", url: "https://www.navdhya.com/resources" }
  ]
})

interface ResourceSection {
  id: number;
  title: string;
  excerpt: string;
  icon: React.ReactNode;
  href: string;
  color: string;
}

// Resource sections data
const resourceSections: ResourceSection[] = [
  {
    id: 1,
    title: "Articles & Blogs → SEO content",
    excerpt: "Explore our collection of spiritual articles and blogs covering rituals, philosophy, and practical guidance.",
    icon: <BookOpen className="w-6 h-6" />,
    href: "/resources/blog",
    color: "bg-blue-100 text-blue-800"
  },
  {
    id: 2,
    title: "Scriptures & Texts → Rich spiritual library",
    excerpt: "Access ancient scriptures, sacred texts, and spiritual writings with translations and commentary.",
    icon: <BookMarked className="w-6 h-6" />,
    href: "/resources/scriptures",
    color: "bg-green-100 text-green-800"
  },
  {
    id: 3,
    title: "Tools & Calculators → Horoscope, Muhurat, Panchang",
    excerpt: "Utilize our spiritual tools for horoscopes, muhurat finding, and panchang calculations.",
    icon: <Calculator className="w-6 h-6" />,
    href: "/resources/tools",
    color: "bg-purple-100 text-purple-800"
  },
  {
    id: 4,
    title: "News & Updates → Real-time temple info",
    excerpt: "Stay informed with the latest news, temple updates, and community announcements.",
    icon: <Bell className="w-6 h-6" />,
    href: "/resources/news",
    color: "bg-yellow-100 text-yellow-800"
  },
  {
    id: 5,
    title: "Digital Library → Downloadable wisdom",
    excerpt: "Download spiritual resources including texts, guided meditations, and educational materials.",
    icon: <Download className="w-6 h-6" />,
    href: "/resources/library",
    color: "bg-red-100 text-red-800"
  }
];

export default function ResourcesClient() {
  const schema = JSON.stringify(RESOURCES_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: schema }}
      />
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <Link href="/" className="hover:text-foreground">Home</Link>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">Resources</span>
          </nav>
        </div>

        {/* Hero Banner */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div 
            className="relative h-64 rounded-lg overflow-hidden bg-cover bg-center flex items-center"
            style={{
              backgroundImage: "url('https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg')"
            }}
          >
            <div className="absolute inset-0 bg-gradient-to-r from-black/80 to-black/40"></div>
            <div className="relative z-10 text-white p-8 max-w-3xl">
              <h1 className="text-3xl font-serif font-semibold mb-4">Spiritual Resources</h1>
              <p className="text-lg mb-6">
                Comprehensive spiritual resources including articles, scriptures, tools, news, and a digital library for your spiritual journey.
              </p>
              <Link 
                href="#sections"
                className="inline-flex items-center gap-2 bg-white text-[#0E3F2E] hover:bg-gray-100 font-medium py-2 px-6 rounded-md transition-all duration-300 shadow-sm"
              >
                <span>Explore Resources</span>
                <ArrowRight className="w-4 h-4" />
              </Link>
            </div>
          </div>
        </div>

        {/* Stats Bar */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-10">
          <div className="bg-card border border-border rounded-lg p-6 grid grid-cols-2 md:grid-cols-4 gap-6">
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">500+</div>
              <div className="text-sm text-muted-foreground">Resource Items</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">100K+</div>
              <div className="text-sm text-muted-foreground">Monthly Users</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">4.8/5</div>
              <div className="text-sm text-muted-foreground">Resource Rating</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">5</div>
              <div className="text-sm text-muted-foreground">Resource Categories</div>
            </div>
          </div>
        </div>

        {/* Resource Sections */}
        <div id="sections" className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">Explore Our Resources</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Discover a wealth of spiritual knowledge and tools to support your journey.
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {resourceSections.map(section => (
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

        {/* Featured Resources */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">Featured Resources</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Our most popular and highly-rated spiritual resources.
            </p>
          </div>
          
          <div className="bg-card border border-border rounded-lg p-6">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="border border-border rounded-lg p-4">
                <div className="flex items-start gap-3 mb-3">
                  <div className="w-10 h-10 rounded-lg bg-blue-100 text-blue-800 flex items-center justify-center flex-shrink-0">
                    <BookOpen className="w-5 h-5" />
                  </div>
                  <div>
                    <h3 className="font-medium text-sm">Understanding Karma in Daily Life</h3>
                    <p className="text-xs text-muted-foreground">Featured Article</p>
                  </div>
                </div>
                <p className="text-xs text-muted-foreground mb-3">
                  A comprehensive guide to understanding how the law of karma applies to our everyday actions and decisions.
                </p>
                <Link href="/resources/blog/5" className="text-xs text-blue-600 hover:underline">Read Now</Link>
              </div>
              
              <div className="border border-border rounded-lg p-4">
                <div className="flex items-start gap-3 mb-3">
                  <div className="w-10 h-10 rounded-lg bg-green-100 text-green-800 flex items-center justify-center flex-shrink-0">
                    <BookMarked className="w-5 h-5" />
                  </div>
                  <div>
                    <h3 className="font-medium text-sm">Bhagavad Gita - Chapter Summaries</h3>
                    <p className="text-xs text-muted-foreground">Featured Scripture</p>
                  </div>
                </div>
                <p className="text-xs text-muted-foreground mb-3">
                  Concise summaries and key teachings from all 18 chapters of the Bhagavad Gita for easy reference.
                </p>
                <Link href="/resources/scriptures/6" className="text-xs text-green-600 hover:underline">Download</Link>
              </div>
              
              <div className="border border-border rounded-lg p-4">
                <div className="flex items-start gap-3 mb-3">
                  <div className="w-10 h-10 rounded-lg bg-purple-100 text-purple-800 flex items-center justify-center flex-shrink-0">
                    <Calculator className="w-5 h-5" />
                  </div>
                  <div>
                    <h3 className="font-medium text-sm">Muhurat Finder Tool</h3>
                    <p className="text-xs text-muted-foreground">Featured Tool</p>
                  </div>
                </div>
                <p className="text-xs text-muted-foreground mb-3">
                  Find the most auspicious timings for important events like weddings and business ventures.
                </p>
                <Link href="/resources/tools/muhurat" className="text-xs text-purple-600 hover:underline">Use Tool</Link>
              </div>
            </div>
          </div>
        </div>

        {/* Resource Benefits */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">Why Our Resources?</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Curated by experts and verified for authenticity and spiritual value.
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="border border-border rounded-lg p-6 bg-card text-center">
              <div className="w-12 h-12 rounded-lg bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
                <Award className="w-6 h-6" />
              </div>
              <h3 className="font-medium text-lg mb-2">Expert Verified</h3>
              <p className="text-sm text-muted-foreground">
                All resources are reviewed and verified by certified pandits and spiritual experts.
              </p>
            </div>
            
            <div className="border border-border rounded-lg p-6 bg-card text-center">
              <div className="w-12 h-12 rounded-lg bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
                <Download className="w-6 h-6" />
              </div>
              <h3 className="font-medium text-lg mb-2">Easy Access</h3>
              <p className="text-sm text-muted-foreground">
                Download or access resources anytime, anywhere on any device.
              </p>
            </div>
            
            <div className="border border-border rounded-lg p-6 bg-card text-center">
              <div className="w-12 h-12 rounded-lg bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
                <TrendingUp className="w-6 h-6" />
              </div>
              <h3 className="font-medium text-lg mb-2">Regular Updates</h3>
              <p className="text-sm text-muted-foreground">
                Our library is constantly growing with new resources and updated content.
              </p>
            </div>
          </div>
        </div>
      </div>
    </>
  )
}
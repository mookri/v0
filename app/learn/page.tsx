'use client'
import React from 'react';
import Link from "next/link"
import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video, Baby, BookMarked, Sparkles, Gamepad2, GraduationCap } from "lucide-react"

// Schema markup for the main learn page
const LEARN_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Learning & Spiritual Growth - Navdhya",
  description: "Expand your spiritual knowledge with our comprehensive learning resources. From beginner courses to advanced certification programs, discover pathways to deepen your understanding of Vedic wisdom.",
  path: "/learn",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Learning", url: "https://www.navdhya.com/learn" }
  ]
})

interface LearningPath {
  id: number;
  title: string;
  excerpt: string;
  icon: React.ReactNode;
  href: string;
  color: string;
}

// Learning paths data
const learningPaths: LearningPath[] = [
  {
    id: 1,
    title: "Courses — Gita Studies, Meditation, Ritual Science",
    excerpt: "Structured learning programs covering foundational and advanced topics in Vedic wisdom and spiritual practices.",
    icon: <BookOpen className="w-6 h-6" />,
    href: "/learn/courses",
    color: "bg-blue-100 text-blue-800"
  },
  {
    id: 2,
    title: "Workshops — Live sessions with spiritual mentors",
    excerpt: "Interactive live sessions where you can learn directly from experienced pandits and spiritual teachers.",
    icon: <Users className="w-6 h-6" />,
    href: "/learn/workshops",
    color: "bg-green-100 text-green-800"
  },
  {
    id: 3,
    title: "Self-Learning Library — Videos, PDFs, podcasts",
    excerpt: "Extensive collection of resources you can access anytime to continue your spiritual journey at your own pace.",
    icon: <BookMarked className="w-6 h-6" />,
    href: "/learn/library",
    color: "bg-purple-100 text-purple-800"
  },
  {
    id: 4,
    title: "Certification Programs — Authorized cultural training",
    excerpt: "Professional certification courses for those seeking to deepen their expertise and credentials.",
    icon: <GraduationCap className="w-6 h-6" />,
    href: "/learn/certified",
    color: "bg-yellow-100 text-yellow-800"
  },
  {
    id: 5,
    title: "For Kids — Mythology & value-based learning modules",
    excerpt: "Engaging content designed specifically for children to learn about Indian culture and values.",
    icon: <Baby className="w-6 h-6" />,
    href: "/learn/kids",
    color: "bg-pink-100 text-pink-800"
  }
];

export default function LearnPage() {
  const learnSchema = JSON.stringify(LEARN_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: learnSchema }}
      />
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <Link href="/" className="hover:text-foreground">Home</Link>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">Learning</span>
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
              <h1 className="text-3xl font-serif font-semibold mb-4">Learning & Spiritual Growth</h1>
              <p className="text-lg mb-6">
                Expand your spiritual knowledge with our comprehensive learning resources. From beginner courses to advanced certification programs, discover pathways to deepen your understanding of Vedic wisdom.
              </p>
              <Link 
                href="#paths"
                className="inline-flex items-center gap-2 bg-white text-[#0E3F2E] hover:bg-gray-100 font-medium py-2 px-6 rounded-md transition-all duration-300 shadow-sm"
              >
                <span>Explore Learning Paths</span>
                <ArrowRight className="w-4 h-4" />
              </Link>
            </div>
          </div>
        </div>

        {/* Stats Bar */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-10">
          <div className="bg-card border border-border rounded-lg p-6 grid grid-cols-2 md:grid-cols-4 gap-6">
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">50+</div>
              <div className="text-sm text-muted-foreground">Learning Resources</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">10K+</div>
              <div className="text-sm text-muted-foreground">Active Learners</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">4.9/5</div>
              <div className="text-sm text-muted-foreground">Average Rating</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">15+</div>
              <div className="text-sm text-muted-foreground">Expert Instructors</div>
            </div>
          </div>
        </div>

        {/* Learning Paths */}
        <div id="paths" className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">Our Learning Paths</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Choose from multiple pathways to suit your learning style and goals. Whether you're a beginner or advanced practitioner, we have resources for every stage of your spiritual journey.
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {learningPaths.map(path => (
              <Link 
                key={path.id}
                href={path.href}
                className="group border border-border rounded-lg overflow-hidden bg-card hover:shadow-lg transition-all duration-300 flex flex-col h-full"
              >
                <div className="p-6 flex-grow">
                  <div className={`w-12 h-12 rounded-lg ${path.color} flex items-center justify-center mb-4`}>
                    {path.icon}
                  </div>
                  <h3 className="font-medium text-lg mb-3 group-hover:text-[#0E3F2E] transition-colors">
                    {path.title}
                  </h3>
                  <p className="text-sm text-muted-foreground mb-4">
                    {path.excerpt}
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

        {/* Featured Courses */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">Featured Courses</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Get started with our most popular learning resources, recommended by our community of spiritual seekers.
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
                    <h3 className="font-medium text-sm">Bhagavad Gita Essentials</h3>
                    <p className="text-xs text-muted-foreground">Beginner Course</p>
                  </div>
                </div>
                <p className="text-xs text-muted-foreground mb-3">
                  A comprehensive introduction to the core teachings of the Bhagavad Gita and their practical application in daily life.
                </p>
                <div className="flex items-center justify-between">
                  <span className="text-sm font-medium text-[#0E3F2E]">₹2,999</span>
                  <Link href="/learn/courses/1" className="text-xs text-blue-600 hover:underline">Enroll Now</Link>
                </div>
              </div>
              
              <div className="border border-border rounded-lg p-4">
                <div className="flex items-start gap-3 mb-3">
                  <div className="w-10 h-10 rounded-lg bg-green-100 text-green-800 flex items-center justify-center flex-shrink-0">
                    <Users className="w-5 h-5" />
                  </div>
                  <div>
                    <h3 className="font-medium text-sm">Meditation for Inner Peace</h3>
                    <p className="text-xs text-muted-foreground">Live Workshop</p>
                  </div>
                </div>
                <p className="text-xs text-muted-foreground mb-3">
                  Learn practical meditation techniques to manage stress and find inner peace in today's fast-paced world.
                </p>
                <div className="flex items-center justify-between">
                  <span className="text-sm font-medium text-[#0E3F2E]">₹499</span>
                  <Link href="/learn/workshops/1" className="text-xs text-green-600 hover:underline">Register</Link>
                </div>
              </div>
              
              <div className="border border-border rounded-lg p-4">
                <div className="flex items-start gap-3 mb-3">
                  <div className="w-10 h-10 rounded-lg bg-purple-100 text-purple-800 flex items-center justify-center flex-shrink-0">
                    <BookMarked className="w-5 h-5" />
                  </div>
                  <div>
                    <h3 className="font-medium text-sm">Vedic Mantras Collection</h3>
                    <p className="text-xs text-muted-foreground">Self-Learning</p>
                  </div>
                </div>
                <p className="text-xs text-muted-foreground mb-3">
                  A comprehensive collection of sacred Vedic mantras with proper pronunciation guides and explanations.
                </p>
                <div className="flex items-center justify-between">
                  <span className="text-sm font-medium text-[#0E3F2E]">Free</span>
                  <Link href="/learn/library/4" className="text-xs text-purple-600 hover:underline">Access Now</Link>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Testimonials */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">What Our Learners Say</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Hear from members of our spiritual learning community about their experiences and transformations.
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="border border-border rounded-lg p-6 bg-card">
              <div className="flex items-center gap-1 mb-4">
                {[...Array(5)].map((_, i) => (
                  <Star key={i} className="w-4 h-4 text-yellow-500 fill-current" />
                ))}
              </div>
              <p className="text-sm text-muted-foreground mb-4">
                "The Bhagavad Gita course completely transformed my understanding of this ancient text. The instructors made complex concepts accessible and practical for daily life."
              </p>
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-full bg-muted flex items-center justify-center">
                  <span className="text-xs font-medium">SP</span>
                </div>
                <div>
                  <div className="text-sm font-medium">Sneha Patel</div>
                  <div className="text-xs text-muted-foreground">Course Graduate</div>
                </div>
              </div>
            </div>
            
            <div className="border border-border rounded-lg p-6 bg-card">
              <div className="flex items-center gap-1 mb-4">
                {[...Array(5)].map((_, i) => (
                  <Star key={i} className="w-4 h-4 text-yellow-500 fill-current" />
                ))}
              </div>
              <p className="text-sm text-muted-foreground mb-4">
                "The kids' learning modules have been wonderful for my 8-year-old. She now loves hearing stories from our mythology and understands values much better."
              </p>
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-full bg-muted flex items-center justify-center">
                  <span className="text-xs font-medium">RK</span>
                </div>
                <div>
                  <div className="text-sm font-medium">Rajesh Kumar</div>
                  <div className="text-xs text-muted-foreground">Parent</div>
                </div>
              </div>
            </div>
            
            <div className="border border-border rounded-lg p-6 bg-card">
              <div className="flex items-center gap-1 mb-4">
                {[...Array(5)].map((_, i) => (
                  <Star key={i} className="w-4 h-4 text-yellow-500 fill-current" />
                ))}
              </div>
              <p className="text-sm text-muted-foreground mb-4">
                "The certification program gave me the credentials I needed to start my own spiritual consulting practice. The training was thorough and practical."
              </p>
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-full bg-muted flex items-center justify-center">
                  <span className="text-xs font-medium">MP</span>
                </div>
                <div>
                  <div className="text-sm font-medium">Meera Pillai</div>
                  <div className="text-xs text-muted-foreground">Certified Practitioner</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  )
}
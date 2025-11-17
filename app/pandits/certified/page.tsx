'use client'
import React from 'react';
import Link from "next/link"
import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video } from "lucide-react"

// Schema markup for the main pandits page
const PANDITS_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Pandits & Experts - Navdhya",
  description: "Connect with verified and experienced Vedic pandits in Delhi NCR. Book authentic puja services, astrology consultations, and spiritual guidance from certified experts.",
  path: "/pandits",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Pandits & Experts", url: "https://www.navdhya.com/pandits" }
  ]
})

interface PanditService {
  id: number;
  title: string;
  excerpt: string;
  icon: React.ReactNode;
  href: string;
  color: string;
}

// Pandit services data
const panditServices: PanditService[] = [
  {
    id: 1,
    title: "Find a Pandit",
    excerpt: "Browse our directory of verified and experienced Vedic pandits specializing in various rituals and ceremonies.",
    icon: <Users className="w-6 h-6" />,
    href: "/pandits",
    color: "bg-blue-100 text-blue-800"
  },
  {
    id: 2,
    title: "Book Consultation",
    excerpt: "Schedule a personalized consultation with our experts for puja services, astrology readings, and spiritual guidance.",
    icon: <Calendar className="w-6 h-6" />,
    href: "/pandits/book",
    color: "bg-green-100 text-green-800"
  },
  {
    id: 3,
    title: "Join as Pandit",
    excerpt: "Are you a qualified pandit? Join our network and serve families in Delhi NCR with authentic Vedic services.",
    icon: <Award className="w-6 h-6" />,
    href: "/pandits/join",
    color: "bg-purple-100 text-purple-800"
  },
  {
    id: 4,
    title: "Insights",
    excerpt: "Expert articles, resources, and knowledge from our verified Vedic pandits on rituals, astrology, and spiritual practices.",
    icon: <Lightbulb className="w-6 h-6" />,
    href: "/pandits/insights",
    color: "bg-yellow-100 text-yellow-800"
  }
];

export default function PanditsPage() {
  const panditsSchema = JSON.stringify(PANDITS_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: panditsSchema }}
      />
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <Link href="/" className="hover:text-foreground">Home</Link>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">Pandits & Experts</span>
          </nav>
        </div>

        {/* Hero Banner */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div 
            className="relative h-40 sm:h-56 rounded-lg overflow-hidden bg-cover bg-center flex items-center"
            style={{
              backgroundImage: "url('https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg')"
            }}
          >
            <div className="absolute inset-0 bg-gradient-to-r from-black/80 to-black/40"></div>
            <div className="relative z-10 text-white p-4 sm:p-6 max-w-3xl">
              <h1 className="text-xl sm:text-2xl font-serif font-semibold mb-2 sm:mb-3">Pandits & Experts</h1>
              <p className="text-sm sm:text-base mb-3 sm:mb-4">
                Connect with verified Vedic pandits in Delhi NCR. Book authentic puja services and spiritual guidance.
              </p>
              <Link 
                href="#services"
                className="inline-flex items-center gap-1 bg-white text-[#0E3F2E] hover:bg-gray-100 font-medium py-1 px-3 sm:py-1.5 sm:px-4 rounded-md transition-all duration-300 shadow-sm text-xs sm:text-sm"
              >
                <span>Explore Services</span>
                <ArrowRight className="w-3 h-3 sm:w-4 sm:h-4" />
              </Link>
            </div>
          </div>
        </div>

        {/* Stats Bar */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-10">
          <div className="bg-card border border-border rounded-lg p-6 grid grid-cols-2 md:grid-cols-4 gap-6">
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">200+</div>
              <div className="text-sm text-muted-foreground">Verified Pandits</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">5000+</div>
              <div className="text-sm text-muted-foreground">Happy Families</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">4.9/5</div>
              <div className="text-sm text-muted-foreground">Average Rating</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">15+</div>
              <div className="text-sm text-muted-foreground">Years Experience</div>
            </div>
          </div>
        </div>

        {/* Pandit Services */}
        <div id="services" className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">Our Pandit Services</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Access a range of services from finding the right pandit to joining our network as a certified expert.
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {panditServices.map(service => (
              <Link 
                key={service.id}
                href={service.href}
                className="group border border-border rounded-lg overflow-hidden bg-card hover:shadow-lg transition-all duration-300 flex flex-col h-full"
              >
                <div className="p-6 flex-grow">
                  <div className={`w-12 h-12 rounded-lg ${service.color} flex items-center justify-center mb-4`}>
                    {service.icon}
                  </div>
                  <h3 className="font-medium text-lg mb-3 group-hover:text-[#0E3F2E] transition-colors">
                    {service.title}
                  </h3>
                  <p className="text-sm text-muted-foreground">
                    {service.excerpt}
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

        {/* Featured Pandits */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">Featured Pandits</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Meet some of our most experienced and highly-rated Vedic pandits ready to serve you.
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="border border-border rounded-xl overflow-hidden bg-card mx-2 sm:mx-0 shadow-sm hover:shadow-md transition-shadow duration-300">
              <div className="relative h-40 bg-muted">
                <img 
                  src="https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg" 
                  alt="Pandit Rajesh Sharma"
                  className="w-full h-full object-cover"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                <div className="absolute top-3 right-3">
                  <span className="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold bg-[#0E3F2E] shadow-md">
                    4.9
                  </span>
                </div>
              </div>
              <div className="p-4">
                <h3 className="font-semibold text-base mb-2 text-foreground">Pandit Rajesh Sharma</h3>
                <p className="text-xs text-muted-foreground mb-3">Vedic Rituals & Astrology Specialist</p>
                <div className="flex items-center gap-2 mb-3">
                  <div className="flex items-center gap-1">
                    <MapPin className="w-3.5 h-3.5 text-muted-foreground" />
                    <span className="text-xs text-muted-foreground">Delhi</span>
                  </div>
                  <span className="text-xs text-muted-foreground">•</span>
                  <span className="text-xs text-muted-foreground">15+ years</span>
                </div>
                <div className="flex flex-wrap gap-2 mb-4">
                  <span className="px-2 py-1 bg-muted rounded-full text-[10px] text-muted-foreground">
                    Griha Pravesh
                  </span>
                  <span className="px-2 py-1 bg-muted rounded-full text-[10px] text-muted-foreground">
                    Kundli Analysis
                  </span>
                  <span className="px-2 py-1 bg-muted rounded-full text-[10px] text-muted-foreground">
                    Satyanarayan Katha
                  </span>
                </div>
                <Link 
                  href="/pandits/1"
                  className="inline-flex items-center justify-center gap-2 w-full bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white font-medium py-2.5 px-4 rounded-lg transition-all duration-300 text-center text-sm shadow-sm hover:shadow-md hover:-translate-y-0.5"
                >
                  <span>View Profile</span>
                  <ArrowRight className="w-4 h-4" />
                </Link>
              </div>
            </div>
            
            <div className="border border-border rounded-xl overflow-hidden bg-card mx-2 sm:mx-0 shadow-sm hover:shadow-md transition-shadow duration-300">
              <div className="relative h-40 bg-muted">
                <img 
                  src="https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg" 
                  alt="Pandit Anil Verma"
                  className="w-full h-full object-cover"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                <div className="absolute top-3 right-3">
                  <span className="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold bg-[#0E3F2E] shadow-md">
                    4.9
                  </span>
                </div>
              </div>
              <div className="p-4">
                <h3 className="font-semibold text-base mb-2 text-foreground">Pandit Anil Verma</h3>
                <p className="text-xs text-muted-foreground mb-3">Business & Prosperity Rituals</p>
                <div className="flex items-center gap-2 mb-3">
                  <div className="flex items-center gap-1">
                    <MapPin className="w-3.5 h-3.5 text-muted-foreground" />
                    <span className="text-xs text-muted-foreground">Gurgaon</span>
                  </div>
                  <span className="text-xs text-muted-foreground">•</span>
                  <span className="text-xs text-muted-foreground">18+ years</span>
                </div>
                <div className="flex flex-wrap gap-2 mb-4">
                  <span className="px-2 py-1 bg-muted rounded-full text-[10px] text-muted-foreground">
                    Lakshmi Puja
                  </span>
                  <span className="px-2 py-1 bg-muted rounded-full text-[10px] text-muted-foreground">
                    Vastu Shanti
                  </span>
                  <span className="px-2 py-1 bg-muted rounded-full text-[10px] text-muted-foreground">
                    Navagraha Homa
                  </span>
                </div>
                <Link 
                  href="/pandits/3"
                  className="inline-flex items-center justify-center gap-2 w-full bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white font-medium py-2.5 px-4 rounded-lg transition-all duration-300 text-center text-sm shadow-sm hover:shadow-md hover:-translate-y-0.5"
                >
                  <span>View Profile</span>
                  <ArrowRight className="w-4 h-4" />
                </Link>
              </div>
            </div>
            
            <div className="border border-border rounded-xl overflow-hidden bg-card mx-2 sm:mx-0 shadow-sm hover:shadow-md transition-shadow duration-300">
              <div className="relative h-40 bg-muted">
                <img 
                  src="https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg" 
                  alt="Pandit Suresh Iyer"
                  className="w-full h-full object-cover"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                <div className="absolute top-3 right-3">
                  <span className="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold bg-[#0E3F2E] shadow-md">
                    4.8
                  </span>
                </div>
              </div>
              <div className="p-4">
                <h3 className="font-semibold text-base mb-2 text-foreground">Pandit Suresh Iyer</h3>
                <p className="text-xs text-muted-foreground mb-3">South Indian Rituals Specialist</p>
                <div className="flex items-center gap-2 mb-3">
                  <div className="flex items-center gap-1">
                    <MapPin className="w-3.5 h-3.5 text-muted-foreground" />
                    <span className="text-xs text-muted-foreground">Noida</span>
                  </div>
                  <span className="text-xs text-muted-foreground">•</span>
                  <span className="text-xs text-muted-foreground">12+ years</span>
                </div>
                <div className="flex flex-wrap gap-2 mb-4">
                  <span className="px-2 py-1 bg-muted rounded-full text-[10px] text-muted-foreground">
                    Pongal
                  </span>
                  <span className="px-2 py-1 bg-muted rounded-full text-[10px] text-muted-foreground">
                    Ganesh Puja
                  </span>
                  <span className="px-2 py-1 bg-muted rounded-full text-[10px] text-muted-foreground">
                    Annaprashan
                  </span>
                </div>
                <Link 
                  href="/pandits/2"
                  className="inline-flex items-center justify-center gap-2 w-full bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white font-medium py-2.5 px-4 rounded-lg transition-all duration-300 text-center text-sm shadow-sm hover:shadow-md hover:-translate-y-0.5"
                >
                  <span>View Profile</span>
                  <ArrowRight className="w-4 h-4" />
                </Link>
              </div>
            </div>
          </div>
        </div>

        {/* Testimonials */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">What Our Clients Say</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Hear from families who have experienced our authentic Vedic services through our verified pandits.
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
                "Pandit Rajesh Sharma conducted our Griha Pravesh ceremony with such precision and devotion. The positive energy in our new home is palpable. Highly recommended!"
              </p>
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-full bg-muted flex items-center justify-center">
                  <span className="text-xs font-medium">VS</span>
                </div>
                <div>
                  <div className="text-sm font-medium">Vikram Singh</div>
                  <div className="text-xs text-muted-foreground">Gurgaon</div>
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
                "The baby naming ceremony performed by Pandit Suresh Iyer was beautiful and authentic. He took care to explain each step and made our family feel comfortable throughout."
              </p>
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-full bg-muted flex items-center justify-center">
                  <span className="text-xs font-medium">PG</span>
                </div>
                <div>
                  <div className="text-sm font-medium">Priya Gupta</div>
                  <div className="text-xs text-muted-foreground">Delhi</div>
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
                "Our office puja conducted by Pandit Anil Verma brought tremendous positive energy to our workspace. Business has improved significantly since then. Truly grateful!"
              </p>
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-full bg-muted flex items-center justify-center">
                  <span className="text-xs font-medium">RM</span>
                </div>
                <div>
                  <div className="text-sm font-medium">Rahul Mehta</div>
                  <div className="text-xs text-muted-foreground">Noida</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  )
}
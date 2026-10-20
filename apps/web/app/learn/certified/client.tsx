'use client'
import React, { useState, useMemo } from 'react';
import Link from "next/link"
import Script from "next/script"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video } from "lucide-react"

interface Certification {
  id: number;
  title: string;
  excerpt: string;
  instructor: string;
  duration: string;
  lessons: number;
  level: string;
  rating: number;
  price: string;
  originalPrice?: string;
  image: string;
  category: string;
}

// Certification data
const certifications: Certification[] = [
  {
    id: 1,
    title: "Vedic Rituals Certification",
    excerpt: "Professional certification in conducting traditional Vedic rituals with proper mantras and procedures.",
    instructor: "Pandit Rajesh Sharma",
    duration: "12 weeks",
    lessons: 24,
    level: "Professional",
    rating: 4.9,
    price: "₹7,999",
    originalPrice: "₹12,999",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg",
    category: "Rituals"
  },
  {
    id: 2,
    title: "Astrology Certification",
    excerpt: "Comprehensive training in Vedic astrology including chart reading, predictions, and remedial measures.",
    instructor: "Dr. Priya Sharma",
    duration: "16 weeks",
    lessons: 32,
    level: "Professional",
    rating: 4.8,
    price: "₹9,999",
    originalPrice: "₹15,999",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_db05ba7c437e48abe6440798d2a33b09dr_bokdku.jpg",
    category: "Astrology"
  },
  {
    id: 3,
    title: "Yoga Instructor Certification",
    excerpt: "Authorized certification to teach traditional yoga practices including asanas, pranayama, and meditation.",
    instructor: "Swami Anand",
    duration: "10 weeks",
    lessons: 20,
    level: "Professional",
    rating: 4.9,
    price: "₹6,999",
    originalPrice: "₹10,999",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg",
    category: "Yoga"
  },
  {
    id: 4,
    title: "Mantra Science Certification",
    excerpt: "Expert-level certification in the science of sacred sounds and their applications in healing.",
    instructor: "Pandit Suresh Patel",
    duration: "8 weeks",
    lessons: 16,
    level: "Advanced",
    rating: 4.8,
    price: "₹5,999",
    originalPrice: "₹8,999",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    category: "Mantras"
  }
];

const categories = [
  "All", "Rituals", "Astrology", "Yoga", "Mantras"
];

const levels = [
  "All Levels", "Professional", "Advanced"
];

export default function CertificationClient({ certificationSchema }: { certificationSchema: string }) {
  const [categoryFilter, setCategoryFilter] = useState("All");
  const [levelFilter, setLevelFilter] = useState("All Levels");
  
  const filteredCertifications = useMemo(() => certifications.filter(cert => 
    (categoryFilter === "All" || cert.category === categoryFilter) &&
    (levelFilter === "All Levels" || cert.level === levelFilter)
  ), [certifications, categoryFilter, levelFilter]);
  
  const CertificationCard = ({ cert }: { cert: Certification }) => {
    return (
      <div className="group border border-border rounded-lg overflow-hidden bg-card flex flex-col h-full">
        <div className="relative bg-muted flex-shrink-0">
          <img 
            src={cert.image} 
            alt={cert.title}
            className="w-full h-40 object-cover group-hover:scale-105 transition-transform duration-300"
          />
          <div className="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
          
          {cert.originalPrice && (
            <div className="absolute top-2 right-2">
              <span className="px-2 py-1 bg-red-600 text-white text-[10px] rounded-full">
                Save ₹{parseInt(cert.originalPrice.replace(/[₹,]/g, "")) - parseInt(cert.price.replace(/[₹,]/g, ""))}
              </span>
            </div>
          )}
          
          <div className="absolute bottom-2 left-2 right-2">
            <div className="flex items-center justify-between">
              <span className="px-2 py-1 bg-[#0E3F2E]/80 text-white text-[10px] rounded-full">
                {cert.category}
              </span>
              <span className="px-2 py-1 bg-background/80 text-foreground text-[10px] rounded-full">
                {cert.level}
              </span>
            </div>
          </div>
        </div>
        
        <div className="p-4 space-y-3 flex flex-col flex-grow">
          <div className="flex justify-between items-start">
            <h3 className="font-medium hover:text-[#0E3F2E] transition-colors line-clamp-2 text-sm flex-grow">
              {cert.title}
            </h3>
            <div className="flex items-center gap-1 ml-2">
              <Star className="w-3 h-3 text-yellow-500 fill-current" />
              <span className="text-[10px] font-medium">{cert.rating}</span>
            </div>
          </div>
          
          <p className="text-[11px] text-muted-foreground line-clamp-2">
            {cert.excerpt}
          </p>
          
          <div className="flex items-center gap-2 text-[10px] text-muted-foreground">
            <div className="flex items-center gap-1">
              <Play className="w-3 h-3" />
              <span>{cert.lessons} modules</span>
            </div>
            <span>•</span>
            <div className="flex items-center gap-1">
              <Clock className="w-3 h-3" />
              <span>{cert.duration}</span>
            </div>
          </div>
          
          <div className="flex items-center justify-between pt-2">
            <div className="flex items-center gap-2">
              <div className="w-6 h-6 rounded-full bg-muted flex items-center justify-center">
                <span className="text-[8px] font-medium">
                  {cert.instructor.split(' ').map(n => n[0]).join('')}
                </span>
              </div>
              <div className="text-[10px] font-medium">{cert.instructor}</div>
            </div>
          </div>
          
          <div className="flex items-center justify-between pt-2">
            <div className="flex items-center gap-1">
              {cert.originalPrice && (
                <span className="text-[10px] text-muted-foreground line-through">{cert.originalPrice}</span>
              )}
              <span className={`font-medium text-sm ${cert.originalPrice ? 'text-red-600' : 'text-foreground'}`}>
                {cert.price}
              </span>
            </div>
            <Link 
              href={`/learn/certified/${cert.id}`}
              className="inline-flex items-center justify-center gap-1 bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white font-medium py-1.5 px-3 rounded-md transition-all duration-300 text-center text-[11px]"
            >
              <span>Enroll Now</span>
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
        dangerouslySetInnerHTML={{ __html: certificationSchema }}
      />
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <Link href="/" className="hover:text-foreground">Home</Link>
            <span className="mx-1.5">/</span>
            <Link href="/learn" className="hover:text-foreground">Learning</Link>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">Certification Programs</span>
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
              <h1 className="text-2xl font-serif font-semibold">Certification Programs</h1>
              <p className="text-sm mt-1">
                Earn authorized cultural training certifications in Vedic rituals, astrology, and spiritual practices. Advance your knowledge and credentials with our professional certification programs.
              </p>
            </div>
          </div>
        </div>

        {/* Stats Bar */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div className="bg-card border border-border rounded-lg p-4 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">15+</div>
              <div className="text-xs text-muted-foreground">Certifications Available</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">300+</div>
              <div className="text-xs text-muted-foreground">Certified Professionals</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">4.9/5</div>
              <div className="text-xs text-muted-foreground">Average Rating</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">100%</div>
              <div className="text-xs text-muted-foreground">Industry Recognition</div>
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
              <div className="text-xs font-medium mb-2">Level</div>
              <div className="flex flex-wrap gap-2">
                {levels.map((level, index) => (
                  <button
                    key={index}
                    onClick={() => setLevelFilter(level)}
                    className={`px-3 py-1.5 text-xs rounded-full transition-colors ${
                      levelFilter === level
                        ? 'bg-[#0E3F2E] text-white' 
                        : 'bg-background hover:bg-muted border border-border text-foreground'
                    }`}
                  >
                    {level}
                  </button>
                ))}
              </div>
            </div>
          </div>
        </div>

        {/* Certifications Grid */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 pb-10">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {filteredCertifications.map(cert => (
              <div key={cert.id} className="flex">
                <CertificationCard cert={cert} />
              </div>
            ))}
          </div>
        </div>
      </div>
    </>
  )
}
'use client'

import React, { useState } from 'react';
import Link from "next/link"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video, Search, MessageCircle, ThumbsUp, Reply, Flag, MoreHorizontal, User, Shield, Check, Plus, BookOpen as BookOpenIcon } from "lucide-react"

// Vedic rituals categories
const ritualCategories = [
  {
    id: 1,
    name: "Samskaras",
    description: "Sacraments marking important life transitions",
    icon: BookOpenIcon,
    rituals: [
      "Upanayana (Sacred Thread)",
      "Vivaha (Marriage)",
      "Antyeshti (Funeral Rites)",
      "Namakarana (Naming)",
      "Annaprashana (First Feeding)"
    ]
  },
  {
    id: 2,
    name: "Yajnas",
    description: "Fire sacrifices for specific purposes",
    icon: Flame,
    rituals: [
      "Agnihotra (Daily Fire Offering)",
      "Sandhyavandanam (Twilight Worship)",
      "Purnahoma (Full Moon Offering)",
      "Chaturmasya (Seasonal Rituals)",
      "Ashvamedha (Horse Sacrifice)"
    ]
  },
  {
    id: 3,
    name: "Pujas",
    description: "Worship ceremonies for deities",
    icon: Heart,
    rituals: [
      "Ganesh Puja",
      "Lakshmi Puja",
      "Saraswati Puja",
      "Shiva Puja",
      "Vishnu Puja"
    ]
  },
  {
    id: 4,
    name: "Upavasas",
    description: "Fasting rituals for purification",
    icon: Leaf,
    rituals: [
      "Ekadashi Vrat",
      "Shivratri Vrat",
      "Navratri Vrat",
      "Karva Chauth",
      "Teej Vrat"
    ]
  }
];

// Vedic rituals packages
const ritualPackages = [
  {
    id: 1,
    name: "Scholar Guidance",
    price: "₹1,999",
    originalPrice: "₹2,999",
    description: "Consultation with Vedic scholar for ritual understanding",
    features: [
      "1-hour consultation",
      "Ritual explanation",
      "Personal guidance",
      "Scriptural references",
      "Follow-up support"
    ],
    popular: false
  },
  {
    id: 2,
    name: "Complete Ritual",
    price: "₹4,999",
    originalPrice: "₹6,999",
    description: "Full Vedic ritual performed by certified pandits",
    features: [
      "Complete ritual performance",
      "Certified Vedic pandits",
      "All necessary materials",
      "Personalized guidance",
      "Prasad distribution",
      "Recorded ceremony"
    ],
    popular: true
  },
  {
    id: 3,
    name: "Scholar & Ritual",
    price: "₹7,999",
    originalPrice: "₹9,999",
    description: "Comprehensive package with scholar and full ritual",
    features: [
      "Pre-ritual consultation",
      "Complete ritual performance",
      "Senior Vedic scholars",
      "All necessary materials",
      "Personalized guidance",
      "Prasad distribution",
      "Recorded ceremony",
      "Post-ritual consultation"
    ],
    popular: false
  }
];

// Testimonials data
const testimonials = [
  {
    id: 1,
    name: "Dr. Priya Sharma",
    location: "Bangalore",
    rating: 5,
    comment: "The Vedic scholars were incredibly knowledgeable. They explained the significance of each ritual in detail, making the experience both spiritual and educational.",
    date: "2025-10-20"
  },
  {
    id: 2,
    name: "Prof. Ramesh Iyer",
    location: "Chennai",
    rating: 5,
    comment: "As a scholar myself, I appreciated the authenticity and precision of the rituals. The pandits followed the scriptures meticulously while making it accessible to us.",
    date: "2025-10-15"
  },
  {
    id: 3,
    name: "Sneha Patel",
    location: "Ahmedabad",
    rating: 4,
    comment: "The upanayana ceremony for my son was conducted with great reverence. The scholars explained the significance to our entire family, making it memorable.",
    date: "2025-10-10"
  }
];

// FAQ data
const faqData = [
  {
    question: "What makes Vedic rituals different from regular pujas?",
    answer: "Vedic rituals follow ancient scriptures (Vedas) with precise procedures, mantras, and timings. They require specialized knowledge and are performed for specific spiritual purposes beyond general worship."
  },
  {
    question: "Do I need to understand Sanskrit for Vedic rituals?",
    answer: "No, our scholars will guide you through the process in your preferred language. However, understanding the meaning enhances the spiritual experience, so we provide explanations throughout the ritual."
  },
  {
    question: "How long do Vedic rituals typically last?",
    answer: "Duration varies from 1-2 hours for simple rituals to several days for complex ceremonies like yajnas. The exact time will be discussed during your consultation based on your specific needs."
  },
  {
    question: "Can Vedic rituals be performed at home?",
    answer: "Yes, most Vedic rituals can be performed at home with proper arrangements. Our scholars will guide you on the space requirements and preparations needed."
  }
];

// FAQ Item Component with progressive disclosure
const FAQItem = ({ question, answer, isOpen, onClick }: { question: string; answer: string; isOpen: boolean; onClick: () => void }) => {
  return (
    <div className="bg-card border border-border rounded-lg overflow-hidden transition-all duration-200 hover:shadow-sm">
      <button
        className="flex justify-between items-center w-full p-4 text-left focus:outline-none"
        onClick={onClick}
        aria-expanded={isOpen}
      >
        <h3 className="text-sm font-medium text-foreground">{question}</h3>
        <ChevronDown 
          className={`w-5 h-5 text-muted-foreground transition-transform duration-200 ${isOpen ? 'rotate-180' : ''}`} 
        />
      </button>
      <div 
        className={`overflow-hidden transition-all duration-200 ease-in-out ${
          isOpen ? 'max-h-96 opacity-100 pb-4' : 'max-h-0 opacity-0'
        }`}
      >
        <div className="px-4 text-muted-foreground text-sm">
          <p>{answer}</p>
        </div>
      </div>
    </div>
  );
};

// Adding the missing Flame and Leaf icons since they're not imported
function Flame(props: React.SVGProps<SVGSVGElement>) {
  return (
    <svg
      {...props}
      xmlns="http://www.w3.org/2000/svg"
      width="24"
      height="24"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    >
      <path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z" />
    </svg>
  )
}

function Leaf(props: React.SVGProps<SVGSVGElement>) {
  return (
    <svg
      {...props}
      xmlns="http://www.w3.org/2000/svg"
      width="24"
      height="24"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    >
      <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z" />
      <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12" />
    </svg>
  )
}

export default function VedicRitualsClient() {
  const [openFaqIndex, setOpenFaqIndex] = useState<number | null>(null)
  const [selectedPackage, setSelectedPackage] = useState(2); // Default to Complete Ritual

  const toggleFaq = (index: number) => {
    setOpenFaqIndex(openFaqIndex === index ? null : index)
  };

  return (
    <div className="min-h-screen bg-background">
      {/* Breadcrumb */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
        <nav className="text-xs text-muted-foreground">
          <Link href="/" className="hover:text-foreground">Home</Link>
          <span className="mx-1.5">/</span>
          <Link href="/pujas" className="hover:text-foreground">Puja Services</Link>
          <span className="mx-1.5">/</span>
          <span className="text-foreground">Vedic Rituals</span>
        </nav>
      </div>

      {/* Hero Banner */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-8">
        <div className="relative rounded-lg overflow-hidden bg-cover bg-center flex items-center h-80"
          style={{
            backgroundImage: "url('https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg')"
          }}
        >
          <div className="absolute inset-0 bg-gradient-to-r from-black/80 to-black/40"></div>
          <div className="relative z-10 text-white p-8 max-w-3xl">
            <div className="inline-block bg-white/20 backdrop-blur-sm text-white px-3 py-1 rounded-full text-sm font-medium mb-4">
              <Shield className="w-4 h-4 inline mr-1" />
              Ancient Wisdom
            </div>
            <h1 className="text-3xl font-serif font-semibold mb-4">Vedic Rituals</h1>
            <p className="text-lg mb-6">
              Authentic Vedic rituals performed by certified scholars for spiritual growth, purification, and divine blessings.
            </p>
            <Link 
              href="/book"
              className="inline-flex items-center gap-2 bg-white text-[#0E3F2E] hover:bg-gray-100 font-medium py-3 px-6 rounded-lg transition-all duration-300 shadow-sm"
            >
              <span>Book Now</span>
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
            <div className="text-sm text-muted-foreground">Vedic Rituals Performed</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-[#0E3F2E] mb-2">4.9/5</div>
            <div className="text-sm text-muted-foreground">Average Rating</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-[#0E3F2E] mb-2">30+</div>
            <div className="text-sm text-muted-foreground">Types of Rituals</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-[#0E3F2E] mb-2">20+</div>
            <div className="text-sm text-muted-foreground">Years of Expertise</div>
          </div>
        </div>
      </div>

      {/* About Vedic Rituals */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
        <div className="bg-card border border-border rounded-lg p-8">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div>
              <h2 className="text-2xl font-serif font-semibold mb-4">The Sacred Tradition of Vedic Rituals</h2>
              <p className="text-muted-foreground mb-4">
                Vedic rituals are ancient practices rooted in the sacred Vedas, dating back thousands of years. These ceremonies connect us with cosmic energies and divine principles through precise procedures, mantras, and offerings.
              </p>
              <p className="text-muted-foreground mb-4">
                Each ritual serves a specific purpose - from purifying the mind and body to invoking divine blessings for prosperity, health, and spiritual growth. Performed by knowledgeable scholars, these rituals create positive vibrations that benefit individuals and communities.
              </p>
              <div className="flex items-center gap-4 mt-6">
                <div className="flex items-center gap-2">
                  <Check className="w-5 h-5 text-[#0E3F2E]" />
                  <span className="text-sm">Scripturally Authentic</span>
                </div>
                <div className="flex items-center gap-2">
                  <Check className="w-5 h-5 text-[#0E3F2E]" />
                  <span className="text-sm">Scholar Guidance</span>
                </div>
              </div>
            </div>
            <div className="bg-muted rounded-lg overflow-hidden">
              <img 
                src="https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg" 
                alt="Vedic Rituals" 
                className="w-full h-full object-cover"
              />
            </div>
          </div>
        </div>
      </div>

      {/* Ritual Categories */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
        <div className="text-center mb-10">
          <h2 className="text-2xl font-serif font-semibold mb-4">Categories of Vedic Rituals</h2>
          <p className="text-muted-foreground max-w-2xl mx-auto">
            Different types of rituals for various spiritual purposes
          </p>
        </div>
        
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {ritualCategories.map((category) => (
            <div key={category.id} className="border border-border rounded-lg p-6 bg-card">
              <div className="w-12 h-12 rounded-full bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
                <category.icon className="w-6 h-6" />
              </div>
              <h3 className="font-medium text-lg mb-2">{category.name}</h3>
              <p className="text-sm text-muted-foreground mb-4">{category.description}</p>
              <ul className="space-y-2">
                {category.rituals.map((ritual, index) => (
                  <li key={index} className="flex items-start gap-2">
                    <Plus className="w-4 h-4 text-[#0E3F2E] mt-0.5 flex-shrink-0" />
                    <span className="text-sm">{ritual}</span>
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>
      </div>

      {/* Vedic Rituals Packages */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
        <div className="text-center mb-10">
          <h2 className="text-2xl font-serif font-semibold mb-4">Vedic Rituals Packages</h2>
          <p className="text-muted-foreground max-w-2xl mx-auto">
            Choose the perfect package for your spiritual journey
          </p>
        </div>
        
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {ritualPackages.map((pkg) => (
            <div 
              key={pkg.id} 
              className={`border border-border rounded-lg overflow-hidden bg-card flex flex-col h-full relative ${
                pkg.popular ? 'ring-2 ring-[#0E3F2E]' : ''
              }`}
            >
              {pkg.popular && (
                <div className="absolute top-4 right-4 z-10">
                  <span className="bg-[#0E3F2E] text-white px-3 py-1 rounded-full text-xs font-medium">
                    Most Popular
                  </span>
                </div>
              )}
              
              <div className="p-6 flex-grow">
                <h3 className="font-medium text-lg mb-2">{pkg.name}</h3>
                <div className="mb-4">
                  <span className="text-2xl font-bold text-[#0E3F2E]">{pkg.price}</span>
                  <span className="text-sm text-muted-foreground line-through ml-2">{pkg.originalPrice}</span>
                </div>
                <p className="text-sm text-muted-foreground mb-4">{pkg.description}</p>
                
                <ul className="space-y-2 mb-6">
                  {pkg.features.map((feature, index) => (
                    <li key={index} className="flex items-start gap-2">
                      <Check className="w-4 h-4 text-[#0E3F2E] mt-0.5 flex-shrink-0" />
                      <span className="text-sm">{feature}</span>
                    </li>
                  ))}
                </ul>
              </div>
              
              <div className="px-6 py-4 bg-muted/50 border-t border-border">
                <button 
                  className={`w-full py-2.5 rounded-md text-sm font-medium transition-all duration-300 ${
                    selectedPackage === pkg.id
                      ? 'bg-[#0E3F2E] text-white'
                      : 'bg-background border border-border text-foreground hover:bg-muted'
                  }`}
                  onClick={() => setSelectedPackage(pkg.id)}
                >
                  {selectedPackage === pkg.id ? 'Selected' : 'Select Package'}
                </button>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Testimonials */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
        <div className="text-center mb-10">
          <h2 className="text-2xl font-serif font-semibold mb-4">Sacred Experiences</h2>
          <p className="text-muted-foreground max-w-2xl mx-auto">
            Hear from those who have experienced our Vedic rituals
          </p>
        </div>
        
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {testimonials.map((testimonial) => (
            <div key={testimonial.id} className="border border-border rounded-lg p-6 bg-card">
              <div className="flex items-center gap-1 mb-4">
                {[...Array(5)].map((_, i) => (
                  <Star 
                    key={i} 
                    className={`w-4 h-4 ${
                      i < testimonial.rating 
                        ? 'text-yellow-500 fill-current' 
                        : 'text-gray-300'
                    }`} 
                  />
                ))}
              </div>
              <p className="text-sm text-muted-foreground mb-4">
                "{testimonial.comment}"
              </p>
              <div className="flex items-center justify-between">
                <div>
                  <div className="font-medium text-sm">{testimonial.name}</div>
                  <div className="text-xs text-muted-foreground">{testimonial.location}</div>
                </div>
                <div className="text-xs text-muted-foreground">
                  {new Date(testimonial.date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* FAQ Section */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 pb-12">
        <div className="text-center mb-10">
          <h2 className="text-2xl font-serif font-semibold mb-4">Frequently Asked Questions</h2>
          <p className="text-muted-foreground max-w-2xl mx-auto">
            Find answers to common questions about our Vedic rituals
          </p>
        </div>
        
        <div className="max-w-3xl mx-auto space-y-4">
          {faqData.map((faq, index) => (
            <FAQItem
              key={index}
              question={faq.question}
              answer={faq.answer}
              isOpen={openFaqIndex === index}
              onClick={() => toggleFaq(index)}
            />
          ))}
        </div>
      </div>
    </div>
  )
}
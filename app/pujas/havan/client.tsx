'use client'

import React, { useState } from 'react';
import Link from "next/link"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video, Search, MessageCircle, ThumbsUp, Reply, Flag, MoreHorizontal, User, Flame, Check, Plus, Leaf } from "lucide-react"

// Havan & Yagya packages data
const havanPackages = [
  {
    id: 1,
    name: "Basic Havan",
    price: "₹2,999",
    originalPrice: "₹3,999",
    description: "Simple havan ceremony with essential offerings",
    features: [
      "1008 havan kund",
      "Basic ghee & samagri",
      "Standard mantras",
      "One certified pandit",
      "Guidance throughout ceremony"
    ],
    popular: false
  },
  {
    id: 2,
    name: "Premium Havan",
    price: "₹5,999",
    originalPrice: "₹7,999",
    description: "Enhanced havan with extended rituals",
    features: [
      "1008 havan kund",
      "Premium ghee & samagri",
      "Extended mantras",
      "Two certified pandits",
      "Personalized guidance",
      "Prasad distribution"
    ],
    popular: true
  },
  {
    id: 3,
    name: "Special Yagya",
    price: "₹9,999",
    originalPrice: "₹12,999",
    description: "Complete yagya experience with multiple pandits",
    features: [
      "1008 havan kund",
      "Premium ghee & samagri",
      "Extended mantras",
      "Three senior pandits",
      "Personalized guidance",
      "Prasad distribution",
      "Recorded ceremony",
      "Post-yagya consultation",
      "Special offerings"
    ],
    popular: false
  }
];

// Types of Havan/Yagya
const havanTypes = [
  {
    id: 1,
    name: "Ganesh Havan",
    purpose: "Removal of obstacles",
    duration: "1-2 hours",
    icon: Leaf
  },
  {
    id: 2,
    name: "Navagraha Havan",
    purpose: "Planetary peace",
    duration: "2-3 hours",
    icon: Star
  },
  {
    id: 3,
    name: "Lakshmi Havan",
    purpose: "Prosperity & wealth",
    duration: "1.5-2 hours",
    icon: Heart
  },
  {
    id: 4,
    name: "Santana Havan",
    purpose: "Family welfare",
    duration: "2-2.5 hours",
    icon: Users
  }
];

// Testimonials data
const testimonials = [
  {
    id: 1,
    name: "Amitabh Verma",
    location: "Delhi",
    rating: 5,
    comment: "The havan was performed with great devotion and expertise. The pandits explained the significance of each offering, making it a truly enlightening experience.",
    date: "2025-10-18"
  },
  {
    id: 2,
    name: "Shalini Rao",
    location: "Mumbai",
    rating: 5,
    comment: "We organized a Lakshmi havan for our new business. The ceremony was conducted flawlessly and we've seen positive changes since then. Highly recommended!",
    date: "2025-10-12"
  },
  {
    id: 3,
    name: "Ramesh Iyer",
    location: "Chennai",
    rating: 4,
    comment: "The pandits were knowledgeable and respectful of our family traditions. The havan kund was set up beautifully and the entire process was smooth.",
    date: "2025-10-06"
  }
];

// FAQ data
const faqData = [
  {
    question: "What is the significance of Havan/Yagya?",
    answer: "Havan/Yagya is a sacred fire ritual that purifies the environment, invokes divine blessings, and brings harmony to life. The offerings made in the sacred fire carry our prayers to the divine realm."
  },
  {
    question: "How long does a typical havan last?",
    answer: "Basic havans last 1-2 hours, while more elaborate yagyas can extend to 3-4 hours. The duration depends on the type of havan and the number of offerings."
  },
  {
    question: "Can I participate in the havan?",
    answer: "Yes, family members can participate by offering ghee, grains, and other materials into the sacred fire. The pandits will guide you on the proper procedures."
  },
  {
    question: "What preparations are needed at home?",
    answer: "We handle all the arrangements including the havan kund, materials, and setup. You just need to provide a clean, open space and ensure no flammable materials are nearby."
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

export default function HavanYagyaClient() {
  const [openFaqIndex, setOpenFaqIndex] = useState<number | null>(null)
  const [selectedPackage, setSelectedPackage] = useState(2); // Default to Premium

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
          <span className="text-foreground">Havan & Yagya</span>
        </nav>
      </div>

      {/* Hero Banner */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-8">
        <div className="relative rounded-lg overflow-hidden bg-cover bg-center flex items-center h-80"
          style={{
            backgroundImage: "url('https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg')"
          }}
        >
          <div className="absolute inset-0 bg-gradient-to-r from-black/80 to-black/40"></div>
          <div className="relative z-10 text-white p-8 max-w-3xl">
            <div className="inline-block bg-white/20 backdrop-blur-sm text-white px-3 py-1 rounded-full text-sm font-medium mb-4">
              <Flame className="w-4 h-4 inline mr-1" />
              Sacred Fire Ritual
            </div>
            <h1 className="text-3xl font-serif font-semibold mb-4">Havan & Yagya</h1>
            <p className="text-lg mb-6">
              Sacred fire rituals performed by certified Vedic pandits for purification, prosperity, and spiritual upliftment.
            </p>
            <Link 
              href="/booking"
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
            <div className="text-2xl font-bold text-[#0E3F2E] mb-2">1500+</div>
            <div className="text-sm text-muted-foreground">Havans Performed</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-[#0E3F2E] mb-2">4.9/5</div>
            <div className="text-sm text-muted-foreground">Average Rating</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-[#0E3F2E] mb-2">50+</div>
            <div className="text-sm text-muted-foreground">Types of Havan</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-[#0E3F2E] mb-2">25+</div>
            <div className="text-sm text-muted-foreground">Years of Expertise</div>
          </div>
        </div>
      </div>

      {/* About Havan & Yagya */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
        <div className="bg-card border border-border rounded-lg p-8">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div>
              <h2 className="text-2xl font-serif font-semibold mb-4">The Sacred Tradition of Havan & Yagya</h2>
              <p className="text-muted-foreground mb-4">
                Havan and Yagya are ancient Vedic rituals that involve making offerings into a consecrated fire while chanting specific mantras. These sacred ceremonies are performed to invoke divine blessings, purify the environment, and achieve specific spiritual or material goals.
              </p>
              <p className="text-muted-foreground mb-4">
                The fire (Agni) is considered a divine messenger that carries our offerings and prayers to the celestial beings. The ritual creates positive vibrations that promote harmony, health, and prosperity.
              </p>
              <div className="flex items-center gap-4 mt-6">
                <div className="flex items-center gap-2">
                  <Check className="w-5 h-5 text-[#0E3F2E]" />
                  <span className="text-sm">Vedic Authenticity</span>
                </div>
                <div className="flex items-center gap-2">
                  <Check className="w-5 h-5 text-[#0E3F2E]" />
                  <span className="text-sm">Certified Pandits</span>
                </div>
              </div>
            </div>
            <div className="bg-muted rounded-lg overflow-hidden">
              <img 
                src="https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg" 
                alt="Havan & Yagya Ceremony" 
                className="w-full h-full object-cover"
              />
            </div>
          </div>
        </div>
      </div>

      {/* Types of Havan/Yagya */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
        <div className="text-center mb-10">
          <h2 className="text-2xl font-serif font-semibold mb-4">Types of Havan & Yagya</h2>
          <p className="text-muted-foreground max-w-2xl mx-auto">
            Different havans for various purposes and life situations
          </p>
        </div>
        
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {havanTypes.map((type) => (
            <div key={type.id} className="border border-border rounded-lg p-6 bg-card text-center">
              <div className="w-12 h-12 rounded-full bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
                <type.icon className="w-6 h-6" />
              </div>
              <h3 className="font-medium text-lg mb-2">{type.name}</h3>
              <p className="text-sm text-muted-foreground mb-3">{type.purpose}</p>
              <div className="inline-block bg-muted px-3 py-1 rounded-full text-xs">
                {type.duration}
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Havan & Yagya Packages */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
        <div className="text-center mb-10">
          <h2 className="text-2xl font-serif font-semibold mb-4">Havan & Yagya Packages</h2>
          <p className="text-muted-foreground max-w-2xl mx-auto">
            Choose the perfect package for your spiritual needs
          </p>
        </div>
        
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {havanPackages.map((pkg) => (
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
            Hear from those who have experienced our havan and yagya services
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
            Find answers to common questions about our havan and yagya services
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
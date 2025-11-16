'use client'

import React, { useState } from 'react';
import Link from "next/link"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video, Search, MessageCircle, ThumbsUp, Reply, Flag, MoreHorizontal, User, Home, Check, Plus, Minus } from "lucide-react"

// Puja packages data
const pujaPackages = [
  {
    id: 1,
    name: "Basic Puja",
    price: "₹1,999",
    originalPrice: "₹2,999",
    description: "Essential puja service with one pandit",
    features: [
      "One certified pandit",
      "Basic puja samagri",
      "Standard rituals",
      "Guidance throughout ceremony"
    ],
    popular: false
  },
  {
    id: 2,
    name: "Premium Puja",
    price: "₹3,499",
    originalPrice: "₹4,999",
    description: "Comprehensive puja service with enhanced offerings",
    features: [
      "One senior pandit",
      "Premium puja samagri",
      "Extended rituals",
      "Personalized guidance",
      "Prasad distribution"
    ],
    popular: true
  },
  {
    id: 3,
    name: "Family Puja",
    price: "₹4,999",
    originalPrice: "₹6,999",
    description: "Complete family puja experience with multiple pandits",
    features: [
      "Two certified pandits",
      "Premium puja samagri",
      "Extended rituals",
      "Personalized guidance",
      "Prasad distribution",
      "Recorded ceremony",
      "Post-puja consultation"
    ],
    popular: false
  }
];

// Testimonials data
const testimonials = [
  {
    id: 1,
    name: "Priya Sharma",
    location: "Delhi",
    rating: 5,
    comment: "The pandit was knowledgeable and conducted the puja with great devotion. The setup was perfect and the experience was truly spiritual.",
    date: "2025-10-15"
  },
  {
    id: 2,
    name: "Rajesh Kumar",
    location: "Noida",
    rating: 5,
    comment: "Booking was seamless and the pandit arrived on time. The puja was performed exactly as per our traditions. Highly recommended!",
    date: "2025-10-10"
  },
  {
    id: 3,
    name: "Anita Desai",
    location: "Gurgaon",
    rating: 4,
    comment: "Great service overall. The pandit was respectful and explained everything clearly. Will definitely book again for future pujas.",
    date: "2025-10-05"
  }
];

// FAQ data
const faqData = [
  {
    question: "How far in advance should I book?",
    answer: "We recommend booking at least 3-7 days in advance to ensure availability of pandits and materials. For popular festivals or special occasions, booking 2-3 weeks ahead is advisable."
  },
  {
    question: "What do I need to prepare at home?",
    answer: "Our pandits will bring all the necessary puja samagri. You just need to provide a clean space for the puja setup. If you have any specific family traditions, please let us know in advance."
  },
  {
    question: "How long does a typical home puja last?",
    answer: "Most home pujas last between 1-2 hours depending on the complexity of the rituals. The pandit will discuss the timeline with you before starting."
  },
  {
    question: "Can I request specific rituals or mantras?",
    answer: "Absolutely! Our pandits are flexible and can incorporate specific rituals or mantras according to your family traditions and preferences."
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

export default function PujaHomeClient() {
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
          <span className="text-foreground">Puja at Home</span>
        </nav>
      </div>

      {/* Hero Banner */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-8">
        <div className="relative rounded-lg overflow-hidden bg-cover bg-center flex items-center h-80"
          style={{
            backgroundImage: "url('https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg')"
          }}
        >
          <div className="absolute inset-0 bg-gradient-to-r from-black/80 to-black/40"></div>
          <div className="relative z-10 text-white p-8 max-w-3xl">
            <div className="inline-block bg-white/20 backdrop-blur-sm text-white px-3 py-1 rounded-full text-sm font-medium mb-4">
              <Home className="w-4 h-4 inline mr-1" />
              Home Service
            </div>
            <h1 className="text-3xl font-serif font-semibold mb-4">Puja at Home</h1>
            <p className="text-lg mb-6">
              Experience authentic Hindu pujas performed by certified Vedic pandits in the comfort of your home.
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
            <div className="text-2xl font-bold text-[#0E3F2E] mb-2">5000+</div>
            <div className="text-sm text-muted-foreground">Home Pujas Performed</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-[#0E3F2E] mb-2">4.9/5</div>
            <div className="text-sm text-muted-foreground">Average Rating</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-[#0E3F2E] mb-2">200+</div>
            <div className="text-sm text-muted-foreground">Certified Pandits</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-[#0E3F2E] mb-2">15+</div>
            <div className="text-sm text-muted-foreground">Years of Service</div>
          </div>
        </div>
      </div>

      {/* How It Works */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
        <div className="text-center mb-10">
          <h2 className="text-2xl font-serif font-semibold mb-4">How It Works</h2>
          <p className="text-muted-foreground max-w-2xl mx-auto">
            Simple steps to bring divine blessings into your home
          </p>
        </div>
        
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="border border-border rounded-lg p-6 bg-card text-center">
            <div className="w-12 h-12 rounded-full bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
              <Calendar className="w-6 h-6" />
            </div>
            <h3 className="font-medium text-lg mb-2">1. Book Online</h3>
            <p className="text-sm text-muted-foreground">
              Select your preferred date, time, and puja package through our easy online booking system.
            </p>
          </div>
          
          <div className="border border-border rounded-lg p-6 bg-card text-center">
            <div className="w-12 h-12 rounded-full bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
              <Users className="w-6 h-6" />
            </div>
            <h3 className="font-medium text-lg mb-2">2. Pandit Arrives</h3>
            <p className="text-sm text-muted-foreground">
              Our certified pandit arrives at your home with all necessary puja samagri and materials.
            </p>
          </div>
          
          <div className="border border-border rounded-lg p-6 bg-card text-center">
            <div className="w-12 h-12 rounded-full bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
              <Heart className="w-6 h-6" />
            </div>
            <h3 className="font-medium text-lg mb-2">3. Divine Blessings</h3>
            <p className="text-sm text-muted-foreground">
              Experience an authentic puja ceremony with personalized guidance and spiritual fulfillment.
            </p>
          </div>
        </div>
      </div>

      {/* Puja Packages */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
        <div className="text-center mb-10">
          <h2 className="text-2xl font-serif font-semibold mb-4">Puja Packages</h2>
          <p className="text-muted-foreground max-w-2xl mx-auto">
            Choose the perfect package for your spiritual needs
          </p>
        </div>
        
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {pujaPackages.map((pkg) => (
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
          <h2 className="text-2xl font-serif font-semibold mb-4">What Our Customers Say</h2>
          <p className="text-muted-foreground max-w-2xl mx-auto">
            Hear from families who have experienced our home puja services
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
            Find answers to common questions about our home puja services
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
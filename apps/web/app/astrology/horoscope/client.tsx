'use client'

import React, { useState } from 'react';
import Link from "next/link"
import Script from "next/script"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video, Search, MessageCircle, ThumbsUp, Reply, Flag, MoreHorizontal, User, Calendar as CalendarIcon, Check, Plus, TrendingUp as TrendingUpIcon } from "lucide-react"

// Horoscope types
const horoscopeTypes = [
  {
    id: 1,
    title: "Daily Horoscope",
    description: "Precise daily predictions for guidance",
    icon: CalendarIcon,
    frequency: "Updated daily"
  },
  {
    id: 2,
    title: "Weekly Horoscope",
    description: "Comprehensive weekly forecasts",
    icon: Calendar,
    frequency: "Updated weekly"
  },
  {
    id: 3,
    title: "Monthly Horoscope",
    description: "Detailed monthly predictions and trends",
    icon: Calendar,
    frequency: "Updated monthly"
  },
  {
    id: 4,
    title: "Yearly Horoscope",
    description: "Extensive annual predictions and remedies",
    icon: Calendar,
    frequency: "Updated yearly"
  }
];

// Life areas covered
const lifeAreas = [
  {
    id: 1,
    title: "Career & Business",
    description: "Professional growth and opportunities",
    icon: TrendingUpIcon
  },
  {
    id: 2,
    title: "Love & Relationships",
    description: "Romantic and personal connections",
    icon: Heart
  },
  {
    id: 3,
    title: "Health & Wellness",
    description: "Physical and mental well-being",
    icon: Heart
  },
  {
    id: 4,
    title: "Finance & Wealth",
    description: "Financial prospects and investments",
    icon: TrendingUp
  }
];

// Horoscope packages
const horoscopePackages = [
  {
    id: 1,
    name: "Daily Predictions",
    price: "₹499",
    originalPrice: "₹599",
    description: "Daily horoscope predictions for one zodiac sign",
    features: [
      "Daily email predictions",
      "Sun sign analysis",
      "General guidance",
      "Basic remedies",
      "Mobile app access"
    ],
    popular: false
  },
  {
    id: 2,
    name: "Premium Horoscope",
    price: "₹1,299",
    originalPrice: "₹1,599",
    description: "Comprehensive predictions for all life areas",
    features: [
      "Daily, weekly, monthly predictions",
      "Detailed zodiac analysis",
      "Career & business insights",
      "Love & relationship guidance",
      "Health predictions",
      "Financial forecasts",
      "Personalized remedies",
      "Mobile app access",
      "Priority support"
    ],
    popular: true
  },
  {
    id: 3,
    name: "Ultimate Package",
    price: "₹2,499",
    originalPrice: "₹2,999",
    description: "Extensive predictions with expert consultations",
    features: [
      "All premium features",
      "Personalized kundli analysis",
      "Monthly expert consultations",
      "Custom remedies & gemstones",
      "Festival & eclipse predictions",
      "Ritual timing guidance",
      "Priority support",
      "Exclusive content",
      "PDF reports"
    ],
    popular: false
  }
];

// Testimonials data
const testimonials = [
  {
    id: 1,
    name: "Anjali Mehta",
    location: "Delhi",
    rating: 5,
    comment: "The daily horoscope predictions have been remarkably accurate. It's become an essential part of my morning routine, helping me prepare for the day ahead.",
    date: "2025-10-20"
  },
  {
    id: 2,
    name: "Rajesh Kumar",
    location: "Mumbai",
    rating: 5,
    comment: "The career predictions helped me make a crucial job decision. The timing advice was perfect and led to a successful transition. Highly recommended!",
    date: "2025-10-14"
  },
  {
    id: 3,
    name: "Sunita Patel",
    location: "Ahmedabad",
    rating: 4,
    comment: "The weekly horoscope gives great insights into upcoming trends. The love and relationship guidance has been particularly helpful for our marriage.",
    date: "2025-10-08"
  }
];

// FAQ data
const faqData = [
  {
    question: "How accurate are your horoscope predictions?",
    answer: "Our predictions are based on traditional Vedic astrology principles combined with modern techniques. While we strive for accuracy, horoscopes provide guidance rather than definitive outcomes. Personal choices and efforts also influence life events."
  },
  {
    question: "How often are the predictions updated?",
    answer: "Daily horoscopes are updated every morning, weekly predictions every Monday, monthly forecasts on the first of each month, and yearly predictions at the beginning of each year. Special updates are provided for eclipses and significant celestial events."
  },
  {
    question: "Can I get predictions for multiple zodiac signs?",
    answer: "Yes, our premium packages include predictions for all 12 zodiac signs. This is particularly useful if you have family members with different sun signs or want comprehensive guidance for various aspects of life."
  },
  {
    question: "Do you provide remedial measures?",
    answer: "Yes, our premium packages include personalized remedies based on your horoscope. These may include specific mantras, gemstones, rituals, or lifestyle recommendations to enhance positive influences and mitigate challenges."
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

export default function HoroscopeClient({ horoscopeSchema }: { horoscopeSchema: string }) {
  const [openFaqIndex, setOpenFaqIndex] = useState<number | null>(null)
  const [selectedPackage, setSelectedPackage] = useState(2); // Default to Premium

  const toggleFaq = (index: number) => {
    setOpenFaqIndex(openFaqIndex === index ? null : index)
  };

  return (
    <>
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <Link href="/" className="hover:text-foreground">Home</Link>
            <span className="mx-1.5">/</span>
            <Link href="/astrology" className="hover:text-foreground">Astrology</Link>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">Horoscope</span>
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
                <CalendarIcon className="w-4 h-4 inline mr-1" />
                Daily Guidance
              </div>
              <h1 className="text-3xl font-serif font-semibold mb-4">Daily Horoscope</h1>
              <p className="text-lg mb-6">
                Accurate daily, weekly, and monthly horoscope predictions based on Vedic astrology for guidance in all aspects of life.
              </p>
              <Link 
                href="/book"
                className="inline-flex items-center gap-2 bg-white text-[#0E3F2E] hover:bg-gray-100 font-medium py-3 px-6 rounded-lg transition-all duration-300 shadow-sm"
              >
                <span>Get Your Horoscope</span>
                <ArrowRight className="w-4 h-4" />
              </Link>
            </div>
          </div>
        </div>

        {/* Stats Bar */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-10">
          <div className="bg-card border border-border rounded-lg p-6 grid grid-cols-2 md:grid-cols-4 gap-6">
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">50K+</div>
              <div className="text-sm text-muted-foreground">Daily Readers</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">4.7/5</div>
              <div className="text-sm text-muted-foreground">Average Rating</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">12</div>
              <div className="text-sm text-muted-foreground">Zodiac Signs</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">24/7</div>
              <div className="text-sm text-muted-foreground">Updated Predictions</div>
            </div>
          </div>
        </div>

        {/* About Horoscope */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="bg-card border border-border rounded-lg p-8">
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
              <div>
                <h2 className="text-2xl font-serif font-semibold mb-4">Guidance Through Celestial Wisdom</h2>
                <p className="text-muted-foreground mb-4">
                  Our Vedic horoscope predictions are based on the ancient wisdom of astrology, interpreting the positions and movements of celestial bodies to provide guidance for your daily life.
                </p>
                <p className="text-muted-foreground mb-4">
                  Whether you're seeking guidance for career decisions, relationship advice, health insights, or financial planning, our expert astrologers provide accurate and actionable predictions tailored to your zodiac sign.
                </p>
                <div className="flex items-center gap-4 mt-6">
                  <div className="flex items-center gap-2">
                    <Check className="w-5 h-5 text-[#0E3F2E]" />
                    <span className="text-sm">Vedic Authenticity</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <Check className="w-5 h-5 text-[#0E3F2E]" />
                    <span className="text-sm">Daily Updates</span>
                  </div>
                </div>
              </div>
              <div className="bg-muted rounded-lg overflow-hidden">
                <img 
                  src="https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg" 
                  alt="Horoscope" 
                  className="w-full h-full object-cover"
                />
              </div>
            </div>
          </div>
        </div>

        {/* Horoscope Types */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">Types of Horoscope Predictions</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Comprehensive predictions for every time frame and life aspect
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {horoscopeTypes.map((type) => (
              <div key={type.id} className="border border-border rounded-lg p-6 bg-card text-center">
                <div className="w-12 h-12 rounded-full bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
                  <type.icon className="w-6 h-6" />
                </div>
                <h3 className="font-medium text-lg mb-2">{type.title}</h3>
                <p className="text-sm text-muted-foreground mb-3">
                  {type.description}
                </p>
                <div className="inline-block bg-muted px-3 py-1 rounded-full text-xs">
                  {type.frequency}
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Life Areas Covered */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">Life Areas Covered</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Comprehensive guidance for all aspects of your life
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {lifeAreas.map((area) => (
              <div key={area.id} className="border border-border rounded-lg p-6 bg-card text-center">
                <div className="w-12 h-12 rounded-full bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
                  <area.icon className="w-6 h-6" />
                </div>
                <h3 className="font-medium text-lg mb-2">{area.title}</h3>
                <p className="text-sm text-muted-foreground">
                  {area.description}
                </p>
              </div>
            ))}
          </div>
        </div>

        {/* Horoscope Packages */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">Horoscope Packages</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Choose the perfect package for your astrological guidance
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {horoscopePackages.map((pkg) => (
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
            <h2 className="text-2xl font-serif font-semibold mb-4">Astrological Guidance</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Hear from those who have benefited from our horoscope predictions
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
              Find answers to common questions about our horoscope services
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
    </>
  )
}
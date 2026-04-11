'use client'

import React, { useState } from 'react';
import Link from "next/link"
import Script from "next/script"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video, Search, MessageCircle, ThumbsUp, Reply, Flag, MoreHorizontal, User, Heart as HeartIcon, Check, Plus, Users as UsersIcon } from "lucide-react"

// Matching parameters
const matchingParameters = [
  {
    id: 1,
    title: "Guna Milan",
    description: "Traditional point-based compatibility analysis",
    icon: HeartIcon
  },
  {
    id: 2,
    title: "Mangal Dosha",
    description: "Analysis of Mars afflictions and remedies",
    icon: Shield
  },
  {
    id: 3,
    title: "Nadi Compatibility",
    description: "Prana, Apana, and Vyana nadi matching",
    icon: UsersIcon
  },
  {
    id: 4,
    title: "Dasha Analysis",
    description: "Planetary period compatibility assessment",
    icon: Calendar
  }
];

// Matching packages
const matchingPackages = [
  {
    id: 1,
    name: "Basic Matching",
    price: "₹1,999",
    originalPrice: "₹2,499",
    description: "Essential compatibility analysis for marriage decisions",
    features: [
      "Basic Guna Milan (8 points)",
      "Mangal Dosha analysis",
      "Nadi compatibility check",
      "General compatibility score",
      "Brief report",
      "15-minute consultation"
    ],
    popular: false
  },
  {
    id: 2,
    name: "Comprehensive Matching",
    price: "₹3,499",
    originalPrice: "₹4,499",
    description: "Detailed compatibility analysis with remedies",
    features: [
      "Complete Guna Milan (36 points)",
      "Detailed Mangal Dosha analysis",
      "Nadi, Bhakoot, and Nadi compatibility",
      "Dasha period analysis",
      "Career and financial compatibility",
      "Health compatibility",
      "Remedial measures",
      "30-minute consultation",
      "PDF report"
    ],
    popular: true
  },
  {
    id: 3,
    name: "Premium Matching Report",
    price: "₹5,999",
    originalPrice: "₹7,499",
    description: "Extensive analysis with detailed remedies and follow-up",
    features: [
      "Complete Guna Milan analysis",
      "Detailed planetary compatibility",
      "Mangal Dosha and remedies",
      "Nadi, Bhakoot, Vasya matching",
      "Dasha period compatibility",
      "Career, finance, health compatibility",
      "Spiritual compatibility",
      "Personalized remedies",
      "Gemstone recommendations",
      "60-minute consultation",
      "Detailed PDF report",
      "Follow-up support"
    ],
    popular: false
  }
];

// Testimonials data
const testimonials = [
  {
    id: 1,
    name: "Rahul & Sneha",
    location: "Delhi",
    rating: 5,
    comment: "The horoscope matching gave us confidence in our marriage decision. The detailed analysis and remedies helped us address potential issues before they became problems.",
    date: "2025-10-15"
  },
  {
    id: 2,
    name: "Vikram & Priya",
    location: "Mumbai",
    rating: 5,
    comment: "We were initially concerned about Mangal Dosha, but the astrologer explained everything clearly and provided effective remedies. Our married life has been wonderful!",
    date: "2025-10-08"
  },
  {
    id: 3,
    name: "Arjun & Meera",
    location: "Bangalore",
    rating: 4,
    comment: "The matching report was comprehensive and professional. It helped our families understand the compatibility better and made the marriage process smoother.",
    date: "2025-10-01"
  }
];

// FAQ data
const faqData = [
  {
    question: "What information is needed for horoscope matching?",
    answer: "You'll need the exact date, time, and place of birth for both individuals. The more accurate the information, the more precise the analysis. If exact birth times are unknown, we can still provide a general compatibility assessment."
  },
  {
    question: "How is the compatibility score calculated?",
    answer: "We use the traditional Guna Milan system which evaluates 36 points of compatibility across eight factors: Varna, Vasya, Tara, Yoni, Maitri, Gana, Rashi, and Nadi. Each factor is assigned points, and the total determines overall compatibility."
  },
  {
    question: "What if the compatibility score is low?",
    answer: "A lower score doesn't mean the relationship won't work. Our astrologers provide detailed remedies and guidance to overcome potential challenges. Many successful marriages have been conducted with lower scores after proper remedies."
  },
  {
    question: "How long does the matching consultation take?",
    answer: "Basic consultations take 15-20 minutes, while comprehensive analyses can take 30-60 minutes depending on the package. We ensure sufficient time to address all your questions and concerns."
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

// Adding the missing Shield icon
function Shield(props: React.SVGProps<SVGSVGElement>) {
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
      <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
    </svg>
  )
}

export default function HoroscopeMatchingClient({ horoscopeMatchingSchema }: { horoscopeMatchingSchema: string }) {
  const [openFaqIndex, setOpenFaqIndex] = useState<number | null>(null)
  const [selectedPackage, setSelectedPackage] = useState(2); // Default to Comprehensive

  const toggleFaq = (index: number) => {
    setOpenFaqIndex(openFaqIndex === index ? null : index)
  };

  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: horoscopeMatchingSchema }}
      />
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <Link href="/" className="hover:text-foreground">Home</Link>
            <span className="mx-1.5">/</span>
            <Link href="/astrology" className="hover:text-foreground">Astrology</Link>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">Horoscope Matching</span>
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
                <HeartIcon className="w-4 h-4 inline mr-1" />
                Marriage Compatibility
              </div>
              <h1 className="text-3xl font-serif font-semibold mb-4">Horoscope Matching</h1>
              <p className="text-lg mb-6">
                Expert Vedic astrology horoscope matching for marriage compatibility and relationship harmony.
              </p>
              <Link 
                href="/book"
                className="inline-flex items-center gap-2 bg-white text-[#0E3F2E] hover:bg-gray-100 font-medium py-3 px-6 rounded-lg transition-all duration-300 shadow-sm"
              >
                <span>Get Matching Report</span>
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
              <div className="text-sm text-muted-foreground">Matchings Performed</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">4.9/5</div>
              <div className="text-sm text-muted-foreground">Average Rating</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">95%</div>
              <div className="text-sm text-muted-foreground">Success Rate</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">20+</div>
              <div className="text-sm text-muted-foreground">Years of Expertise</div>
            </div>
          </div>
        </div>

        {/* About Horoscope Matching */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="bg-card border border-border rounded-lg p-8">
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
              <div>
                <h2 className="text-2xl font-serif font-semibold mb-4">Marriage Compatibility Analysis</h2>
                <p className="text-muted-foreground mb-4">
                  Horoscope matching (Kundli Milan) is a traditional Vedic practice that evaluates the compatibility between two individuals for marriage. It helps identify potential challenges and strengths in the relationship.
                </p>
                <p className="text-muted-foreground mb-4">
                  Our expert astrologers use the ancient Guna Milan system to analyze 36 points of compatibility across eight key factors. This comprehensive assessment provides valuable insights for making informed marriage decisions.
                </p>
                <div className="flex items-center gap-4 mt-6">
                  <div className="flex items-center gap-2">
                    <Check className="w-5 h-5 text-[#0E3F2E]" />
                    <span className="text-sm">Traditional Guna Milan</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <Check className="w-5 h-5 text-[#0E3F2E]" />
                    <span className="text-sm">Expert Remedies</span>
                  </div>
                </div>
              </div>
              <div className="bg-muted rounded-lg overflow-hidden">
                <img 
                  src="https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg" 
                  alt="Horoscope Matching" 
                  className="w-full h-full object-cover"
                />
              </div>
            </div>
          </div>
        </div>

        {/* Matching Parameters */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">Key Parameters in Matching</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Comprehensive evaluation of compatibility factors
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {matchingParameters.map((parameter) => (
              <div key={parameter.id} className="border border-border rounded-lg p-6 bg-card text-center">
                <div className="w-12 h-12 rounded-full bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
                  <parameter.icon className="w-6 h-6" />
                </div>
                <h3 className="font-medium text-lg mb-2">{parameter.title}</h3>
                <p className="text-sm text-muted-foreground">
                  {parameter.description}
                </p>
              </div>
            ))}
          </div>
        </div>

        {/* Matching Packages */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">Horoscope Matching Packages</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Choose the perfect package for your compatibility analysis
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {matchingPackages.map((pkg) => (
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
            <h2 className="text-2xl font-serif font-semibold mb-4">Successful Matchings</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Hear from couples who have benefited from our horoscope matching
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
              Find answers to common questions about our horoscope matching services
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
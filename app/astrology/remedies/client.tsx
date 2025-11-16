'use client'

import React, { useState } from 'react';
import Link from "next/link"
import Script from "next/script"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video, Search, MessageCircle, ThumbsUp, Reply, Flag, MoreHorizontal, User, Shield, Check, Plus, Leaf } from "lucide-react"

// Types of remedies
const remedyTypes = [
  {
    id: 1,
    title: "Mantra Remedies",
    description: "Sacred chants to appease planetary influences",
    icon: BookOpen
  },
  {
    id: 2,
    title: "Gemstone Therapy",
    description: "Wearing specific gemstones to enhance energies",
    icon: Diamond
  },
  {
    id: 3,
    title: "Ritual Remedies",
    description: "Special pujas and havans for specific purposes",
    icon: Flame
  },
  {
    id: 4,
    title: "Yantra Solutions",
    description: "Sacred geometric designs for energy enhancement",
    icon: Shield
  }
];

// Common remedies by planet
const planetaryRemedies = [
  {
    id: 1,
    planet: "Sun (Surya)",
    remedies: [
      "Chant Surya Namaskar daily",
      "Donate wheat and red cloth on Sundays",
      "Wear ruby after proper consultation",
      "Offer water to Sun God at sunrise"
    ],
    color: "bg-orange-100 text-orange-800"
  },
  {
    id: 2,
    planet: "Moon (Chandra)",
    remedies: [
      "Chant Om Som Somaya Namah",
      "Donate rice and white cloth on Mondays",
      "Wear pearl after proper consultation",
      "Offer milk to Peepal tree"
    ],
    color: "bg-blue-100 text-blue-800"
  },
  {
    id: 3,
    planet: "Mars (Mangal)",
    remedies: [
      "Chant Om Mangal Mangalaya Namah",
      "Donate red lentils on Tuesdays",
      "Wear red coral after consultation",
      "Offer red flowers to Hanuman"
    ],
    color: "bg-red-100 text-red-800"
  },
  {
    id: 4,
    planet: "Mercury (Budha)",
    remedies: [
      "Chant Om Budhaya Namah",
      "Donate green gram on Wednesdays",
      "Wear emerald after consultation",
      "Offer green flowers to Lord Vishnu"
    ],
    color: "bg-green-100 text-green-800"
  }
];

// Remedy packages
const remedyPackages = [
  {
    id: 1,
    name: "Basic Remedy Consultation",
    price: "₹1,299",
    originalPrice: "₹1,599",
    description: "Personalized remedies based on your birth chart",
    features: [
      "Kundli analysis",
      "Planetary strength assessment",
      "3-5 personalized remedies",
      "Basic procedure guidance",
      "15-minute consultation"
    ],
    popular: false
  },
  {
    id: 2,
    name: "Comprehensive Remedy Package",
    price: "₹2,999",
    originalPrice: "₹3,799",
    description: "Detailed remedies with follow-up support",
    features: [
      "Complete kundli analysis",
      "Detailed planetary assessment",
      "10-15 personalized remedies",
      "Step-by-step procedures",
      "Mantra pronunciation guide",
      "Timing recommendations",
      "30-minute consultation"
    ],
    popular: true
  },
  {
    id: 3,
    name: "Premium Remedy Solution",
    price: "₹5,999",
    originalPrice: "₹7,499",
    description: "Extensive remedies with ritual performance",
    features: [
      "Complete astrological analysis",
      "Comprehensive assessment",
      "Unlimited personalized remedies",
      "Ritual performance by pandits",
      "Gemstone recommendations",
      "Yantra installation",
      "60-minute consultation",
      "Monthly follow-up for 3 months",
      "PDF remedy booklet"
    ],
    popular: false
  }
];

// Testimonials data
const testimonials = [
  {
    id: 1,
    name: "Anita Desai",
    location: "Bangalore",
    rating: 5,
    comment: "The remedies for my Moon affliction worked wonderfully. I've found peace and emotional stability after following the prescribed mantras and rituals. The guidance was clear and effective.",
    date: "2025-10-20"
  },
  {
    id: 2,
    name: "Vikram Mehta",
    location: "Mumbai",
    rating: 5,
    comment: "The Mars remedies helped me control my anger and improved my relationships significantly. The astrologer explained everything in detail and the follow-up support was excellent.",
    date: "2025-10-14"
  },
  {
    id: 3,
    name: "Sunita Patel",
    location: "Ahmedabad",
    rating: 4,
    comment: "The remedies package provided practical solutions for my career challenges. The combination of mantras and gemstone therapy has brought positive changes in my professional life.",
    date: "2025-10-08"
  }
];

// FAQ data
const faqData = [
  {
    question: "How do astrological remedies work?",
    answer: "Astrological remedies work by balancing the cosmic energies that influence our lives. Through specific mantras, rituals, gemstones, and yantras, we can strengthen positive planetary influences and mitigate negative ones, creating harmony in our lives."
  },
  {
    question: "How long does it take to see results from remedies?",
    answer: "The time frame varies depending on the severity of planetary afflictions and individual kundli. Some people notice changes within a few weeks, while others may take 3-6 months. Consistency in following the prescribed remedies is crucial for effectiveness."
  },
  {
    question: "Are remedies a substitute for personal effort?",
    answer: "No, remedies complement personal efforts rather than replace them. While remedies help create favorable conditions, success still requires sincere effort, dedication, and righteous actions. Remedies remove obstacles and enhance opportunities."
  },
  {
    question: "Can remedies completely eliminate negative effects?",
    answer: "Remedies significantly reduce the impact of negative planetary influences but may not eliminate them entirely. They help us navigate challenges more effectively and transform obstacles into opportunities for growth and learning."
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

// Adding the missing Diamond and Flame icons
function Diamond(props: React.SVGProps<SVGSVGElement>) {
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
      <path d="M2.7 10.3a2.41 2.41 0 0 0 0 3.41l7.59 7.59a2.41 2.41 0 0 0 3.41 0l7.59-7.59a2.41 2.41 0 0 0 0-3.41L13.7 2.71a2.41 2.41 0 0 0-3.41 0z" />
    </svg>
  )
}

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

export default function RemediesClient({ remediesSchema }: { remediesSchema: string }) {
  const [openFaqIndex, setOpenFaqIndex] = useState<number | null>(null)
  const [selectedPackage, setSelectedPackage] = useState(2); // Default to Comprehensive

  const toggleFaq = (index: number) => {
    setOpenFaqIndex(openFaqIndex === index ? null : index)
  };

  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: remediesSchema }}
      />
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <Link href="/" className="hover:text-foreground">Home</Link>
            <span className="mx-1.5">/</span>
            <Link href="/astrology" className="hover:text-foreground">Astrology</Link>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">Astrological Remedies</span>
          </nav>
        </div>

        {/* Hero Banner */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-8">
          <div className="relative rounded-lg overflow-hidden bg-cover bg-center flex items-center h-80"
            style={{
              backgroundImage: "url('https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg')"
            }}
          >
            <div className="absolute inset-0 bg-gradient-to-r from-black/80 to-black/40"></div>
            <div className="relative z-10 text-white p-8 max-w-3xl">
              <div className="inline-block bg-white/20 backdrop-blur-sm text-white px-3 py-1 rounded-full text-sm font-medium mb-4">
                <Shield className="w-4 h-4 inline mr-1" />
                Vedic Solutions
              </div>
              <h1 className="text-3xl font-serif font-semibold mb-4">Astrological Remedies</h1>
              <p className="text-lg mb-6">
                Personalized Vedic astrological remedies to overcome challenges and enhance positive planetary influences in your life.
              </p>
              <Link 
                href="/booking"
                className="inline-flex items-center gap-2 bg-white text-[#0E3F2E] hover:bg-gray-100 font-medium py-3 px-6 rounded-lg transition-all duration-300 shadow-sm"
              >
                <span>Get Remedies</span>
                <ArrowRight className="w-4 h-4" />
              </Link>
            </div>
          </div>
        </div>

        {/* Stats Bar */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-10">
          <div className="bg-card border border-border rounded-lg p-6 grid grid-cols-2 md:grid-cols-4 gap-6">
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">25000+</div>
              <div className="text-sm text-muted-foreground">Remedies Prescribed</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">4.9/5</div>
              <div className="text-sm text-muted-foreground">Average Rating</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">90%</div>
              <div className="text-sm text-muted-foreground">Success Rate</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">30+</div>
              <div className="text-sm text-muted-foreground">Years of Expertise</div>
            </div>
          </div>
        </div>

        {/* About Remedies */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="bg-card border border-border rounded-lg p-8">
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
              <div>
                <h2 className="text-2xl font-serif font-semibold mb-4">Balancing Cosmic Energies</h2>
                <p className="text-muted-foreground mb-4">
                  Astrological remedies are time-tested solutions from Vedic tradition that help balance the cosmic energies influencing our lives. When planets are weak or afflicted in our birth chart, they can create challenges in various life areas.
                </p>
                <p className="text-muted-foreground mb-4">
                  Our expert astrologers prescribe personalized remedies based on your unique kundli to strengthen positive influences and mitigate negative ones. These remedies work in harmony with natural laws to create favorable conditions for success and well-being.
                </p>
                <div className="flex items-center gap-4 mt-6">
                  <div className="flex items-center gap-2">
                    <Check className="w-5 h-5 text-[#0E3F2E]" />
                    <span className="text-sm">Vedic Authenticity</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <Check className="w-5 h-5 text-[#0E3F2E]" />
                    <span className="text-sm">Personalized Solutions</span>
                  </div>
                </div>
              </div>
              <div className="bg-muted rounded-lg overflow-hidden">
                <img 
                  src="https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg" 
                  alt="Astrological Remedies" 
                  className="w-full h-full object-cover"
                />
              </div>
            </div>
          </div>
        </div>

        {/* Remedy Types */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">Types of Remedies</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Various approaches to balance planetary influences
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {remedyTypes.map((type) => (
              <div key={type.id} className="border border-border rounded-lg p-6 bg-card text-center">
                <div className="w-12 h-12 rounded-full bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
                  <type.icon className="w-6 h-6" />
                </div>
                <h3 className="font-medium text-lg mb-2">{type.title}</h3>
                <p className="text-sm text-muted-foreground">
                  {type.description}
                </p>
              </div>
            ))}
          </div>
        </div>

        {/* Planetary Remedies */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">Remedies by Planet</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Specific remedies for different planetary influences
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {planetaryRemedies.map((remedy) => (
              <div key={remedy.id} className="border border-border rounded-lg p-6 bg-card">
                <h3 className="font-medium text-lg mb-4">{remedy.planet}</h3>
                <ul className="space-y-3">
                  {remedy.remedies.map((item, index) => (
                    <li key={index} className="flex items-start gap-3">
                      <Leaf className="w-4 h-4 text-[#0E3F2E] mt-0.5 flex-shrink-0" />
                      <span className="text-sm">{item}</span>
                    </li>
                  ))}
                </ul>
              </div>
            ))}
          </div>
        </div>

        {/* Remedy Packages */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">Remedy Packages</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Choose the perfect package for your personalized remedies
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {remedyPackages.map((pkg) => (
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
            <h2 className="text-2xl font-serif font-semibold mb-4">Transformative Results</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Hear from those who have benefited from our astrological remedies
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
              Find answers to common questions about our astrological remedies
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
'use client'

import React, { useState } from 'react';
import Link from "next/link"
import { ChevronDown, Star, Check, ArrowRight, Calendar, Users, BookOpen, Heart, Lightbulb, MessageCircle, Settings, Star as StarIcon } from "lucide-react"

// Custom puja benefits
const benefits = [
  {
    id: 1,
    title: "Personalized Rituals",
    description: "Tailored ceremonies based on your specific traditions and requirements",
    icon: Settings
  },
  {
    id: 2,
    title: "Family Traditions",
    description: "Incorporate your family's unique customs and practices",
    icon: Users
  },
  {
    id: 3,
    title: "Specific Intentions",
    description: "Rituals designed for particular goals like health, prosperity, or peace",
    icon: StarIcon
  },
  {
    id: 4,
    title: "Flexible Timing",
    description: "Schedule according to your convenience and astrological recommendations",
    icon: Calendar
  }
];

// Custom puja process steps
const processSteps = [
  {
    id: 1,
    title: "Consultation",
    description: "Discuss your requirements and intentions with our experts",
    icon: MessageCircle
  },
  {
    id: 2,
    title: "Customization",
    description: "Design a personalized ritual based on your needs",
    icon: Settings
  },
  {
    id: 3,
    title: "Preparation",
    description: "Gather all necessary materials and arrange pandits",
    icon: BookOpen
  },
  {
    id: 4,
    title: "Performance",
    description: "Conduct the ceremony with devotion and precision",
    icon: Heart
  },
  {
    id: 5,
    title: "Follow-up",
    description: "Provide guidance and support after the ritual",
    icon: Lightbulb
  }
];

// Custom puja packages
const customPackages = [
  {
    id: 1,
    name: "Basic Custom Puja",
    price: "₹2,999",
    originalPrice: "₹3,999",
    description: "Simple personalized puja with one pandit",
    features: [
      "1-hour consultation",
      "Custom ritual design",
      "One certified pandit",
      "Basic puja samagri",
      "Standard rituals",
      "Guidance throughout ceremony"
    ],
    popular: false
  },
  {
    id: 2,
    name: "Premium Custom Puja",
    price: "₹5,999",
    originalPrice: "₹7,999",
    description: "Enhanced personalized puja with additional features",
    features: [
      "2-hour consultation",
      "Detailed ritual design",
      "Two senior pandits",
      "Premium puja samagri",
      "Extended rituals",
      "Personalized guidance",
      "Prasad distribution",
      "Recorded ceremony"
    ],
    popular: true
  },
  {
    id: 3,
    name: "Ultimate Custom Puja",
    price: "₹9,999",
    originalPrice: "₹12,999",
    description: "Complete personalized puja experience",
    features: [
      "3-hour consultation",
      "Comprehensive ritual design",
      "Three expert pandits",
      "Premium puja samagri",
      "Extended rituals",
      "Personalized guidance",
      "Prasad distribution",
      "Recorded ceremony",
      "Post-puja consultation",
      "Special offerings",
      "Astrological analysis"
    ],
    popular: false
  }
];

// Testimonials data
const testimonials = [
  {
    id: 1,
    name: "Deepak Malhotra",
    location: "Delhi",
    rating: 5,
    comment: "I needed a puja for my business success with specific family traditions. The pandits understood our customs perfectly and created a beautiful ceremony that honored both our requirements and traditions.",
    date: "2025-10-22"
  },
  {
    id: 2,
    name: "Anjali Nair",
    location: "Kochi",
    rating: 5,
    comment: "For my daughter's first birthday, we wanted something unique. They designed a wonderful custom ceremony that combined traditional blessings with our family's special wishes. Truly memorable!",
    date: "2025-10-16"
  },
  {
    id: 3,
    name: "Rajiv Joshi",
    location: "Pune",
    rating: 4,
    comment: "The consultation process was thorough and the pandits were very accommodating to our specific requests. The custom puja was exactly what we envisioned. Will definitely use again for special occasions.",
    date: "2025-10-10"
  }
];

// FAQ data
const faqData = [
  {
    question: "How is a custom puja different from standard pujas?",
    answer: "Custom pujas are designed specifically for your needs, incorporating your family traditions, specific intentions, and preferred timing. Standard pujas follow predefined procedures, while custom pujas are personalized to your unique requirements."
  },
  {
    question: "How long does the consultation process take?",
    answer: "The initial consultation typically lasts 1-2 hours, depending on the complexity of your requirements. We take the time to understand your needs thoroughly to create the perfect ceremony for you."
  },
  {
    question: "Can I request specific mantras or deities?",
    answer: "Absolutely! We can incorporate specific mantras, deities, or rituals that are important to you or your family traditions. Just let us know during the consultation."
  },
  {
    question: "What if I don't know much about rituals?",
    answer: "No problem! Our pandits will guide you through the entire process, explaining everything clearly. You don't need prior knowledge - we'll help you understand and participate meaningfully."
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

export default function CustomPujasClient() {
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
          <span className="text-foreground">Custom Pujas</span>
        </nav>
      </div>

      {/* Hero Banner */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-8">
        <div className="relative rounded-lg overflow-hidden bg-cover bg-center flex items-center h-80"
          style={{
            backgroundImage: "url('https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_db05ba7c437e48abe6440798d2a33b09dr_bokdku.jpg')"
          }}
        >
          <div className="absolute inset-0 bg-gradient-to-r from-black/80 to-black/40"></div>
          <div className="relative z-10 text-white p-8 max-w-3xl">
            <div className="inline-block bg-white/20 backdrop-blur-sm text-white px-3 py-1 rounded-full text-sm font-medium mb-4">
              <StarIcon className="w-4 h-4 inline mr-1" />
              Personalized Service
            </div>
            <h1 className="text-3xl font-serif font-semibold mb-4">Custom Pujas</h1>
            <p className="text-lg mb-6">
              Personalized puja services tailored to your specific spiritual needs and family traditions.
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
            <div className="text-2xl font-bold text-[#0E3F2E] mb-2">800+</div>
            <div className="text-sm text-muted-foreground">Custom Pujas Performed</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-[#0E3F2E] mb-2">4.9/5</div>
            <div className="text-sm text-muted-foreground">Average Rating</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-[#0E3F2E] mb-2">100+</div>
            <div className="text-sm text-muted-foreground">Unique Rituals Created</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-[#0E3F2E] mb-2">15+</div>
            <div className="text-sm text-muted-foreground">Years of Custom Service</div>
          </div>
        </div>
      </div>

      {/* Benefits */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
        <div className="text-center mb-10">
          <h2 className="text-2xl font-serif font-semibold mb-4">Benefits of Custom Pujas</h2>
          <p className="text-muted-foreground max-w-2xl mx-auto">
            Why personalized rituals are perfect for your spiritual journey
          </p>
        </div>
        
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {benefits.map((benefit) => (
            <div key={benefit.id} className="border border-border rounded-lg p-6 bg-card text-center">
              <div className="w-12 h-12 rounded-full bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
                <benefit.icon className="w-6 h-6" />
              </div>
              <h3 className="font-medium text-lg mb-2">{benefit.title}</h3>
              <p className="text-sm text-muted-foreground">
                {benefit.description}
              </p>
            </div>
          ))}
        </div>
      </div>

      {/* How It Works */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
        <div className="text-center mb-10">
          <h2 className="text-2xl font-serif font-semibold mb-4">Our Custom Puja Process</h2>
          <p className="text-muted-foreground max-w-2xl mx-auto">
            Simple steps to create your personalized spiritual experience
          </p>
        </div>
        
        <div className="grid grid-cols-1 md:grid-cols-5 gap-6">
          {processSteps.map((step) => (
            <div key={step.id} className="border border-border rounded-lg p-6 bg-card text-center relative">
              <div className="absolute -top-3 -right-3 w-8 h-8 rounded-full bg-[#0E3F2E] text-white flex items-center justify-center text-sm font-bold">
                {step.id}
              </div>
              <div className="w-12 h-12 rounded-full bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
                <step.icon className="w-6 h-6" />
              </div>
              <h3 className="font-medium text-lg mb-2">{step.title}</h3>
              <p className="text-sm text-muted-foreground">
                {step.description}
              </p>
            </div>
          ))}
        </div>
      </div>

      {/* Custom Puja Packages */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
        <div className="text-center mb-10">
          <h2 className="text-2xl font-serif font-semibold mb-4">Custom Puja Packages</h2>
          <p className="text-muted-foreground max-w-2xl mx-auto">
            Choose the perfect package for your personalized spiritual needs
          </p>
        </div>
        
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {customPackages.map((pkg) => (
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
          <h2 className="text-2xl font-serif font-semibold mb-4">Personalized Experiences</h2>
          <p className="text-muted-foreground max-w-2xl mx-auto">
            Hear from those who have experienced our custom puja services
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
            Find answers to common questions about our custom puja services
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
'use client'

import React, { useState } from 'react';
import Link from "next/link"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video, Search, MessageCircle, ThumbsUp, Reply, Flag, MoreHorizontal, User, Check, Plus, Minus, Monitor, Wifi, Mic, Camera, Headphones } from "lucide-react"

// Online Puja packages data
const pujaPackages = [
  {
    id: 1,
    name: "Basic Online Puja",
    price: "₹1,499",
    originalPrice: "₹1,999",
    description: "Essential puja service conducted via video call",
    features: [
      "One certified pandit",
      "Basic puja samagri list",
      "Standard rituals",
      "Video call guidance",
      "Personalized blessings"
    ],
    popular: false
  },
  {
    id: 2,
    name: "Premium Online Puja",
    price: "₹2,499",
    originalPrice: "₹3,499",
    description: "Comprehensive puja service with enhanced offerings",
    features: [
      "Senior certified pandit",
      "Detailed puja samagri list",
      "Extended rituals",
      "Personalized guidance",
      "Prasad distribution guide",
      "Recorded ceremony"
    ],
    popular: true
  },
  {
    id: 3,
    name: "Family Online Puja",
    price: "₹3,999",
    originalPrice: "₹4,999",
    description: "Complete family puja experience via video call",
    features: [
      "Two certified pandits",
      "Premium puja samagri list",
      "Extended rituals",
      "Personalized guidance",
      "Prasad distribution guide",
      "Recorded ceremony",
      "Post-puja consultation",
      "Family blessings"
    ],
    popular: false
  }
];

// Testimonials data
const testimonials = [
  {
    id: 1,
    name: "Sunita Patel",
    location: "Mumbai",
    rating: 5,
    comment: "The online puja was just as powerful as an in-person one. The pandit was knowledgeable and made us feel connected despite the distance.",
    date: "2025-10-20"
  },
  {
    id: 2,
    name: "Rahul Mehta",
    location: "Bangalore",
    rating: 5,
    comment: "Living abroad, this was the perfect way to stay connected to our traditions. The setup instructions were clear and the pandit was patient with our questions.",
    date: "2025-10-15"
  },
  {
    id: 3,
    name: "Priya Nair",
    location: "Chennai",
    rating: 4,
    comment: "Great service for those who can't have pandits at home. The video quality was good and the pandit guided us through each step clearly.",
    date: "2025-10-10"
  }
];

// FAQ data
const faqData = [
  {
    question: "How does online puja work?",
    answer: "Our online puja service connects you with a certified pandit via secure video call. The pandit conducts the complete ritual while you participate from your home. We provide a detailed list of items you'll need beforehand."
  },
  {
    question: "What do I need for online puja?",
    answer: "You'll need a stable internet connection, a device with camera and microphone (smartphone, tablet, or computer), and the puja samagri items listed by our pandit. We provide a detailed checklist after booking."
  },
  {
    question: "Is online puja as effective as home puja?",
    answer: "Yes, the spiritual efficacy remains the same. The pandit performs all rituals with the same devotion and authenticity. Many of our clients have reported deeply meaningful experiences through online pujas."
  },
  {
    question: "Can I request specific rituals?",
    answer: "Absolutely! Our pandits can customize the rituals according to your specific traditions and requirements. Just let us know your preferences during the booking process."
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

export default function OnlinePujaClient() {
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
          <span className="text-foreground">Online Puja</span>
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
              <Monitor className="w-4 h-4 inline mr-1" />
              Online Service
            </div>
            <h1 className="text-3xl font-serif font-semibold mb-4">Online Puja</h1>
            <p className="text-lg mb-6">
              Participate in authentic Hindu rituals remotely with our online puja services. Connect with certified pandits via video call.
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
            <div className="text-2xl font-bold text-[#0E3F2E] mb-2">2000+</div>
            <div className="text-sm text-muted-foreground">Online Pujas Performed</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-[#0E3F2E] mb-2">4.8/5</div>
            <div className="text-sm text-muted-foreground">Average Rating</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-[#0E3F2E] mb-2">150+</div>
            <div className="text-sm text-muted-foreground">Certified Pandits</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-[#0E3F2E] mb-2">25+</div>
            <div className="text-sm text-muted-foreground">Countries Served</div>
          </div>
        </div>
      </div>

      {/* How It Works */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
        <div className="text-center mb-10">
          <h2 className="text-2xl font-serif font-semibold mb-4">How Online Puja Works</h2>
          <p className="text-muted-foreground max-w-2xl mx-auto">
            Simple steps to connect with divine blessings from anywhere in the world
          </p>
        </div>
        
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
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
              <Monitor className="w-6 h-6" />
            </div>
            <h3 className="font-medium text-lg mb-2">2. Get Setup Guide</h3>
            <p className="text-sm text-muted-foreground">
              Receive a detailed checklist of items needed and technical setup instructions.
            </p>
          </div>
          
          <div className="border border-border rounded-lg p-6 bg-card text-center">
            <div className="w-12 h-12 rounded-full bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
              <Video className="w-6 h-6" />
            </div>
            <h3 className="font-medium text-lg mb-2">3. Video Call</h3>
            <p className="text-sm text-muted-foreground">
              Connect with your certified pandit via secure video call at your scheduled time.
            </p>
          </div>
          
          <div className="border border-border rounded-lg p-6 bg-card text-center">
            <div className="w-12 h-12 rounded-full bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
              <Heart className="w-6 h-6" />
            </div>
            <h3 className="font-medium text-lg mb-2">4. Divine Blessings</h3>
            <p className="text-sm text-muted-foreground">
              Participate in the complete puja ceremony and receive personalized spiritual guidance.
            </p>
          </div>
        </div>
      </div>

      {/* Technology Features */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
        <div className="text-center mb-10">
          <h2 className="text-2xl font-serif font-semibold mb-4">Seamless Technology</h2>
          <p className="text-muted-foreground max-w-2xl mx-auto">
            Our platform ensures a smooth and meaningful online puja experience
          </p>
        </div>
        
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="border border-border rounded-lg p-6 bg-card">
            <div className="w-10 h-10 rounded-lg bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mb-4">
              <Wifi className="w-5 h-5" />
            </div>
            <h3 className="font-medium text-lg mb-2">Stable Connection</h3>
            <p className="text-sm text-muted-foreground">
              High-quality video and audio for uninterrupted participation in rituals.
            </p>
          </div>
          
          <div className="border border-border rounded-lg p-6 bg-card">
            <div className="w-10 h-10 rounded-lg bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mb-4">
              <Mic className="w-5 h-5" />
            </div>
            <h3 className="font-medium text-lg mb-2">Clear Audio</h3>
            <p className="text-sm text-muted-foreground">
              Crystal clear sound so you don't miss any mantras or guidance from the pandit.
            </p>
          </div>
          
          <div className="border border-border rounded-lg p-6 bg-card">
            <div className="w-10 h-10 rounded-lg bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mb-4">
              <Camera className="w-5 h-5" />
            </div>
            <h3 className="font-medium text-lg mb-2">HD Video</h3>
            <p className="text-sm text-muted-foreground">
              High-definition video to clearly see all rituals and offerings during the ceremony.
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
            Hear from families who have experienced our online puja services
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
            Find answers to common questions about our online puja services
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
'use client'

import React, { useState } from 'react';
import Link from "next/link"
import Script from "next/script"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video, Search, MessageCircle, ThumbsUp, Reply, Flag, MoreHorizontal, User, Diamond, Check, Plus, Shield } from "lucide-react"

// Gemstones and their properties
const gemstones = [
  {
    id: 1,
    name: "Ruby (Manik)",
    planet: "Sun",
    properties: "Leadership, confidence, health",
    color: "Red",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg"
  },
  {
    id: 2,
    name: "Pearl (Moti)",
    planet: "Moon",
    properties: "Emotional balance, mental peace",
    color: "White",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg"
  },
  {
    id: 3,
    name: "Red Coral (Moonga)",
    planet: "Mars",
    properties: "Courage, energy, protection",
    color: "Red",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg"
  },
  {
    id: 4,
    name: "Emerald (Panna)",
    planet: "Mercury",
    properties: "Communication, intelligence",
    color: "Green",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg"
  },
  {
    id: 5,
    name: "Yellow Sapphire (Pukhraj)",
    planet: "Jupiter",
    properties: "Wisdom, prosperity, knowledge",
    color: "Yellow",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_db05ba7c437e48abe6440798d2a33b09dr_bokdku.jpg"
  },
  {
    id: 6,
    name: "Diamond (Heera)",
    planet: "Venus",
    properties: "Love, luxury, creativity",
    color: "Colorless",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960318/LoDi-Make_this_logo_exact_lnyy2a.svg"
  },
  {
    id: 7,
    name: "Blue Sapphire (Neelam)",
    planet: "Saturn",
    properties: "Discipline, focus, karma",
    color: "Blue",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg"
  },
  {
    id: 8,
    name: "Hessonite (Gomed)",
    planet: "Rahu",
    properties: "Spiritual growth, protection",
    color: "Honey",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg"
  },
  {
    id: 9,
    name: "Cat's Eye (Lehsunia)",
    planet: "Ketu",
    properties: "Intuition, spiritual insight",
    color: "Greenish",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg"
  }
];

// Gemstone packages
const gemstonePackages = [
  {
    id: 1,
    name: "Basic Recommendation",
    price: "₹999",
    originalPrice: "₹1,299",
    description: "Personalized gemstone recommendation based on your birth chart",
    features: [
      "Kundli analysis",
      "Planetary strength assessment",
      "One gemstone recommendation",
      "Basic wearing instructions",
      "15-minute consultation"
    ],
    popular: false
  },
  {
    id: 2,
    name: "Comprehensive Analysis",
    price: "₹2,499",
    originalPrice: "₹3,299",
    description: "Detailed analysis with multiple gemstone recommendations",
    features: [
      "Complete kundli analysis",
      "Planetary strength assessment",
      "Multiple gemstone recommendations",
      "Quality & authenticity guidance",
      "Wearing procedures",
      "Activation rituals",
      "30-minute consultation"
    ],
    popular: true
  },
  {
    id: 3,
    name: "Premium Package",
    price: "₹4,999",
    originalPrice: "₹6,499",
    description: "Extensive analysis with certified gemstones",
    features: [
      "Complete astrological analysis",
      "Detailed planetary assessment",
      "Personalized recommendations",
      "Certified authentic gemstones",
      "Proper activation rituals",
      "Wearing guidance",
      "Removal procedures",
      "60-minute consultation",
      "Follow-up support"
    ],
    popular: false
  }
];

// Testimonials data
const testimonials = [
  {
    id: 1,
    name: "Suresh Iyer",
    location: "Chennai",
    rating: 5,
    comment: "The ruby recommendation was perfect for my sun sign. I've noticed increased confidence and better health since wearing it. The activation ritual was conducted beautifully.",
    date: "2025-10-18"
  },
  {
    id: 2,
    name: "Priya Sharma",
    location: "Delhi",
    rating: 5,
    comment: "The yellow sapphire for Jupiter has brought remarkable prosperity to my business. The astrologer explained everything in detail, including how to wear and maintain it.",
    date: "2025-10-12"
  },
  {
    id: 3,
    name: "Rajesh Patel",
    location: "Ahmedabad",
    rating: 4,
    comment: "Very professional service. The emerald for Mercury has improved my communication skills significantly. The quality of the gemstone was excellent as promised.",
    date: "2025-10-06"
  }
];

// FAQ data
const faqData = [
  {
    question: "How do you determine which gemstone is right for me?",
    answer: "Our expert astrologers analyze your birth chart to identify weak or afflicted planets. Based on this analysis, they recommend specific gemstones that can strengthen positive planetary influences and mitigate negative ones."
  },
  {
    question: "Are the gemstones certified and authentic?",
    answer: "Yes, all our gemstones are certified authentic and of high quality. We source them from reputable suppliers and provide certification documents with each purchase. Our astrologers verify the quality before recommendation."
  },
  {
    question: "How should I wear a gemstone for maximum benefits?",
    answer: "Each gemstone has specific wearing procedures based on Vedic principles. Our astrologers provide detailed instructions including the metal type, finger, day, time, and mantra for activation. Proper wearing is crucial for effectiveness."
  },
  {
    question: "When will I start feeling the effects of a gemstone?",
    answer: "The effects typically begin within 7-14 days of proper wearing, though some people notice changes within a few days. The intensity and timing vary based on individual kundli and the specific gemstone. Consistent wearing is important for sustained benefits."
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

export default function GemstoneClient({ gemstoneSchema }: { gemstoneSchema: string }) {
  const [openFaqIndex, setOpenFaqIndex] = useState<number | null>(null)
  const [selectedPackage, setSelectedPackage] = useState(2); // Default to Comprehensive

  const toggleFaq = (index: number) => {
    setOpenFaqIndex(openFaqIndex === index ? null : index)
  };

  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: gemstoneSchema }}
      />
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <Link href="/" className="hover:text-foreground">Home</Link>
            <span className="mx-1.5">/</span>
            <Link href="/astrology" className="hover:text-foreground">Astrology</Link>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">Gemstone Recommendations</span>
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
                <Diamond className="w-4 h-4 inline mr-1" />
                Vedic Gemstones
              </div>
              <h1 className="text-3xl font-serif font-semibold mb-4">Gemstone Recommendations</h1>
              <p className="text-lg mb-6">
                Personalized Vedic gemstone recommendations based on your birth chart for enhanced positive energies and protection.
              </p>
              <Link 
                href="/book"
                className="inline-flex items-center gap-2 bg-white text-[#0E3F2E] hover:bg-gray-100 font-medium py-3 px-6 rounded-lg transition-all duration-300 shadow-sm"
              >
                <span>Get Recommendations</span>
                <ArrowRight className="w-4 h-4" />
              </Link>
            </div>
          </div>
        </div>

        {/* Stats Bar */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-10">
          <div className="bg-card border border-border rounded-lg p-6 grid grid-cols-2 md:grid-cols-4 gap-6">
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">15000+</div>
              <div className="text-sm text-muted-foreground">Gemstones Recommended</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">4.8/5</div>
              <div className="text-sm text-muted-foreground">Average Rating</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">95%</div>
              <div className="text-sm text-muted-foreground">Customer Satisfaction</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">25+</div>
              <div className="text-sm text-muted-foreground">Years of Expertise</div>
            </div>
          </div>
        </div>

        {/* About Gemstones */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="bg-card border border-border rounded-lg p-8">
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
              <div>
                <h2 className="text-2xl font-serif font-semibold mb-4">The Power of Vedic Gemstones</h2>
                <p className="text-muted-foreground mb-4">
                  In Vedic astrology, each of the nine planets is associated with a specific gemstone that can channel cosmic energies to enhance positive influences and mitigate negative ones in your life.
                </p>
                <p className="text-muted-foreground mb-4">
                  Our expert astrologers analyze your birth chart to identify weak or afflicted planets and recommend specific gemstones that resonate with your unique astrological profile. When worn correctly, these gemstones can bring balance, prosperity, and protection.
                </p>
                <div className="flex items-center gap-4 mt-6">
                  <div className="flex items-center gap-2">
                    <Check className="w-5 h-5 text-[#0E3F2E]" />
                    <span className="text-sm">Astrological Authenticity</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <Check className="w-5 h-5 text-[#0E3F2E]" />
                    <span className="text-sm">Certified Quality</span>
                  </div>
                </div>
              </div>
              <div className="bg-muted rounded-lg overflow-hidden">
                <img 
                  src="https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg" 
                  alt="Gemstones" 
                  className="w-full h-full object-cover"
                />
              </div>
            </div>
          </div>
        </div>

        {/* Gemstones Grid */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">Vedic Gemstones</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Each gemstone corresponds to a celestial body and specific life aspects
            </p>
          </div>
          
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {gemstones.map((gemstone) => (
              <div key={gemstone.id} className="border border-border rounded-lg overflow-hidden bg-card flex flex-col h-full">
                <div className="bg-muted flex-shrink-0">
                  <img 
                    src={gemstone.image} 
                    alt={gemstone.name}
                    className="w-full h-40 object-cover"
                  />
                </div>
                <div className="p-5 flex-grow">
                  <div className="flex items-center justify-between mb-3">
                    <h3 className="font-medium text-lg">{gemstone.name}</h3>
                    <span className="px-2 py-1 bg-[#0E3F2E]/10 text-[#0E3F2E] text-xs rounded-full">
                      {gemstone.planet}
                    </span>
                  </div>
                  <p className="text-sm text-muted-foreground mb-2">
                    <span className="font-medium">Color:</span> {gemstone.color}
                  </p>
                  <p className="text-sm text-muted-foreground">
                    <span className="font-medium">Properties:</span> {gemstone.properties}
                  </p>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Gemstone Packages */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">Gemstone Packages</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Choose the perfect package for your personalized gemstone recommendations
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {gemstonePackages.map((pkg) => (
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
            <h2 className="text-2xl font-serif font-semibold mb-4">Transformative Experiences</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Hear from those who have benefited from our gemstone recommendations
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
              Find answers to common questions about our gemstone services
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
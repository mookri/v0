'use client'
import React, { useState } from 'react';
import Image from "next/image"
import Link from "next/link"
import { Button } from "@/components/ui/button"
import { Heart, Star, Calendar, Users, BookOpen, ShieldCheck, Zap, Leaf, Target, ChevronLeft, ChevronRight, Play, ChevronDown, Check, MapPin, Mail, Phone } from "lucide-react"

type PujaData = {
  title: string;
  description: string;
  price: string;
  imgQuery: string;
  highlights: string[];
  longDescription: string;
  benefits: string[];
  process: { step: string; description: string }[];
  image: string;
  includes: string[];
  faqs: { question: string; answer: string }[];
};

const PujaDetailsClient = ({ puja, slug }: { puja: PujaData, slug: string }) => {
  const [currentImage, setCurrentImage] = useState(0);
  const [expandedSection, setExpandedSection] = useState<string | null>(null);
  const [selectedPujaOption, setSelectedPujaOption] = useState(0);

  const pujaOptions = [
    { name: "Basic Ceremony", description: "Essential rituals with standard offerings", price: puja.price.split(" – ")[0], duration: "1-2 Hours" },
    { name: "Premium Package", description: "Complete ceremony with additional benefits", price: puja.price.split(" – ")[1] || puja.price, duration: "2-3 Hours" },
  ];

  const nextImage = () => setCurrentImage((prev) => (prev + 1) % 3);
  const prevImage = () => setCurrentImage((prev) => (prev - 1 + 3) % 3);

  const images = [
    puja.image,
    "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg"
  ];

  return (
    <div className="min-h-screen bg-background">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Left Column - Main Content */}
          <div className="lg:col-span-2 space-y-6">
            {/* Breadcrumb - Minimal size */}
            <nav className="text-xs text-muted-foreground">
              <Link href="/" className="hover:text-foreground">Home</Link>
              <span className="mx-1">/</span>
              <Link href="/pujas" className="hover:text-foreground">Puja Services</Link>
              <span className="mx-1">/</span>
              <span className="text-foreground">{puja.title}</span>
            </nav>

            {/* Header - Smaller card */}
            <div className="bg-card rounded-lg shadow-sm p-5 border border-border">
              <div className="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-5">
                <div>
                  <h1 className="text-2xl font-serif font-bold text-foreground mb-2">
                    {puja.title}
                  </h1>
                  <div className="flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                    <span className="flex items-center gap-1">
                      <MapPin size={14} />
                      Delhi NCR
                    </span>
                    <span>Verified Pandits</span>
                    <span>Certified Service</span>
                  </div>
                </div>
                <div className="text-right">
                  <span className="text-xs text-muted-foreground block mb-1">Starts From</span>
                  <span className="text-xl font-bold text-primary">{puja.price.split(" – ")[0]}</span>
                </div>
              </div>

              {/* Image Gallery */}
              <div className="relative mb-4">
                <div className="aspect-video rounded-lg overflow-hidden bg-muted">
                  <img
                    src={images[currentImage]}
                    alt={`${puja.title} - Navdhya Puja Services`}
                    className="w-full h-full object-cover"
                  />
                </div>
                <button
                  onClick={prevImage}
                  className="absolute left-2 top-1/2 -translate-y-1/2 bg-background/80 hover:bg-background rounded-full p-2 shadow-lg border border-border"
                >
                  <ChevronLeft size={20} className="text-foreground" />
                </button>
                <button
                  onClick={nextImage}
                  className="absolute right-2 top-1/2 -translate-y-1/2 bg-background/80 hover:bg-background rounded-full p-2 shadow-lg border border-border"
                >
                  <ChevronRight size={20} className="text-foreground" />
                </button>
              </div>

              {/* Thumbnail Gallery */}
              <div className="flex gap-2 overflow-x-auto pb-2">
                {images.map((img, idx) => (
                  <button
                    key={idx}
                    onClick={() => setCurrentImage(idx)}
                    className={`flex-shrink-0 w-16 h-16 rounded-md overflow-hidden border-2 ${
                      currentImage === idx ? 'border-primary' : 'border-transparent'
                    }`}
                  >
                    <img src={img} alt={`Thumbnail ${idx + 1}`} className="w-full h-full object-cover" />
                  </button>
                ))}
              </div>
            </div>

            {/* Service Overview - Minimal design */}
            <div className="bg-card rounded-lg shadow-sm p-5 border border-border">
              <h2 className="text-lg font-serif font-bold mb-3">About This Puja</h2>
              <p className="text-muted-foreground leading-relaxed text-sm mb-3">
                {puja.longDescription}
              </p>
              <button className="text-primary text-sm font-medium hover:underline">Read More</button>
            </div>

            {/* Service Options - Reduced height */}
            <div className="bg-card rounded-lg shadow-sm p-5 border border-border">
              <h2 className="text-lg font-serif font-bold mb-4">Puja Options</h2>
              <div className="space-y-3">
                {pujaOptions.map((option, idx) => (
                  <div 
                    key={idx} 
                    className={`flex items-center gap-3 p-3 border rounded-lg transition cursor-pointer ${
                      selectedPujaOption === idx 
                        ? 'border-primary bg-primary/5' 
                        : 'border-border hover:border-primary/30'
                    }`}
                    onClick={() => setSelectedPujaOption(idx)}
                  >
                    <div className="w-10 h-10 bg-[#0E3F2E] rounded-lg flex-shrink-0 flex items-center justify-center">
                      <Leaf className="text-white" size={18} />
                    </div>
                    <div className="flex-1">
                      <h3 className="font-medium text-foreground text-sm">{option.name}</h3>
                      <p className="text-xs text-muted-foreground">{option.description}</p>
                    </div>
                    <div className="text-right">
                      <div className="text-primary font-bold text-sm">{option.price}</div>
                      <div className="text-xs text-muted-foreground">{option.duration}</div>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* What's Included - Minimal sleek design */}
            <div className="bg-card rounded-lg shadow-sm p-5 border border-border">
              <h2 className="text-lg font-serif font-bold mb-4">What's Included</h2>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                {puja.includes.map((item: string, idx: number) => (
                  <div key={idx} className="flex items-start gap-2 text-muted-foreground">
                    <Check size={16} className="text-primary flex-shrink-0 mt-0.5" />
                    <span className="text-sm">{item}</span>
                  </div>
                ))}
              </div>
            </div>

            {/* Ceremony Process - Minimal sleek design */}
            <div className="bg-card rounded-lg shadow-sm p-5 border border-border">
              <h2 className="text-lg font-serif font-bold mb-4">Ceremony Process</h2>
              <div className="space-y-3">
                {puja.process.map((step: { step: string; description: string }, idx: number) => (
                  <div key={idx} className="flex gap-3 p-3 border border-border rounded-lg">
                    <div className="w-6 h-6 rounded-full bg-[#0E3F2E] flex items-center justify-center text-white font-bold text-xs flex-shrink-0">
                      {idx + 1}
                    </div>
                    <div>
                      <h3 className="font-medium text-foreground text-sm">{step.step}</h3>
                      <p className="text-xs text-muted-foreground mt-1">{step.description}</p>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* Spiritual Benefits - Minimal sleek design */}
            <div className="bg-card rounded-lg shadow-sm p-5 border border-border">
              <h2 className="text-lg font-serif font-bold mb-4">Spiritual Benefits</h2>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                {puja.benefits.map((benefit: string, idx: number) => (
                  <div key={idx} className="flex items-start gap-2 text-muted-foreground">
                    <Check size={16} className="text-primary flex-shrink-0 mt-0.5" />
                    <span className="text-sm">{benefit}</span>
                  </div>
                ))}
              </div>
            </div>

            {/* FAQ Section - Minimal design */}
            <div className="bg-card rounded-lg shadow-sm p-5 border border-border">
              <h2 className="text-lg font-serif font-bold mb-4">Frequently Asked Questions</h2>
              <div className="space-y-3">
                {puja.faqs.map((faq: { question: string; answer: string }, idx: number) => (
                  <div key={idx} className="border-b border-border pb-3 last:border-0 last:pb-0">
                    <button
                      className="w-full flex items-center justify-between p-2 text-left"
                      onClick={() => setExpandedSection(expandedSection === `faq-${idx}` ? null : `faq-${idx}`)}
                    >
                      <span className="text-foreground text-sm font-medium">{faq.question}</span>
                      <ChevronDown 
                        size={16} 
                        className={`text-muted-foreground flex-shrink-0 ml-2 transition-transform ${
                          expandedSection === `faq-${idx}` ? 'rotate-180' : ''
                        }`} 
                      />
                    </button>
                    {expandedSection === `faq-${idx}` && (
                      <div className="pt-2 pl-2">
                        <p className="text-sm text-muted-foreground">{faq.answer}</p>
                      </div>
                    )}
                  </div>
                ))}
              </div>
            </div>

            {/* Reviews - Minimal design */}
            <div className="bg-card rounded-lg shadow-sm p-5 border border-border">
              <div className="flex items-center justify-between mb-4">
                <h2 className="text-lg font-serif font-bold">Customer Reviews</h2>
                <button className="bg-primary text-primary-foreground px-3 py-1.5 rounded-md text-xs font-medium hover:bg-primary/90 transition">
                  Write a Review
                </button>
              </div>
              <div className="flex items-center gap-2 mb-3">
                <div className="flex">
                  {[...Array(5)].map((_, i) => (
                    <Star key={i} size={14} className="text-yellow-500 fill-current" />
                  ))}
                </div>
                <span className="text-xs font-medium">4.9 Excellent</span>
                <span className="text-xs text-muted-foreground">(128 Reviews)</span>
              </div>
              <div className="space-y-3">
                {[
                  { name: "Rajesh Kumar", date: "2 days ago", comment: "The pandit was very knowledgeable and conducted the ceremony beautifully. Highly recommended!" },
                  { name: "Priya Sharma", date: "1 week ago", comment: "Professional service and authentic rituals. Will definitely book again for other ceremonies." }
                ].map((review, idx) => (
                  <div key={idx} className="border-b border-border pb-3 last:border-0 last:pb-0">
                    <div className="flex justify-between mb-1">
                      <span className="font-medium text-sm">{review.name}</span>
                      <span className="text-xs text-muted-foreground">{review.date}</span>
                    </div>
                    <p className="text-xs text-muted-foreground">{review.comment}</p>
                  </div>
                ))}
              </div>
            </div>
          </div>

          {/* Right Column - Sidebar */}
          <div className="space-y-6">
            {/* Booking Card - Minimal design */}
            <div className="bg-card rounded-lg shadow-sm p-5 border border-border sticky top-6">
              <div className="flex items-center gap-2 mb-3">
                <Star size={14} className="text-yellow-500 fill-current" />
                <span className="text-xs font-medium">4.9 Excellent</span>
                <span className="text-xs text-muted-foreground ml-auto">128 Bookings</span>
              </div>
              
              <div className="flex items-center gap-2 mb-4">
                <Check size={14} className="text-primary" />
                <span className="text-xs text-muted-foreground">Satisfaction Guaranteed</span>
              </div>

              <button className="w-full bg-[#0E3F2E] text-white py-2.5 rounded-md font-medium hover:bg-[#0E3F2E]/90 transition mb-5 flex items-center justify-center gap-2 text-sm">
                <BookOpen size={16} />
                Book This Puja
              </button>

              {/* Service Provider */}
              <div className="border-t border-border pt-5">
                <h3 className="font-serif font-bold mb-3 text-base">Meet Your Pandit</h3>
                <div className="flex items-center gap-3 mb-3">
                  <div className="w-14 h-14 bg-muted rounded-full overflow-hidden flex-shrink-0">
                    <img 
                      src="https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg" 
                      alt="Certified Pandit" 
                      className="w-full h-full object-cover" 
                    />
                  </div>
                  <div>
                    <div className="font-medium text-sm">Certified Vedic Pandit</div>
                    <div className="text-xs text-muted-foreground">10+ Years Experience</div>
                  </div>
                </div>

                <div className="space-y-2 text-xs">
                  <div className="flex items-center gap-2 text-muted-foreground">
                    <span className="w-20">Experience</span>
                    <span className="ml-auto">10+ Years</span>
                  </div>
                  <div className="flex items-center gap-2 text-muted-foreground">
                    <MapPin size={14} className="flex-shrink-0" />
                    <span className="w-20">Service Area</span>
                    <span className="ml-auto text-right">Delhi NCR</span>
                  </div>
                  <div className="flex items-center gap-2 text-muted-foreground">
                    <ShieldCheck size={14} className="flex-shrink-0" />
                    <span className="w-20">Certification</span>
                    <span className="ml-auto">Vedic University</span>
                  </div>
                  <div className="flex items-center gap-2 text-muted-foreground">
                    <Star size={14} className="flex-shrink-0" />
                    <span className="w-20">Rituals Done</span>
                    <span className="ml-auto">1500+</span>
                  </div>
                </div>

                <button className="w-full mt-4 border border-primary text-primary py-2 rounded-md font-medium hover:bg-primary/10 transition flex items-center justify-center gap-1 text-xs">
                  <Phone size={14} />
                  Contact Pandit
                </button>
              </div>
            </div>

            {/* Highlights - Minimal design */}
            <div className="bg-card rounded-lg shadow-sm p-5 border border-border">
              <h3 className="font-serif font-bold mb-3 text-base">Service Highlights</h3>
              <div className="space-y-2">
                {puja.highlights.map((highlight: string, idx: number) => (
                  <div key={idx} className="flex items-center gap-2">
                    <Check size={14} className="text-primary flex-shrink-0" />
                    <span className="text-xs text-muted-foreground">{highlight}</span>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default PujaDetailsClient
'use client'
import React, { useState } from 'react';
import Link from "next/link"
import { Heart, Star, Calendar, Users, BookOpen, ShieldCheck, Zap, Leaf, Target, ChevronLeft, ChevronRight, Play, ChevronDown, Check, MapPin, Mail, Phone, Clock, Award, CalendarDays, User, PhoneCall } from "lucide-react"
import Preloader from "@/components/ui/preloader"

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
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Left Column - Main Content */}
          <div className="lg:col-span-2 space-y-7">
            {/* Breadcrumb */}
            <nav className="text-xs">
              <Link href="/" className="text-muted-foreground hover:text-foreground transition-colors">Home</Link>
              <span className="mx-1.5 text-muted-foreground">/</span>
              <Link href="/pujas" className="text-muted-foreground hover:text-foreground transition-colors">Puja Services</Link>
              <span className="mx-1.5 text-muted-foreground">/</span>
              <span className="text-foreground font-medium">{puja.title}</span>
            </nav>

            {/* Header Section */}
            <div className="border-b border-border pb-4">
              <h1 className="text-xl font-serif font-bold text-foreground mb-2">
                {puja.title}
              </h1>
              <div className="flex flex-wrap items-center gap-2.5 text-xs">
                <div className="flex items-center gap-1">
                  <MapPin size={12} className="text-primary" />
                  <span className="whitespace-nowrap">Delhi NCR</span>
                </div>
                <div className="flex items-center gap-1">
                  <ShieldCheck size={12} className="text-primary" />
                  <span className="whitespace-nowrap">Verified Pandits</span>
                </div>
                <div className="flex items-center gap-1">
                  <Award size={12} className="text-primary" />
                  <span className="whitespace-nowrap">Certified Service</span>
                </div>
              </div>
            </div>

            {/* Image Gallery */}
            <div className="relative">
              <div className="aspect-video rounded-md overflow-hidden bg-muted">
                <img
                  src={images[currentImage]}
                  alt={`${puja.title} - Navdhya Puja Services`}
                  className="w-full h-full object-cover"
                />
              </div>
              <button
                onClick={prevImage}
                className="absolute left-2 top-1/2 -translate-y-1/2 bg-background/90 hover:bg-background rounded-full p-1.5 shadow border border-border transition-all"
              >
                <ChevronLeft size={16} className="text-foreground" />
              </button>
              <button
                onClick={nextImage}
                className="absolute right-2 top-1/2 -translate-y-1/2 bg-background/90 hover:bg-background rounded-full p-1.5 shadow border border-border transition-all"
              >
                <ChevronRight size={16} className="text-foreground" />
              </button>
              
              {/* Thumbnail Gallery */}
              <div className="flex gap-1 mt-2.5 justify-center">
                {images.map((img, idx) => (
                  <button
                    key={idx}
                    onClick={() => setCurrentImage(idx)}
                    className={`flex-shrink-0 w-12 h-12 rounded-sm overflow-hidden border transition-all ${
                      currentImage === idx ? 'border-primary' : 'border-transparent hover:border-border'
                    }`}
                  >
                    <img src={img} alt={`Thumbnail ${idx + 1}`} className="w-full h-full object-cover" />
                  </button>
                ))}
              </div>
            </div>

            {/* Service Overview */}
            <div className="space-y-3.5">
              <h2 className="text-lg font-serif font-bold text-foreground">About This Puja</h2>
              <div className="text-muted-foreground text-sm">
                <p className="leading-relaxed">{puja.longDescription}</p>
              </div>
            </div>

            {/* Service Options */}
            <div className="space-y-3.5">
              <h2 className="text-lg font-serif font-bold text-foreground">Puja Options</h2>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                {pujaOptions.map((option, idx) => (
                  <div 
                    key={idx} 
                    className={`border rounded p-3 transition-all cursor-pointer ${
                      selectedPujaOption === idx 
                        ? 'border-primary bg-primary/5' 
                        : 'border-border hover:border-primary/50'
                    }`}
                    onClick={() => setSelectedPujaOption(idx)}
                  >
                    <div className="flex items-start gap-2.5">
                      <div className="w-9 h-9 bg-[#0E3F2E] rounded-sm flex-shrink-0 flex items-center justify-center">
                        <Leaf className="text-white" size={16} />
                      </div>
                      <div className="flex-1">
                        <h3 className="font-bold text-foreground text-sm mb-1 whitespace-nowrap">{option.name}</h3>
                        <p className="text-muted-foreground text-xs mb-2 whitespace-nowrap">{option.description}</p>
                        <div className="flex items-center justify-between">
                          <div className="flex items-center gap-1 text-muted-foreground">
                            <Clock size={12} />
                            <span className="text-xs whitespace-nowrap">{option.duration}</span>
                          </div>
                          <div className="text-primary font-bold text-sm whitespace-nowrap">{option.price}</div>
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* What's Included */}
            <div className="space-y-3.5">
              <h2 className="text-lg font-serif font-bold text-foreground">What's Included</h2>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                {puja.includes.map((item: string, idx: number) => (
                  <div key={idx} className="flex items-start gap-2 p-2.5 rounded-sm bg-muted/30 text-xs">
                    <Check size={14} className="text-primary flex-shrink-0 mt-0.5" />
                    <span className="text-foreground whitespace-nowrap overflow-hidden text-ellipsis">{item}</span>
                  </div>
                ))}
              </div>
            </div>

            {/* Ceremony Process */}
            <div className="space-y-3.5">
              <h2 className="text-lg font-serif font-bold text-foreground">Ceremony Process</h2>
              <div className="space-y-2.5">
                {puja.process.map((step: { step: string; description: string }, idx: number) => (
                  <div key={idx} className="flex gap-3 p-3 rounded border border-border text-sm">
                    <div className="w-7 h-7 rounded-full bg-[#0E3F2E] flex items-center justify-center text-white font-bold text-xs flex-shrink-0">
                      {idx + 1}
                    </div>
                    <div className="min-w-0">
                      <h3 className="font-bold text-foreground mb-1 whitespace-nowrap overflow-hidden text-ellipsis">{step.step}</h3>
                      <p className="text-muted-foreground text-xs whitespace-nowrap overflow-hidden text-ellipsis">{step.description}</p>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* Spiritual Benefits */}
            <div className="space-y-3.5">
              <h2 className="text-lg font-serif font-bold text-foreground">Spiritual Benefits</h2>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                {puja.benefits.map((benefit: string, idx: number) => (
                  <div key={idx} className="flex items-start gap-2 p-2.5 rounded-sm bg-muted/30 text-xs">
                    <Check size={14} className="text-primary flex-shrink-0 mt-0.5" />
                    <span className="text-foreground whitespace-nowrap overflow-hidden text-ellipsis">{benefit}</span>
                  </div>
                ))}
              </div>
            </div>

            {/* FAQ Section */}
            <div className="space-y-3.5">
              <h2 className="text-lg font-serif font-bold text-foreground">Frequently Asked Questions</h2>
              <div className="space-y-2">
                {puja.faqs.map((faq: { question: string; answer: string }, idx: number) => (
                  <div key={idx} className="border border-border rounded overflow-hidden">
                    <button
                      className="w-full flex items-center justify-between p-3 text-left bg-muted/30 hover:bg-muted/50 transition-colors"
                      onClick={() => setExpandedSection(expandedSection === `faq-${idx}` ? null : `faq-${idx}`)}
                    >
                      <span className="font-medium text-foreground text-sm whitespace-nowrap overflow-hidden text-ellipsis">{faq.question}</span>
                      <ChevronDown 
                        size={16} 
                        className={`text-muted-foreground flex-shrink-0 ml-2 transition-transform ${
                          expandedSection === `faq-${idx}` ? 'rotate-180' : ''
                        }`} 
                      />
                    </button>
                    {expandedSection === `faq-${idx}` && (
                      <div className="p-3 pt-0">
                        <p className="text-muted-foreground text-xs whitespace-nowrap overflow-hidden text-ellipsis">{faq.answer}</p>
                      </div>
                    )}
                  </div>
                ))}
              </div>
            </div>

            {/* Reviews */}
            <div className="space-y-3.5 pb-7">
              <div className="flex items-center justify-between">
                <h2 className="text-lg font-serif font-bold text-foreground">Customer Reviews</h2>
                <button className="bg-primary text-primary-foreground px-3 py-1.5 rounded font-medium hover:bg-primary/90 transition text-xs whitespace-nowrap">
                  Write a Review
                </button>
              </div>
              <div className="flex items-center gap-2 mb-3">
                <div className="flex">
                  {[...Array(5)].map((_, i) => (
                    <Star key={i} size={14} className="text-yellow-500 fill-current" />
                  ))}
                </div>
                <span className="font-bold text-sm whitespace-nowrap">4.9</span>
                <span className="text-muted-foreground text-xs whitespace-nowrap">(128 Reviews)</span>
              </div>
              <div className="space-y-3">
                {[
                  { name: "Rajesh Kumar", date: "2 days ago", comment: "The pandit was very knowledgeable and conducted the ceremony beautifully. Highly recommended!" },
                  { name: "Priya Sharma", date: "1 week ago", comment: "Professional service and authentic rituals. Will definitely book again for other ceremonies." }
                ].map((review, idx) => (
                  <div key={idx} className="border-b border-border pb-3 last:border-0 last:pb-0">
                    <div className="flex justify-between mb-2">
                      <span className="font-bold text-foreground text-sm whitespace-nowrap overflow-hidden text-ellipsis">{review.name}</span>
                      <span className="text-muted-foreground text-xs whitespace-nowrap">{review.date}</span>
                    </div>
                    <p className="text-muted-foreground text-xs whitespace-nowrap overflow-hidden text-ellipsis">{review.comment}</p>
                  </div>
                ))}
              </div>
            </div>
          </div>

          {/* Right Column - Sidebar */}
          <div className="space-y-5 lg:pt-36 lg:self-start lg:h-fit">
            {/* Booking Card */}
            <div className="bg-card rounded p-3 border border-border">
              <div className="flex items-center gap-1.5 mb-2.5">
                <Star size={12} className="text-yellow-500 fill-current" />
                <span className="font-bold text-sm whitespace-nowrap">4.9 Excellent</span>
                <span className="text-muted-foreground ml-auto text-xs whitespace-nowrap">128 Bookings</span>
              </div>
              
              <div className="flex items-center gap-1.5 mb-3">
                <Check size={12} className="text-primary" />
                <span className="text-muted-foreground text-xs whitespace-nowrap">Satisfaction Guaranteed</span>
              </div>

              <div className="mb-3">
                <div className="text-muted-foreground text-xs mb-1 whitespace-nowrap">Starts From</div>
                <div className="text-lg font-bold text-primary whitespace-nowrap">{puja.price.split(" – ")[0]}</div>
              </div>

              <button className="w-full bg-[#0E3F2E] text-white py-2.5 rounded font-bold hover:bg-[#0E3F2E]/90 transition mb-4 flex items-center justify-center gap-1.5 text-sm whitespace-nowrap">
                <BookOpen size={14} />
                Book This Puja
              </button>

              {/* Service Provider */}
              <div className="border-t border-border pt-3">
                <h3 className="font-serif font-bold mb-2.5 text-base whitespace-nowrap">Meet Your Pandit</h3>
                <div className="flex items-center gap-3 mb-3">
                  <div className="w-12 h-12 bg-muted rounded-sm overflow-hidden flex-shrink-0">
                    <img 
                      src="https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg" 
                      alt="Certified Pandit" 
                      className="w-full h-full object-cover" 
                    />
                  </div>
                  <div>
                    <div className="font-bold text-sm whitespace-nowrap">Certified Vedic Pandit</div>
                    <div className="text-muted-foreground text-xs whitespace-nowrap">10+ Years Experience</div>
                  </div>
                </div>

                <div className="space-y-2">
                  <div className="flex items-center gap-2 text-muted-foreground text-xs">
                    <CalendarDays size={12} className="flex-shrink-0" />
                    <span className="w-14 whitespace-nowrap">Experience</span>
                    <span className="ml-auto whitespace-nowrap">10+ Years</span>
                  </div>
                  <div className="flex items-center gap-2 text-muted-foreground text-xs">
                    <MapPin size={12} className="flex-shrink-0" />
                    <span className="w-14 whitespace-nowrap">Service Area</span>
                    <span className="ml-auto text-right whitespace-nowrap">Delhi NCR</span>
                  </div>
                  <div className="flex items-center gap-2 text-muted-foreground text-xs">
                    <ShieldCheck size={12} className="flex-shrink-0" />
                    <span className="w-14 whitespace-nowrap">Certification</span>
                    <span className="ml-auto whitespace-nowrap">Vedic University</span>
                  </div>
                  <div className="flex items-center gap-2 text-muted-foreground text-xs">
                    <Star size={12} className="flex-shrink-0" />
                    <span className="w-14 whitespace-nowrap">Rituals Done</span>
                    <span className="ml-auto whitespace-nowrap">1500+</span>
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-1.5 mt-3">
                  <button className="border border-primary text-primary py-1.5 rounded font-medium hover:bg-primary/10 transition flex items-center justify-center gap-1 text-xs whitespace-nowrap">
                    <PhoneCall size={12} />
                    Call
                  </button>
                  <button className="bg-primary text-primary-foreground py-1.5 rounded font-medium hover:bg-primary/90 transition flex items-center justify-center gap-1 text-xs whitespace-nowrap">
                    <User size={12} />
                    Message
                  </button>
                </div>
              </div>
            </div>

            {/* Highlights */}
            <div className="bg-card rounded p-3 border border-border">
              <h3 className="font-serif font-bold mb-2.5 text-base whitespace-nowrap">Service Highlights</h3>
              <div className="space-y-2">
                {puja.highlights.map((highlight: string, idx: number) => (
                  <div key={idx} className="flex items-center gap-2 p-2 rounded-sm bg-muted/30 text-xs">
                    <Check size={12} className="text-primary flex-shrink-0" />
                    <span className="text-foreground whitespace-nowrap overflow-hidden text-ellipsis">{highlight}</span>
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
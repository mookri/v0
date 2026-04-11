'use client'
import React, { useState } from 'react';
import Link from "next/link"
import Script from "next/script"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock } from "lucide-react"
import { Button } from "@/components/ui/button"

export default function BookConsultationClient({ consultationSchema }: { consultationSchema: string }) {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    serviceType: '',
    date: '',
    time: '',
    message: ''
  });
  
  const serviceTypes = [
    "Puja Service Booking",
    "Astrology Consultation",
    "Vastu Consultation",
    "Graha Shanti",
    "Baby Naming Ceremony",
    "Wedding Rituals",
    "Business Prosperity Rituals",
    "Health & Wellness Rituals"
  ];
  
  const timeSlots = [
    "09:00 AM - 11:00 AM",
    "11:00 AM - 01:00 PM",
    "02:00 PM - 04:00 PM",
    "04:00 PM - 06:00 PM",
    "06:00 PM - 08:00 PM"
  ];

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    // Handle form submission
    console.log('Form submitted:', formData);
    alert('Thank you! We will contact you shortly to confirm your consultation.');
  };

  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: consultationSchema }}
      />
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <Link href="/" className="hover:text-foreground">Home</Link>
            <span className="mx-1.5">/</span>
            <Link href="/pandits" className="hover:text-foreground">Pandits & Experts</Link>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">Book Consultation</span>
          </nav>
        </div>

        {/* Hero Banner */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div 
            className="relative h-48 rounded-lg overflow-hidden bg-cover bg-center flex items-end"
            style={{
              backgroundImage: "url('https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg')"
            }}
          >
            <div className="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
            <div className="relative z-10 text-white p-4 max-w-2xl">
              <h1 className="text-2xl font-serif font-semibold">Book Pandit Consultation</h1>
              <p className="text-sm mt-1">
                Schedule a personalized consultation with our verified Vedic pandits for puja services, astrology readings, and spiritual guidance.
              </p>
            </div>
          </div>
        </div>

        <div className="max-w-7xl mx-auto px-4 sm:px-6 pb-10">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {/* Booking Form */}
            <div className="lg:col-span-2">
              <div className="bg-card border border-border rounded-lg p-6">
                <h2 className="text-xl font-semibold mb-6">Book Your Consultation</h2>
                
                <form onSubmit={handleSubmit} className="space-y-6">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <label htmlFor="name" className="block text-sm font-medium mb-2">Full Name</label>
                      <input
                        type="text"
                        id="name"
                        name="name"
                        value={formData.name}
                        onChange={handleChange}
                        className="w-full px-3 py-2 border border-border rounded-md bg-background focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]"
                        required
                      />
                    </div>
                    
                    <div>
                      <label htmlFor="email" className="block text-sm font-medium mb-2">Email Address</label>
                      <input
                        type="email"
                        id="email"
                        name="email"
                        value={formData.email}
                        onChange={handleChange}
                        className="w-full px-3 py-2 border border-border rounded-md bg-background focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]"
                        required
                      />
                    </div>
                    
                    <div>
                      <label htmlFor="phone" className="block text-sm font-medium mb-2">Phone Number</label>
                      <input
                        type="tel"
                        id="phone"
                        name="phone"
                        value={formData.phone}
                        onChange={handleChange}
                        className="w-full px-3 py-2 border border-border rounded-md bg-background focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]"
                        required
                      />
                    </div>
                    
                    <div>
                      <label htmlFor="serviceType" className="block text-sm font-medium mb-2">Service Type</label>
                      <select
                        id="serviceType"
                        name="serviceType"
                        value={formData.serviceType}
                        onChange={handleChange}
                        className="w-full px-3 py-2 border border-border rounded-md bg-background focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]"
                        required
                      >
                        <option value="">Select a service</option>
                        {serviceTypes.map((type, index) => (
                          <option key={index} value={type}>{type}</option>
                        ))}
                      </select>
                    </div>
                    
                    <div>
                      <label htmlFor="date" className="block text-sm font-medium mb-2">Preferred Date</label>
                      <input
                        type="date"
                        id="date"
                        name="date"
                        value={formData.date}
                        onChange={handleChange}
                        className="w-full px-3 py-2 border border-border rounded-md bg-background focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]"
                        required
                      />
                    </div>
                    
                    <div>
                      <label htmlFor="time" className="block text-sm font-medium mb-2">Preferred Time</label>
                      <select
                        id="time"
                        name="time"
                        value={formData.time}
                        onChange={handleChange}
                        className="w-full px-3 py-2 border border-border rounded-md bg-background focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]"
                        required
                      >
                        <option value="">Select a time slot</option>
                        {timeSlots.map((slot, index) => (
                          <option key={index} value={slot}>{slot}</option>
                        ))}
                      </select>
                    </div>
                  </div>
                  
                  <div>
                    <label htmlFor="message" className="block text-sm font-medium mb-2">Additional Details</label>
                    <textarea
                      id="message"
                      name="message"
                      value={formData.message}
                      onChange={handleChange}
                      rows={4}
                      className="w-full px-3 py-2 border border-border rounded-md bg-background focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]"
                      placeholder="Please provide any specific requirements or questions you have for the pandit..."
                    ></textarea>
                  </div>
                  
                  <Button type="submit" className="w-full bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white font-medium py-3 rounded-md transition-all duration-300 shadow-sm hover:shadow-md">
                    Book Consultation
                  </Button>
                </form>
              </div>
            </div>
            
            {/* Information Sidebar */}
            <div className="space-y-6">
              {/* Why Book With Us */}
              <div className="bg-card border border-border rounded-lg p-6">
                <h3 className="text-lg font-semibold mb-4">Why Book With Navdhya?</h3>
                <ul className="space-y-3">
                  <li className="flex items-start gap-2">
                    <Award className="w-5 h-5 text-[#0E3F2E] mt-0.5 flex-shrink-0" />
                    <span className="text-sm">Verified & Certified Pandits</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <Star className="w-5 h-5 text-[#0E3F2E] mt-0.5 flex-shrink-0" />
                    <span className="text-sm">4.9/5 Average Rating</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <Clock className="w-5 h-5 text-[#0E3F2E] mt-0.5 flex-shrink-0" />
                    <span className="text-sm">Flexible Scheduling</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <Users className="w-5 h-5 text-[#0E3F2E] mt-0.5 flex-shrink-0" />
                    <span className="text-sm">15+ Years Experience</span>
                  </li>
                </ul>
              </div>
              
              {/* Contact Info */}
              <div className="bg-card border border-border rounded-lg p-6">
                <h3 className="text-lg font-semibold mb-4">Need Help?</h3>
                <div className="space-y-4">
                  <div className="flex items-center gap-3">
                    <Phone className="w-5 h-5 text-[#0E3F2E]" />
                    <div>
                      <div className="text-sm font-medium">Call Us</div>
                      <div className="text-sm text-muted-foreground">+91 7289000377</div>
                    </div>
                  </div>
                  <div className="flex items-center gap-3">
                    <Mail className="w-5 h-5 text-[#0E3F2E]" />
                    <div>
                      <div className="text-sm font-medium">Email Us</div>
                      <div className="text-sm text-muted-foreground">support@navdhya.com</div>
                    </div>
                  </div>
                </div>
              </div>
              
              {/* Service Guarantee */}
              <div className="bg-card border border-border rounded-lg p-6">
                <h3 className="text-lg font-semibold mb-4">Our Guarantee</h3>
                <p className="text-sm text-muted-foreground mb-4">
                  We guarantee satisfaction with every service. If you're not completely happy with your consultation, we'll work with you to make it right or provide a full refund.
                </p>
                <Link href="/service-guarantee" className="text-sm text-[#0E3F2E] hover:underline">
                  View Service Guarantee →
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  )
}

'use client'
import React, { useState } from 'react';
import Link from "next/link"
import Script from "next/script"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, Upload, FileText, CheckCircle } from "lucide-react"
import { Button } from "@/components/ui/button"

interface JoinPanditClientProps {
  joinSchema: string;
}

export default function JoinPanditClient({ joinSchema }: JoinPanditClientProps) {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    city: '',
    experience: '',
    expertise: '',
    certification: '',
    message: ''
  });
  
  const expertiseAreas = [
    "Vedic Rituals",
    "South Indian Pujas",
    "North Indian Ceremonies",
    "Astrology & Kundli",
    "Vastu Consultation",
    "Temple Rituals",
    "Wedding Ceremonies",
    "Health & Wellness Rituals",
    "Business Prosperity Rituals",
    "Festival Celebrations"
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
    alert('Thank you for your application! Our team will review your details and contact you within 3-5 business days.');
  };

  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: joinSchema }}
      />
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <Link href="/" className="hover:text-foreground">Home</Link>
            <span className="mx-1.5">/</span>
            <Link href="/pandits" className="hover:text-foreground">Pandits & Experts</Link>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">Join as Pandit</span>
          </nav>
        </div>

        {/* Hero Banner */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div 
            className="relative h-48 rounded-lg overflow-hidden bg-cover bg-center flex items-end"
            style={{
              backgroundImage: "url('https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg')"
            }}
          >
            <div className="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
            <div className="relative z-10 text-white p-4 max-w-2xl">
              <h1 className="text-2xl font-serif font-semibold">Join as Pandit</h1>
              <p className="text-sm mt-1">
                Become a verified pandit with Navdhya. Join our network of certified Vedic experts and serve families in Delhi NCR with authentic puja services.
              </p>
            </div>
          </div>
        </div>

        <div className="max-w-7xl mx-auto px-4 sm:px-6 pb-10">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {/* Application Form */}
            <div className="lg:col-span-2">
              <div className="bg-card border border-border rounded-lg p-6">
                <h2 className="text-xl font-semibold mb-6">Pandit Application Form</h2>
                
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
                      <label htmlFor="city" className="block text-sm font-medium mb-2">City</label>
                      <input
                        type="text"
                        id="city"
                        name="city"
                        value={formData.city}
                        onChange={handleChange}
                        className="w-full px-3 py-2 border border-border rounded-md bg-background focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]"
                        required
                      />
                    </div>
                    
                    <div>
                      <label htmlFor="experience" className="block text-sm font-medium mb-2">Years of Experience</label>
                      <select
                        id="experience"
                        name="experience"
                        value={formData.experience}
                        onChange={handleChange}
                        className="w-full px-3 py-2 border border-border rounded-md bg-background focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]"
                        required
                      >
                        <option value="">Select experience</option>
                        <option value="1-3 years">1-3 years</option>
                        <option value="3-5 years">3-5 years</option>
                        <option value="5-10 years">5-10 years</option>
                        <option value="10+ years">10+ years</option>
                      </select>
                    </div>
                    
                    <div>
                      <label htmlFor="expertise" className="block text-sm font-medium mb-2">Primary Expertise</label>
                      <select
                        id="expertise"
                        name="expertise"
                        value={formData.expertise}
                        onChange={handleChange}
                        className="w-full px-3 py-2 border border-border rounded-md bg-background focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]"
                        required
                      >
                        <option value="">Select expertise</option>
                        {expertiseAreas.map((area, index) => (
                          <option key={index} value={area}>{area}</option>
                        ))}
                      </select>
                    </div>
                  </div>
                  
                  <div>
                    <label htmlFor="certification" className="block text-sm font-medium mb-2">Certification Details</label>
                    <textarea
                      id="certification"
                      name="certification"
                      value={formData.certification}
                      onChange={handleChange}
                      rows={3}
                      className="w-full px-3 py-2 border border-border rounded-md bg-background focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]"
                      placeholder="Please provide details about your certifications, training, or qualifications..."
                      required
                    ></textarea>
                  </div>
                  
                  <div>
                    <label htmlFor="message" className="block text-sm font-medium mb-2">Why do you want to join Navdhya?</label>
                    <textarea
                      id="message"
                      name="message"
                      value={formData.message}
                      onChange={handleChange}
                      rows={4}
                      className="w-full px-3 py-2 border border-border rounded-md bg-background focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]"
                      placeholder="Tell us about your passion for Vedic traditions and why you'd like to be part of our community..."
                    ></textarea>
                  </div>
                  
                  <div className="border border-border rounded-md p-4">
                    <div className="flex items-center gap-2 mb-3">
                      <Upload className="w-5 h-5 text-[#0E3F2E]" />
                      <span className="font-medium">Upload Documents</span>
                    </div>
                    <p className="text-sm text-muted-foreground mb-3">
                      Please upload your certificates, ID proof, and any other relevant documents.
                    </p>
                    <Button type="button" variant="outline" className="text-sm">
                      Choose Files
                    </Button>
                    <p className="text-xs text-muted-foreground mt-2">
                      Supported formats: PDF, JPG, PNG (Max 5MB each)
                    </p>
                  </div>
                  
                  <Button type="submit" className="w-full bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white font-medium py-3 rounded-md transition-all duration-300 shadow-sm hover:shadow-md">
                    Submit Application
                  </Button>
                </form>
              </div>
            </div>
            
            {/* Information Sidebar */}
            <div className="space-y-6">
              {/* Benefits */}
              <div className="bg-card border border-border rounded-lg p-6">
                <h3 className="text-lg font-semibold mb-4">Benefits of Joining Navdhya</h3>
                <ul className="space-y-3">
                  <li className="flex items-start gap-2">
                    <CheckCircle className="w-5 h-5 text-[#0E3F2E] mt-0.5 flex-shrink-0" />
                    <span className="text-sm">Verified Certification</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <CheckCircle className="w-5 h-5 text-[#0E3F2E] mt-0.5 flex-shrink-0" />
                    <span className="text-sm">Regular Bookings</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <CheckCircle className="w-5 h-5 text-[#0E3F2E] mt-0.5 flex-shrink-0" />
                    <span className="text-sm">Competitive Pricing</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <CheckCircle className="w-5 h-5 text-[#0E3F2E] mt-0.5 flex-shrink-0" />
                    <span className="text-sm">Training & Development</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <CheckCircle className="w-5 h-5 text-[#0E3F2E] mt-0.5 flex-shrink-0" />
                    <span className="text-sm">Marketing Support</span>
                  </li>
                </ul>
              </div>
              
              {/* Requirements */}
              <div className="bg-card border border-border rounded-lg p-6">
                <h3 className="text-lg font-semibold mb-4">Requirements</h3>
                <ul className="space-y-3">
                  <li className="flex items-start gap-2">
                    <FileText className="w-5 h-5 text-[#0E3F2E] mt-0.5 flex-shrink-0" />
                    <span className="text-sm">Valid ID Proof</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <FileText className="w-5 h-5 text-[#0E3F2E] mt-0.5 flex-shrink-0" />
                    <span className="text-sm">Certification Documents</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <FileText className="w-5 h-5 text-[#0E3F2E] mt-0.5 flex-shrink-0" />
                    <span className="text-sm">Bank Account Details</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <FileText className="w-5 h-5 text-[#0E3F2E] mt-0.5 flex-shrink-0" />
                    <span className="text-sm">References (Optional)</span>
                  </li>
                </ul>
              </div>
              
              {/* Process */}
              <div className="bg-card border border-border rounded-lg p-6">
                <h3 className="text-lg font-semibold mb-4">Application Process</h3>
                <ol className="space-y-3 text-sm">
                  <li className="flex items-start gap-2">
                    <span className="w-6 h-6 rounded-full bg-[#0E3F2E] text-white text-xs flex items-center justify-center flex-shrink-0">1</span>
                    <span>Submit Application</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <span className="w-6 h-6 rounded-full bg-[#0E3F2E] text-white text-xs flex items-center justify-center flex-shrink-0">2</span>
                    <span>Document Verification</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <span className="w-6 h-6 rounded-full bg-[#0E3F2E] text-white text-xs flex items-center justify-center flex-shrink-0">3</span>
                    <span>Interview & Assessment</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <span className="w-6 h-6 rounded-full bg-[#0E3F2E] text-white text-xs flex items-center justify-center flex-shrink-0">4</span>
                    <span>Onboarding & Training</span>
                  </li>
                </ol>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  )
}
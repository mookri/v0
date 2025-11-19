'use client'

import { Button } from "@/components/ui/button"
import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Calendar, Clock, Users, MapPin, Phone, Mail } from "lucide-react"
import Script from "next/script"
import Link from "next/link"
import { useState } from "react"

const serviceTypes = [
  { id: "home-puja", name: "Home Puja Service", description: "Pujas performed in the comfort of your home" },
  { id: "temple-puja", name: "Temple Puja Service", description: "Special pujas at renowned temples" },
  { id: "life-event", name: "Life Event Puja", description: "Sacred ceremonies for significant milestones" },
  { id: "festival", name: "Festival Puja", description: "Traditional celebrations for Hindu festivals" },
  { id: "astrology", name: "Astrology Consultation", description: "Expert guidance based on Vedic astrology" }
]

const locations = [
  "East Delhi",
  "West Delhi",
  "North Delhi",
  "South Delhi",
  "Central Delhi",
  "Gurgaon",
  "Noida",
  "Ghaziabad",
  "Faridabad"
]

export default function BookingClient({ bookingSchema }: { bookingSchema: string }) {
  const [formData, setFormData] = useState({
    name: '',
    phone: '',
    email: '',
    serviceType: '',
    date: '',
    time: '',
    participants: '1',
    location: '',
    requirements: ''
  })

  const handleChange = (name: string, value: string) => {
    setFormData(prev => ({ ...prev, [name]: value }))
  }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    console.log('Booking data:', formData)
    // In a real implementation, this would submit to an API
    alert('Booking request submitted! In a real application, this would be sent to our servers.')
  }

  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: bookingSchema }}
      />
      <main className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-12 overflow-x-hidden w-full">
        {/* Header */}
        <div className="text-center mb-12">
          <h1 className="text-3xl md:text-4xl font-bold tracking-tight mb-4">
            Book Your <span className="text-primary">Spiritual Service</span>
          </h1>
          <p className="text-lg text-muted-foreground max-w-2xl mx-auto">
            Schedule an authentic Hindu puja with our certified Vedic pandits. 
            Simply fill out the form below and we'll contact you to confirm details.
          </p>
        </div>
        
        <div className="grid md:grid-cols-3 gap-8">
          {/* Booking Form */}
          <div className="md:col-span-2">
            <div className="bg-card border border-border rounded-xl p-6 md:p-8">
              <form className="space-y-6" onSubmit={handleSubmit}>
                <div>
                  <h2 className="text-xl font-semibold mb-4">Personal Information</h2>
                  <div className="grid md:grid-cols-2 gap-4">
                    <div>
                      <Label htmlFor="name">Full Name *</Label>
                      <Input 
                        id="name" 
                        name="name"
                        placeholder="Enter your full name" 
                        required 
                        value={formData.name}
                        onChange={(e) => handleChange('name', e.target.value)}
                      />
                    </div>
                    <div>
                      <Label htmlFor="phone">Phone Number *</Label>
                      <div className="relative">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                          <span className="text-muted-foreground text-sm">+91</span>
                        </div>
                        <Input 
                          id="phone" 
                          name="phone"
                          placeholder="Enter 10-digit number" 
                          className="pl-12" 
                          required 
                          value={formData.phone}
                          onChange={(e) => handleChange('phone', e.target.value)}
                        />
                      </div>
                    </div>
                  </div>
                  <div className="mt-4">
                    <Label htmlFor="email">Email Address</Label>
                    <Input 
                      id="email" 
                      name="email"
                      type="email" 
                      placeholder="Enter your email" 
                      value={formData.email}
                      onChange={(e) => handleChange('email', e.target.value)}
                    />
                  </div>
                </div>
                
                <div>
                  <h2 className="text-xl font-semibold mb-4">Service Details</h2>
                  <div className="grid md:grid-cols-2 gap-4">
                    <div>
                      <Label htmlFor="service-type">Service Type *</Label>
                      <Select value={formData.serviceType} onValueChange={(value) => handleChange('serviceType', value)}>
                        <SelectTrigger id="service-type">
                          <SelectValue placeholder="Select a service" />
                        </SelectTrigger>
                        <SelectContent>
                          {serviceTypes.map((service) => (
                            <SelectItem key={service.id} value={service.id}>
                              {service.name}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                      <input type="hidden" name="serviceType" value={formData.serviceType} />
                    </div>
                    <div>
                      <Label htmlFor="date">Preferred Date *</Label>
                      <div className="relative">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                          <Calendar className="h-4 w-4 text-muted-foreground" />
                        </div>
                        <Input 
                          id="date" 
                          name="date"
                          type="date" 
                          className="pl-10" 
                          required 
                          value={formData.date}
                          onChange={(e) => handleChange('date', e.target.value)}
                        />
                      </div>
                    </div>
                  </div>
                  
                  <div className="grid md:grid-cols-2 gap-4 mt-4">
                    <div>
                      <Label htmlFor="time">Preferred Time *</Label>
                      <div className="relative">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                          <Clock className="h-4 w-4 text-muted-foreground" />
                        </div>
                        <Input 
                          id="time" 
                          name="time"
                          type="time" 
                          className="pl-10" 
                          required 
                          value={formData.time}
                          onChange={(e) => handleChange('time', e.target.value)}
                        />
                      </div>
                    </div>
                    <div>
                      <Label htmlFor="participants">Number of Participants</Label>
                      <div className="relative">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                          <Users className="h-4 w-4 text-muted-foreground" />
                        </div>
                        <Input 
                          id="participants" 
                          name="participants"
                          type="number" 
                          min="1" 
                          defaultValue="1"
                          className="pl-10" 
                          value={formData.participants}
                          onChange={(e) => handleChange('participants', e.target.value)}
                        />
                      </div>
                    </div>
                  </div>
                  
                  <div className="mt-4">
                    <Label htmlFor="location">Location *</Label>
                    <div className="relative">
                      <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <MapPin className="h-4 w-4 text-muted-foreground" />
                      </div>
                      <Select value={formData.location} onValueChange={(value) => handleChange('location', value)}>
                        <SelectTrigger id="location" className="pl-10">
                          <SelectValue placeholder="Select location" />
                        </SelectTrigger>
                        <SelectContent>
                          {locations.map((location) => (
                            <SelectItem key={location} value={location}>
                              {location}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                      <input type="hidden" name="location" value={formData.location} />
                    </div>
                  </div>
                  
                  <div className="mt-4">
                    <Label htmlFor="requirements">Special Requirements</Label>
                    <Textarea 
                      id="requirements" 
                      name="requirements"
                      placeholder="Any specific requirements or customs you'd like us to follow..." 
                      rows={4}
                      value={formData.requirements}
                      onChange={(e) => handleChange('requirements', e.target.value)}
                    />
                  </div>
                </div>
                
                <div className="pt-4">
                  <Button type="submit" className="w-full bg-primary hover:bg-primary/90 text-primary-foreground py-6">
                    Submit Booking Request
                  </Button>
                </div>
              </form>
            </div>
          </div>
          
          {/* Contact Info */}
          <div>
            <div className="bg-card border border-border rounded-xl p-6 sticky top-6">
              <h2 className="text-xl font-semibold mb-4">Need Help?</h2>
              <p className="text-muted-foreground mb-6">
                Our team is available to assist you with any questions about our services.
              </p>
              
              <div className="space-y-4">
                <div className="flex items-center">
                  <Phone className="h-5 w-5 text-primary mr-3" />
                  <div>
                    <div className="font-medium">Phone</div>
                    <a href="tel:+917289000377" className="text-muted-foreground hover:text-foreground">
                      +91-7289000377
                    </a>
                  </div>
                </div>
                
                <div className="flex items-center">
                  <Mail className="h-5 w-5 text-primary mr-3" />
                  <div>
                    <div className="font-medium">Email</div>
                    <a href="mailto:help@navdhya.com" className="text-muted-foreground hover:text-foreground">
                      help@navdhya.com
                    </a>
                  </div>
                </div>
                
                <div className="pt-4">
                  <h3 className="font-medium mb-2">Office Hours</h3>
                  <p className="text-sm text-muted-foreground">
                    Monday - Sunday: 9:00 AM - 8:00 PM
                  </p>
                </div>
              </div>
              
              <div className="mt-8 pt-6 border-t border-border">
                <h3 className="font-medium mb-3">Booking Policy</h3>
                <ul className="text-sm text-muted-foreground space-y-2">
                  <li className="flex items-start">
                    <span className="text-primary mr-2">•</span>
                    <span>Bookings must be made at least 24 hours in advance</span>
                  </li>
                  <li className="flex items-start">
                    <span className="text-primary mr-2">•</span>
                    <span>Confirmation will be sent within 2 hours</span>
                  </li>
                  <li className="flex items-start">
                    <span className="text-primary mr-2">•</span>
                    <span>Cancellations require 48 hours notice</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </main>
    </>
  )
}
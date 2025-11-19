import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Button } from "@/components/ui/button"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Phone, Mail, MessageCircle, Clock, MapPin, User, Building, Send, Upload, Shield, Award, Headphones } from "lucide-react"
import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import { LayoutOrchestrator } from '@/components/dlo-x/LayoutOrchestrator'
import { ErrorBoundary } from '@/components/dlo-x/ErrorBoundary'
import { resolveContext } from '@/lib/context-resolver'

// Add page-specific metadata
export const metadata = seoManager.generateMetadata({
  title: "Contact Us - Premium Spiritual Services | Navdhya",
  description: "Get in touch with Navdhya for authentic Puja and Spiritual Wellness services in Delhi NCR. Book trusted pandits for Navdhya Puja, Grah Shanti, Vedic rituals, and home ceremonies. 24/7 support available.",
  path: "/contact",
  keywords: [
    "Contact Navdhya",
    "Vedic Pandit Booking",
    "Puja Services Delhi",
    "Spiritual Consultation",
    "24/7 Support"
  ]
})

// Schema markup for the contact page
const CONTACT_SCHEMA = seoManager.generateSchema({
  type: "ContactPage",
  name: "Contact Us - Navdhya",
  description: "Get in touch with Navdhya for authentic Puja and Spiritual Wellness services in Delhi NCR. Book trusted pandits for Navdhya Puja, Grah Shanti, Vedic rituals, and home ceremonies.",
  path: "/contact",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Contact Us", url: "https://www.navdhya.com/contact" }
  ]
})

// Local business schema with address information
const LOCAL_BUSINESS_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "Navdhya",
  "image": "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
  "telephone": "+91-7289000377",
  "email": "help@navdhya.com",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "Pandav Nagar",
    "addressLocality": "New Delhi",
    "postalCode": "110092",
    "addressCountry": "IN"
  },
  "url": "https://www.navdhya.com",
  "sameAs": [
    "https://www.facebook.com/navdhya",
    "https://www.instagram.com/navdhya",
    "https://twitter.com/navdhya"
  ],
  "openingHoursSpecification": [
    {
      "@type": "OpeningHoursSpecification",
      "dayOfWeek": [
        "Monday",
        "Tuesday",
        "Wednesday",
        "Thursday",
        "Friday",
        "Saturday",
        "Sunday"
      ],
      "opens": "09:00",
      "closes": "20:00"
    }
  ]
}

export default async function ContactPage() {
  const contactSchema = JSON.stringify(CONTACT_SCHEMA)
  const localBusinessSchema = JSON.stringify(LOCAL_BUSINESS_SCHEMA)
  
  // Resolve context using the new resolver
  const context = await resolveContext('/contact');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: contactSchema }}
        />
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: localBusinessSchema }}
        />
        <main className="mx-auto max-w-7xl px-4 sm:px-6 py-12 overflow-x-hidden w-full">
          {/* Hero Section */}
          <div className="text-center mb-16">
            <div className="inline-flex items-center gap-2 bg-primary/10 px-4 py-2 rounded-full text-sm font-medium text-primary mb-6">
              <MessageCircle className="w-4 h-4" />
              <span>Get in Touch</span>
            </div>
            <h1 id="contact-us" className="font-serif text-4xl md:text-5xl font-bold tracking-tight mb-6">
              Get in Touch
            </h1>
            <p className="text-xl text-muted-foreground max-w-3xl mx-auto mb-10">
              We're here to support you 24/7
            </p>
            {/* Trust badges */}
            <div className="flex flex-wrap justify-center gap-4 mb-10">
              <div className="flex items-center gap-2 bg-card border border-border rounded-full px-4 py-2">
                <Shield className="w-4 h-4 text-primary" />
                <span className="text-sm font-medium">ISO Certified</span>
              </div>
              <div className="flex items-center gap-2 bg-card border border-border rounded-full px-4 py-2">
                <Shield className="w-4 h-4 text-primary" />
                <span className="text-sm font-medium">Secure</span>
              </div>
              <div className="flex items-center gap-2 bg-card border border-border rounded-full px-4 py-2">
                <Headphones className="w-4 h-4 text-primary" />
                <span className="text-sm font-medium">24/7 Support</span>
              </div>
            </div>
          </div>
          
          <div className="grid lg:grid-cols-3 gap-8">
            {/* Contact Information */}
            <div className="lg:col-span-1">
              <div className="bg-card border border-border rounded-2xl p-6">
                <h2 className="text-2xl font-serif font-semibold mb-6">Contact Information</h2>
                
                <div className="space-y-5 mb-8">
                  <div className="flex items-start space-x-4 p-4 rounded-lg border border-border">
                    <div className="flex-shrink-0 w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                      <MapPin className="w-5 h-5 text-primary" />
                    </div>
                    <div>
                      <h3 className="font-semibold text-base mb-1">Office Address</h3>
                      <p className="text-muted-foreground text-sm">Pandav Nagar</p>
                      <p className="text-xs text-muted-foreground mt-1">New Delhi, 110092, India</p>
                    </div>
                  </div>
                  
                  <div className="flex items-start space-x-4 p-4 rounded-lg border border-border">
                    <div className="flex-shrink-0 w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                      <Phone className="w-5 h-5 text-primary" />
                    </div>
                    <div>
                      <h3 className="font-semibold text-base mb-1">Phone Number</h3>
                      <a href="tel:+917289000377" className="text-muted-foreground hover:text-primary transition-colors duration-300 text-sm">
                        +91-7289000377
                      </a>
                      <p className="text-xs text-muted-foreground mt-1">24/7 Helpline</p>
                    </div>
                  </div>
                  
                  <div className="flex items-start space-x-4 p-4 rounded-lg border border-border">
                    <div className="flex-shrink-0 w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                      <MessageCircle className="w-5 h-5 text-primary" />
                    </div>
                    <div>
                      <h3 className="font-semibold text-base mb-1">WhatsApp</h3>
                      <a href="https://wa.me/917289000377" target="_blank" rel="noopener noreferrer" className="text-muted-foreground hover:text-primary transition-colors duration-300 text-sm">
                        +91-7289000377
                      </a>
                      <p className="text-xs text-muted-foreground mt-1">Instant Messaging</p>
                    </div>
                  </div>
                  
                  <div className="flex items-start space-x-4 p-4 rounded-lg border border-border">
                    <div className="flex-shrink-0 w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                      <Mail className="w-5 h-5 text-primary" />
                    </div>
                    <div>
                      <h3 className="font-semibold text-base mb-1">Email IDs</h3>
                      <a href="mailto:support@navdhya.com" className="text-muted-foreground hover:text-primary transition-colors duration-300 text-sm block">
                        support@navdhya.com
                      </a>
                      <a href="mailto:sales@navdhya.com" className="text-muted-foreground hover:text-primary transition-colors duration-300 text-sm block mt-1">
                        sales@navdhya.com
                      </a>
                      <a href="mailto:hr@navdhya.com" className="text-muted-foreground hover:text-primary transition-colors duration-300 text-sm block mt-1">
                        hr@navdhya.com
                      </a>
                    </div>
                  </div>
                  
                  <div className="flex items-start space-x-4 p-4 rounded-lg border border-border">
                    <div className="flex-shrink-0 w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                      <Clock className="w-5 h-5 text-primary" />
                    </div>
                    <div>
                      <h3 className="font-semibold text-base mb-1">Office Hours</h3>
                      <p className="text-muted-foreground text-sm">Monday - Sunday</p>
                      <p className="text-xs text-muted-foreground mt-1">9:00 AM - 8:00 PM IST</p>
                    </div>
                  </div>
                </div>
                
                {/* Map placeholder */}
                <div className="bg-muted rounded-xl h-48 flex items-center justify-center mb-6">
                  <div className="text-center">
                    <MapPin className="w-6 h-6 text-muted-foreground mx-auto mb-2" />
                    <p className="text-muted-foreground text-sm">Google Maps Integration</p>
                  </div>
                </div>
                
                <div className="bg-primary/5 rounded-xl p-4 border border-primary/10">
                  <h3 className="font-semibold text-base mb-2 flex items-center gap-2">
                    <MessageCircle className="w-4 h-4 text-primary" />
                    Emergency Support
                  </h3>
                  <p className="text-muted-foreground text-sm mb-3">
                    For urgent spiritual needs, our advisors are available 24/7.
                  </p>
                  <Button asChild size="sm" className="w-full bg-primary hover:bg-primary/90 text-primary-foreground">
                    <a href="tel:+917289000377">
                      Emergency Line: +91-7289000377
                    </a>
                  </Button>
                </div>
              </div>
            </div>
            
            {/* Primary Form */}
            <div className="lg:col-span-2">
              <div className="bg-card border border-border rounded-2xl p-6">
                <div className="mb-6">
                  <h2 className="text-2xl font-serif font-semibold mb-2">Send us a Message</h2>
                  <p className="text-muted-foreground text-sm">
                    Fill out the form below and our spiritual advisors will get back to you within 2 hours.
                  </p>
                </div>
                
                <form className="space-y-5">
                  <div className="grid md:grid-cols-2 gap-5">
                    <div>
                      <Label htmlFor="name" className="flex items-center gap-2 mb-2 text-sm">
                        <User className="w-4 h-4" />
                        Full Name *
                      </Label>
                      <Input 
                        id="name" 
                        placeholder="Enter your full name" 
                        required 
                        className="py-2.5 px-3 rounded-md border-border focus:ring-2 focus:ring-primary/50 text-sm"
                      />
                    </div>
                    <div>
                      <Label htmlFor="email" className="flex items-center gap-2 mb-2 text-sm">
                        <Mail className="w-4 h-4" />
                        Email Address *
                      </Label>
                      <Input 
                        id="email" 
                        type="email" 
                        placeholder="your.email@example.com" 
                        required 
                        className="py-2.5 px-3 rounded-md border-border focus:ring-2 focus:ring-primary/50 text-sm"
                      />
                    </div>
                  </div>
                  
                  <div className="grid md:grid-cols-2 gap-5">
                    <div>
                      <Label htmlFor="phone" className="flex items-center gap-2 mb-2 text-sm">
                        <Phone className="w-4 h-4" />
                        Mobile Number
                      </Label>
                      <div className="flex flex-col sm:flex-row gap-2">
                        <div className="flex-shrink-0 flex items-center justify-center px-2 rounded-md border border-border bg-muted sm:w-16 h-10">
                          <span className="text-muted-foreground text-sm">+91</span>
                        </div>
                        <Input 
                          id="phone" 
                          placeholder="9876543210" 
                          className="py-2 px-3 rounded-md border-border focus:ring-2 focus:ring-primary/50 flex-grow h-10 text-sm"
                        />
                      </div>
                      <div className="mt-2 flex items-center">
                        <input type="checkbox" id="otp-verification" className="mr-2 h-4 w-4" />
                        <Label htmlFor="otp-verification" className="text-xs text-muted-foreground">
                          Enable OTP verification
                        </Label>
                      </div>
                    </div>
                    <div>
                      <Label htmlFor="role" className="flex items-center gap-2 mb-2 text-sm">
                        <User className="w-4 h-4" />
                        Your Role *
                      </Label>
                      <Select>
                        <SelectTrigger id="role" className="py-2.5 px-3 rounded-md border-border focus:ring-2 focus:ring-primary/50 text-sm">
                          <SelectValue placeholder="Select your role" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="user">User</SelectItem>
                          <SelectItem value="vendor">Vendor</SelectItem>
                          <SelectItem value="pandit">Pandit</SelectItem>
                          <SelectItem value="partner">Partner</SelectItem>
                          <SelectItem value="enterprise">Enterprise</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                  </div>
                  
                  <div className="grid md:grid-cols-2 gap-5">
                    <div>
                      <Label htmlFor="subject" className="flex items-center gap-2 mb-2 text-sm">
                        <Building className="w-4 h-4" />
                        Subject
                      </Label>
                      <Select>
                        <SelectTrigger id="subject" className="py-2.5 px-3 rounded-md border-border focus:ring-2 focus:ring-primary/50 text-sm">
                          <SelectValue placeholder="Select subject" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="pricing">Pricing & Business</SelectItem>
                          <SelectItem value="order">Order Issue</SelectItem>
                          <SelectItem value="bug">Bug Report</SelectItem>
                          <SelectItem value="job">Job Application</SelectItem>
                          <SelectItem value="vendor">Vendor Inquiry</SelectItem>
                          <SelectItem value="puja">Puja Booking</SelectItem>
                          <SelectItem value="other">Other</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                    <div>
                      <Label htmlFor="file" className="flex items-center gap-2 mb-2 text-sm">
                        <Upload className="w-4 h-4" />
                        Attach File (Optional)
                      </Label>
                      <div className="relative">
                        <Input 
                          id="file" 
                          type="file" 
                          className="py-2.5 px-3 rounded-md border-border focus:ring-2 focus:ring-primary/50 file:mr-3 file:py-1.5 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 text-sm"
                        />
                      </div>
                      <p className="text-xs text-muted-foreground mt-1">Maximum file size: 10 MB</p>
                    </div>
                  </div>
                  
                  <div>
                    <Label htmlFor="message" className="flex items-center gap-2 mb-2 text-sm">
                      <MessageCircle className="w-4 h-4" />
                      Your Message *
                    </Label>
                    <Textarea 
                      id="message" 
                      placeholder="Please share your spiritual needs or questions..." 
                      rows={5}
                      required
                      className="py-2.5 px-3 rounded-md border-border focus:ring-2 focus:ring-primary/50 text-sm"
                    />
                  </div>
                  
                  {/* Captcha placeholder */}
                  <div className="bg-muted rounded-lg p-4 flex items-center justify-center">
                    <div className="text-center">
                      <Shield className="w-5 h-5 text-muted-foreground mx-auto mb-2" />
                      <p className="text-muted-foreground text-xs">Captcha Verification (Cloudflare Turnstile)</p>
                    </div>
                  </div>
                  
                  <div className="pt-2">
                    <Button 
                      type="submit" 
                      className="w-full bg-primary hover:bg-primary/90 text-primary-foreground py-2.5 rounded-md flex items-center justify-center gap-2 transition-all duration-300 text-sm"
                    >
                      <Send className="w-4 h-4" />
                      <span>Send Message</span>
                    </Button>
                  </div>
                </form>
              </div>
              
              {/* Instant Support Section */}
              <div className="mt-6 bg-card border border-border rounded-2xl p-6">
                <h2 className="text-xl font-serif font-semibold mb-5">Instant Support</h2>
                
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                  {/* Live Chat Widget */}
                  <div className="border border-border rounded-lg p-4 text-center">
                    <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-3">
                      <MessageCircle className="w-5 h-5 text-primary" />
                    </div>
                    <h3 className="font-semibold text-sm mb-2">Live Chat</h3>
                    <p className="text-xs text-muted-foreground mb-3">Chat with our support team in real-time</p>
                    <Button size="sm" className="w-full bg-primary hover:bg-primary/90 text-primary-foreground py-2">
                      Start Chat
                    </Button>
                  </div>
                  
                  {/* WhatsApp CTA */}
                  <div className="border border-border rounded-lg p-4 text-center">
                    <div className="w-10 h-10 rounded-full bg-green-500/10 flex items-center justify-center mx-auto mb-3">
                      <MessageCircle className="w-5 h-5 text-green-500" />
                    </div>
                    <h3 className="font-semibold text-sm mb-2">WhatsApp</h3>
                    <p className="text-xs text-muted-foreground mb-3">Connect with us on WhatsApp for instant support</p>
                    <Button asChild size="sm" className="w-full bg-green-500 hover:bg-green-500/90 text-white py-2">
                      <a href="https://wa.me/917289000377" target="_blank" rel="noopener noreferrer">
                        Chat on WhatsApp
                      </a>
                    </Button>
                  </div>
                  
                  {/* Schedule a Call */}
                  <div className="border border-border rounded-lg p-4 text-center">
                    <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-3">
                      <Clock className="w-5 h-5 text-primary" />
                    </div>
                    <h3 className="font-semibold text-sm mb-2">Schedule a Call</h3>
                    <p className="text-xs text-muted-foreground mb-3">Book a time for our expert to call you</p>
                    <Button size="sm" className="w-full bg-primary hover:bg-primary/90 text-primary-foreground py-2">
                      Schedule Now
                    </Button>
                  </div>
                </div>
                
                {/* Knowledge Base */}
                <div className="mt-6 pt-5 border-t border-border">
                  <h3 className="font-semibold mb-3 flex items-center gap-2 text-sm">
                    <Award className="w-4 h-4 text-primary" />
                    Knowledge Base
                  </h3>
                  <div className="grid grid-cols-2 gap-2">
                    <a href="#" className="text-xs text-muted-foreground hover:text-primary transition-colors duration-300 p-2 rounded hover:bg-muted">
                      Puja Booking Guide
                    </a>
                    <a href="#" className="text-xs text-muted-foreground hover:text-primary transition-colors duration-300 p-2 rounded hover:bg-muted">
                      Pandit Certification Process
                    </a>
                    <a href="#" className="text-xs text-muted-foreground hover:text-primary transition-colors duration-300 p-2 rounded hover:bg-muted">
                      Payment Methods
                    </a>
                    <a href="#" className="text-xs text-muted-foreground hover:text-primary transition-colors duration-300 p-2 rounded hover:bg-muted">
                      Cancellation Policy
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          {/* Stats Section */}
          <div className="mt-12 grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div className="bg-card border border-border rounded-xl p-5 text-center">
              <div className="text-2xl font-bold text-primary mb-1">24/7</div>
              <div className="text-xs text-muted-foreground">Support Available</div>
            </div>
            <div className="bg-card border border-border rounded-xl p-5 text-center">
              <div className="text-2xl font-bold text-primary mb-1">2 hrs</div>
              <div className="text-xs text-muted-foreground">Response Time</div>
            </div>
            <div className="bg-card border border-border rounded-xl p-5 text-center">
              <div className="text-2xl font-bold text-primary mb-1">150+</div>
              <div className="text-xs text-muted-foreground">Expert Pandits</div>
            </div>
            <div className="bg-card border border-border rounded-xl p-5 text-center">
              <div className="text-2xl font-bold text-primary mb-1">10K+</div>
              <div className="text-xs text-muted-foreground">Happy Clients</div>
            </div>
          </div>
        </main>
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}

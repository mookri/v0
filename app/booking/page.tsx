import { SiteHeader } from "@/components/site-header"
import { SiteFooter } from "@/components/site-footer"
import { Button } from "@/components/ui/button"
import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Calendar, Clock, Users, MapPin, Phone, Mail } from "lucide-react"
import { SEO_CONFIG } from "@/lib/seo-config"
import Script from "next/script"
import Link from "next/link"

// Add page-specific metadata
export const metadata = {
  title: "Book a Puja | Navdhya – Delhi's Trusted Spiritual Wellness",
  description: "Schedule your authentic Hindu puja service with certified Vedic pandits in Delhi. Easy online booking for home pujas, temple ceremonies, and spiritual consultations.",
  keywords: [
    "Book Puja Delhi",
    "Online Puja Booking",
    "Vedic Pandit Booking",
    "Spiritual Service Booking",
    "Hindu Ritual Booking"
  ],
  openGraph: {
    title: "Book a Puja | Navdhya – Delhi's Trusted Spiritual Wellness",
    description: "Schedule your authentic Hindu puja service with certified Vedic pandits in Delhi. Easy online booking for home pujas, temple ceremonies, and spiritual consultations.",
    url: `https://www.navdhya.com/booking`,
    siteName: SEO_CONFIG.siteName,
    images: [
      {
        url: SEO_CONFIG.defaultImage,
        width: 1200,
        height: 630,
        alt: "Book a Puja with Navdhya - Authentic Hindu Rituals in Delhi",
      },
    ],
    locale: SEO_CONFIG.locale,
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "Book a Puja | Navdhya – Delhi's Trusted Spiritual Wellness",
    description: "Schedule your authentic Hindu puja service with certified Vedic pandits in Delhi. Easy online booking for home pujas, temple ceremonies, and spiritual consultations.",
    images: [SEO_CONFIG.defaultImage],
    site: SEO_CONFIG.twitter.site,
  },
  alternates: {
    canonical: "https://www.navdhya.com/booking",
  },
}

// Schema markup for the booking page
const BOOKING_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "Book a Puja",
  "description": "Schedule your authentic Hindu puja service with certified Vedic pandits in Delhi",
  "url": "https://www.navdhya.com/booking"
}

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

export default function BookingPage() {
  const bookingSchema = JSON.stringify(BOOKING_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: bookingSchema }}
      />
      <SiteHeader />
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
              <form className="space-y-6">
                <div>
                  <h2 className="text-xl font-semibold mb-4">Personal Information</h2>
                  <div className="grid md:grid-cols-2 gap-4">
                    <div>
                      <Label htmlFor="name">Full Name *</Label>
                      <Input id="name" placeholder="Enter your full name" required />
                    </div>
                    <div>
                      <Label htmlFor="phone">Phone Number *</Label>
                      <div className="relative">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                          <span className="text-muted-foreground text-sm">+91</span>
                        </div>
                        <Input id="phone" placeholder="Enter 10-digit number" className="pl-12" required />
                      </div>
                    </div>
                  </div>
                  <div className="mt-4">
                    <Label htmlFor="email">Email Address</Label>
                    <Input id="email" type="email" placeholder="Enter your email" />
                  </div>
                </div>
                
                <div>
                  <h2 className="text-xl font-semibold mb-4">Service Details</h2>
                  <div className="grid md:grid-cols-2 gap-4">
                    <div>
                      <Label htmlFor="service-type">Service Type *</Label>
                      <Select>
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
                    </div>
                    <div>
                      <Label htmlFor="date">Preferred Date *</Label>
                      <div className="relative">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                          <Calendar className="h-4 w-4 text-muted-foreground" />
                        </div>
                        <Input 
                          id="date" 
                          type="date" 
                          className="pl-10" 
                          required 
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
                          type="time" 
                          className="pl-10" 
                          required 
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
                          type="number" 
                          min="1" 
                          defaultValue="1"
                          className="pl-10" 
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
                      <Select>
                        <SelectTrigger id="location" className="pl-10">
                          <SelectValue placeholder="Select your location" />
                        </SelectTrigger>
                        <SelectContent>
                          {locations.map((location) => (
                            <SelectItem key={location} value={location}>
                              {location}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>
                  </div>
                  
                  <div className="mt-4">
                    <Label htmlFor="address">Full Address *</Label>
                    <Textarea 
                      id="address" 
                      placeholder="Enter your complete address including city and pin code" 
                      rows={3}
                      required 
                    />
                  </div>
                  
                  <div className="mt-4">
                    <Label htmlFor="requirements">Special Requirements</Label>
                    <Textarea 
                      id="requirements" 
                      placeholder="Any specific requirements, traditions, or preferences for the puja" 
                      rows={3}
                    />
                  </div>
                </div>
                
                <div className="pt-4">
                  <Button type="submit" className="w-full">
                    Submit Booking Request
                  </Button>
                </div>
              </form>
            </div>
          </div>
          
          {/* Booking Information */}
          <div>
            <div className="bg-card border border-border rounded-xl p-6 sticky top-6">
              <h2 className="text-xl font-semibold mb-4">Booking Information</h2>
              
              <div className="space-y-4 mb-6">
                <div className="flex items-start">
                  <Phone className="h-5 w-5 text-primary mt-0.5 mr-3" />
                  <div>
                    <div className="text-sm text-muted-foreground">Call Us</div>
                    <div className="font-medium">
                      <a href="tel:+917289000377" className="hover:text-primary">+91-7289000377</a>
                    </div>
                  </div>
                </div>
                
                <div className="flex items-start">
                  <Mail className="h-5 w-5 text-primary mt-0.5 mr-3" />
                  <div>
                    <div className="text-sm text-muted-foreground">Email Us</div>
                    <div className="font-medium">
                      <a href="mailto:help@navdhya.com" className="hover:text-primary">help@navdhya.com</a>
                    </div>
                  </div>
                </div>
                
                <div className="pt-2">
                  <div className="text-sm text-muted-foreground">Response Time</div>
                  <div className="font-medium">Within 2 hours during business hours</div>
                </div>
              </div>
              
              <div className="bg-muted rounded-lg p-4">
                <h3 className="font-medium mb-2">What Happens Next?</h3>
                <ol className="text-sm space-y-2">
                  <li className="flex items-start">
                    <span className="text-primary mr-2">1.</span>
                    <span>We'll confirm your booking within 2 hours</span>
                  </li>
                  <li className="flex items-start">
                    <span className="text-primary mr-2">2.</span>
                    <span>A pandit will contact you to discuss details</span>
                  </li>
                  <li className="flex items-start">
                    <span className="text-primary mr-2">3.</span>
                    <span>Final confirmation and preparation</span>
                  </li>
                  <li className="flex items-start">
                    <span className="text-primary mr-2">4.</span>
                    <span>Puja conducted as scheduled</span>
                  </li>
                </ol>
              </div>
              
              <div className="mt-6 pt-4 border-t border-border">
                <h3 className="font-medium mb-3">Need Help?</h3>
                <Button asChild variant="outline" className="w-full">
                  <Link href="/contact">Contact Support</Link>
                </Button>
              </div>
            </div>
          </div>
        </div>
        
        {/* Service Types Info */}
        <div className="mt-12">
          <h2 className="text-2xl font-bold mb-6 text-center">Our Service Types</h2>
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {serviceTypes.map((service) => (
              <div key={service.id} className="bg-card border border-border rounded-lg p-5">
                <h3 className="font-semibold mb-2">{service.name}</h3>
                <p className="text-sm text-muted-foreground">{service.description}</p>
              </div>
            ))}
          </div>
        </div>
      </main>
      <SiteFooter />
    </>
  )
}
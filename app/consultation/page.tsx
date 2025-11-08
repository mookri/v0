import { SiteHeader } from "@/components/site-header"
import { SiteFooter } from "@/components/site-footer"
import { Button } from "@/components/ui/button"
import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Calendar, Clock, BookOpen, Star, Heart, Users } from "lucide-react"
import { SEO_CONFIG } from "@/lib/seo-config"
import Script from "next/script"
import Link from "next/link"

// Add page-specific metadata
export const metadata = {
  title: "Astrology Consultation | Navdhya – Delhi's Trusted Spiritual Wellness",
  description: "Get expert Vedic astrology guidance from certified pandits in Delhi. Kundli analysis, marriage compatibility, career guidance, and remedial solutions.",
  keywords: [
    "Vedic Astrology Delhi",
    "Kundli Analysis",
    "Marriage Compatibility",
    "Career Astrology",
    "Remedial Solutions",
    "Astrology Consultation"
  ],
  openGraph: {
    title: "Astrology Consultation | Navdhya – Delhi's Trusted Spiritual Wellness",
    description: "Get expert Vedic astrology guidance from certified pandits in Delhi. Kundli analysis, marriage compatibility, career guidance, and remedial solutions.",
    url: `https://www.navdhya.com/consultation`,
    siteName: SEO_CONFIG.siteName,
    images: [
      {
        url: SEO_CONFIG.defaultImage,
        width: 1200,
        height: 630,
        alt: "Astrology Consultation with Navdhya - Vedic Astrology in Delhi",
      },
    ],
    locale: SEO_CONFIG.locale,
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "Astrology Consultation | Navdhya – Delhi's Trusted Spiritual Wellness",
    description: "Get expert Vedic astrology guidance from certified pandits in Delhi. Kundli analysis, marriage compatibility, career guidance, and remedial solutions.",
    images: [SEO_CONFIG.defaultImage],
    site: SEO_CONFIG.twitter.site,
  },
  alternates: {
    canonical: "https://www.navdhya.com/consultation",
  },
}

// Schema markup for the consultation page
const CONSULTATION_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Astrology Consultation",
  "provider": {
    "@type": "Organization",
    "name": "Navdhya"
  },
  "description": "Expert Vedic astrology guidance from certified pandits in Delhi",
  "url": "https://www.navdhya.com/consultation"
}

const consultationTypes = [
  { 
    id: "kundli", 
    name: "Kundli Analysis", 
    description: "Detailed analysis of your birth chart for life insights",
    duration: "60-90 minutes",
    price: "₹1,500"
  },
  { 
    id: "compatibility", 
    name: "Marriage Compatibility", 
    description: "Analysis of partner compatibility for harmonious relationships",
    duration: "90-120 minutes",
    price: "₹2,500"
  },
  { 
    id: "career", 
    name: "Career Guidance", 
    description: "Astrological insights for career decisions and growth",
    duration: "60-90 minutes",
    price: "₹1,800"
  },
  { 
    id: "remedial", 
    name: "Remedial Solutions", 
    description: "Personalized remedies to overcome challenges",
    duration: "45-60 minutes",
    price: "₹1,200"
  },
  { 
    id: "muhurat", 
    name: "Muhurat Selection", 
    description: "Selection of auspicious timings for important events",
    duration: "30-45 minutes",
    price: "₹1,000"
  }
]

const expertiseAreas = [
  "Career & Business",
  "Marriage & Relationships",
  "Health & Well-being",
  "Education & Learning",
  "Finance & Wealth",
  "Spiritual Growth",
  "Family Matters",
  "Legal Issues"
]

export default function ConsultationPage() {
  const consultationSchema = JSON.stringify(CONSULTATION_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: consultationSchema }}
      />
      <SiteHeader />
      <main className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-12 overflow-x-hidden w-full">
        {/* Header */}
        <div className="text-center mb-12">
          <div className="inline-flex items-center gap-2 bg-primary/10 px-3 py-1.5 rounded-full text-sm font-medium text-primary mb-4">
            <Star className="w-4 h-4" />
            <span>Expert Guidance</span>
          </div>
          <h1 className="text-3xl md:text-4xl font-bold tracking-tight mb-4">
            Vedic <span className="text-primary">Astrology Consultation</span>
          </h1>
          <p className="text-lg text-muted-foreground max-w-2xl mx-auto">
            Gain profound insights into your life's path with expert guidance from our certified Vedic astrologers. 
            Discover solutions to challenges and opportunities for growth.
          </p>
        </div>
        
        <div className="grid md:grid-cols-3 gap-8">
          {/* Consultation Form */}
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
                  <h2 className="text-xl font-semibold mb-4">Birth Details</h2>
                  <div className="grid md:grid-cols-2 gap-4">
                    <div>
                      <Label htmlFor="date-of-birth">Date of Birth *</Label>
                      <div className="relative">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                          <Calendar className="h-4 w-4 text-muted-foreground" />
                        </div>
                        <Input 
                          id="date-of-birth" 
                          type="date" 
                          className="pl-10" 
                          required 
                        />
                      </div>
                    </div>
                    <div>
                      <Label htmlFor="time-of-birth">Time of Birth</Label>
                      <div className="relative">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                          <Clock className="h-4 w-4 text-muted-foreground" />
                        </div>
                        <Input 
                          id="time-of-birth" 
                          type="time" 
                          className="pl-10" 
                        />
                      </div>
                    </div>
                  </div>
                  
                  <div className="grid md:grid-cols-2 gap-4 mt-4">
                    <div>
                      <Label htmlFor="place-of-birth">Place of Birth *</Label>
                      <Input id="place-of-birth" placeholder="Enter city and state" required />
                    </div>
                    <div>
                      <Label htmlFor="gender">Gender</Label>
                      <Select>
                        <SelectTrigger id="gender">
                          <SelectValue placeholder="Select gender" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="male">Male</SelectItem>
                          <SelectItem value="female">Female</SelectItem>
                          <SelectItem value="other">Other</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                  </div>
                </div>
                
                <div>
                  <h2 className="text-xl font-semibold mb-4">Consultation Details</h2>
                  <div className="grid md:grid-cols-2 gap-4">
                    <div>
                      <Label htmlFor="consultation-type">Consultation Type *</Label>
                      <Select>
                        <SelectTrigger id="consultation-type">
                          <SelectValue placeholder="Select consultation type" />
                        </SelectTrigger>
                        <SelectContent>
                          {consultationTypes.map((type) => (
                            <SelectItem key={type.id} value={type.id}>
                              {type.name}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>
                    <div>
                      <Label htmlFor="preferred-date">Preferred Date *</Label>
                      <div className="relative">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                          <Calendar className="h-4 w-4 text-muted-foreground" />
                        </div>
                        <Input 
                          id="preferred-date" 
                          type="date" 
                          className="pl-10" 
                          required 
                        />
                      </div>
                    </div>
                  </div>
                  
                  <div className="mt-4">
                    <Label htmlFor="preferred-time">Preferred Time *</Label>
                    <div className="relative">
                      <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <Clock className="h-4 w-4 text-muted-foreground" />
                      </div>
                      <Input 
                        id="preferred-time" 
                        type="time" 
                        className="pl-10" 
                        required 
                      />
                    </div>
                  </div>
                  
                  <div className="mt-4">
                    <Label htmlFor="areas-of-concern">Areas of Concern</Label>
                    <Select>
                      <SelectTrigger id="areas-of-concern">
                        <SelectValue placeholder="Select areas of concern" />
                      </SelectTrigger>
                      <SelectContent>
                        {expertiseAreas.map((area) => (
                          <SelectItem key={area} value={area}>
                            {area}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                  
                  <div className="mt-4">
                    <Label htmlFor="questions">Specific Questions</Label>
                    <Textarea 
                      id="questions" 
                      placeholder="Please share any specific questions or concerns you'd like addressed during the consultation" 
                      rows={4}
                    />
                  </div>
                </div>
                
                <div className="pt-4">
                  <Button type="submit" className="w-full">
                    Request Consultation
                  </Button>
                </div>
              </form>
            </div>
          </div>
          
          {/* Consultation Information */}
          <div>
            <div className="bg-card border border-border rounded-xl p-6 sticky top-6">
              <h2 className="text-xl font-semibold mb-4">Consultation Information</h2>
              
              <div className="space-y-6">
                <div>
                  <h3 className="font-medium mb-2">Consultation Types</h3>
                  <div className="space-y-3">
                    {consultationTypes.map((type) => (
                      <div key={type.id} className="bg-muted rounded-lg p-3">
                        <div className="flex justify-between items-start">
                          <div>
                            <div className="font-medium">{type.name}</div>
                            <div className="text-sm text-muted-foreground">{type.duration}</div>
                          </div>
                          <div className="font-bold">{type.price}</div>
                        </div>
                        <div className="text-sm mt-1">{type.description}</div>
                      </div>
                    ))}
                  </div>
                </div>
                
                <div>
                  <h3 className="font-medium mb-2">What to Expect</h3>
                  <ul className="text-sm space-y-2">
                    <li className="flex items-start">
                      <span className="text-primary mr-2">•</span>
                      <span>Detailed analysis by certified Vedic astrologer</span>
                    </li>
                    <li className="flex items-start">
                      <span className="text-primary mr-2">•</span>
                      <span>Personalized insights and guidance</span>
                    </li>
                    <li className="flex items-start">
                      <span className="text-primary mr-2">•</span>
                      <span>Remedial solutions if needed</span>
                    </li>
                    <li className="flex items-start">
                      <span className="text-primary mr-2">•</span>
                      <span>Follow-up support available</span>
                    </li>
                  </ul>
                </div>
                
                <div className="pt-4 border-t border-border">
                  <h3 className="font-medium mb-3">Need Immediate Help?</h3>
                  <Button asChild variant="outline" className="w-full">
                    <Link href="/contact">Contact Us</Link>
                  </Button>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        {/* Expertise Areas */}
        <div className="mt-12">
          <h2 className="text-2xl font-bold mb-6 text-center">Areas of Expertise</h2>
          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
            {expertiseAreas.map((area, index) => {
              const icons = [BookOpen, Heart, Star, Users, Clock, Calendar, BookOpen, Heart]
              const Icon = icons[index]
              return (
                <div key={area} className="bg-card border border-border rounded-lg p-5 text-center">
                  <div className="mx-auto bg-primary/10 p-3 rounded-full w-12 h-12 flex items-center justify-center mb-3">
                    <Icon className="w-6 h-6 text-primary" />
                  </div>
                  <h3 className="font-medium">{area}</h3>
                </div>
              )
            })}
          </div>
        </div>
      </main>
      <SiteFooter />
    </>
  )
}
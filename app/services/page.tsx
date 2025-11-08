import { SiteHeader } from "@/components/site-header"
import { SiteFooter } from "@/components/site-footer"
import { Button } from "@/components/ui/button"
import { Separator } from "@/components/ui/separator"
import { 
  Calendar, 
  Users, 
  Home, 
  Heart, 
  Star, 
  Leaf, 
  BookOpen, 
  Shield, 
  Clock, 
  CheckCircle 
} from "lucide-react"
import { SEO_CONFIG, BREADCRUMB_SCHEMA } from "@/lib/seo-config"
import Script from "next/script"
import Link from "next/link"

// Add page-specific metadata
export const metadata = {
  title: "Puja Services | Navdhya – Delhi's Trusted Spiritual Wellness",
  description: "Explore our comprehensive range of authentic Hindu puja services in Delhi. From home pujas to temple ceremonies, we connect you with certified Vedic pandits for every spiritual occasion.",
  keywords: [
    "Puja Services Delhi",
    "Vedic Rituals",
    "Hindu Puja",
    "Spiritual Wellness",
    "Certified Pandits",
    "Home Puja Services",
    "Temple Puja Booking",
    "Astrology Consultation"
  ],
  openGraph: {
    title: "Puja Services | Navdhya – Delhi's Trusted Spiritual Wellness",
    description: "Explore our comprehensive range of authentic Hindu puja services in Delhi. From home pujas to temple ceremonies, we connect you with certified Vedic pandits for every spiritual occasion.",
    url: `https://www.navdhya.com/services`,
    siteName: SEO_CONFIG.siteName,
    images: [
      {
        url: SEO_CONFIG.defaultImage,
        width: 1200,
        height: 630,
        alt: "Navdhya Puja Services - Authentic Hindu Rituals in Delhi",
      },
    ],
    locale: SEO_CONFIG.locale,
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "Puja Services | Navdhya – Delhi's Trusted Spiritual Wellness",
    description: "Explore our comprehensive range of authentic Hindu puja services in Delhi. From home pujas to temple ceremonies, we connect you with certified Vedic pandits for every spiritual occasion.",
    images: [SEO_CONFIG.defaultImage],
    site: SEO_CONFIG.twitter.site,
  },
  alternates: {
    canonical: "https://www.navdhya.com/services",
  },
}

// Schema markup for the services page
const SERVICES_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Puja Services",
  "provider": {
    "@type": "Organization",
    "name": "Navdhya"
  },
  "description": "Authentic Hindu puja services performed by certified Vedic pandits in Delhi",
  "breadcrumb": BREADCRUMB_SCHEMA([
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Services", url: `https://www.navdhya.com/services` }
  ])
}

// Service data
const services = [
  {
    id: 1,
    title: "Home Puja Services",
    description: "Authentic pujas performed in the comfort of your home by certified Vedic pandits",
    icon: Home,
    price: "From ₹2,500",
    features: [
      "Grih Pravesh (House Warming)",
      "Annaprashan (Baby's First Rice)",
      "Naming Ceremonies",
      "Birthday Celebrations"
    ],
    popular: true
  },
  {
    id: 2,
    title: "Temple Puja Services",
    description: "Special pujas conducted at renowned temples across Delhi NCR",
    icon: Star,
    price: "From ₹1,800",
    features: [
      "Special Occasion Pujas",
      "Festival Celebrations",
      "Personalized Rituals",
      "Temple Priest Guidance"
    ],
    popular: false
  },
  {
    id: 3,
    title: "Life Event Pujas",
    description: "Sacred ceremonies for significant life transitions and milestones",
    icon: Heart,
    price: "From ₹3,500",
    features: [
      "Wedding Rituals",
      "Engagement Ceremonies",
      "Vastu Pujas",
      "Grah Shanti"
    ],
    popular: true
  },
  {
    id: 4,
    title: "Festival Pujas",
    description: "Traditional celebrations for major Hindu festivals throughout the year",
    icon: Leaf,
    price: "From ₹2,000",
    features: [
      "Diwali Lakshmi Puja",
      "Navratri Durga Puja",
      "Ganesh Chaturthi",
      "Durga Puja"
    ],
    popular: false
  },
  {
    id: 5,
    title: "Astrology Consultation",
    description: "Expert guidance based on Vedic astrology for life decisions",
    icon: BookOpen,
    price: "From ₹1,500",
    features: [
      "Kundli Analysis",
      "Marriage Compatibility",
      "Career Guidance",
      "Remedial Solutions"
    ],
    popular: false
  },
  {
    id: 6,
    title: "Spiritual Products",
    description: "Authentic puja samagri and spiritual items for personal practice",
    icon: Shield,
    price: "Starting at ₹500",
    features: [
      "Puja Samagri Kits",
      "Rudraksha & Gemstones",
      "Yantras & Idols",
      "Sacred Books"
    ],
    popular: false
  }
]

const benefits = [
  {
    title: "Certified Pandits",
    description: "All our pandits are verified experts with authentic Vedic knowledge",
    icon: CheckCircle
  },
  {
    title: "Transparent Pricing",
    description: "Clear, upfront pricing with no hidden costs",
    icon: Clock
  },
  {
    title: "Customized Rituals",
    description: "Personalized ceremonies based on your specific traditions",
    icon: Users
  },
  {
    title: "Seamless Booking",
    description: "Easy online booking with real-time availability",
    icon: Calendar
  }
]

export default function ServicesPage() {
  const servicesSchema = JSON.stringify(SERVICES_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: servicesSchema }}
      />
      <SiteHeader />
      <main className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 overflow-x-hidden w-full">
        {/* Hero Section */}
        <section className="text-center mb-16">
          <div className="inline-block bg-primary/10 text-primary px-3 py-1 rounded-full text-sm font-medium mb-4">
            Our Services
          </div>
          <h1 className="text-4xl md:text-5xl font-bold tracking-tight mb-6">
            Authentic <span className="text-primary">Spiritual Services</span> for Every Occasion
          </h1>
          <p className="text-xl text-muted-foreground max-w-3xl mx-auto mb-10">
            Connecting Delhi families with certified Vedic pandits for meaningful, authentic rituals that bring peace and prosperity to your home.
          </p>
          <div className="flex flex-wrap justify-center gap-4">
            <Button asChild size="lg">
              <Link href="/booking">Book a Puja</Link>
            </Button>
            <Button asChild variant="outline" size="lg">
              <Link href="/consultation">Consult an Expert</Link>
            </Button>
          </div>
        </section>

        {/* Benefits Section */}
        <section className="mb-20">
          <h2 className="text-3xl font-bold text-center mb-12">Why Choose Navdhya</h2>
          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            {benefits.map((benefit, index) => (
              <div key={index} className="bg-card border border-border rounded-lg p-6 text-center">
                <div className="mx-auto bg-primary/10 p-3 rounded-full w-12 h-12 flex items-center justify-center mb-4">
                  <benefit.icon className="w-6 h-6 text-primary" />
                </div>
                <h3 className="text-lg font-semibold mb-2">{benefit.title}</h3>
                <p className="text-muted-foreground text-sm">{benefit.description}</p>
              </div>
            ))}
          </div>
        </section>

        <Separator className="my-12" />

        {/* Services Section */}
        <section className="mb-20">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold mb-4">Our Puja Services</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Comprehensive spiritual services designed to meet the diverse needs of modern families while preserving traditional authenticity.
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            {services.map((service) => {
              const Icon = service.icon
              return (
                <div key={service.id} className="bg-card border border-border rounded-lg p-6 flex flex-col h-full">
                  <div className="flex justify-between items-start">
                    <div className="bg-primary/10 p-3 rounded-full w-12 h-12 flex items-center justify-center">
                      <Icon className="w-6 h-6 text-primary" />
                    </div>
                    {service.popular && (
                      <div className="bg-secondary text-secondary-foreground px-2 py-1 rounded-full text-xs font-medium">
                        Popular
                      </div>
                    )}
                  </div>
                  <h3 className="text-xl font-semibold mt-4 mb-2">{service.title}</h3>
                  <p className="text-muted-foreground mb-4">{service.description}</p>
                  <div className="text-2xl font-bold mb-4">{service.price}</div>
                  <ul className="space-y-2 mb-6 flex-grow">
                    {service.features.map((feature, index) => (
                      <li key={index} className="flex items-center">
                        <CheckCircle className="w-4 h-4 text-primary mr-2" />
                        <span className="text-sm">{feature}</span>
                      </li>
                    ))}
                  </ul>
                  <Button asChild>
                    <Link href={`/services/${service.id}`}>Learn More</Link>
                  </Button>
                </div>
              )
            })}
          </div>
        </section>

        {/* CTA Section */}
        <section className="bg-muted rounded-2xl p-8 md:p-12 text-center mb-20">
          <h2 className="text-3xl font-bold mb-4">Ready to Book Your Puja?</h2>
          <p className="text-muted-foreground max-w-2xl mx-auto mb-8">
            Connect with our certified pandits and experience authentic spiritual wellness in the comfort of your home.
          </p>
          <div className="flex flex-wrap justify-center gap-4">
            <Button asChild size="lg">
              <Link href="/booking">Schedule a Puja</Link>
            </Button>
            <Button asChild variant="outline" size="lg">
              <Link href="/contact">Contact Us</Link>
            </Button>
          </div>
        </section>

        {/* FAQ Section */}
        <section className="mb-20">
          <h2 className="text-3xl font-bold text-center mb-12">Frequently Asked Questions</h2>
          <div className="max-w-3xl mx-auto space-y-6">
            <div className="bg-card border border-border rounded-lg p-6">
              <h3 className="text-lg font-semibold mb-2">How do I book a puja?</h3>
              <p className="text-muted-foreground">
                Booking is simple! You can either use our online booking system or call us directly. 
                Just select your preferred service, choose a date and time, and our team will connect 
                you with a certified pandit who will guide you through the process.
              </p>
            </div>
            <div className="bg-card border border-border rounded-lg p-6">
              <h3 className="text-lg font-semibold mb-2">What is included in the puja service?</h3>
              <p className="text-muted-foreground">
                Our puja services include a certified pandit, all necessary puja samagri (ritual items), 
                and guidance throughout the ceremony. For home pujas, we also provide setup and cleanup 
                services to ensure a hassle-free experience.
              </p>
            </div>
            <div className="bg-card border border-border rounded-lg p-6">
              <h3 className="text-lg font-semibold mb-2">Can I customize the rituals?</h3>
              <p className="text-muted-foreground">
                Absolutely! We understand that every family has unique traditions. Our pandits work 
                with you to customize rituals according to your specific customs, beliefs, and requirements 
                while maintaining the authenticity of Vedic practices.
              </p>
            </div>
          </div>
        </section>
      </main>
      <SiteFooter />
    </>
  )
}
import { SiteHeader } from "@/components/site-header"
import { SiteFooter } from "@/components/site-footer"
import { Button } from "@/components/ui/button"
import { Calendar, Clock, Users, Leaf, Heart, Star, Home, BookOpen, Shield } from "lucide-react"
import { SEO_CONFIG } from "@/lib/seo-config"
import Link from "next/link"

// Service data
const services = [
  {
    id: "1",
    title: "Home Puja Services",
    description: "Authentic pujas performed in the comfort of your home by certified Vedic pandits",
    icon: Home,
    price: "From ₹2,500",
    duration: "2-3 hours",
    participants: "1-10 people",
    features: [
      "Grih Pravesh (House Warming)",
      "Annaprashan (Baby's First Rice)",
      "Naming Ceremonies",
      "Birthday Celebrations",
      "Special Occasion Pujas"
    ],
    details: "Our home puja service brings the sacred traditions of Vedic rituals directly to your living space. Our certified pandits will arrive with all necessary puja samagri and conduct the ceremony according to authentic traditions. We handle all setup and cleanup, allowing you to focus entirely on the spiritual experience.",
    process: [
      "Initial consultation to understand your requirements",
      "Selection of auspicious date and time",
      "Pandit arrives with all puja materials",
      "Setup of sacred space in your home",
      "Performance of rituals with mantras and offerings",
      "Blessings and prasad distribution",
      "Cleanup of puja materials"
    ]
  },
  {
    id: "2",
    title: "Temple Puja Services",
    description: "Special pujas conducted at renowned temples across Delhi NCR",
    icon: Star,
    price: "From ₹1,800",
    duration: "1-2 hours",
    participants: "Any group size",
    features: [
      "Special Occasion Pujas",
      "Festival Celebrations",
      "Personalized Rituals",
      "Temple Priest Guidance"
    ],
    details: "Experience the divine energy of temple worship with our specialized puja services. We arrange for pujas to be conducted at prominent temples in Delhi NCR, with dedicated priests ensuring your rituals are performed with utmost devotion and authenticity.",
    process: [
      "Selection of appropriate temple",
      "Booking of puja slot",
      "Coordination with temple authorities",
      "Guidance through temple rituals",
      "Special arrangements for large groups",
      "Receipt of prasad and blessings"
    ]
  },
  {
    id: "3",
    title: "Life Event Pujas",
    description: "Sacred ceremonies for significant life transitions and milestones",
    icon: Heart,
    price: "From ₹3,500",
    duration: "3-4 hours",
    participants: "Family and invited guests",
    features: [
      "Wedding Rituals",
      "Engagement Ceremonies",
      "Vastu Pujas",
      "Grah Shanti",
      "Mundan (Head Shaving)",
      "Upanayana (Sacred Thread)"
    ],
    details: "Mark life's most important moments with sacred rituals that bring blessings and positive energy. Our pandits guide you through traditional ceremonies that honor cultural customs while adapting to modern family needs.",
    process: [
      "Detailed consultation on ceremony requirements",
      "Selection of muhurat (auspicious time)",
      "Preparation of specific samagri for the ceremony",
      "Guidance for family participation",
      "Performance of all ritualistic elements",
      "Distribution of prasad and blessings"
    ]
  },
  {
    id: "4",
    title: "Festival Pujas",
    description: "Traditional celebrations for major Hindu festivals throughout the year",
    icon: Leaf,
    price: "From ₹2,000",
    duration: "1-3 hours",
    participants: "Family and community",
    features: [
      "Diwali Lakshmi Puja",
      "Navratri Durga Puja",
      "Ganesh Chaturthi",
      "Durga Puja",
      "Holi Celebrations",
      "Raksha Bandhan"
    ],
    details: "Celebrate the joy and spirituality of Hindu festivals with properly conducted pujas that invoke divine blessings. Our festival pujas follow traditional methods while incorporating family customs and regional variations.",
    process: [
      "Festival-specific puja preparation",
      "Arrangement of festival decorations",
      "Performance of traditional rituals",
      "Special mantras for the festival",
      "Community participation guidance",
      "Festival prasad distribution"
    ]
  },
  {
    id: "5",
    title: "Astrology Consultation",
    description: "Expert guidance based on Vedic astrology for life decisions",
    icon: BookOpen,
    price: "From ₹1,500",
    duration: "45-90 minutes",
    participants: "Individual or couple",
    features: [
      "Kundli Analysis",
      "Marriage Compatibility",
      "Career Guidance",
      "Remedial Solutions",
      "Gemstone Recommendations",
      "Muhurat Selection"
    ],
    details: "Gain clarity on life's path through expert Vedic astrological analysis. Our certified astrologers provide personalized insights based on your birth chart, helping you make informed decisions about career, relationships, and spiritual growth.",
    process: [
      "Collection of birth details",
      "Preparation of detailed kundli",
      "Analysis of planetary positions",
      "Identification of favorable periods",
      "Recommendation of remedial measures",
      "Guidance on future planning"
    ]
  },
  {
    id: "6",
    title: "Spiritual Products",
    description: "Authentic puja samagri and spiritual items for personal practice",
    icon: Shield,
    price: "Starting at ₹500",
    duration: "Ongoing practice",
    participants: "Individual practitioners",
    features: [
      "Puja Samagri Kits",
      "Rudraksha & Gemstones",
      "Yantras & Idols",
      "Sacred Books",
      "Incense and Oils",
      "Specialized Items"
    ],
    details: "Access authentic spiritual products sourced directly from traditional artisans and temples. Our collection includes everything needed for daily worship as well as specialized items for specific rituals and practices.",
    process: [
      "Consultation on spiritual needs",
      "Selection of appropriate items",
      "Blessing of purchased items",
      "Guidance on proper usage",
      "Ongoing support for practice",
      "Replacement and renewal advice"
    ]
  }
]

export async function generateMetadata({ params }: { params: { id: string } }) {
  const service = services.find(s => s.id === params.id)
  
  if (!service) {
    return {
      title: "Service Not Found | Navdhya",
      description: "The requested service could not be found."
    }
  }
  
  return {
    title: `${service.title} | Navdhya – Delhi's Trusted Spiritual Wellness`,
    description: service.description,
    openGraph: {
      title: `${service.title} | Navdhya – Delhi's Trusted Spiritual Wellness`,
      description: service.description,
      url: `https://www.navdhya.com/services/${params.id}`,
      siteName: SEO_CONFIG.siteName,
      images: [
        {
          url: SEO_CONFIG.defaultImage,
          width: 1200,
          height: 630,
          alt: `${service.title} - Navdhya Spiritual Services`,
        },
      ],
      locale: SEO_CONFIG.locale,
      type: "website",
    },
    twitter: {
      card: "summary_large_image",
      title: `${service.title} | Navdhya – Delhi's Trusted Spiritual Wellness`,
      description: service.description,
      images: [SEO_CONFIG.defaultImage],
      site: SEO_CONFIG.twitter.site,
    },
    alternates: {
      canonical: `https://www.navdhya.com/services/${params.id}`,
    },
  }
}

export function generateStaticParams() {
  return services.map((service) => ({
    id: service.id,
  }))
}

export default function ServiceDetailPage({ params }: { params: { id: string } }) {
  const service = services.find(s => s.id === params.id)
  
  if (!service) {
    return (
      <div className="min-h-screen flex flex-col">
        <SiteHeader />
        <main className="flex-grow flex items-center justify-center">
          <div className="text-center">
            <h1 className="text-3xl font-bold mb-4">Service Not Found</h1>
            <p className="text-muted-foreground mb-8">The service you're looking for doesn't exist or has been moved.</p>
            <Button asChild>
              <Link href="/services">View All Services</Link>
            </Button>
          </div>
        </main>
        <SiteFooter />
      </div>
    )
  }
  
  const Icon = service.icon
  
  return (
    <div className="min-h-screen flex flex-col">
      <SiteHeader />
      <main className="flex-grow mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 w-full">
        {/* Breadcrumb */}
        <nav className="flex mb-8 text-sm" aria-label="Breadcrumb">
          <ol className="inline-flex items-center space-x-1 md:space-x-2">
            <li className="inline-flex items-center">
              <Link href="/" className="text-muted-foreground hover:text-foreground">
                Home
              </Link>
            </li>
            <li>
              <div className="flex items-center">
                <span className="mx-2 text-muted-foreground">/</span>
                <Link href="/services" className="text-muted-foreground hover:text-foreground">
                  Services
                </Link>
              </div>
            </li>
            <li aria-current="page">
              <div className="flex items-center">
                <span className="mx-2 text-muted-foreground">/</span>
                <span className="text-foreground">{service.title}</span>
              </div>
            </li>
          </ol>
        </nav>
        
        {/* Service Header */}
        <div className="bg-card border border-border rounded-xl p-6 md:p-8 mb-12">
          <div className="flex flex-col md:flex-row md:items-start md:justify-between gap-6">
            <div>
              <div className="flex items-center gap-4 mb-4">
                <div className="bg-primary/10 p-3 rounded-full">
                  <Icon className="w-8 h-8 text-primary" />
                </div>
                <h1 className="text-3xl font-bold">{service.title}</h1>
              </div>
              <p className="text-muted-foreground text-lg max-w-3xl">{service.description}</p>
            </div>
            <div className="bg-primary/5 rounded-lg p-4 min-w-[200px]">
              <div className="text-3xl font-bold text-primary mb-2">{service.price}</div>
              <Button asChild className="w-full">
                <Link href="/booking">Book Now</Link>
              </Button>
            </div>
          </div>
        </div>
        
        {/* Service Details */}
        <div className="grid lg:grid-cols-3 gap-8 mb-12">
          <div className="lg:col-span-2">
            <div className="bg-card border border-border rounded-xl p-6 md:p-8">
              <h2 className="text-2xl font-bold mb-6">About This Service</h2>
              <p className="text-muted-foreground mb-8">{service.details}</p>
              
              <h3 className="text-xl font-semibold mb-4">What's Included</h3>
              <ul className="grid md:grid-cols-2 gap-3 mb-8">
                {service.features.map((feature, index) => (
                  <li key={index} className="flex items-start">
                    <span className="text-primary mr-2 mt-1">•</span>
                    <span className="text-foreground">{feature}</span>
                  </li>
                ))}
              </ul>
              
              <h3 className="text-xl font-semibold mb-4">Process Overview</h3>
              <ol className="space-y-4">
                {service.process.map((step, index) => (
                  <li key={index} className="flex items-start">
                    <span className="flex-shrink-0 flex items-center justify-center h-6 w-6 rounded-full bg-primary text-primary-foreground text-sm font-medium mr-3 mt-0.5">
                      {index + 1}
                    </span>
                    <span className="text-foreground">{step}</span>
                  </li>
                ))}
              </ol>
            </div>
          </div>
          
          <div>
            <div className="bg-card border border-border rounded-xl p-6 sticky top-6">
              <h3 className="text-xl font-semibold mb-6">Service Details</h3>
              
              <div className="space-y-4 mb-8">
                <div className="flex items-center">
                  <Clock className="w-5 h-5 text-primary mr-3" />
                  <div>
                    <div className="text-sm text-muted-foreground">Duration</div>
                    <div className="font-medium">{service.duration}</div>
                  </div>
                </div>
                
                <div className="flex items-center">
                  <Users className="w-5 h-5 text-primary mr-3" />
                  <div>
                    <div className="text-sm text-muted-foreground">Participants</div>
                    <div className="font-medium">{service.participants}</div>
                  </div>
                </div>
              </div>
              
              <Button asChild className="w-full mb-4">
                <Link href="/booking">Book This Service</Link>
              </Button>
              
              <Button asChild variant="outline" className="w-full">
                <Link href="/contact">Contact for Details</Link>
              </Button>
            </div>
          </div>
        </div>
        
        {/* Related Services */}
        <div className="mb-12">
          <h2 className="text-2xl font-bold mb-6">Related Services</h2>
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {services
              .filter(s => s.id !== service.id)
              .slice(0, 3)
              .map((relatedService) => {
                const RelatedIcon = relatedService.icon
                return (
                  <div key={relatedService.id} className="bg-card border border-border rounded-lg p-6">
                    <div className="flex items-center gap-3 mb-4">
                      <div className="bg-primary/10 p-2 rounded-full">
                        <RelatedIcon className="w-5 h-5 text-primary" />
                      </div>
                      <h3 className="font-semibold">{relatedService.title}</h3>
                    </div>
                    <p className="text-muted-foreground text-sm mb-4">{relatedService.description}</p>
                    <div className="flex justify-between items-center">
                      <span className="font-bold">{relatedService.price}</span>
                      <Button asChild variant="outline" size="sm">
                        <Link href={`/services/${relatedService.id}`}>Learn More</Link>
                      </Button>
                    </div>
                  </div>
                )
              })}
          </div>
        </div>
      </main>
      <SiteFooter />
    </div>
  )
}
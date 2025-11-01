import { SiteHeader } from "@/components/site-header"
import { SiteFooter } from "@/components/site-footer"
import { SEO_CONFIG, BREADCRUMB_SCHEMA } from "@/lib/seo-config"
import Script from "next/script"
import Link from "next/link"
import { Grid, Heart, Star, Calendar, Users, BookOpen, Zap, ShieldCheck } from "lucide-react"

// Add page-specific metadata
export const metadata = {
  title: "Puja Services & Rituals | Navdhya – Delhi's Trusted Spiritual Wellness",
  description: "Explore our comprehensive catalog of authentic Hindu puja services and spiritual rituals. From home pujas to life event ceremonies, performed by certified Vedic pandits in Delhi NCR.",
  keywords: "Puja Services in Delhi, Hindu Rituals, Spiritual Ceremonies, Vedic Pandits, Home Pujas, Life Event Ceremonies, Delhi Spiritual Services",
  openGraph: {
    title: "Puja Services & Rituals | Navdhya – Delhi's Trusted Spiritual Wellness",
    description: "Explore our comprehensive catalog of authentic Hindu puja services and spiritual rituals. From home pujas to life event ceremonies, performed by certified Vedic pandits in Delhi NCR.",
    url: `https://www.navdhya.com/pujas`,
    siteName: SEO_CONFIG.siteName,
    images: [
      {
        url: SEO_CONFIG.defaultImage,
        width: 1200,
        height: 630,
        alt: "Navdhya Puja Services Catalog",
      },
    ],
    locale: SEO_CONFIG.locale,
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "Puja Services & Rituals | Navdhya – Delhi's Trusted Spiritual Wellness",
    description: "Explore our comprehensive catalog of authentic Hindu puja services and spiritual rituals. From home pujas to life event ceremonies, performed by certified Vedic pandits in Delhi NCR.",
    images: [SEO_CONFIG.defaultImage],
    site: SEO_CONFIG.twitter.site,
  },
  alternates: {
    canonical: "https://www.navdhya.com/pujas",
  },
}

// Schema markup for the puja catalog page
const PUJA_CATALOG_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "CollectionPage",
  name: "Puja Services Catalog - Navdhya",
  url: `https://www.navdhya.com/pujas`,
  description: "Comprehensive catalog of authentic Hindu puja services and spiritual rituals performed by certified Vedic pandits in Delhi NCR.",
  breadcrumb: BREADCRUMB_SCHEMA([
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Puja Services", url: `https://www.navdhya.com/pujas` }
  ])
}

// Puja data for the catalog
const PUJA_CATEGORIES = [
  {
    id: "home-family",
    name: "Home & Family Rituals",
    icon: <Heart className="w-6 h-6" />,
    description: "Sacred ceremonies to bless your home and family with harmony, prosperity, and divine protection.",
    pujas: [
      {
        slug: "griha-pravesh",
        title: "Griha Pravesh Puja",
        description: "An auspicious ceremony performed before entering a new home to invoke blessings, prosperity, and harmony.",
        price: "₹2,500 – ₹5,500",
        image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg"
      },
      {
        slug: "satyanarayan-katha",
        title: "Satyanarayan Katha",
        description: "A devotional ritual dedicated to Lord Vishnu for prosperity, peace, and fulfillment of wishes.",
        price: "₹2,000 – ₹4,500",
        image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg"
      },
      {
        slug: "lakshmi-ganesh-puja",
        title: "Lakshmi Ganesh Puja",
        description: "Performed to seek blessings from Lord Ganesh and Goddess Lakshmi for removal of obstacles and prosperity.",
        price: "₹1,800 – ₹3,800",
        image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg"
      }
    ]
  },
  {
    id: "life-events",
    name: "Life Events & Milestones",
    icon: <Users className="w-6 h-6" />,
    description: "Sacred ceremonies to mark important life transitions and celebrate divine blessings.",
    pujas: [
      {
        slug: "hindu-wedding",
        title: "Hindu Wedding Ceremony",
        description: "Complete traditional wedding rituals with Vedic mantras, mandap, and proper vidhi performed by experienced Pandits.",
        price: "₹24,999 – ₹75,000",
        image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg"
      },
      {
        slug: "baby-naming",
        title: "Baby Naming (Namkaran)",
        description: "A sacred ceremony to give your child a divine name and seek blessings for their future.",
        price: "₹2,500 – ₹4,500",
        image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg"
      },
      {
        slug: "annaprashan",
        title: "Annaprashan Ceremony",
        description: "The first feeding of solid food to your child, marking an important milestone in their growth.",
        price: "₹2,000 – ₹4,000",
        image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg"
      }
    ]
  },
  {
    id: "business-prosperity",
    name: "Business & Prosperity",
    icon: <Zap className="w-6 h-6" />,
    description: "Rituals to invoke divine blessings for business success, wealth, and professional growth.",
    pujas: [
      {
        slug: "office-opening",
        title: "Office/Shop Opening Puja",
        description: "Bless your new business space with divine energy for success, prosperity, and protection.",
        price: "₹3,500 – ₹7,500",
        image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg"
      },
      {
        slug: "vastu-shanti",
        title: "Vastu Shanti Puja",
        description: "Harmonize your living or working space with Vastu principles for positive energy flow.",
        price: "₹3,000 – ₹6,000",
        image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg"
      },
      {
        slug: "dhan-prapti",
        title: "Dhan Prapti Puja",
        description: "Invoke the blessings of Lakshmi Mata for wealth, prosperity, and financial stability.",
        price: "₹2,500 – ₹5,000",
        image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg"
      }
    ]
  },
  {
    id: "online-hybrid",
    name: "Online & Hybrid Services",
    icon: <Grid className="w-6 h-6" />,
    description: "Modern spiritual solutions that bring traditional rituals to your digital space.",
    pujas: [
      {
        slug: "live-video-puja",
        title: "Live Video Puja",
        description: "Participate in authentic pujas from anywhere through our secure video platform with certified pandits.",
        price: "₹1,500 – ₹3,500",
        image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg"
      },
      {
        slug: "virtual-pandit",
        title: "Virtual Pandit Assistance",
        description: "Get personalized guidance from experienced pandits through video calls for your spiritual needs.",
        price: "₹1,000/hr – ₹2,500/session",
        image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg"
      },
      {
        slug: "digital-puja-kit",
        title: "Digital Puja Kit Delivery",
        description: "Authentically sourced puja samagri delivered to your doorstep with detailed instructions.",
        price: "₹500 – ₹2,500",
        image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg"
      }
    ]
  }
]

export default function PujaCatalogPage() {
  const pujaSchema = JSON.stringify(PUJA_CATALOG_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: pujaSchema }}
      />
      <SiteHeader />
      <main className="mx-auto max-w-7xl py-12 px-4 sm:px-8 lg:space-y-20 overflow-x-hidden w-full">
        {/* Hero Section */}
        <section aria-labelledby="hero-heading" className="relative overflow-hidden rounded-t-3xl" style={{ minHeight: "550px" }}>
          {/* Background Gradient */}
          <div className="absolute inset-0 bg-gradient-to-b from-muted/80 to-background z-0"></div>
          
          <div className="relative z-10 max-w-7xl mx-auto px-6 sm:px-8 py-16 lg:py-20">
            <div className="flex flex-col h-full">
              {/* Badge */}
              <div className="self-start mb-6">
                <div className="border border-border/50 rounded-2xl py-1.5 px-5 bg-card/50 backdrop-blur-sm">
                  <h1 className="text-sm font-medium tracking-tight text-foreground/80 uppercase">
                    Puja Services
                  </h1>
                </div>
              </div>
              
              {/* Heading */}
              <div className="max-w-3xl mb-10">
                <h2 id="hero-heading" className="text-4xl md:text-5xl lg:text-6xl font-normal tracking-tight text-foreground leading-tight">
                  Authentic <span className="text-primary">Spiritual Rituals</span> for Every Occasion
                </h2>
              </div>
              
              {/* Subheading */}
              <div className="max-w-2xl mb-10">
                <p className="text-lg text-muted-foreground leading-relaxed">
                  From home pujas to life milestones, our certified Vedic pandits bring divine blessings to your most sacred moments.
                </p>
              </div>
              
              {/* Image */}
              <div className="mt-auto">
                <div className="bg-gradient-to-br from-muted/50 to-muted/20 rounded-3xl overflow-hidden border border-border/30 h-80 md:h-96">
                  <img 
                    src="https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg" 
                    alt="Navdhya Puja Services in Delhi" 
                    className="w-full h-full object-cover"
                  />
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* Categories Overview */}
        <section aria-labelledby="categories-overview" className="py-10 lg:py-14">
          <div className="text-center mb-16">
            <div className="inline-block px-4 py-2 bg-card border border-border rounded-full text-sm text-muted-foreground mb-6">
              OUR PUJA CATEGORIES
            </div>
            
            <h2 id="categories-overview" className="font-serif text-3xl lg:text-4xl font-bold tracking-tight text-foreground mb-6">
              Comprehensive Spiritual Services
            </h2>
            
            <p className="text-muted-foreground max-w-3xl mx-auto text-lg leading-relaxed">
              Explore our carefully curated puja categories, each designed to address specific spiritual needs 
              and life occasions with authentic Vedic traditions.
            </p>
          </div>

          {/* Category Cards */}
          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            {PUJA_CATEGORIES.map((category) => (
              <div key={category.id} className="bg-card rounded-3xl px-4 py-4 border border-border hover:shadow-md transition-all duration-300 flex flex-col justify-between" style={{ minHeight: "380px" }}>
                <div className="w-14 h-14 bg-[#0E3F2E] rounded-full flex items-center justify-center mb-24">
                  {category.icon}
                </div>
                
                <div className="bg-muted rounded-2xl p-6 flex-grow">
                  <h3 className="text-xl font-medium text-foreground mb-3">
                    {category.name}
                  </h3>
                  
                  <p className="text-muted-foreground text-sm leading-relaxed">
                    {category.description}
                  </p>
                </div>
              </div>
            ))}
          </div>
        </section>

        {/* Puja Catalog */}
        <section aria-labelledby="puja-catalog" className="py-10 lg:py-14">
          <h2 id="puja-catalog" className="font-serif text-3xl lg:text-4xl font-bold tracking-tight text-foreground text-center mb-5">
            Our Puja Catalog
          </h2>
          <p className="text-muted-foreground text-center max-w-3xl mx-auto mb-14 text-lg leading-relaxed">
            Discover our extensive collection of authentic Hindu rituals and ceremonies, each performed 
            with meticulous attention to Vedic traditions and personalized care.
          </p>
          
          {/* Category Tabs */}
          <div className="space-y-20">
            {PUJA_CATEGORIES.map((category) => (
              <div key={category.id} className="border-b border-border/50 pb-16">
                <div className="flex items-center mb-10">
                  <div className="w-12 h-12 bg-[#0E3F2E] rounded-full flex items-center justify-center mr-4">
                    {category.icon}
                  </div>
                  <div>
                    <h3 className="font-serif text-2xl font-bold tracking-tight text-foreground">
                      {category.name}
                    </h3>
                    <p className="text-muted-foreground">
                      {category.description}
                    </p>
                  </div>
                </div>
                
                {/* Puja Cards */}
                <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                  {category.pujas.map((puja) => (
                    <div key={puja.slug} className="border border-border/50 rounded-xl p-6 bg-card hover:shadow-md transition-all duration-300 backdrop-blur-sm flex flex-col h-full">
                      <div className="bg-muted rounded-lg overflow-hidden mb-5 h-48">
                        <img 
                          src={puja.image} 
                          alt={puja.title} 
                          className="w-full h-full object-cover"
                        />
                      </div>
                      
                      <h4 className="font-serif text-xl font-bold tracking-tight text-foreground mb-3">
                        {puja.title}
                      </h4>
                      
                      <p className="text-muted-foreground text-sm mb-4 flex-grow leading-relaxed">
                        {puja.description}
                      </p>
                      
                      <div className="mt-auto">
                        <p className="text-primary font-medium mb-5">
                          {puja.price}
                        </p>
                        
                        <Link 
                          href={`/pujas/${puja.slug}`}
                          className="w-full bg-primary hover:bg-primary/90 text-primary-foreground font-medium py-2.5 px-4 rounded-lg transition-all duration-300 shadow-sm hover:shadow-md inline-block text-center focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 text-sm"
                        >
                          View Details
                        </Link>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            ))}
          </div>
        </section>

        {/* Why Choose Navdhya for Pujas */}
        <section aria-labelledby="why-pujas" className="py-10 lg:py-14 bg-muted/30 rounded-2xl p-8 lg:p-12">
          <h2 id="why-pujas" className="font-serif text-3xl lg:text-4xl font-bold tracking-tight text-foreground text-center mb-5">
            Why Choose Navdhya for Your Pujas
          </h2>
          <p className="text-muted-foreground text-center max-w-3xl mx-auto mb-14 text-lg leading-relaxed">
            Our commitment to authenticity, professionalism, and personalized service sets us apart in delivering 
            meaningful spiritual experiences.
          </p>
          
          <div className="grid lg:grid-cols-2 gap-12">
            <div>
              <h3 className="font-serif text-xl font-bold tracking-tight text-foreground mb-6">Authentic Rituals</h3>
              <ul className="space-y-6">
                <li className="flex">
                  <div className="flex-shrink-0 h-7 w-7 rounded-full bg-gradient-to-br from-primary/10 to-secondary/10 flex items-center justify-center mr-4 mt-0.5">
                    <span className="text-primary text-sm">✓</span>
                  </div>
                  <div>
                    <p className="font-medium text-foreground">Vedic Authenticity</p>
                    <p className="text-muted-foreground text-sm mt-1">
                      All rituals follow authentic Vedic traditions with proper mantras and procedures.
                    </p>
                  </div>
                </li>
                <li className="flex">
                  <div className="flex-shrink-0 h-7 w-7 rounded-full bg-gradient-to-br from-primary/10 to-secondary/10 flex items-center justify-center mr-4 mt-0.5">
                    <span className="text-primary text-sm">✓</span>
                  </div>
                  <div>
                    <p className="font-medium text-foreground">Certified Pandits</p>
                    <p className="text-muted-foreground text-sm mt-1">
                      Our pandits are verified experts with deep knowledge of rituals and traditions.
                    </p>
                  </div>
                </li>
                <li className="flex">
                  <div className="flex-shrink-0 h-7 w-7 rounded-full bg-gradient-to-br from-primary/10 to-secondary/10 flex items-center justify-center mr-4 mt-0.5">
                    <span className="text-primary text-sm">✓</span>
                  </div>
                  <div>
                    <p className="font-medium text-foreground">Personalized Approach</p>
                    <p className="text-muted-foreground text-sm mt-1">
                      Customized ceremonies based on your specific traditions and requirements.
                    </p>
                  </div>
                </li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl font-bold tracking-tight text-foreground mb-6">Professional Service</h3>
              <ul className="space-y-6">
                <li className="flex">
                  <div className="flex-shrink-0 h-7 w-7 rounded-full bg-gradient-to-br from-primary/10 to-secondary/10 flex items-center justify-center mr-4 mt-0.5">
                    <span className="text-primary text-sm">✓</span>
                  </div>
                  <div>
                    <p className="font-medium text-foreground">End-to-End Management</p>
                    <p className="text-muted-foreground text-sm mt-1">
                      We handle everything from booking to completion, so you can focus on the spiritual experience.
                    </p>
                  </div>
                </li>
                <li className="flex">
                  <div className="flex-shrink-0 h-7 w-7 rounded-full bg-gradient-to-br from-primary/10 to-secondary/10 flex items-center justify-center mr-4 mt-0.5">
                    <span className="text-primary text-sm">✓</span>
                  </div>
                  <div>
                    <p className="font-medium text-foreground">Transparent Pricing</p>
                    <p className="text-muted-foreground text-sm mt-1">
                      Clear, upfront pricing with no hidden costs or last-minute additions.
                    </p>
                  </div>
                </li>
                <li className="flex">
                  <div className="flex-shrink-0 h-7 w-7 rounded-full bg-gradient-to-br from-primary/10 to-secondary/10 flex items-center justify-center mr-4 mt-0.5">
                    <span className="text-primary text-sm">✓</span>
                  </div>
                  <div>
                    <p className="font-medium text-foreground">Quality Assurance</p>
                    <p className="text-muted-foreground text-sm mt-1">
                      Standardized procedures ensure consistent, high-quality service delivery.
                    </p>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </section>

        {/* Booking CTA */}
        <section aria-labelledby="booking-cta" className="py-16 lg:py-20 text-center border-t border-b border-border/50 my-10">
          <h2 id="booking-cta" className="font-serif text-3xl lg:text-4xl font-bold tracking-tight text-foreground mb-8">
            Ready to Book Your Puja?
          </h2>
          <p className="text-muted-foreground max-w-4xl mx-auto text-lg mb-10 leading-relaxed">
            Connect with our certified pandits to schedule your puja ceremony. We'll ensure every ritual 
            is performed with the utmost reverence and authenticity.
          </p>
          <a 
            href="/#booking" 
            className="bg-primary hover:bg-primary/90 text-primary-foreground font-medium py-3 px-8 rounded-lg transition-all duration-300 shadow-sm hover:shadow-md inline-block focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
          >
            Schedule Your Puja
          </a>
        </section>
      </main>
      <div className="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10 pb-12">
        <p className="text-center text-muted-foreground italic text-lg">
          May divine blessings grace every sacred moment. – Team Navdhya
        </p>
      </div>
      <SiteFooter />
    </>
  )
}
import Image from "next/image"
import Link from "next/link"
import { notFound } from "next/navigation"
import { Button } from "@/components/ui/button"
import { SiteHeader } from "@/components/site-header"
import { SiteFooter } from "@/components/site-footer"
import { SEO_CONFIG, BREADCRUMB_SCHEMA } from "@/lib/seo-config"
import Script from "next/script"
import { Heart, Star, Calendar, Users, BookOpen, ShieldCheck, Zap, Leaf, Target } from "lucide-react"

// Add page-specific metadata
export async function generateMetadata({ params }: { params: { slug: string } }) {
  const { slug } = await params;
  const puja = DATA[slug]
  
  if (!puja) {
    return {
      title: "Puja Not Found | Navdhya",
      description: "The puja you're looking for could not be found."
    }
  }
  
  return {
    title: `${puja.title} | Navdhya – Delhi's Trusted Spiritual Wellness`,
    description: puja.description,
    keywords: [puja.title, "Puja Services in Delhi", "Hindu Rituals", "Vedic Pandits", "Spiritual Ceremonies"],
    openGraph: {
      title: `${puja.title} | Navdhya – Delhi's Trusted Spiritual Wellness`,
      description: puja.description,
      url: `https://www.navdhya.com/pujas/${slug}`,
      siteName: SEO_CONFIG.siteName,
      images: [
        {
          url: puja.image || SEO_CONFIG.defaultImage,
          width: 1200,
          height: 630,
          alt: `${puja.title} - Navdhya Puja Services`,
        },
      ],
      locale: SEO_CONFIG.locale,
      type: "website",
    },
    twitter: {
      card: "summary_large_image",
      title: `${puja.title} | Navdhya – Delhi's Trusted Spiritual Wellness`,
      description: puja.description,
      images: [puja.image || SEO_CONFIG.defaultImage],
      site: SEO_CONFIG.twitter.site,
    },
    alternates: {
      canonical: `https://www.navdhya.com/pujas/${slug}`,
    },
  }
}

const DATA: Record<
  string,
  { 
    title: string; 
    description: string; 
    price: string; 
    imgQuery: string; 
    highlights: string[];
    longDescription: string;
    benefits: string[];
    process: { step: string; description: string }[];
    image: string;
  }
> = {
  "griha-pravesh": {
    title: "Griha Pravesh Puja",
    description: "An auspicious ceremony performed before entering a new home to invoke blessings, prosperity, and harmony. Conducted by certified Pandits with proper Vidhi and Muhurat guidance.",
    price: "₹2,500 – ₹5,500",
    imgQuery: "griha%20pravesh%20puja%20mandap%20home%20entrance",
    highlights: ["Muhurat selection guidance", "All samagri support", "On-time service", "Post-puja clean-up"],
    longDescription: "Griha Pravesh is one of the most significant rituals in Hindu tradition, performed when entering a new home for the first time. This sacred ceremony invokes divine blessings for peace, prosperity, and harmony in the new dwelling. Our certified pandits conduct the complete vidhi with authentic mantras, ensuring every aspect follows Vedic traditions. The ceremony includes Vastu Purush worship, Ganesha Puja, Navagraha Shanti, and specific rituals to sanctify each room of your home.",
    benefits: [
      "Invokes divine blessings for peace and harmony in the new home",
      "Removes negative energies and creates a positive environment",
      "Ensures prosperity and well-being for all family members",
      "Establishes a sacred connection between the home and divine forces",
      "Follows authentic Vedic traditions with proper mantras and procedures"
    ],
    process: [
      { step: "Preparation", description: "Selection of auspicious Muhurat, preparation of puja samagri, and cleaning of the new home" },
      { step: "Kalasha Sthapana", description: "Installation of the sacred kalasha representing abundance and divine presence" },
      { step: "Vastu Purush Puja", description: "Worship of Vastu Purush to harmonize the energy of the space" },
      { step: "Ganesha Puja", description: "Invocation of Lord Ganesha to remove obstacles and ensure smooth proceedings" },
      { step: "Navagraha Shanti", description: "Pacification rituals for the nine celestial bodies for cosmic harmony" },
      { step: "Room Sanctification", description: "Specific rituals to bless each room with appropriate deities" },
      { step: "Aarti & Prasad", description: "Concluding rituals with devotional songs and distribution of blessed food" }
    ],
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg"
  },
  "satyanarayan-katha": {
    title: "Satyanarayan Puja",
    description: "A devotional ritual dedicated to Lord Vishnu for prosperity, peace, and fulfillment of wishes. Includes Katha, Aarti, and Prasad distribution.",
    price: "₹2,000 – ₹4,500",
    imgQuery: "satyanarayan%20katha%20devotional%20ritual",
    highlights: ["Professional Pandit", "Bhog & Aarti", "Simple setup", "Transparent pricing"],
    longDescription: "Satyanarayan Katha is a sacred ritual dedicated to Lord Vishnu, performed to seek his blessings for prosperity, peace, and the fulfillment of sincere wishes. The ceremony includes the recitation of the Satyanarayan Katha, which narrates the divine stories of Lord Vishnu's incarnations. This puja is particularly significant for new ventures, overcoming obstacles, and expressing gratitude for blessings received. Our pandits conduct the complete vidhi with devotion and authenticity.",
    benefits: [
      "Brings peace, prosperity, and harmony to the family",
      "Fulfills sincere wishes and removes obstacles in life",
      "Strengthens faith and spiritual connection with the divine",
      "Promotes harmony and unity among family members",
      "Bestows divine protection and blessings for new beginnings"
    ],
    process: [
      { step: "Ganesha Puja", description: "Invocation of Lord Ganesha to remove obstacles and ensure smooth proceedings" },
      { step: "Kalasha Sthapana", description: "Installation of the sacred kalasha representing abundance and divine presence" },
      { step: "Vishnu Puja", description: "Worship of Lord Vishnu with specific mantras and offerings" },
      { step: "Katha Recitation", description: "Narration of the divine stories of Lord Vishnu's incarnations" },
      { step: "Aarti & Bhog", description: "Devotional songs and offering of blessed food to the deity" },
      { step: "Prasad Distribution", description: "Distribution of blessed food among all participants" },
      { step: "Conclusion", description: "Final prayers and seeking continued divine blessings" }
    ],
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg"
  },
  "lakshmi-ganesh-puja": {
    title: "Lakshmi Ganesh Puja",
    description: "Performed to seek blessings from Lord Ganesh and Goddess Lakshmi for removal of obstacles and prosperity.",
    price: "₹1,800 – ₹3,800",
    imgQuery: "ganesh%20puja%20traditional%20setup",
    highlights: ["Clean, quick setup", "Proper mantra chanting", "Auspicious timing", "Guided Aarti"],
    longDescription: "Lakshmi Ganesh Puja is a powerful combination ritual performed to invoke the blessings of Lord Ganesha, the remover of obstacles, and Goddess Lakshmi, the bestower of wealth and prosperity. This puja is ideal for new beginnings, business ventures, financial decisions, and seeking divine blessings for overall well-being. The ceremony includes specific mantras and offerings to both deities, ensuring comprehensive spiritual benefits.",
    benefits: [
      "Removes obstacles and challenges in personal and professional life",
      "Attracts wealth, prosperity, and financial stability",
      "Brings success in new ventures and business endeavors",
      "Enhances wisdom, knowledge, and decision-making abilities",
      "Creates a positive environment filled with divine energy and blessings"
    ],
    process: [
      { step: "Preparation", description: "Arrangement of idols or images of Lord Ganesha and Goddess Lakshmi" },
      { step: "Ganesha Puja", description: "Invocation and worship of Lord Ganesha with specific mantras" },
      { step: "Lakshmi Puja", description: "Worship of Goddess Lakshmi with lotus flowers and coins" },
      { step: "Mantra Chanting", description: "Recitation of powerful mantras dedicated to both deities" },
      { step: "Offerings", description: "Presentation of modaks, sweets, and other traditional offerings" },
      { step: "Aarti", description: "Devotional songs with lamps to honor the deities" },
      { step: "Prasad Distribution", description: "Distribution of blessed food among all participants" }
    ],
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg"
  },
  "hindu-wedding": {
    title: "Hindu Wedding Ceremony",
    description: "Complete traditional wedding rituals with Vedic mantras, mandap, and proper vidhi performed by experienced Pandits.",
    price: "₹24,999 – ₹75,000",
    imgQuery: "hindu%20wedding%20mandap%20ritual%20sacred%20fire",
    highlights: ["Muhurat selection", "Full ceremony flow", "Mantra guidance", "Professional setup"],
    longDescription: "Our Hindu Wedding Ceremony package includes all traditional rituals performed by experienced Vedic pandits. From pre-wedding ceremonies like Muhurat selection to the complete wedding vidhi, we ensure every aspect follows authentic traditions. The ceremony includes Kanyadaan, Panigrahana, Saptpadi, and other sacred rituals that unite the couple in the divine presence of Agni Devta. Our comprehensive service includes mandap setup, puja samagri, and expert guidance throughout the process.",
    benefits: [
      "Sacred union blessed by Vedic traditions and divine witnesses",
      "Complete ritualistic ceremony ensuring spiritual and legal validity",
      "Expert guidance from certified pandits throughout the process",
      "Comprehensive service including mandap setup and all requirements",
      "Blessings for a harmonious, prosperous, and fulfilling married life"
    ],
    process: [
      { step: "Pre-Wedding", description: "Muhurat selection, pre-wedding rituals, and ceremony planning" },
      { step: "Baraat Reception", description: "Welcome rituals for the groom's procession at the venue" },
      { step: "Ganesh Puja", description: "Invocation of Lord Ganesha to remove obstacles" },
      { step: "Kanyadaan", description: "Sacred giving away of the bride by her parents" },
      { step: "Panigrahana", description: "Groom holding the bride's hand as a symbol of acceptance" },
      { step: "Saptpadi", description: "Seven sacred vows taken around the holy fire" },
      { step: "Aashirwad", description: "Blessings from elders and distribution of prasad" }
    ],
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg"
  },
  "baby-naming": {
    title: "Baby Naming (Namkaran)",
    description: "A sacred ceremony to give your child a divine name and seek blessings for their future.",
    price: "₹2,500 – ₹4,500",
    imgQuery: "baby%20naming%20ceremony%20hindu%20ritual",
    highlights: ["Astrological name selection", "Sacred ceremony", "Blessings for child", "Family participation"],
    longDescription: "The Baby Naming ceremony, or Namkaran, is a significant Hindu ritual performed to give your child their divine name and seek blessings for their future. This ceremony is typically conducted 11-21 days after birth, as per astrological calculations. Our pandits guide you through the process of selecting an auspicious name based on the child's nakshatra and perform the sacred ceremony with Vedic mantras. The ritual includes invoking divine blessings for the child's health, education, and overall well-being.",
    benefits: [
      "Gives the child their divine name as per Vedic traditions",
      "Invokes divine blessings for health, education, and prosperity",
      "Establishes the child's spiritual connection with the divine",
      "Brings family together to celebrate the new arrival",
      "Ensures positive energy and protection for the child's future"
    ],
    process: [
      { step: "Preparation", description: "Selection of auspicious date and time, preparation of samagri" },
      { step: "Ganesha Puja", description: "Invocation of Lord Ganesha to remove obstacles" },
      { step: "Name Selection", description: "Selection of divine name based on nakshatra and family traditions" },
      { step: "Nama Karan Vidhi", description: "Sacred ceremony of giving the child their name" },
      { step: "Mantra Chanting", description: "Recitation of protective mantras for the child's well-being" },
      { step: "Aarti & Prasad", description: "Devotional songs and distribution of blessed food" },
      { step: "Blessings", description: "Receiving blessings from elders and family members" }
    ],
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg"
  },
  "annaprashan": {
    title: "Annaprashan Ceremony",
    description: "The first feeding of solid food to your child, marking an important milestone in their growth.",
    price: "₹2,000 – ₹4,000",
    imgQuery: "annaprashan%20ceremony%20baby%20first%20food",
    highlights: ["Sacred first feeding", "Health blessings", "Family celebration", "Traditional rituals"],
    longDescription: "Annaprashan is a sacred Hindu ceremony marking the first feeding of solid food to your child, typically performed between 6 months to 1 year of age. This ritual signifies the child's transition to a new stage of growth and development. Our pandits conduct the ceremony with Vedic mantras, invoking divine blessings for the child's health, strength, and intellectual development. The ceremony includes worship of Goddess Annapurna, the deity of nourishment, and specific rituals to ensure the child's well-being.",
    benefits: [
      "Marks an important milestone in the child's physical development",
      "Invokes divine blessings for health, strength, and immunity",
      "Ensures proper nutrition and growth for the child",
      "Strengthens the child's connection with traditional values",
      "Brings family together to celebrate the child's growth"
    ],
    process: [
      { step: "Preparation", description: "Selection of auspicious date and time, preparation of food and samagri" },
      { step: "Ganesha Puja", description: "Invocation of Lord Ganesha to remove obstacles" },
      { step: "Annapurna Puja", description: "Worship of Goddess Annapurna, deity of nourishment" },
      { step: "Food Preparation", description: "Preparation of kheer or other traditional food with religious rituals" },
      { step: "Feeding Ceremony", description: "Sacred feeding of the child with Vedic mantras" },
      { step: "Aarti & Prasad", description: "Devotional songs and distribution of blessed food" },
      { step: "Blessings", description: "Receiving blessings from elders and family members" }
    ],
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg"
  }
}

// Schema markup for individual puja pages
const generatePujaSchema = (puja: any, slug: string) => ({
  "@context": "https://schema.org",
  "@type": "Service",
  name: puja.title,
  description: puja.description,
  provider: {
    "@type": "Organization",
    name: "Navdhya",
    url: "https://www.navdhya.com"
  },
  serviceType: "Hindu Puja Service",
  category: "Spiritual Services",
  offers: {
    "@type": "Offer",
    priceCurrency: "INR",
    price: puja.price
  },
  url: `https://www.navdhya.com/pujas/${slug}`,
  breadcrumb: BREADCRUMB_SCHEMA([
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Puja Services", url: "https://www.navdhya.com/pujas" },
    { name: puja.title, url: `https://www.navdhya.com/pujas/${slug}` }
  ])
})

export default async function PujaDetailsPage({ params }: { params: { slug: string } }) {
  const { slug } = await params;
  const puja = DATA[slug]
  if (!puja) return notFound()
  
  const pujaSchema = JSON.stringify(generatePujaSchema(puja, slug))

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
                    Puja Service
                  </h1>
                </div>
              </div>
              
              {/* Heading */}
              <div className="max-w-3xl mb-10">
                <h2 id="hero-heading" className="text-4xl md:text-5xl lg:text-6xl font-normal tracking-tight text-foreground leading-tight">
                  {puja.title}
                </h2>
              </div>
              
              {/* Description */}
              <div className="max-w-2xl mb-10">
                <p className="text-lg text-muted-foreground leading-relaxed">
                  {puja.description}
                </p>
              </div>
              
              {/* Price */}
              <div className="mb-10">
                <p className="text-xl font-medium text-primary">
                  Typical range: {puja.price}
                </p>
              </div>
              
              {/* Image */}
              <div className="mt-auto">
                <div className="bg-gradient-to-br from-muted/50 to-muted/20 rounded-3xl overflow-hidden border border-border/30 h-80 md:h-96">
                  <img 
                    src={puja.image} 
                    alt={`${puja.title} - Navdhya Puja Services`} 
                    className="w-full h-full object-cover"
                  />
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* Highlights */}
        <section aria-labelledby="highlights" className="py-10 lg:py-14">
          <h2 id="highlights" className="font-serif text-3xl lg:text-4xl font-bold tracking-tight text-foreground mb-10">
            Service Highlights
          </h2>
          
          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            {puja.highlights.map((highlight, index) => (
              <div key={index} className="bg-card rounded-3xl px-4 py-4 border border-border hover:shadow-md transition-all duration-300 flex flex-col justify-between" style={{ minHeight: "180px" }}>
                <div className="w-14 h-14 bg-[#0E3F2E] rounded-full flex items-center justify-center mb-4">
                  <ShieldCheck className="w-6 h-6 text-white" />
                </div>
                
                <div className="bg-muted rounded-2xl p-4 flex-grow">
                  <p className="text-muted-foreground text-sm leading-relaxed">
                    {highlight}
                  </p>
                </div>
              </div>
            ))}
          </div>
        </section>

        {/* Detailed Description */}
        <section aria-labelledby="detailed-description" className="py-10 lg:py-14">
          <div className="grid lg:grid-cols-2 gap-16 items-center">
            <div>
              <h2 id="detailed-description" className="font-serif text-3xl lg:text-4xl font-bold tracking-tight text-foreground mb-6">
                About This Puja
              </h2>
              <p className="text-muted-foreground mb-5 text-base leading-relaxed">
                {puja.longDescription}
              </p>
              <div className="mt-8">
                <a 
                  href="/#booking" 
                  className="bg-primary hover:bg-primary/90 text-primary-foreground font-medium py-3 px-8 rounded-lg transition-all duration-300 shadow-sm hover:shadow-md inline-block focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                >
                  Book This Puja
                </a>
              </div>
            </div>
            <div className="bg-muted/50 rounded-xl h-80 flex items-center justify-center shadow-sm border border-border/30 overflow-hidden">
              <img 
                src={puja.image} 
                alt={`${puja.title} - Navdhya Puja Services`} 
                className="w-full h-full object-cover"
              />
            </div>
          </div>
        </section>

        {/* Benefits */}
        <section aria-labelledby="benefits" className="py-10 lg:py-14">
          <h2 id="benefits" className="font-serif text-3xl lg:text-4xl font-bold tracking-tight text-foreground text-center mb-5">
            Spiritual Benefits
          </h2>
          <p className="text-muted-foreground text-center max-w-3xl mx-auto mb-14 text-lg leading-relaxed">
            Experience the profound spiritual and material benefits of this sacred ritual.
          </p>
          
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {puja.benefits.map((benefit, index) => (
              <div key={index} className="border border-border/50 rounded-xl p-6 bg-card hover:shadow-md transition-all duration-300 backdrop-blur-sm">
                <div className="flex items-center mb-4">
                  <div className="flex-shrink-0 h-8 w-8 rounded-full bg-gradient-to-br from-primary/10 to-secondary/10 flex items-center justify-center mr-3">
                    <span className="text-primary text-sm">✓</span>
                  </div>
                  <h3 className="font-medium text-foreground">Benefit {index + 1}</h3>
                </div>
                <p className="text-muted-foreground text-sm">
                  {benefit}
                </p>
              </div>
            ))}
          </div>
        </section>

        {/* Process */}
        <section aria-labelledby="process" className="py-10 lg:py-14 bg-muted/30 rounded-2xl p-8 lg:p-12">
          <h2 id="process" className="font-serif text-3xl lg:text-4xl font-bold tracking-tight text-foreground text-center mb-5">
            Ceremony Process
          </h2>
          <p className="text-muted-foreground text-center max-w-3xl mx-auto mb-14 text-lg leading-relaxed">
            Our pandits follow a detailed, authentic process to ensure the complete spiritual benefit of this ritual.
          </p>
          
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            {puja.process.map((step, index) => (
              <div key={index} className="bg-card border border-border/50 rounded-xl p-6 shadow-sm backdrop-blur-sm">
                <div className="w-12 h-12 rounded-full bg-[#0E3F2E] flex items-center justify-center text-white font-bold mb-5">
                  {index + 1}
                </div>
                <h3 className="font-serif text-xl font-bold tracking-tight text-foreground mb-3">
                  {step.step}
                </h3>
                <p className="text-muted-foreground text-sm leading-relaxed">
                  {step.description}
                </p>
              </div>
            ))}
          </div>
        </section>

        {/* Booking CTA */}
        <section aria-labelledby="booking-cta" className="py-16 lg:py-20 text-center border-t border-b border-border/50 my-10">
          <h2 id="booking-cta" className="font-serif text-3xl lg:text-4xl font-bold tracking-tight text-foreground mb-8">
            Ready to Book Your {puja.title}?
          </h2>
          <p className="text-muted-foreground max-w-4xl mx-auto text-lg mb-10 leading-relaxed">
            Connect with our certified pandits to schedule your puja ceremony. We'll ensure every ritual 
            is performed with the utmost reverence and authenticity.
          </p>
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <a 
              href="/#booking" 
              className="bg-primary hover:bg-primary/90 text-primary-foreground font-medium py-3 px-8 rounded-lg transition-all duration-300 shadow-sm hover:shadow-md inline-block focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            >
              Book This Puja
            </a>
            <Link 
              href="/pujas" 
              className="bg-transparent border border-primary text-primary hover:bg-primary/10 font-medium py-3 px-8 rounded-lg transition-all duration-300 inline-block focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            >
              View All Pujas
            </Link>
          </div>
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
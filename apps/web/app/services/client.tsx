'use client'
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
  CheckCircle,
  BarChart2,
  ArrowRight,
  ChevronDown
} from "lucide-react"
import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import Link from "next/link"
import { useState } from "react"

// Schema markup for the services page
const SERVICES_SCHEMA = seoManager.generateSchema({
  type: "Service",
  name: "Puja Services",
  description: "Authentic Hindu puja services performed by certified Vedic pandits in Delhi",
  path: "/services",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Services", url: "https://www.navdhya.com/services" }
  ]
})

// Service data for major categories with images
const serviceCategories = [
  {
    id: 1,
    title: "Puja Services",
    description: "Authentic Hindu pujas performed by certified Vedic pandits for every spiritual occasion",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    link: "/pujas",
    popular: true
  },
  {
    id: 2,
    title: "Astrology",
    description: "Expert guidance based on Vedic astrology for life decisions and remedies",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    link: "/astrology",
    popular: true
  },
  {
    id: 3,
    title: "Consultations",
    description: "Personalized spiritual guidance and consultation with experienced gurus",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg",
    link: "/consultations",
    popular: false
  },
  {
    id: 4,
    title: "Festivals",
    description: "Traditional celebrations for major Hindu festivals throughout the year",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_db05ba7c437e48abe6440798d2a33b09dr_bokdku.jpg",
    link: "/festivals",
    popular: false
  },
  {
    id: 5,
    title: "Life Events",
    description: "Sacred ceremonies for significant life transitions and milestones",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960318/LoDi-Make_this_logo_exact_lnyy2a.svg",
    link: "/life-events",
    popular: false
  },
  {
    id: 6,
    title: "Business & Prosperity",
    description: "Rituals to bless your business ventures and attract prosperity",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg",
    link: "/business-prosperity",
    popular: false
  }
]

const benefits = [
  {
    title: "Certified Pandits",
    description: "Verified experts with authentic Vedic knowledge",
    icon: CheckCircle
  },
  {
    title: "Transparent Pricing",
    description: "Clear, upfront pricing with no hidden costs",
    icon: Clock
  },
  {
    title: "Customized Rituals",
    description: "Personalized ceremonies based on your traditions",
    icon: Users
  },
  {
    title: "Seamless Booking",
    description: "Easy online booking with real-time availability",
    icon: Calendar
  }
]

// FAQ data
const faqData = [
  {
    question: "How do I book a service?",
    answer: "Booking is simple! You can either use our online booking system or call us directly. Just select your preferred service, choose a date and time, and our team will connect you with a certified pandit who will guide you through the process."
  },
  {
    question: "What is included in the service?",
    answer: "Our services include a certified pandit, all necessary items (puja samagri), and guidance throughout the ceremony. For home services, we also provide setup and cleanup to ensure a hassle-free experience."
  },
  {
    question: "Can I customize the rituals?",
    answer: "Absolutely! We understand that every family has unique traditions. Our pandits work with you to customize rituals according to your specific customs, beliefs, and requirements while maintaining the authenticity of Vedic practices."
  }
]

// Image-based service category card component - minimal and mobile optimized
const ServiceCategoryCard = ({ category }: { category: typeof serviceCategories[0] }) => {
  return (
    <div className="group relative border border-border rounded-lg overflow-hidden bg-card flex flex-col h-full transition-all duration-300 hover:shadow-md">
      <div className="relative bg-muted flex-shrink-0 h-32 overflow-hidden">
        <img 
          src={category.image} 
          alt={category.title}
          className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
        />
        
        <div className="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
        
        {category.popular && (
          <div className="absolute top-2 right-2 z-10">
            <span className="bg-secondary text-secondary-foreground px-2 py-0.5 rounded-full text-[10px] font-medium">
              Popular
            </span>
          </div>
        )}
      </div>
      
      <div className="p-3 space-y-2 flex flex-col flex-grow">
        <h3 className="text-sm font-semibold text-foreground group-hover:text-primary transition-colors duration-200">
          {category.title}
        </h3>
        
        <p className="text-muted-foreground text-xs flex-grow line-clamp-2">
          {category.description}
        </p>
        
        <Link 
          href={category.link}
          className="inline-flex items-center justify-center gap-1 w-full bg-primary hover:bg-primary/90 text-primary-foreground font-medium py-1.5 px-2 rounded text-[10px] transition-all duration-200 shadow-sm"
        >
          <span>Explore</span>
          <ArrowRight className="w-2.5 h-2.5" />
        </Link>
      </div>
    </div>
  );
};

// FAQ Item Component with progressive disclosure
const FAQItem = ({ question, answer, isOpen, onClick }: { question: string; answer: string; isOpen: boolean; onClick: () => void }) => {
  return (
    <div className="bg-card border border-border rounded-lg overflow-hidden transition-all duration-200 hover:shadow-sm">
      <button
        className="flex justify-between items-center w-full p-3 text-left focus:outline-none"
        onClick={onClick}
        aria-expanded={isOpen}
      >
        <h3 className="text-sm font-medium text-foreground">{question}</h3>
        <ChevronDown 
          className={`w-4 h-4 text-muted-foreground transition-transform duration-200 ${isOpen ? 'rotate-180' : ''}`} 
        />
      </button>
      <div 
        className={`overflow-hidden transition-all duration-200 ease-in-out ${
          isOpen ? 'max-h-40 opacity-100 pb-3' : 'max-h-0 opacity-0'
        }`}
      >
        <div className="px-3 text-muted-foreground text-xs">
          <p>{answer}</p>
        </div>
      </div>
    </div>
  );
};

// Client component wrapper for state management
export default function ServicesClient() {
  const servicesSchema = JSON.stringify(SERVICES_SCHEMA)
  const [openFaqIndex, setOpenFaqIndex] = useState<number | null>(null)

  const toggleFaq = (index: number) => {
    setOpenFaqIndex(openFaqIndex === index ? null : index)
  }
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: servicesSchema }}
      />
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 overflow-x-hidden w-full">
        {/* Hero Section - minimal and compact */}
        <section className="text-center mb-10 mt-4">
          <div className="inline-block bg-primary/10 text-primary px-2.5 py-1 rounded-full text-xs font-medium mb-4">
            Our Services
          </div>
          <h1 className="text-2xl md:text-3xl font-bold tracking-tight mb-4 leading-tight">
            Authentic <span className="text-primary">Spiritual Services</span>
          </h1>
          <p className="text-sm text-muted-foreground max-w-2xl mx-auto mb-6">
            Connecting Delhi families with certified Vedic pandits for meaningful rituals.
          </p>
          <div className="flex flex-wrap justify-center gap-2">
            <Button asChild size="sm" className="px-4 py-2 rounded-md text-xs">
              <Link href="/book">Book a Puja</Link>
            </Button>
            <Button asChild variant="outline" size="sm" className="px-4 py-2 rounded-md text-xs border-2">
              <Link href="/consultation">Consult an Expert</Link>
            </Button>
          </div>
        </section>

        {/* Benefits Section - compact grid */}
        <section className="mb-12">
          <h2 className="text-xl font-bold text-center mb-6">Why Choose Navdhya</h2>
          <div className="grid grid-cols-2 gap-3">
            {benefits.map((benefit, index) => (
              <div key={index} className="bg-card border border-border rounded-lg p-3 text-center hover:shadow-sm transition-all duration-200">
                <div className="mx-auto bg-primary/10 p-2 rounded-full w-8 h-8 flex items-center justify-center mb-2">
                  <benefit.icon className="w-4 h-4 text-primary" />
                </div>
                <h3 className="text-xs font-semibold mb-1">{benefit.title}</h3>
                <p className="text-[10px] text-muted-foreground">{benefit.description}</p>
              </div>
            ))}
          </div>
        </section>

        <Separator className="my-8" />

        {/* Major Service Categories Section - mobile optimized */}
        <section className="mb-12">
          <div className="text-center mb-6">
            <h2 className="text-xl font-bold mb-2">Explore Our Services</h2>
            <p className="text-muted-foreground text-sm max-w-xs mx-auto">
              Comprehensive spiritual services for modern families.
            </p>
          </div>

          <div className="grid grid-cols-2 gap-3">
            {serviceCategories.map((category) => (
              <ServiceCategoryCard key={category.id} category={category} />
            ))}
          </div>
        </section>

        {/* CTA Section - compact */}
        <section className="bg-muted rounded-xl p-6 text-center mb-12">
          <h2 className="text-xl font-bold mb-3">Ready to Book?</h2>
          <p className="text-muted-foreground text-sm mb-4">
            Experience authentic spiritual wellness in your home.
          </p>
          <div className="flex flex-wrap justify-center gap-2">
            <Button asChild size="sm" className="px-4 py-2 rounded-md text-xs">
              <Link href="/book">Schedule</Link>
            </Button>
            <Button asChild variant="outline" size="sm" className="px-4 py-2 rounded-md text-xs border-2">
              <Link href="/contact">Contact</Link>
            </Button>
          </div>
        </section>

        {/* FAQ Section - compact */}
        <section className="mb-12">
          <h2 className="text-xl font-bold text-center mb-6">Frequently Asked Questions</h2>
          <div className="space-y-2">
            {faqData.map((faq, index) => (
              <FAQItem
                key={index}
                question={faq.question}
                answer={faq.answer}
                isOpen={openFaqIndex === index}
                onClick={() => toggleFaq(index)}
              />
            ))}
          </div>
        </section>
      </div>
    </>
  )
}
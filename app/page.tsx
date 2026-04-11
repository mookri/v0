import { FeaturedPujas } from "@/components/sections/featured-pujas"
import { WhyChoose } from "@/components/sections/why-choose"
import { ProcessSteps } from "@/components/sections/process-steps"
import { Catalog } from "@/components/sections/catalog"
import { Testimonials } from "@/components/sections/testimonials"
import { TrustStats } from "@/components/sections/trust-stats"
import { BlogPreview } from "@/components/sections/blog"
import { FAQ } from "@/components/sections/faq"
import Hero from "@/components/sections/hero"
import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Generate page metadata using SEO manager
export const metadata = seoManager.generateMetadata({
  title: "Verified Pandit Booking & Vedic Puja Services",
  description: "Book verified pandits for home pujas, online rituals, astrology consultations, and Hindu ceremonies with Navdhya across Delhi NCR and beyond.",
  path: "/",
  keywords: [
    "Navdhya",
    "Verified Pandit Booking",
    "Vedic Puja Services",
    "Home Puja Services",
    "Online Puja Services",
    "Book Pandit Online",
    "Hindu Ceremonies",
    "Astrology Consultation",
    "Spiritual Guidance",
    "Vedic Rituals India",
    "Pandit in Delhi-NCR",
  ]
})

// Enhanced schema markup for the homepage with additional structured data
const HOME_SCHEMA = seoManager.generateSchema({
  type: "WebSite",
  name: "Navdhya - Verified Pandit Booking & Authentic Vedic Puja Services",
  description: "Navdhya is a Vedic ritual services platform that connects families with verified Hindu priests for authentic home pujas, online ceremonies, astrology consultations, and spiritual guidance across Delhi-NCR and India.",
  path: "/",
  breadcrumbs: [
    {
      name: "Home",
      url: "https://www.navdhya.com"
    }
  ],
  faqs: [
    {
      question: "What is Navdhya?",
      answer: "Navdhya is a Vedic ritual services platform that connects families with verified Hindu priests for home pujas, online ceremonies, astrology consultations, and spiritual guidance across Delhi-NCR and India."
    },
    {
      question: "How does Navdhya verify its priests?",
      answer: "Every pandit undergoes a multi-step evaluation covering scriptural knowledge, ritual experience, mantra pronunciation accuracy, and professional conduct before joining the Navdhya platform."
    },
    {
      question: "What areas does Navdhya serve?",
      answer: "Navdhya currently serves families across Delhi-NCR with home puja services, and offers live online pujas for families anywhere in India."
    }
  ]
})

// Breadcrumb list schema
const BREADCRUMB_LIST_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "Home",
      "item": "https://www.navdhya.com"
    }
  ]
};

// Additional Organization schema with site navigation
const ORGANIZATION_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Navdhya",
  "url": "https://www.navdhya.com",
  "logo": "https://www.navdhya.com/logo.png",
  "description": "Navdhya is a Vedic ritual services platform offering verified pandit booking, home and online puja services, astrology consultations, and spiritual guidance across India.",
  "foundingDate": "2023",
  "areaServed": [
    { "@type": "Place", "name": "Delhi-NCR" },
    { "@type": "Country", "name": "India" }
  ],
  "sameAs": [
    "https://www.facebook.com/navdhya",
    "https://www.instagram.com/navdhya"
  ],
  "contactPoint": {
    "@type": "ContactPoint",
    "telephone": "+91-7289000377",
    "contactType": "Customer Service",
    "availableLanguage": ["English", "Hindi"]
  },
  "mainEntity": {
    "@type": "ItemList",
    "name": "Main Navigation",
    "itemListElement": [
      {
        "@type": "SiteNavigationElement",
        "position": 1,
        "name": "Book a Puja",
        "url": "https://www.navdhya.com/pujas"
      },
      {
        "@type": "SiteNavigationElement",
        "position": 2,
        "name": "Find a Pandit",
        "url": "https://www.navdhya.com/pandits"
      },
      {
        "@type": "SiteNavigationElement",
        "position": 3,
        "name": "Kundli",
        "url": "https://www.navdhya.com/astrology/kundli"
      },
      {
        "@type": "SiteNavigationElement",
        "position": 4,
        "name": "Digital Library",
        "url": "https://www.navdhya.com/resources/library"
      },
      {
        "@type": "SiteNavigationElement",
        "position": 5,
        "name": "Tools",
        "url": "https://www.navdhya.com/resources/tools"
      }
    ]
  }
}

// Additional LocalBusiness schema
const LOCAL_BUSINESS_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "Navdhya",
  "image": "https://www.navdhya.com/logo.png",
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "Delhi",
    "addressRegion": "Delhi-NCR",
    "addressCountry": "IN"
  },
  "telephone": "+91-7289000377",
  "description": "Verified pandit booking and authentic Vedic puja services across Delhi-NCR. Home pujas, online rituals, astrology consultations, and Hindu ceremonies by Navdhya.",
  "priceRange": "INR 500-INR 5000",
  "openingHours": "Mo-Su 08:00-20:00"
}

// Service schemas for rich results
const SERVICE_SCHEMAS = {
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Service",
      "serviceType": "Home Puja Services",
      "name": "Book a Puja",
      "description": "Professionally conducted home puja services by verified Vedic priests - Satyanarayan Katha, Griha Pravesh, Navgraha Shanti, and more across Delhi-NCR.",
      "url": "https://www.navdhya.com/pujas",
      "areaServed": [
        { "@type": "Place", "name": "Delhi-NCR" },
        { "@type": "Country", "name": "India" }
      ],
      "provider": {
        "@type": "Organization",
        "name": "Navdhya",
        "url": "https://www.navdhya.com"
      }
    },
    {
      "@type": "Service",
      "serviceType": "Verified Pandit Booking",
      "name": "Find a Pandit",
      "description": "Connect with verified Hindu priests screened for scriptural knowledge, mantra proficiency, and professional conduct through the Navdhya platform.",
      "url": "https://www.navdhya.com/pandits",
      "areaServed": [
        { "@type": "Place", "name": "Delhi-NCR" },
        { "@type": "Country", "name": "India" }
      ],
      "provider": {
        "@type": "Organization",
        "name": "Navdhya",
        "url": "https://www.navdhya.com"
      }
    },
    {
      "@type": "Service",
      "serviceType": "Vedic Astrology Services",
      "name": "Kundli & Astrology",
      "description": "Personalized Vedic astrology consultations, muhurat selection, vastu guidance, and spiritual counselling by experienced practitioners.",
      "url": "https://www.navdhya.com/astrology/kundli",
      "areaServed": { "@type": "Country", "name": "India" },
      "provider": {
        "@type": "Organization",
        "name": "Navdhya",
        "url": "https://www.navdhya.com"
      }
    },
    {
      "@type": "Service",
      "serviceType": "Digital Spiritual Resources",
      "name": "Digital Library",
      "description": "A curated library of spiritual texts, Vedic learning material, and practical ritual resources from Navdhya.",
      "url": "https://www.navdhya.com/resources/library",
      "provider": {
        "@type": "Organization",
        "name": "Navdhya",
        "url": "https://www.navdhya.com"
      }
    }
  ]
}

export default async function HomePage() {
  const homeSchema = JSON.stringify(HOME_SCHEMA)
  const breadcrumbListSchema = JSON.stringify(BREADCRUMB_LIST_SCHEMA)
  const organizationSchema = JSON.stringify(ORGANIZATION_SCHEMA)
  const localBusinessSchema = JSON.stringify(LOCAL_BUSINESS_SCHEMA)
  const serviceSchemas = JSON.stringify(SERVICE_SCHEMAS)
  
  // Resolve context using the new resolver
  const context = await resolveContext('/');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: homeSchema }}
        />
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: breadcrumbListSchema }}
        />
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: organizationSchema }}
        />
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: localBusinessSchema }}
        />
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: serviceSchemas }}
        />
        <main className="overflow-x-hidden w-full">
          <Hero />
          <FeaturedPujas />
          <WhyChoose />
          <ProcessSteps />
          <Catalog />
          <Testimonials />
          <TrustStats />
          <BlogPreview />
          <FAQ />
          {/* <Booking /> */}
        </main>
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}


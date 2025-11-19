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
  title: "Navdhya – Delhi's Trusted Puja & Spiritual Wellness",
  description: "Experience authentic Puja and Spiritual Wellness in Delhi with Navdhya. Book trusted pandits for Navdhya Puja, Grah Shanti, Vedic rituals, and home ceremonies for peace and prosperity.",
  path: "/",
  keywords: [
    "Navdhya Puja in Delhi",
    "Puja Services in Delhi",
    "Spiritual Wellness",
    "Vedic Rituals",
    "Grah Shanti",
    "Homam",
    "Book Pandit Online",
    "Trusted Pandits in Delhi",
    "Hindu Puja",
    "Puja for Peace and Prosperity"
  ]
})

// Enhanced schema markup for the homepage with additional structured data
const HOME_SCHEMA = seoManager.generateSchema({
  type: "WebSite",
  name: "Navdhya - Delhi's Trusted Puja & Spiritual Wellness",
  description: "Experience authentic Puja and Spiritual Wellness in Delhi with Navdhya. Book trusted pandits for Navdhya Puja, Grah Shanti, Vedic rituals, and home ceremonies for peace and prosperity.",
  path: "/",
  breadcrumbs: [
    {
      name: "Home",
      url: "https://www.navdhya.com"
    }
  ],
  faqs: [
    {
      question: "What is Navdhya Puja service?",
      answer: "Navdhya offers authentic Puja services in Delhi performed by experienced pandits. Our services include Navdhya Puja, Grah Shanti, Vedic rituals, and home ceremonies for peace and prosperity."
    },
    {
      question: "How can I book a pandit online?",
      answer: "You can easily book a trusted pandit through our website by selecting your preferred service, choosing a convenient date and time, and completing the booking process securely online."
    },
    {
      question: "What areas in Delhi do you serve?",
      answer: "We serve all major areas in Delhi and the National Capital Region (NCR) with our professional pandit services for various spiritual and religious ceremonies."
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
      "name": "Book a Pooja",
      "item": "https://www.navdhya.com/book-pooja"
    },
    {
      "@type": "ListItem",
      "position": 2,
      "name": "Find a Pandit",
      "item": "https://www.navdhya.com/find-pandit"
    },
    {
      "@type": "ListItem",
      "position": 3,
      "name": "Learn Rituals",
      "item": "https://www.navdhya.com/learn"
    },
    {
      "@type": "ListItem",
      "position": 4,
      "name": "Join the Community",
      "item": "https://www.navdhya.com/community"
    },
    {
      "@type": "ListItem",
      "position": 5,
      "name": "Explore Resources",
      "item": "https://www.navdhya.com/resources"
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
  "sameAs": [
    "https://www.facebook.com/navdhya",
    "https://www.instagram.com/navdhya",
    "https://www.twitter.com/navdhya"
  ],
  "contactPoint": {
    "@type": "ContactPoint",
    "telephone": "+91-9876543210",
    "contactType": "Customer Service",
    "availableLanguage": "English"
  },
  // Site navigation for sitelinks
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
  "name": "Navdhya Puja Services",
  "image": "https://www.navdhya.com/logo.png",
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "Delhi",
    "addressCountry": "IN"
  },
  "telephone": "+91-9876543210",
  "priceRange": "₹500-₹5000",
  "openingHours": "Mo-Su 08:00-20:00"
}

// Service schemas for rich results
const SERVICE_SCHEMAS = [
  {
    "@context": "https://schema.org",
    "@type": "Service",
    "serviceType": "Puja Services",
    "name": "Book a Puja",
    "description": "Authentic Puja services performed by experienced pandits for various occasions and needs",
    "url": "https://www.navdhya.com/pujas",
    "provider": {
      "@type": "Organization",
      "name": "Navdhya",
      "url": "https://www.navdhya.com"
    }
  },
  {
    "@context": "https://schema.org",
    "@type": "Service",
    "serviceType": "Pandit Booking",
    "name": "Find a Pandit",
    "description": "Connect with trusted pandits for personalized spiritual guidance and rituals",
    "url": "https://www.navdhya.com/pandits",
    "provider": {
      "@type": "Organization",
      "name": "Navdhya",
      "url": "https://www.navdhya.com"
    }
  },
  {
    "@context": "https://schema.org",
    "@type": "Service",
    "serviceType": "Astrology Services",
    "name": "Kundli",
    "description": "Detailed Kundli analysis and astrological insights for life guidance",
    "url": "https://www.navdhya.com/astrology/kundli",
    "provider": {
      "@type": "Organization",
      "name": "Navdhya",
      "url": "https://www.navdhya.com"
    }
  },
  {
    "@context": "https://schema.org",
    "@type": "Service",
    "serviceType": "Digital Resources",
    "name": "Digital Library",
    "description": "Comprehensive collection of spiritual texts, scriptures, and educational materials",
    "url": "https://www.navdhya.com/resources/library",
    "provider": {
      "@type": "Organization",
      "name": "Navdhya",
      "url": "https://www.navdhya.com"
    }
  }
]

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
import { Suspense } from "react";
import dynamic from "next/dynamic";
import { seoManager } from "@/lib/seo-manager";
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator";
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary";
import { resolveContext } from "@/lib/context-resolver";

// Critical above-the-fold components loaded normally for LCP optimization
import Hero from "@/components/sections/hero";
import MobileHome from "@/components/mobile/home/MobileHome";

// Lazy-loaded components for better performance & reduced initial JS payload
const QuickActions = dynamic(() => import("@/components/sections/quick").then((mod) => mod.QuickActions));
const FeaturedPujas = dynamic(() => import("@/components/sections/featured-pujas").then((mod) => mod.FeaturedPujas));
const WhyChoose = dynamic(() => import("@/components/sections/why-choose").then((mod) => mod.WhyChoose));
const ProcessSteps = dynamic(() => import("@/components/sections/process-steps").then((mod) => mod.ProcessSteps));
const Catalog = dynamic(() => import("@/components/sections/catalog").then((mod) => mod.Catalog));
const Testimonials = dynamic(() => import("@/components/sections/testimonials").then((mod) => mod.Testimonials));
const TrustStats = dynamic(() => import("@/components/sections/trust-stats").then((mod) => mod.TrustStats));
const BlogPreview = dynamic(() => import("@/components/sections/blog").then((mod) => mod.BlogPreview));
const FAQ = dynamic(() => import("@/components/sections/faq").then((mod) => mod.FAQ));

export const viewport = {
  themeColor: "#023020",
  width: "device-width",
  initialScale: 1,
  maximumScale: 5,
};

export const metadata = seoManager.generateMetadata({
  title: "Navdhya | Book Verified Pandits for Home Puja Services in Delhi NCR",
  description:
    "Navdhya helps families book verified pandits in Delhi NCR for home pujas, havan, wedding rituals, astrology consultations, and online ceremonies.",
  path: "/",
  keywords: [
    "navdhya",
    "book pandit online delhi ncr",
    "verified pandit booking",
    "pandit booking delhi ncr",
    "home puja services delhi ncr",
    "online puja services delhi ncr",
  ],
});

const HOME_SCHEMA = seoManager.generateSchema({
  type: "WebSite",
  name: "Navdhya Ritual Services",
  description:
    "Navdhya is a Delhi NCR ritual booking platform that helps families book verified pandits for home pujas, online ceremonies, astrology consultations, and spiritual guidance.",
  path: "/",
  breadcrumbs: [{ name: "Home", url: "https://www.navdhya.com" }],
});

const BREADCRUMB_LIST_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  itemListElement: [
    {
      "@type": "ListItem",
      position: 1,
      name: "Home",
      item: "https://www.navdhya.com",
    },
  ],
};

const ORGANIZATION_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "Organization",
  name: "Navdhya",
  url: "https://www.navdhya.com",
  logo: "https://www.navdhya.com/navdhya-logo.png",
  description:
    "Navdhya is a Delhi NCR ritual booking platform offering verified pandit booking, home puja services, online ceremonies, astrology consultations, and spiritual guidance.",
  foundingDate: "2023",
  areaServed: [
    { "@type": "Place", name: "Delhi NCR" },
    { "@type": "Country", name: "India" },
  ],
  sameAs: [
    "https://www.facebook.com/thenavdhya",
    "https://www.instagram.com/thenavdhya",
    "https://www.youtube.com/@thenavdhya",
  ],
  contactPoint: {
    "@type": "ContactPoint",
    telephone: "+91-7289000377",
    email: "help@navdhya.com",
    contactType: "Customer Service",
    availableLanguage: ["English", "Hindi"],
  },
};

const LOCAL_BUSINESS_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  name: "Navdhya Ritual Services",
  image: "https://www.navdhya.com/og/navdhya-default-og.png",
  url: "https://www.navdhya.com",
  telephone: "+91-7289000377",
  email: "help@navdhya.com",
  address: {
    "@type": "PostalAddress",
    streetAddress: "Pandav Nagar",
    addressLocality: "Delhi",
    addressRegion: "Delhi",
    postalCode: "110092",
    addressCountry: "IN",
  },
  description:
    "Verified pandit booking and authentic Vedic puja services across Delhi NCR. Home pujas, online ceremonies, astrology consultations, and Hindu rituals by Navdhya.",
  areaServed: "Delhi NCR",
  priceRange: "Rs. 2,500 - Rs. 7,500",
  openingHours: "Mo-Su 08:00-20:00",
};

const SERVICE_SCHEMAS = {
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Service",
      serviceType: "Home Puja Services",
      name: "Book a Puja",
      description:
        "Professionally conducted home puja services by verified Vedic priests including Satyanarayan Katha, Griha Pravesh, Navagraha Shanti, and more across Delhi NCR.",
      url: "https://www.navdhya.com/pujas",
      areaServed: [
        { "@type": "Place", name: "Delhi NCR" },
        { "@type": "Country", name: "India" },
      ],
      provider: {
        "@type": "Organization",
        name: "Navdhya",
        url: "https://www.navdhya.com",
      },
    },
    {
      "@type": "Service",
      serviceType: "Verified Pandit Booking",
      name: "Find a Pandit",
      description:
        "Connect with verified Hindu priests screened for scriptural knowledge, mantra proficiency, and professional conduct through the Navdhya platform.",
      url: "https://www.navdhya.com/pandits",
      areaServed: [
        { "@type": "Place", name: "Delhi NCR" },
        { "@type": "Country", name: "India" },
      ],
      provider: {
        "@type": "Organization",
        name: "Navdhya",
        url: "https://www.navdhya.com",
      },
    },
    {
      "@type": "Service",
      serviceType: "Vedic Astrology Services",
      name: "Kundli and Astrology",
      description:
        "Personalized Vedic astrology consultations, muhurat selection, vastu guidance, and spiritual counseling by experienced practitioners.",
      url: "https://www.navdhya.com/astrology/kundli",
      areaServed: { "@type": "Country", name: "India" },
      provider: {
        "@type": "Organization",
        name: "Navdhya",
        url: "https://www.navdhya.com",
      },
    },
  ],
};

// HomePage component
export default async function HomePage() {
  const context = await resolveContext("/");

  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        {/* Optimized JSON-LD Injection - batched into a single script tag */}
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{
            __html: JSON.stringify([
              HOME_SCHEMA,
              BREADCRUMB_LIST_SCHEMA,
              ORGANIZATION_SCHEMA,
              LOCAL_BUSINESS_SCHEMA,
              SERVICE_SCHEMAS,
            ]),
          }}
        />

        <div className="block w-full md:hidden">
          {/* Mobile Home is critical for mobile LCP, loaded normally */}
          <MobileHome />
        </div>
        
        <main className="hidden w-full overflow-x-hidden md:block">
          {/* Hero is critical for desktop LCP, loaded normally */}
          <Hero />
          
          {/* Below-the-fold components are lazy loaded with Suspense for non-blocking rendering */}
          <Suspense fallback={<div className="h-24 w-full animate-pulse bg-gray-100" />}>
            <QuickActions />
          </Suspense>
          
          <Suspense fallback={<div className="h-96 w-full animate-pulse bg-gray-50" />}>
            <FeaturedPujas />
            <WhyChoose />
            <ProcessSteps />
            <Catalog />
            <Testimonials />
            <TrustStats />
            <BlogPreview />
            <FAQ />
          </Suspense>
        </main>
      </LayoutOrchestrator>
    </ErrorBoundary>
  );
}

import { seoManager } from "@/lib/seo-manager";
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator";
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary";
import { resolveContext } from "@/lib/context-resolver";
import { NavdhyaMarketplaceHome } from "@/components/product/NavdhyaMarketplaceHome";
import Hero from "@/components/sections/hero";
import { QuickActions } from "@/components/sections/quick";
import { FeaturedPujas } from "@/components/sections/featured-pujas";
import { WhyChoose } from "@/components/sections/why-choose";
import { ProcessSteps } from "@/components/sections/process-steps";
import { Catalog } from "@/components/sections/catalog";
import { Testimonials } from "@/components/sections/testimonials";
import { TrustStats } from "@/components/sections/trust-stats";
import { BlogPreview } from "@/components/sections/blog";
import { FAQ } from "@/components/sections/faq";

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

export default async function HomePage() {
  const context = await resolveContext("/");

  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(HOME_SCHEMA) }} />
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: JSON.stringify(BREADCRUMB_LIST_SCHEMA) }}
        />
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: JSON.stringify(ORGANIZATION_SCHEMA) }}
        />
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: JSON.stringify(LOCAL_BUSINESS_SCHEMA) }}
        />
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: JSON.stringify(SERVICE_SCHEMAS) }}
        />
        <main className="w-full overflow-x-hidden">
          <Hero />
          <QuickActions />
          <FeaturedPujas />
          <WhyChoose />
          <ProcessSteps />
          <Catalog />
          <Testimonials />
          <TrustStats />
          <BlogPreview />
          <FAQ />
        </main>
      </LayoutOrchestrator>
    </ErrorBoundary>
  );
}

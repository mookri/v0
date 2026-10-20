import { SEO_CONFIG } from "@/lib/seo-config"

export const servicesMetadata = {
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
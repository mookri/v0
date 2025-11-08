// /lib/metadata-config.ts
import { SEO_CONFIG } from "@/lib/seo-config"
import themeConfig from "@/lib/theme-config.json"

export const SITE_METADATA = {
  title: "Navdhya – Delhi's Trusted Puja & Spiritual Wellness | Experts Pandits for Every Occasion",
  description:
    "Experience authentic Puja and Spiritual Wellness in Delhi with Navdhya. Book trusted pandits for Navdhya Puja, Grah Shanti, Vedic rituals, and home ceremonies for peace and prosperity.",
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
    "Puja for Peace and Prosperity",
  ],
  authors: [{ name: SEO_CONFIG.siteName }],
  creator: "Navdhya Team",
  publisher: SEO_CONFIG.siteName,
  formatDetection: { telephone: false },
  openGraph: {
    title: "Navdhya – Delhi's Trusted Puja & Spiritual Wellness | Experts Pandits for Every Occasion",
    description:
      "Experience authentic Puja and Spiritual Wellness in Delhi with Navdhya. Book trusted pandits for Navdhya Puja, Grah Shanti, Vedic rituals, and home ceremonies for peace and prosperity.",
    url: SEO_CONFIG.siteUrl,
    siteName: SEO_CONFIG.siteName,
    images: [
      {
        url: SEO_CONFIG.defaultImage,
        width: 1200,
        height: 630,
        alt: SEO_CONFIG.siteName,
      },
    ],
    locale: SEO_CONFIG.locale,
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "Navdhya – Delhi's Trusted Puja & Spiritual Wellness | Experts Pandits for Every Occasion",
    description:
      "Experience authentic Puja and Spiritual Wellness in Delhi with Navdhya. Book trusted pandits for Navdhya Puja, Grah Shanti, Vedic rituals, and home ceremonies for peace and prosperity.",
    images: [SEO_CONFIG.defaultImage],
    site: SEO_CONFIG.twitter.site,
  },
  icons: {
    icon: [
      { url: "/favicon/favicon.ico" },
      { url: "/favicon/favicon-16x16.png", sizes: "16x16", type: "image/png" },
      { url: "/favicon/favicon-32x32.png", sizes: "32x32", type: "image/png" },
    ],
    apple: [{ url: "/favicon/apple-touch-icon.png" }],
    other: [
      { rel: "android-chrome-192x192", url: "/favicon/android-chrome-192x192.png" },
      { rel: "android-chrome-512x512", url: "/favicon/android-chrome-512x512.png" },
    ],
  },
  manifest: "/favicon/site.webmanifest",
  appleWebApp: {
    capable: true,
    statusBarStyle: "default" as const,
    title: SEO_CONFIG.siteName,
  },
  alternates: { canonical: "https://www.navdhya.com/" },
}

export const VIEWPORT_CONFIG = {
  width: "device-width",
  initialScale: 1,
  maximumScale: 1,
  themeColor: themeConfig.themeColor,
}
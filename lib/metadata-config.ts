import { SEO_CONFIG } from "@/lib/seo-config";
import themeConfig from "@/lib/theme-config.json";

const HOME_TITLE = "Navdhya | Book Verified Pandits for Home Puja Services in Delhi NCR";
const HOME_DESCRIPTION =
  "Navdhya helps families book verified pandits in Delhi NCR for home pujas, havan, wedding rituals, astrology consultations, and online ceremonies.";

export const SITE_METADATA = {
  title: HOME_TITLE,
  description: HOME_DESCRIPTION,
  authors: [{ name: SEO_CONFIG.siteName }],
  creator: "Navdhya Team",
  publisher: SEO_CONFIG.siteName,
  formatDetection: { telephone: false },
  openGraph: {
    title: HOME_TITLE,
    description: HOME_DESCRIPTION,
    url: SEO_CONFIG.siteUrl,
    siteName: SEO_CONFIG.siteName,
    images: [
      {
        url: SEO_CONFIG.defaultImage,
        width: 1200,
        height: 630,
        alt: "Navdhya verified pandit booking and puja services in Delhi NCR",
      },
    ],
    locale: SEO_CONFIG.locale,
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: HOME_TITLE,
    description: HOME_DESCRIPTION,
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
  robots: {
    index: true,
    follow: true,
    googleBot: {
      index: true,
      follow: true,
      "max-image-preview": "large" as const,
      "max-snippet": -1,
      "max-video-preview": -1,
    },
  },
  alternates: { canonical: "https://www.navdhya.com/" },
};

export const VIEWPORT_CONFIG = {
  width: "device-width",
  initialScale: 1,
  themeColor: themeConfig.themeColor,
};

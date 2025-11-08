'use client'
import type React from "react"
import type { Metadata, Viewport } from "next"
import { Albert_Sans } from "next/font/google"
import { Analytics } from "@vercel/analytics/next"
import { Suspense } from "react"
import "./globals.css"
import BottomMenu from "@/components/bottom-menu"
import themeConfig from "@/lib/theme-config.json"
import { SEO_CONFIG, WEBSITE_SCHEMA, ORGANIZATION_SCHEMA } from "@/lib/seo-config"
import { LoadingProvider } from "@/context/LoadingContext"
import { useNavigationLoader } from "@/hooks/useNavigationLoader"
import Preloader from "@/components/ui/preloader"
import ProgressBar from "@/components/ui/ProgressBar"

const albert = Albert_Sans({
  subsets: ["latin"],
  variable: "--font-albert",
  display: "swap",
})

// // ✅ SEO Metadata (no change)
// export const metadata: Metadata = {
//   title: "Navdhya – Delhi's Trusted Puja & Spiritual Wellness | Experts Pandits for Every Occasion",
//   description:
//     "Experience authentic Puja and Spiritual Wellness in Delhi with Navdhya. Book trusted pandits for Navdhya Puja, Grah Shanti, Vedic rituals, and home ceremonies for peace and prosperity.",
//   generator: "Next.js",
//   applicationName: SEO_CONFIG.siteName,
//   keywords: [
//     "Navdhya Puja in Delhi",
//     "Puja Services in Delhi",
//     "Spiritual Wellness",
//     "Vedic Rituals",
//     "Grah Shanti",
//     "Homam",
//     "Book Pandit Online",
//     "Trusted Pandits in Delhi",
//     "Hindu Puja",
//     "Puja for Peace and Prosperity",
//   ],
//   authors: [{ name: SEO_CONFIG.siteName }],
//   creator: "Navdhya Team",
//   publisher: SEO_CONFIG.siteName,
//   formatDetection: { telephone: false },
//   metadataBase: new URL(SEO_CONFIG.siteUrl),
//   openGraph: {
//     title: "Navdhya – Delhi's Trusted Puja & Spiritual Wellness | Experts Pandits for Every Occasion",
//     description:
//       "Experience authentic Puja and Spiritual Wellness in Delhi with Navdhya. Book trusted pandits for Navdhya Puja, Grah Shanti, Vedic rituals, and home ceremonies for peace and prosperity.",
//     url: SEO_CONFIG.siteUrl,
//     siteName: SEO_CONFIG.siteName,
//     images: [
//       {
//         url: SEO_CONFIG.defaultImage,
//         width: 1200,
//         height: 630,
//         alt: SEO_CONFIG.siteName,
//       },
//     ],
//     locale: SEO_CONFIG.locale,
//     type: "website",
//   },
//   twitter: {
//     card: "summary_large_image",
//     title: "Navdhya – Delhi's Trusted Puja & Spiritual Wellness | Experts Pandits for Every Occasion",
//     description:
//       "Experience authentic Puja and Spiritual Wellness in Delhi with Navdhya. Book trusted pandits for Navdhya Puja, Grah Shanti, Vedic rituals, and home ceremonies for peace and prosperity.",
//     images: [SEO_CONFIG.defaultImage],
//     site: SEO_CONFIG.twitter.site,
//   },
//   icons: {
//     icon: [
//       { url: "/favicon/favicon.ico" },
//       { url: "/favicon/favicon-16x16.png", sizes: "16x16", type: "image/png" },
//       { url: "/favicon/favicon-32x32.png", sizes: "32x32", type: "image/png" },
//     ],
//     apple: [{ url: "/favicon/apple-touch-icon.png" }],
//     other: [
//       { rel: "android-chrome-192x192", url: "/favicon/android-chrome-192x192.png" },
//       { rel: "android-chrome-512x512", url: "/favicon/android-chrome-512x512.png" },
//     ],
//   },
//   manifest: "/favicon/site.webmanifest",
//   appleWebApp: {
//     capable: true,
//     statusBarStyle: "default",
//     title: SEO_CONFIG.siteName,
//   },
//   alternates: { canonical: "https://www.navdhya.com/" },
// }

// export const viewport: Viewport = {
//   width: "device-width",
//   initialScale: 1,
//   maximumScale: 1,
//   themeColor: themeConfig.themeColor,
// }

function ClientShell({ children }: { children: React.ReactNode }) {
  useNavigationLoader({ minDisplay: 700 }) // Adjust duration for smoother effect

  return (
    <>
      <ProgressBar />
      <Preloader />
      {children}
    </>
  )
}

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode
}>) {
  const websiteSchema = JSON.stringify(WEBSITE_SCHEMA)
  const organizationSchema = JSON.stringify(ORGANIZATION_SCHEMA)

  return (
    <html lang="en" className={`${albert.variable} antialiased`}>
      <head>
        <meta name="mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-title" content={SEO_CONFIG.siteName} />
        <meta name="theme-color" content={themeConfig.themeColor} />
        <meta name="robots" content="index, follow" />
        <meta name="geo.region" content="IN-DL" />
        <meta name="geo.placename" content="Delhi" />
        <meta name="geo.position" content="28.6139;77.2090" />
        <meta name="ICBM" content="28.6139, 77.2090" />
        <link rel="manifest" href="/favicon/site.webmanifest" />
        <link rel="canonical" href="https://www.navdhya.com/" />
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: websiteSchema }}
        />
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: organizationSchema }}
        />
      </head>

      <body className="font-sans bg-background text-foreground">
        <LoadingProvider>
          <ClientShell>
            <Suspense
              fallback={
                <div className="fixed inset-0 flex items-center justify-center bg-background z-50">
                  <Preloader size={96} className="text-primary" />
                </div>
              }
            >
              {children}
              <Analytics />
            </Suspense>
          </ClientShell>

          {/* Bottom Navigation */}
          <BottomMenu />
        </LoadingProvider>

        {/* 🔧 Service Worker Script (untouched) */}
        <script
          dangerouslySetInnerHTML={{
            __html: `
              const isDevelopment = location.hostname === 'localhost' || 
                                    location.hostname === '127.0.0.1' || 
                                    location.hostname === '[::1]' ||
                                    location.hostname.includes('.local');
              
              if (!isDevelopment && 'serviceWorker' in navigator) {
                window.addEventListener('load', function() {
                  navigator.serviceWorker.register('/sw.js', { scope: '/' })
                    .then(function(registration) {
                      console.log('SW registered: ', registration);
                    })
                    .catch(function(err) {
                      console.log('SW registration failed: ', err);
                    });
                });
                
                window.addEventListener('online', function() {
                  if (navigator.serviceWorker.controller) {
                    navigator.serviceWorker.controller.postMessage({ type: 'ONLINE' });
                  }
                });
                
                window.addEventListener('offline', function() {
                  if (navigator.serviceWorker.controller) {
                    navigator.serviceWorker.controller.postMessage({ type: 'OFFLINE' });
                  }
                });
                
                navigator.serviceWorker.addEventListener('message', function(event) {
                  if (event.data && event.data.type === 'ONLINE') {
                    window.location.reload();
                  }
                });
              }
            `,
          }}
        />
      </body>
    </html>
  )
}

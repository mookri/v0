import type React from "react"
import type { Metadata, Viewport } from "next"
import { Albert_Sans } from "next/font/google"
import { Analytics } from "@vercel/analytics/next"
import { Suspense } from "react"
import "./globals.css"
import BottomMenu from "@/components/bottom-menu"
import { SITE_METADATA, VIEWPORT_CONFIG } from "@/lib/metadata-config"
import { SEO_CONFIG } from "@/lib/seo-config"

// ⚙️ New Imports
import { LoadingProvider } from "@/context/LoadingContext"
import NavigationLoader from "@/components/NavigationLoader"
import Preloader from "@/components/ui/preloader"

const albert = Albert_Sans({
  subsets: ["latin"],
  variable: "--font-albert",
  display: "swap",
})

export const metadata: Metadata = {
  title: SITE_METADATA.title,
  description: SITE_METADATA.description,
  generator: "Upflame",
  applicationName: SEO_CONFIG.siteName,
  keywords: SITE_METADATA.keywords,
  authors: SITE_METADATA.authors,
  creator: SITE_METADATA.creator,
  publisher: SITE_METADATA.publisher,
  formatDetection: SITE_METADATA.formatDetection,
  metadataBase: new URL(SITE_METADATA.openGraph.url),
  openGraph: SITE_METADATA.openGraph,
  twitter: SITE_METADATA.twitter,
  icons: SITE_METADATA.icons,
  manifest: SITE_METADATA.manifest,
  appleWebApp: SITE_METADATA.appleWebApp,
  alternates: SITE_METADATA.alternates,
}

export const viewport: Viewport = VIEWPORT_CONFIG

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode
}>) {
  const websiteSchema = JSON.stringify({
    "@context": "https://schema.org",
    "@type": "WebSite",
    name: "Navdhya",
    url: "https://www.navdhya.com",
    description: SITE_METADATA.description,
    publisher: {
      "@type": "Organization",
      name: "Navdhya",
      logo: {
        "@type": "ImageObject",
        url: "https://www.navdhya.com/favicon/android-chrome-512x512.png"
      }
    },
    potentialAction: {
      "@type": "SearchAction",
      target: "https://www.navdhya.com/search?q={search_term_string}",
      "query-input": "required name=search_term_string"
    }
  })

  const organizationSchema = JSON.stringify({
    "@context": "https://schema.org",
    "@type": "Organization",
    name: "Navdhya",
    url: "https://www.navdhya.com",
    logo: "https://www.navdhya.com/favicon/android-chrome-512x512.png",
    description: SITE_METADATA.description,
    sameAs: [
      // Add social media links here when available
    ]
  })

  return (
    <html lang="en" className={`${albert.variable} antialiased`}>
      <head>
        <meta name="mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-title" content="Navdhya" />
        <meta name="theme-color" content={VIEWPORT_CONFIG.themeColor} />
        <meta name="robots" content="index, follow" />
        <meta name="geo.region" content="IN-DL" />
        <meta name="geo.placename" content="Delhi" />
        <meta name="geo.position" content="28.6139;77.2090" />
        <meta name="ICBM" content="28.6139, 77.2090" />
        <link rel="manifest" href="/favicon/site.webmanifest" />
        <link rel="canonical" href="https://www.navdhya.com/" />

        {/* Structured Data */}
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
          <NavigationLoader />
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
          <BottomMenu />
        </LoadingProvider>

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
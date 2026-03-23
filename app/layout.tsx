import type React from "react"
import type { Metadata, Viewport } from "next"
import { Albert_Sans } from "next/font/google"
import { Analytics } from "@vercel/analytics/next"
import { Suspense } from "react"
import "./globals.css"
import { SITE_METADATA, VIEWPORT_CONFIG } from "@/lib/metadata-config"
import { SEO_CONFIG, WEBSITE_SCHEMA, ORGANIZATION_SCHEMA } from "@/lib/seo-config"
import Preloader from "@/components/ui/preloader"
import { LoadingProvider } from "@/context/LoadingContext"
import NavigationLoader from "@/components/NavigationLoader"
import { AdProvider } from './providers';

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
  const websiteSchema = JSON.stringify(WEBSITE_SCHEMA)
  const organizationSchema = JSON.stringify(ORGANIZATION_SCHEMA)

  return (
    <html lang="en" className={`${albert.variable} antialiased`}>
      <head>
        <meta name="mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-title" content="Navdhya" />
        <meta name="theme-color" content={VIEWPORT_CONFIG.themeColor} />
        <meta name="geo.region" content="IN-DL" />
        <meta name="geo.placename" content="Delhi-NCR" />
        <meta name="geo.position" content="28.6139;77.2090" />
        <meta name="ICBM" content="28.6139, 77.2090" />
        <link rel="manifest" href="/favicon/site.webmanifest" />

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
          <AdProvider>
            <Suspense
              fallback={
                <div className="fixed inset-0 flex items-center justify-center bg-background z-50">
                  <Preloader />
                </div>
              }
            >
              {children}
            </Suspense>
          </AdProvider>
        </LoadingProvider>
        <Analytics />
      </body>
    </html>
  )
}

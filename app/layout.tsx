import type React from "react"
import type { Metadata } from "next"
import { Playfair_Display, Albert_Sans } from "next/font/google"
import { Analytics } from "@vercel/analytics/next"
import { Suspense } from "react"
import "./globals.css"

const playfair = Playfair_Display({
  subsets: ["latin"],
  variable: "--font-playfair",
  display: "swap",
})
const albert = Albert_Sans({
  subsets: ["latin"],
  variable: "--font-albert",
  display: "swap",
})

export const metadata: Metadata = {
  title: "Navdhya — Premium Hindu Ritual & Puja Services",
  description:
    "Trusted partner for authentic Hindu rituals and ceremonies. Experienced pandits, complete samagri, and professional setup for a hassle-free, sacred experience.",
  generator: "v0.app",
}

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode
}>) {
  return (
    <html lang="en" className={`${albert.variable} ${playfair.variable} antialiased`}>
      <body className="font-sans bg-background text-foreground">
        <Suspense fallback={<div>Loading...</div>}>
          {children}
          <Analytics />
        </Suspense>
      </body>
    </html>
  )
}

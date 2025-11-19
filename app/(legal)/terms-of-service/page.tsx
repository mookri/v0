import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import { LayoutOrchestrator } from '@/components/dlo-x/LayoutOrchestrator'
import { ErrorBoundary } from '@/components/dlo-x/ErrorBoundary'
import { resolveContext } from '@/lib/context-resolver'

// Add page-specific metadata
export const metadata = seoManager.generateMetadata({
  title: "Terms of Service",
  description: "Read the terms and conditions that govern your use of Navdhya's platform for booking authentic Hindu rituals and spiritual wellness services.",
  path: "/terms-of-service",
  keywords: ["terms of service", "terms and conditions", "user agreement", "platform usage", "Navdhya terms"]
})

// Schema markup for the terms of service page
const TERMS_SCHEMA = seoManager.generateSchema({
  type: "WebPage",
  name: "Terms of Service - Navdhya",
  description: "Read the terms and conditions that govern your use of Navdhya's platform for booking authentic Hindu rituals and spiritual wellness services.",
  path: "/terms-of-service",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Terms of Service", url: "https://www.navdhya.com/terms-of-service" }
  ]
})

export default async function TermsOfServicePage() {
  const termsSchema = JSON.stringify(TERMS_SCHEMA)
  
  // Resolve context using the new resolver
  const context = await resolveContext('/terms-of-service');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: termsSchema }}
        />
        <div className="mx-auto max-w-6xl px-4 py-12 space-y-10 overflow-x-hidden w-full">
          <section aria-labelledby="terms-heading">
            <h1 id="terms-heading" className="font-serif text-3xl">
              Terms of Service
            </h1>
            <p className="mt-3 text-muted-foreground">
              Last Updated: November 1, 2025
            </p>
          </section>

          <section aria-labelledby="acceptance">
            <h2 id="acceptance" className="font-serif text-2xl">
              Acceptance of Terms
            </h2>
            <p className="mt-3 text-muted-foreground">
              By accessing or using the Navdhya website and services, you agree to be bound by these Terms of Service and all applicable laws and regulations. If you do not agree with any of these terms, you are prohibited from using or accessing this site and our services.
            </p>
          </section>

          <section aria-labelledby="services">
            <h2 id="services" className="font-serif text-2xl">
              Services Description
            </h2>
            <p className="mt-3 text-muted-foreground">
              Navdhya provides a platform for connecting users with certified Vedic pandits for authentic Hindu rituals and spiritual wellness services. Our services include but are not limited to:
            </p>
            <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
              <li>Puja booking and scheduling</li>
              <li>Astrology consultation services</li>
              <li>Vastu consultation</li>
              <li>Spiritual guidance and counseling</li>
              <li>Online and in-person ritual services</li>
            </ul>
          </section>

          <section aria-labelledby="user-obligations">
            <h2 id="user-obligations" className="font-serif text-2xl">
              User Obligations
            </h2>
            <p className="mt-3 text-muted-foreground">
              As a user of our services, you agree to:
            </p>
            <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
              <li>Provide accurate and complete information when registering</li>
              <li>Maintain the confidentiality of your account credentials</li>
              <li>Use the services only for lawful purposes</li>
              <li>Respect the rights and dignity of our pandits and other users</li>
              <li>Comply with all applicable laws and regulations</li>
            </ul>
          </section>

          <section aria-labelledby="booking-payment">
            <h2 id="booking-payment" className="font-serif text-2xl">
              Booking and Payment Terms
            </h2>
            <p className="mt-3 text-muted-foreground">
              All bookings are subject to availability and confirmation by our pandits. Payment must be made through our secure payment gateway at the time of booking unless otherwise specified. Cancellation and refund policies are outlined in our separate Cancellation and Refund Policy document.
            </p>
          </section>

          <section aria-labelledby="intellectual-property">
            <h2 id="intellectual-property" className="font-serif text-2xl">
              Intellectual Property
            </h2>
            <p className="mt-3 text-muted-foreground">
              All content on the Navdhya website, including text, graphics, logos, images, and software, is the property of Navdhya or its content suppliers and is protected by Indian and international copyright laws. You may not reproduce, distribute, modify, or create derivative works of any content without our express written permission.
            </p>
          </section>

          <section aria-labelledby="limitation-liability">
            <h2 id="limitation-liability" className="font-serif text-2xl">
              Limitation of Liability
            </h2>
            <p className="mt-3 text-muted-foreground">
              Navdhya shall not be liable for any direct, indirect, incidental, special, or consequential damages arising out of or in connection with your use of our services. This includes but is not limited to damages for loss of profits, goodwill, use, data, or other intangible losses.
            </p>
          </section>

          <section aria-labelledby="indemnification">
            <h2 id="indemnification" className="font-serif text-2xl">
              Indemnification
            </h2>
            <p className="mt-3 text-muted-foreground">
              You agree to indemnify and hold harmless Navdhya, its affiliates, officers, directors, employees, and agents from any claims, liabilities, damages, losses, or expenses, including reasonable attorneys' fees, arising out of or in any way connected with your access to or use of the services or your violation of these Terms.
            </p>
          </section>

          <section aria-labelledby="termination">
            <h2 id="termination" className="font-serif text-2xl">
              Termination
            </h2>
            <p className="mt-3 text-muted-foreground">
              We may terminate or suspend your access to our services immediately, without prior notice or liability, for any reason whatsoever, including without limitation if you breach the Terms. Upon termination, your right to use the services will immediately cease.
            </p>
          </section>

          <section aria-labelledby="governing-law">
            <h2 id="governing-law" className="font-serif text-2xl">
              Governing Law
            </h2>
            <p className="mt-3 text-muted-foreground">
              These Terms shall be governed and construed in accordance with the laws of India, without regard to its conflict of law provisions. Our failure to enforce any right or provision of these Terms will not be considered a waiver of those rights.
            </p>
          </section>

          <section aria-labelledby="changes-terms">
            <h2 id="changes-terms" className="font-serif text-2xl">
              Changes to Terms
            </h2>
            <p className="mt-3 text-muted-foreground">
              We reserve the right, at our sole discretion, to modify or replace these Terms at any time. If a revision is material, we will provide at least 30 days' notice prior to any new terms taking effect. What constitutes a material change will be determined at our sole discretion.
            </p>
          </section>

          <section aria-labelledby="contact-us">
            <h2 id="contact-us" className="font-serif text-2xl">
              Contact Us
            </h2>
            <p className="mt-3 text-muted-foreground">
              If you have any questions about these Terms of Service, please contact us at:
            </p>
            <div className="mt-3 text-muted-foreground">
              <p><strong>Email:</strong> help@navdhya.com</p>
              <p><strong>Phone:</strong> +91-7289000377</p>
              <p><strong>Address:</strong> Pandav Nagar, Delhi 110092</p>
            </div>
          </section>
        </div>
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}
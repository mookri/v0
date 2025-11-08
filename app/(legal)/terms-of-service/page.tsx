import { SEO_CONFIG, BREADCRUMB_SCHEMA } from "@/lib/seo-config"
import Script from "next/script"

// Add page-specific metadata
export const metadata = {
  title: "Terms of Service | Navdhya",
  description: "Read the terms and conditions that govern your use of Navdhya's platform for booking authentic Hindu rituals and spiritual wellness services.",
  keywords: "terms of service, terms and conditions, user agreement, platform usage, Navdhya terms",
  openGraph: {
    title: "Terms of Service | Navdhya",
    description: "Read the terms and conditions that govern your use of Navdhya's platform for booking authentic Hindu rituals and spiritual wellness services.",
    url: "https://www.navdhya.com/terms-of-service",
    siteName: SEO_CONFIG.siteName,
    images: [
      {
        url: SEO_CONFIG.defaultImage,
        width: 1200,
        height: 630,
        alt: "Navdhya Terms of Service",
      },
    ],
    locale: SEO_CONFIG.locale,
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "Terms of Service | Navdhya",
    description: "Read the terms and conditions that govern your use of Navdhya's platform for booking authentic Hindu rituals and spiritual wellness services.",
    images: [SEO_CONFIG.defaultImage],
    site: SEO_CONFIG.twitter.site,
  },
  alternates: {
    canonical: "https://www.navdhya.com/terms-of-service",
  },
}

// Schema markup for the terms of service page
const TERMS_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "WebPage",
  name: "Terms of Service - Navdhya",
  url: "https://www.navdhya.com/terms-of-service",
  description: "Read the terms and conditions that govern your use of Navdhya's platform for booking authentic Hindu rituals and spiritual wellness services.",
  breadcrumb: BREADCRUMB_SCHEMA([
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Terms of Service", url: "https://www.navdhya.com/terms-of-service" }
  ])
}

export default function TermsOfServicePage() {
  const termsSchema = JSON.stringify(TERMS_SCHEMA)
  
  return (
    <>
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
            By accessing or using the Navdhya website (https://www.navdhya.com) and services, you agree to be bound by these Terms of Service and all applicable laws and regulations. If you do not agree with any of these terms, you are prohibited from using or accessing this site and our services.
          </p>
          <p className="mt-3 text-muted-foreground">
            These terms apply to all visitors, users, and others who access or use our services. We reserve the right to update or change these Terms at any time without prior notice. Your continued use of the service after we post any modifications to the Terms constitutes acceptance of those changes.
          </p>
        </section>

        <section aria-labelledby="service-description">
          <h2 id="service-description" className="font-serif text-2xl">
            Description of Service
          </h2>
          <p className="mt-3 text-muted-foreground">
            Navdhya provides a platform for booking authentic Hindu rituals, pujas, and spiritual wellness services performed by certified pandits. Our services include:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Online and in-person puja bookings</li>
            <li>Astrological consultations and kundli analysis</li>
            <li>Sacred item sales (samagri, yantras, rudraksha)</li>
            <li>Spiritual education and guidance</li>
            <li>Vastu and muhurat consultations</li>
          </ul>
          <p className="mt-3 text-muted-foreground">
            We act as an intermediary between customers and pandits. We do not perform rituals ourselves but facilitate connections with qualified professionals.
          </p>
        </section>

        <section aria-labelledby="user-responsibilities">
          <h2 id="user-responsibilities" className="font-serif text-2xl">
            User Responsibilities
          </h2>
          <p className="mt-3 text-muted-foreground">
            As a user of our services, you agree to:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Provide accurate, current, and complete information</li>
            <li>Maintain the security of your account and password</li>
            <li>Use the services only for lawful purposes</li>
            <li>Not interfere with or disrupt the services or servers</li>
            <li>Not use the services to transmit any harmful or malicious content</li>
            <li>Respect the rights and dignity of pandits and other users</li>
          </ul>
        </section>

        <section aria-labelledby="booking-payment">
          <h2 id="booking-payment" className="font-serif text-2xl">
            Booking and Payment Terms
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Service Booking
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                All bookings are subject to pandit availability. We will confirm your booking within 24 hours of receipt. Bookings made less than 48 hours before the scheduled service may be subject to additional charges or may not be available.
              </p>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Payment Terms
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Payment is required at the time of booking unless otherwise specified. We accept payments through secure third-party processors. All fees are quoted in Indian Rupees (INR).
              </p>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Taxes
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                All fees displayed on our platform exclude applicable taxes. Taxes will be added to your total at checkout as required by law.
              </p>
            </div>
          </div>
        </section>

        <section aria-labelledby="cancellation-refund">
          <h2 id="cancellation-refund" className="font-serif text-2xl">
            Cancellation and Refund Policy
          </h2>
          <p className="mt-3 text-muted-foreground">
            Our cancellation and refund policy is detailed in our separate Cancellation & Refund Policy document. Please refer to that document for specific terms regarding cancellations and refunds.
          </p>
          <p className="mt-3 text-muted-foreground">
            Generally, cancellations made more than 48 hours before the scheduled service may be eligible for a full or partial refund at our discretion. Cancellations made within 48 hours may not be eligible for refunds.
          </p>
        </section>

        <section aria-labelledby="limitation-liability">
          <h2 id="limitation-liability" className="font-serif text-2xl">
            Limitation of Liability
          </h2>
          <p className="mt-3 text-muted-foreground">
            To the maximum extent permitted by law, Navdhya shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses, resulting from:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Your access to or use of or inability to access or use the services</li>
            <li>Any conduct or content of any third party on the services</li>
            <li>Any content obtained from the services</li>
            <li>Unauthorized access, use, or alteration of your transmissions or content</li>
          </ul>
          <p className="mt-3 text-muted-foreground">
            In no event shall our total liability to you for all damages exceed the amount paid by you, if any, for accessing our services.
          </p>
        </section>

        <section aria-labelledby="intellectual-property">
          <h2 id="intellectual-property" className="font-serif text-2xl">
            Intellectual Property
          </h2>
          <p className="mt-3 text-muted-foreground">
            The service and its original content, features, and functionality are and will remain the exclusive property of Navdhya and its licensors. Our trademarks and trade dress may not be used in connection with any product or service without the prior written consent of Navdhya.
          </p>
          <p className="mt-3 text-muted-foreground">
            All content provided on our platform, including texts, images, audio, video, and other materials, are protected by copyright and other intellectual property laws.
          </p>
        </section>

        <section aria-labelledby="disclaimer">
          <h2 id="disclaimer" className="font-serif text-2xl">
            Disclaimer of Warranties
          </h2>
          <p className="mt-3 text-muted-foreground">
            Our services are provided on an "as is" and "as available" basis. We do not warrant that the services will be uninterrupted, timely, secure, or error-free. We do not warrant that the results obtained from the use of the services will be accurate or reliable.
          </p>
        </section>

        <section aria-labelledby="indemnification">
          <h2 id="indemnification" className="font-serif text-2xl">
            Indemnification
          </h2>
          <p className="mt-3 text-muted-foreground">
            You agree to defend, indemnify, and hold harmless Navdhya and its affiliates, licensors, and service providers, and its and their respective officers, directors, employees, contractors, agents, licensors, suppliers, successors, and assigns from and against any claims, liabilities, damages, judgments, awards, losses, costs, expenses, or fees (including reasonable attorneys' fees) arising out of or relating to your violation of these Terms or your use of the services.
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
            If you have any questions about these Terms, please contact us at:
          </p>
          <p className="mt-3 text-muted-foreground">
            Email: help@navdhya.com<br />
            Phone: +91-7289000377<br />
            Address: Pandav Nagar, New Delhi, 110092
          </p>
        </section>
      </div>
    </>
  )
}
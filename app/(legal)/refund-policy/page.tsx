import { SiteHeader } from "@/components/site-header"
import { SiteFooter } from "@/components/site-footer"
import { SEO_CONFIG, BREADCRUMB_SCHEMA } from "@/lib/seo-config"
import Script from "next/script"

// Add page-specific metadata
export const metadata = {
  title: "Refund Policy | Navdhya",
  description: "Understand Navdhya's refund policy for puja bookings, spiritual services, and product purchases. We ensure a fair and transparent refund process.",
  keywords: "refund policy, money back guarantee, cancellation refund, Navdhya refund, service refund",
  openGraph: {
    title: "Refund Policy | Navdhya",
    description: "Understand Navdhya's refund policy for puja bookings, spiritual services, and product purchases. We ensure a fair and transparent refund process.",
    url: "https://www.navdhya.com/refund-policy",
    siteName: SEO_CONFIG.siteName,
    images: [
      {
        url: SEO_CONFIG.defaultImage,
        width: 1200,
        height: 630,
        alt: "Navdhya Refund Policy",
      },
    ],
    locale: SEO_CONFIG.locale,
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "Refund Policy | Navdhya",
    description: "Understand Navdhya's refund policy for puja bookings, spiritual services, and product purchases. We ensure a fair and transparent refund process.",
    images: [SEO_CONFIG.defaultImage],
    site: SEO_CONFIG.twitter.site,
  },
  alternates: {
    canonical: "https://www.navdhya.com/refund-policy",
  },
}

// Schema markup for the refund policy page
const REFUND_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "WebPage",
  name: "Refund Policy - Navdhya",
  url: "https://www.navdhya.com/refund-policy",
  description: "Understand Navdhya's refund policy for puja bookings, spiritual services, and product purchases. We ensure a fair and transparent refund process.",
  breadcrumb: BREADCRUMB_SCHEMA([
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Refund Policy", url: "https://www.navdhya.com/refund-policy" }
  ])
}

export default function RefundPolicyPage() {
  const refundSchema = JSON.stringify(REFUND_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: refundSchema }}
      />
      <SiteHeader />
      <main className="mx-auto max-w-6xl px-4 py-12 space-y-10 overflow-x-hidden w-full">
        <section aria-labelledby="refund-heading">
          <h1 id="refund-heading" className="font-serif text-3xl">
            Refund Policy
          </h1>
          <p className="mt-3 text-muted-foreground">
            Last Updated: November 1, 2025
          </p>
        </section>

        <section aria-labelledby="commitment">
          <h2 id="commitment" className="font-serif text-2xl">
            Our Commitment
          </h2>
          <p className="mt-3 text-muted-foreground">
            At Navdhya, we are committed to providing exceptional spiritual services and products. Our refund policy is designed to be fair and transparent while respecting the sacred nature of our services.
          </p>
        </section>

        <section aria-labelledby="eligibility">
          <h2 id="eligibility" className="font-serif text-2xl">
            General Refund Eligibility
          </h2>
          <p className="mt-3 text-muted-foreground">
            Refunds are considered based on the following criteria:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Cancellations made more than 48 hours before the scheduled service</li>
            <li>Services not delivered as booked</li>
            <li>Products received damaged or defective</li>
            <li>Technical issues preventing access to digital services</li>
          </ul>
        </section>

        <section aria-labelledby="service-refunds">
          <h2 id="service-refunds" className="font-serif text-2xl">
            Service Booking Refunds
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Full Refund (100%)
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>If cancellation is made more than 7 days before the scheduled service</li>
                <li>If we are unable to provide the booked service due to pandit unavailability</li>
                <li>If the service is not performed as described in the booking</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Partial Refund (50%)
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>If cancellation is made between 3-7 days before the scheduled service</li>
                <li>If the service is performed but with significant deviations from the booking</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                No Refund
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>If cancellation is made less than 48 hours before the scheduled service</li>
                <li>If the customer fails to be present for a home puja without prior notice</li>
                <li>If the service has been fully performed as booked</li>
                <li>If the cancellation is due to customer error in booking details</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="product-refunds">
          <h2 id="product-refunds" className="font-serif text-2xl">
            Product Purchase Refunds
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Physical Products
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Full refund available within 7 days of delivery for damaged or defective items</li>
                <li>Products must be returned in original packaging with all accessories</li>
                <li>Customer responsible for return shipping costs unless product is defective</li>
                <li>No refund for items purchased more than 30 days ago</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Digital Products
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>No refund for digital products once downloaded or accessed</li>
                <li>Refund may be considered if technical issues prevent access</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="special-circumstances">
          <h2 id="special-circumstances" className="font-serif text-2xl">
            Special Circumstances
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Force Majeure Events
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                In cases of natural disasters, government restrictions, or other unforeseeable events that prevent service delivery, we will offer either a full refund or rescheduling at the customer's preference.
              </p>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Pandit Unavailability
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                If a booked pandit becomes unavailable due to illness or emergency, we will either provide a full refund or arrange an alternative qualified pandit at no additional cost.
              </p>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Custom Services
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Custom or personalized services (such as specific yantras or personalized consultations) are non-refundable once work has begun.
              </p>
            </div>
          </div>
        </section>

        <section aria-labelledby="refund-process">
          <h2 id="refund-process" className="font-serif text-2xl">
            Refund Process
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Request Process
              </h3>
              <ol className="mt-3 text-sm text-muted-foreground list-decimal list-inside">
                <li>Contact our customer support team within the specified timeframes</li>
                <li>Provide booking details and reason for refund request</li>
                <li>Submit any required documentation (photos of damaged items, etc.)</li>
                <li>Our team will review and respond within 3 business days</li>
              </ol>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Processing Timeline
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Refund approvals are processed within 5 business days</li>
                <li>Refunds to original payment method typically appear within 7-14 business days</li>
                <li>Bank transfer refunds may take up to 21 business days</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="non-refundable">
          <h2 id="non-refundable" className="font-serif text-2xl">
            Non-Refundable Items
          </h2>
          <p className="mt-3 text-muted-foreground">
            The following are generally non-refundable:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Services already performed</li>
            <li>Custom or personalized items once production begins</li>
            <li>Digital downloads after access</li>
            <li>Offerings or donations made to temples or charitable causes</li>
            <li>Booking fees for special arrangements</li>
          </ul>
        </section>

        <section aria-labelledby="disputes">
          <h2 id="disputes" className="font-serif text-2xl">
            Contact for Disputes
          </h2>
          <p className="mt-3 text-muted-foreground">
            If you have any questions about our refund policy or need to initiate a refund request, please contact us:
          </p>
          <address className="mt-3 not-italic text-muted-foreground">
            Navdhya Ritual Services<br />
            Pandav Nagar<br />
            Delhi 110092<br />
            India<br />
            Email: help@navdhya.com<br />
            Phone: +91-7289000377<br />
            Customer Support Hours: Monday-Sunday, 9:00 AM - 9:00 PM IST
          </address>
          <p className="mt-3 text-muted-foreground">
            For formal disputes, you may also contact our Grievance Officer whose details are available in our Contact & Grievance Officer page.
          </p>
        </section>
      </main>
      <SiteFooter />
    </>
  )
}
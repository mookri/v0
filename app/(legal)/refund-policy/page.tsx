import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"

// Add page-specific metadata
export const metadata = seoManager.generateMetadata({
  title: "Refund Policy",
  description: "Understand Navdhya's refund policy for puja bookings, spiritual services, and product purchases. We ensure a fair and transparent refund process.",
  path: "/refund-policy",
  keywords: ["refund policy", "money back guarantee", "cancellation refund", "Navdhya refund", "service refund"]
})

// Schema markup for the refund policy page
const REFUND_SCHEMA = seoManager.generateSchema({
  type: "WebPage",
  name: "Refund Policy - Navdhya",
  description: "Understand Navdhya's refund policy for puja bookings, spiritual services, and product purchases. We ensure a fair and transparent refund process.",
  path: "/refund-policy",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Refund Policy", url: "https://www.navdhya.com/refund-policy" }
  ]
})

export default function RefundPolicyPage() {
  const refundSchema = JSON.stringify(REFUND_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: refundSchema }}
      />
      <div className="mx-auto max-w-6xl px-4 py-12 space-y-10 overflow-x-hidden w-full">
        <section aria-labelledby="refund-heading">
          <h1 id="refund-heading" className="font-serif text-3xl">
            Refund Policy
          </h1>
          <p className="mt-3 text-muted-foreground">
            Last Updated: November 1, 2025
          </p>
        </section>

        <section aria-labelledby="overview">
          <h2 id="overview" className="font-serif text-2xl">
            Overview
          </h2>
          <p className="mt-3 text-muted-foreground">
            Navdhya is committed to providing you with high-quality spiritual services. If for any reason you are not completely satisfied with our services, we're here to help. This refund policy outlines the terms and conditions under which refunds may be issued for our services.
          </p>
        </section>

        <section aria-labelledby="eligibility">
          <h2 id="eligibility" className="font-serif text-2xl">
            Refund Eligibility
          </h2>
          <p className="mt-3 text-muted-foreground">
            Refunds may be issued in the following circumstances:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Service not delivered as booked</li>
            <li>Significant deviation from the agreed service specifications</li>
            <li>Technical issues preventing service delivery</li>
            <li>Pandit cancellation with insufficient notice</li>
            <li>Service quality issues reported within 24 hours of completion</li>
          </ul>
        </section>

        <section aria-labelledby="non-refundable">
          <h2 id="non-refundable" className="font-serif text-2xl">
            Non-Refundable Situations
          </h2>
          <p className="mt-3 text-muted-foreground">
            The following situations are generally not eligible for refunds:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Change of mind after service completion</li>
            <li>Personal dissatisfaction with spiritual outcomes</li>
            <li>Weather-related postponements for outdoor services</li>
            <li>User no-show without prior notice</li>
            <li>Requests made more than 7 days after service completion</li>
          </ul>
        </section>

        <section aria-labelledby="cancellation-policy">
          <h2 id="cancellation-policy" className="font-serif text-2xl">
            Cancellation Policy
          </h2>
          <p className="mt-3 text-muted-foreground">
            Our cancellation policy is as follows:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li><strong>More than 48 hours before service:</strong> Full refund available</li>
            <li><strong>24-48 hours before service:</strong> 50% refund available</li>
            <li><strong>Less than 24 hours before service:</strong> No refund, but rescheduling may be possible</li>
            <li><strong>No-show:</strong> No refund will be issued</li>
          </ul>
        </section>

        <section aria-labelledby="refund-process">
          <h2 id="refund-process" className="font-serif text-2xl">
            Refund Process
          </h2>
          <p className="mt-3 text-muted-foreground">
            To request a refund, please follow these steps:
          </p>
          <ol className="mt-3 text-sm text-muted-foreground list-decimal list-inside">
            <li>Contact our customer support team within the specified time frame</li>
            <li>Provide details of your booking and reason for refund request</li>
            <li>Our team will review your request and respond within 2 business days</li>
            <li>If approved, refunds will be processed within 7-10 business days</li>
          </ol>
        </section>

        <section aria-labelledby="refund-method">
          <h2 id="refund-method" className="font-serif text-2xl">
            Refund Method
          </h2>
          <p className="mt-3 text-muted-foreground">
            Refunds will be issued using the same payment method used for the original transaction. If the original payment method is no longer available, we may issue the refund via bank transfer or other mutually agreed method.
          </p>
        </section>

        <section aria-labelledby="special-circumstances">
          <h2 id="special-circumstances" className="font-serif text-2xl">
            Special Circumstances
          </h2>
          <p className="mt-3 text-muted-foreground">
            Navdhya reserves the right to make exceptions to this policy in cases of genuine hardship or extenuating circumstances. Each case will be reviewed individually by our customer service team.
          </p>
        </section>

        <section aria-labelledby="disputes">
          <h2 id="disputes" className="font-serif text-2xl">
            Dispute Resolution
          </h2>
          <p className="mt-3 text-muted-foreground">
            If you have any issues with our refund process, please contact our customer service team first. We are committed to resolving disputes amicably and promptly. If we cannot reach a resolution, you may escalate the matter to the appropriate consumer protection authority.
          </p>
        </section>

        <section aria-labelledby="policy-changes">
          <h2 id="policy-changes" className="font-serif text-2xl">
            Changes to This Policy
          </h2>
          <p className="mt-3 text-muted-foreground">
            We may update our Refund Policy from time to time. We will notify you of any changes by posting the new Refund Policy on this page and updating the "Last Updated" date.
          </p>
        </section>

        <section aria-labelledby="contact-us">
          <h2 id="contact-us" className="font-serif text-2xl">
            Contact Us
          </h2>
          <p className="mt-3 text-muted-foreground">
            If you have any questions about our Refund Policy, please contact us at:
          </p>
          <div className="mt-3 text-muted-foreground">
            <p><strong>Email:</strong> help@navdhya.com</p>
            <p><strong>Phone:</strong> +91-7289000377</p>
            <p><strong>Address:</strong> Pandav Nagar, Delhi 110092</p>
          </div>
        </section>
      </div>
    </>
  )
}
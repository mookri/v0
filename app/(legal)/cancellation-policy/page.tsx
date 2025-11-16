import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"

// Add page-specific metadata
export const metadata = seoManager.generateMetadata({
  title: "Cancellation Policy",
  description: "Learn about Navdhya's cancellation policy for puja bookings and spiritual services. Understand the terms and conditions for canceling your bookings.",
  path: "/cancellation-policy",
  keywords: ["cancellation policy", "booking cancellation", "service cancellation", "Navdhya cancellation", "puja cancellation"]
})

// Schema markup for the cancellation policy page
const CANCELLATION_SCHEMA = seoManager.generateSchema({
  type: "WebPage",
  name: "Cancellation Policy - Navdhya",
  description: "Learn about Navdhya's cancellation policy for puja bookings and spiritual services. Understand the terms and conditions for canceling your bookings.",
  path: "/cancellation-policy",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Cancellation Policy", url: "https://www.navdhya.com/cancellation-policy" }
  ]
})

export default function CancellationPolicyPage() {
  const cancellationSchema = JSON.stringify(CANCELLATION_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: cancellationSchema }}
      />
      <main className="mx-auto max-w-6xl px-4 py-12 space-y-10 overflow-x-hidden w-full">
        <section aria-labelledby="cancellation-heading">
          <h1 id="cancellation-heading" className="font-serif text-3xl">
            Cancellation Policy
          </h1>
          <p className="mt-3 text-muted-foreground">
            Last Updated: November 1, 2025
          </p>
        </section>

        <section aria-labelledby="approach">
          <h2 id="approach" className="font-serif text-2xl">
            Our Approach to Cancellations
          </h2>
          <p className="mt-3 text-muted-foreground">
            At Navdhya, we understand that circumstances may require you to cancel your bookings. Our cancellation policy is designed to be fair to both our customers and our pandits while respecting the sacred nature of our services.
          </p>
          <p className="mt-3 text-muted-foreground">
            This policy applies to all bookings made through our platform for puja services, astrological consultations, and other spiritual services.
          </p>
        </section>

        <section aria-labelledby="timeframes">
          <h2 id="timeframes" className="font-serif text-2xl">
            Cancellation Timeframes
          </h2>
          <div className="mt-3 space-y-4">
            <div className="border-l-4 border-[#0E3F2E] pl-4 py-1">
              <h3 className="font-serif text-xl">
                7+ Days Before Service
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Full cancellation with 100% refund. Pandits can be reassigned without disruption.
              </p>
            </div>
            <div className="border-l-4 border-[#0E3F2E] pl-4 py-1">
              <h3 className="font-serif text-xl">
                3-7 Days Before Service
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Cancellation with 50% refund. Pandit scheduling may be affected.
              </p>
            </div>
            <div className="border-l-4 border-red-600 pl-4 py-1">
              <h3 className="font-serif text-xl">
                Less Than 48 Hours Before Service
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                No cancellation possible. No refund will be issued. Pandit scheduling is confirmed.
              </p>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Special Circumstances
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Emergency cancellations due to illness or family emergencies may be considered on a case-by-case basis. Documentation may be required.
              </p>
            </div>
          </div>
        </section>

        <section aria-labelledby="how-to-cancel">
          <h2 id="how-to-cancel" className="font-serif text-2xl">
            How to Cancel a Booking
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Online Cancellation
              </h3>
              <ol className="mt-3 text-sm text-muted-foreground list-decimal list-inside">
                <li>Log into your account on our website or app</li>
                <li>Navigate to "My Bookings"</li>
                <li>Select the booking you wish to cancel</li>
                <li>Click "Cancel Booking" and follow the prompts</li>
                <li>Receive confirmation email with cancellation details</li>
              </ol>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Phone/Email Cancellation
              </h3>
              <ol className="mt-3 text-sm text-muted-foreground list-decimal list-inside">
                <li>Contact our customer support team at +91-7289000377</li>
                <li>Provide your booking reference number and reason for cancellation</li>
                <li>Our team will process your cancellation request</li>
                <li>Receive confirmation via email or phone</li>
              </ol>
            </div>
          </div>
        </section>

        <section aria-labelledby="pandit-cancellations">
          <h2 id="pandit-cancellations" className="font-serif text-2xl">
            Pandit-Specific Cancellations
          </h2>
          <p className="mt-3 text-muted-foreground">
            For bookings with specific pandits:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Cancellations more than 7 days before: Full refund, pandit can be reassigned</li>
            <li>Cancellations 3-7 days before: 50% refund, pandit compensation may apply</li>
            <li>Cancellations less than 48 hours: No refund, pandit compensation applies</li>
          </ul>
          <p className="mt-3 text-muted-foreground">
            Special arrangements with specific pandits may have different cancellation terms which will be disclosed at booking.
          </p>
        </section>

        <section aria-labelledby="festival-bookings">
          <h2 id="festival-bookings" className="font-serif text-2xl">
            Festival and Auspicious Date Bookings
          </h2>
          <p className="mt-3 text-muted-foreground">
            Bookings for festivals and auspicious dates (Muhurat) have stricter cancellation policies due to their limited availability:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Cancellations more than 14 days before: Full refund</li>
            <li>Cancellations 7-14 days before: 75% refund</li>
            <li>Cancellations 3-7 days before: 50% refund</li>
            <li>Cancellations less than 72 hours: No refund</li>
          </ul>
        </section>

        <section aria-labelledby="custom-services">
          <h2 id="custom-services" className="font-serif text-2xl">
            Custom Service Cancellations
          </h2>
          <p className="mt-3 text-muted-foreground">
            Custom or personalized services (such as specific yantras, personalized consultations, or special arrangements) have the following cancellation terms:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>If canceled before work begins: Full refund minus administrative fee</li>
            <li>If canceled after work begins: No refund</li>
            <li>Completed custom services: No cancellation possible</li>
          </ul>
        </section>

        <section aria-labelledby="no-show">
          <h2 id="no-show" className="font-serif text-2xl">
            No-Show Policy
          </h2>
          <p className="mt-3 text-muted-foreground">
            If you do not show up for a scheduled service without prior cancellation notice:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Home pujas: Full charge applies, no refund</li>
            <li>Temple pujas: Full charge applies, no refund</li>
            <li>Online consultations: Full charge applies, no refund</li>
            <li>Repeated no-shows may result in account suspension</li>
          </ul>
        </section>

        <section aria-labelledby="pandit-cancellation-policy">
          <h2 id="pandit-cancellation-policy" className="font-serif text-2xl">
            Pandit Cancellation Policy
          </h2>
          <p className="mt-3 text-muted-foreground">
            In the unlikely event that a pandit cancels a booking:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>We will offer a full refund or rescheduling at your convenience</li>
            <li>We will attempt to provide an alternative qualified pandit</li>
            <li>If you prefer, we will provide a 10% discount on your next booking</li>
          </ul>
        </section>

        <section aria-labelledby="contact-info">
          <h2 id="contact-info" className="font-serif text-2xl">
            Contact Information
          </h2>
          <p className="mt-3 text-muted-foreground">
            For questions about our cancellation policy or to initiate a cancellation:
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
            For formal disputes, please refer to our Contact & Grievance Officer page.
          </p>
        </section>
      </main>
    </>
  )
}
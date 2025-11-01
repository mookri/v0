import { SiteHeader } from "@/components/site-header"
import { SiteFooter } from "@/components/site-footer"
import { SEO_CONFIG, BREADCRUMB_SCHEMA } from "@/lib/seo-config"
import Script from "next/script"

// Add page-specific metadata
export const metadata = {
  title: "User Agreement for Pandits | Navdhya",
  description: "Terms and conditions for pandits joining the Navdhya platform. Understand your rights and responsibilities as a spiritual service provider.",
  keywords: "pandit agreement, pandit terms, spiritual service provider agreement, Navdhya pandit contract",
  openGraph: {
    title: "User Agreement for Pandits | Navdhya",
    description: "Terms and conditions for pandits joining the Navdhya platform. Understand your rights and responsibilities as a spiritual service provider.",
    url: "https://www.navdhya.com/legal/pandit-agreement",
    siteName: SEO_CONFIG.siteName,
    images: [
      {
        url: SEO_CONFIG.defaultImage,
        width: 1200,
        height: 630,
        alt: "Navdhya Pandit Agreement",
      },
    ],
    locale: SEO_CONFIG.locale,
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "User Agreement for Pandits | Navdhya",
    description: "Terms and conditions for pandits joining the Navdhya platform. Understand your rights and responsibilities as a spiritual service provider.",
    images: [SEO_CONFIG.defaultImage],
    site: SEO_CONFIG.twitter.site,
  },
  alternates: {
    canonical: "https://www.navdhya.com/legal/pandit-agreement",
  },
}

// Schema markup for the pandit agreement page
const PANDIT_AGREEMENT_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "WebPage",
  name: "User Agreement for Pandits - Navdhya",
  url: "https://www.navdhya.com/legal/pandit-agreement",
  description: "Terms and conditions for pandits joining the Navdhya platform. Understand your rights and responsibilities as a spiritual service provider.",
  breadcrumb: BREADCRUMB_SCHEMA([
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Legal", url: "https://www.navdhya.com/legal" },
    { name: "User Agreement for Pandits", url: "https://www.navdhya.com/legal/pandit-agreement" }
  ])
}

export default function PanditAgreementPage() {
  const panditAgreementSchema = JSON.stringify(PANDIT_AGREEMENT_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: panditAgreementSchema }}
      />
      <SiteHeader />
      <main className="mx-auto max-w-6xl px-4 py-12 space-y-10 overflow-x-hidden w-full">
        <section aria-labelledby="agreement-heading">
          <h1 id="agreement-heading" className="font-serif text-3xl">
            User Agreement for Pandits
          </h1>
          <p className="mt-3 text-muted-foreground">
            This User Agreement for Pandits ("Agreement") governs the relationship between Navdhya Ritual Services ("Navdhya," "we," "us," or "our") and independent spiritual service providers ("Pandits," "you," or "your") who use our platform to connect with customers seeking Hindu ritual services.
          </p>
          <p className="mt-3 text-muted-foreground">
            Last Updated: November 1, 2025
          </p>
        </section>

        <section aria-labelledby="acceptance">
          <h2 id="acceptance" className="font-serif text-2xl">
            Acceptance of Terms
          </h2>
          <p className="mt-3 text-muted-foreground">
            By registering as a Pandit on the Navdhya platform and accepting bookings through our system, you agree to be bound by this Agreement and all applicable laws and regulations. If you do not agree with any of these terms, you may not register or provide services through our platform.
          </p>
          <p className="mt-3 text-muted-foreground">
            This Agreement applies to all Pandits who use our platform to offer spiritual services, including but not limited to puja performances, astrological consultations, and Vedic rituals.
          </p>
        </section>

        <section aria-labelledby="services">
          <h2 id="services" className="font-serif text-2xl">
            Services Provided by Pandits
          </h2>
          <p className="mt-3 text-muted-foreground">
            As a Pandit on our platform, you may offer the following services:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Performance of Hindu rituals and pujas at customer premises or temples</li>
            <li>Astrological consultations including kundli analysis and muhurat determination</li>
            <li>Vastu consultation services</li>
            <li>Spiritual guidance and educational sessions</li>
            <li>Custom ritual creation and performance</li>
            <li>Participation in festival celebrations and special occasions</li>
          </ul>
          <p className="mt-3 text-muted-foreground">
            You agree to perform all services with professionalism, authenticity, and in accordance with traditional Hindu practices and customs.
          </p>
        </section>

        <section aria-labelledby="pandit-obligations">
          <h2 id="pandit-obligations" className="font-serif text-2xl">
            Pandit Obligations and Responsibilities
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Professional Conduct
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Perform all services with dedication and authenticity</li>
                <li>Maintain the highest standards of Vedic knowledge and practice</li>
                <li>Respect all customers regardless of their background or beliefs</li>
                <li>Arrive on time and prepared for all appointments</li>
                <li>Maintain appropriate attire and hygiene standards</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Quality Standards
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Use authentic mantras and chants as prescribed by tradition</li>
                <li>Follow proper procedures for all rituals and ceremonies</li>
                <li>Provide accurate information about rituals and their significance</li>
                <li>Respect customer property and leave premises clean</li>
                <li>Communicate clearly and honestly about services and capabilities</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Ethical Standards
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Refrain from soliciting additional payments outside of agreed pricing</li>
                <li>Avoid making false promises about ritual outcomes</li>
                <li>Maintain confidentiality of customer information</li>
                <li>Respect customer privacy and personal boundaries</li>
                <li>Comply with all applicable laws and regulations</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="booking-process">
          <h2 id="booking-process" className="font-serif text-2xl">
            Booking and Scheduling Process
          </h2>
          <p className="mt-3 text-muted-foreground">
            All bookings must be processed through the Navdhya platform:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Accept bookings only through our official booking system</li>
            <li>Confirm availability for all requested services within 24 hours</li>
            <li>Communicate any scheduling conflicts or issues immediately</li>
            <li>Update your availability status regularly in the system</li>
            <li>Provide accurate information about service requirements and preparations</li>
          </ul>
          <p className="mt-3 text-muted-foreground">
            You agree to honor all confirmed bookings and provide the agreed-upon services with professionalism and dedication.
          </p>
        </section>

        <section aria-labelledby="compensation">
          <h2 id="compensation" className="font-serif text-2xl">
            Compensation and Payment Terms
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Payment Processing
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>All payments will be processed through Navdhya's secure payment system</li>
                <li>Direct payment requests from customers are strictly prohibited</li>
                <li>Payments will be disbursed on a weekly basis for completed services</li>
                <li>Platform fees will be deducted as specified in the fee schedule</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Pricing and Fees
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Service pricing must be set according to our established guidelines</li>
                <li>Any additional charges must be pre-approved by Navdhya</li>
                <li>Special pricing for bulk services or packages requires approval</li>
                <li>Discounts or promotions must be coordinated through our system</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Cancellations and Refunds
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Follow our established cancellation policy for all bookings</li>
                <li>Report any customer cancellation requests through the platform</li>
                <li>Cooperate with our refund process when required</li>
                <li>No-shows will result in forfeiture of payment</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="intellectual-property">
          <h2 id="intellectual-property" className="font-serif text-2xl">
            Intellectual Property Rights
          </h2>
          <p className="mt-3 text-muted-foreground">
            As a Pandit on our platform, you retain ownership of your original work:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>You maintain rights to your personal interpretations and teachings</li>
            <li>Traditional mantras and rituals are in the public domain</li>
            <li>Original content you create for educational purposes is yours</li>
            <li>Recordings or materials created specifically for Navdhya may have different terms</li>
          </ul>
          <p className="mt-3 text-muted-foreground">
            You agree not to use copyrighted materials without proper authorization and to respect Navdhya's intellectual property rights in the platform and branding.
          </p>
        </section>

        <section aria-labelledby="confidentiality">
          <h2 id="confidentiality" className="font-serif text-2xl">
            Confidentiality and Privacy
          </h2>
          <p className="mt-3 text-muted-foreground">
            You agree to maintain strict confidentiality regarding:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Customer personal information and family details</li>
            <li>Specifics of rituals performed for individual customers</li>
            <li>Financial information and payment details</li>
            <li>Internal business practices and proprietary information</li>
            <li>Any other sensitive information shared in confidence</li>
          </ul>
          <p className="mt-3 text-muted-foreground">
            Breach of confidentiality may result in immediate termination of your account and legal action.
          </p>
        </section>

        <section aria-labelledby="term-termination">
          <h2 id="term-termination" className="font-serif text-2xl">
            Term and Termination
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Agreement Term
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                This Agreement begins when you register as a Pandit and continues indefinitely until terminated by either party.
              </p>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Termination by Navdhya
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                We may terminate this Agreement immediately for:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Breach of terms or violation of policies</li>
                <li>Consistent negative customer feedback</li>
                <li>Fraudulent or unethical behavior</li>
                <li>Failure to perform services as agreed</li>
                <li>Legal or regulatory violations</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Termination by Pandit
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                You may terminate this Agreement by providing 30 days written notice. You must complete all confirmed bookings before deactivation.
              </p>
            </div>
          </div>
        </section>

        <section aria-labelledby="liability">
          <h2 id="liability" className="font-serif text-2xl">
            Liability and Indemnification
          </h2>
          <p className="mt-3 text-muted-foreground">
            You agree to indemnify and hold harmless Navdhya from any claims, damages, or losses arising from:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Your performance of services or failure to perform</li>
            <li>Breach of this Agreement or applicable laws</li>
            <li>Professional negligence or misconduct</li>
            <li>Infringement of third-party rights</li>
            <li>Any disputes with customers or third parties</li>
          </ul>
          <p className="mt-3 text-muted-foreground">
            Our liability to you is limited to the fees paid for services rendered through our platform.
          </p>
        </section>

        <section aria-labelledby="dispute-resolution">
          <h2 id="dispute-resolution" className="font-serif text-2xl">
            Dispute Resolution
          </h2>
          <p className="mt-3 text-muted-foreground">
            Any disputes arising from this Agreement shall be resolved through:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Initial negotiation between parties</li>
            <li>Mediation through a mutually agreed mediator</li>
            <li>Arbitration in Delhi, India if mediation fails</li>
            <li>Governed by Indian law and jurisdiction</li>
          </ul>
        </section>

        <section aria-labelledby="modifications">
          <h2 id="modifications" className="font-serif text-2xl">
            Modifications to Agreement
          </h2>
          <p className="mt-3 text-muted-foreground">
            We reserve the right to modify this Agreement at any time. Changes will be communicated through:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Email notification to registered Pandits</li>
            <li>Updates posted on our website and platform</li>
            <li>Notification through the Pandit dashboard</li>
            <li>Continued use constitutes acceptance of changes</li>
          </ul>
        </section>

        <section aria-labelledby="contact">
          <h2 id="contact" className="font-serif text-2xl">
            Contact Information
          </h2>
          <p className="mt-3 text-muted-foreground">
            For questions about this Agreement or to report issues, contact:
          </p>
          <address className="mt-3 not-italic text-muted-foreground">
            Navdhya Pandit Relations Team<br />
            Email: pandits@navdhya.com<br />
            Phone: +91-7289000377<br />
            Postal Address: Navdhya Ritual Services, Pandav Nagar, Delhi 110092, India
          </address>
        </section>
      </main>
      <SiteFooter />
    </>
  )
}
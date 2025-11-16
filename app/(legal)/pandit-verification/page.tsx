import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"

// Add page-specific metadata
export const metadata = seoManager.generateMetadata({
  title: "Pandit Verification & Code of Conduct",
  description: "Learn about Navdhya's rigorous pandit verification process and code of conduct. We ensure all our pandits are thoroughly vetted and adhere to the highest professional standards.",
  path: "/legal/pandit-verification",
  keywords: ["pandit verification", "pandit code of conduct", "certified pandits", "pandit background check", "Navdhya pandits"]
})

// Schema markup for the pandit verification page
const PANDIT_VERIFICATION_SCHEMA = seoManager.generateSchema({
  type: "WebPage",
  name: "Pandit Verification & Code of Conduct - Navdhya",
  description: "Learn about Navdhya's rigorous pandit verification process and code of conduct. We ensure all our pandits are thoroughly vetted and adhere to the highest professional standards.",
  path: "/legal/pandit-verification",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Legal", url: "https://www.navdhya.com/legal" },
    { name: "Pandit Verification & Code of Conduct", url: "https://www.navdhya.com/legal/pandit-verification" }
  ]
})

export default function PanditVerificationPage() {
  const panditVerificationSchema = JSON.stringify(PANDIT_VERIFICATION_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: panditVerificationSchema }}
      />
      <main className="mx-auto max-w-6xl px-4 py-12 space-y-10 overflow-x-hidden w-full">
        <section aria-labelledby="verification-heading">
          <h1 id="verification-heading" className="font-serif text-3xl">
            Pandit Verification & Code of Conduct
          </h1>
          <p className="mt-3 text-muted-foreground">
            At Navdhya, we believe in connecting our customers with only the most qualified and trustworthy pandits. Our rigorous verification process ensures that every pandit on our platform meets our high standards for expertise, professionalism, and ethical conduct.
          </p>
        </section>

        <section aria-labelledby="verification-process">
          <h2 id="verification-process" className="font-serif text-2xl">
            Our Verification Process
          </h2>
          <p className="mt-3 text-muted-foreground">
            Every pandit who joins our platform undergoes a comprehensive verification process to ensure they meet our quality standards:
          </p>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Educational Qualifications
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                We verify all educational credentials and certifications from recognized Vedic institutions. Our pandits must have formal training in Hindu scriptures, rituals, and traditions.
              </p>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Experience Documentation
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                We require detailed documentation of professional experience, including references from previous employers, temples, or clients. Our pandits typically have a minimum of 5 years of practical experience.
              </p>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Skill Assessment
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                All pandits undergo practical assessments to evaluate their knowledge of rituals, chanting accuracy, and ability to perform ceremonies according to traditional guidelines.
              </p>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Language Proficiency
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                We assess language skills to ensure clear communication with clients. Our pandits are fluent in at least one Indian language and English for broader accessibility.
              </p>
            </div>
          </div>
        </section>

        <section aria-labelledby="background-checks">
          <h2 id="background-checks" className="font-serif text-2xl">
            Background Checks
          </h2>
          <p className="mt-3 text-muted-foreground">
            We conduct thorough background verification to ensure the safety and trust of our customers:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Police verification for criminal record clearance</li>
            <li>Identity verification through government-issued documents</li>
            <li>Address verification through utility bills and local references</li>
            <li>Professional reference checks from previous employers or temples</li>
            <li>Health certification to ensure fitness for performing rituals</li>
          </ul>
        </section>

        <section aria-labelledby="code-of-conduct">
          <h2 id="code-of-conduct" className="font-serif text-2xl">
            Pandit Code of Conduct
          </h2>
          <p className="mt-3 text-muted-foreground">
            All Navdhya pandits are required to adhere to our strict code of conduct which ensures professionalism, respect, and ethical behavior:
          </p>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Professional Integrity
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Perform rituals with complete dedication and authenticity</li>
                <li>Maintain the sanctity and traditions of Hindu practices</li>
                <li>Provide accurate information about rituals and their significance</li>
                <li>Respect all customers regardless of their background or beliefs</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Customer Respect
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Arrive on time and prepared for all appointments</li>
                <li>Maintain appropriate attire and hygiene standards</li>
                <li>Respect customer privacy and personal boundaries</li>
                <li>Communicate clearly and honestly about services and pricing</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Ethical Standards
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Refrain from soliciting additional payments outside of agreed pricing</li>
                <li>Avoid making false promises about ritual outcomes</li>
                <li>Respect customer property and leave premises clean</li>
                <li>Maintain confidentiality of customer information</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="ongoing-evaluation">
          <h2 id="ongoing-evaluation" className="font-serif text-2xl">
            Ongoing Evaluation
          </h2>
          <p className="mt-3 text-muted-foreground">
            Our commitment to quality doesn't end with initial verification:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Regular performance reviews based on customer feedback</li>
            <li>Continuing education requirements to stay updated on traditions</li>
            <li>Periodic re-verification of credentials and background</li>
            <li>Annual code of conduct reaffirmation and training</li>
          </ul>
        </section>

        <section aria-labelledby="customer-assurance">
          <h2 id="customer-assurance" className="font-serif text-2xl">
            Customer Assurance
          </h2>
          <p className="mt-3 text-muted-foreground">
            When you book a pandit through Navdhya, you can be confident that:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>All pandits are verified professionals with proper credentials</li>
            <li>Background checks ensure your safety and security</li>
            <li>Code of conduct adherence guarantees respectful service</li>
            <li>Ongoing evaluation maintains consistent quality standards</li>
            <li>Customer support is available for any concerns or issues</li>
          </ul>
        </section>
      </main>
    </>
  )
}
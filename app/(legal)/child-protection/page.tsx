import { SiteHeader } from "@/components/site-header"
import { SiteFooter } from "@/components/site-footer"
import { SEO_CONFIG, BREADCRUMB_SCHEMA } from "@/lib/seo-config"
import Script from "next/script"

// Add page-specific metadata
export const metadata = {
  title: "Child Protection / Minor Usage Policy | Navdhya",
  description: "Navdhya's commitment to protecting children and minors who access our platform. Learn about our policies for family services and educational content.",
  keywords: "child protection, minor usage policy, family services, children's privacy, online safety, Navdhya child protection",
  openGraph: {
    title: "Child Protection / Minor Usage Policy | Navdhya",
    description: "Navdhya's commitment to protecting children and minors who access our platform. Learn about our policies for family services and educational content.",
    url: "https://www.navdhya.com/legal/child-protection",
    siteName: SEO_CONFIG.siteName,
    images: [
      {
        url: SEO_CONFIG.defaultImage,
        width: 1200,
        height: 630,
        alt: "Navdhya Child Protection Policy",
      },
    ],
    locale: SEO_CONFIG.locale,
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "Child Protection / Minor Usage Policy | Navdhya",
    description: "Navdhya's commitment to protecting children and minors who access our platform. Learn about our policies for family services and educational content.",
    images: [SEO_CONFIG.defaultImage],
    site: SEO_CONFIG.twitter.site,
  },
  alternates: {
    canonical: "https://www.navdhya.com/legal/child-protection",
  },
}

// Schema markup for the child protection page
const CHILD_PROTECTION_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "WebPage",
  name: "Child Protection / Minor Usage Policy - Navdhya",
  url: "https://www.navdhya.com/legal/child-protection",
  description: "Navdhya's commitment to protecting children and minors who access our platform. Learn about our policies for family services and educational content.",
  breadcrumb: BREADCRUMB_SCHEMA([
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Legal", url: "https://www.navdhya.com/legal" },
    { name: "Child Protection / Minor Usage Policy", url: "https://www.navdhya.com/legal/child-protection" }
  ])
}

export default function ChildProtectionPage() {
  const childProtectionSchema = JSON.stringify(CHILD_PROTECTION_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: childProtectionSchema }}
      />
      <SiteHeader />
      <main className="mx-auto max-w-6xl px-4 py-12 space-y-10 overflow-x-hidden w-full">
        <section aria-labelledby="protection-heading">
          <h1 id="protection-heading" className="font-serif text-3xl">
            Child Protection / Minor Usage Policy
          </h1>
          <p className="mt-3 text-muted-foreground">
            This Child Protection / Minor Usage Policy ("Policy") outlines Navdhya Ritual Services' ("Navdhya," "we," "us," or "our") commitment to protecting children and minors who access our platform and services. This policy applies to all users under the age of 18.
          </p>
          <p className="mt-3 text-muted-foreground">
            Last Updated: November 1, 2025
          </p>
        </section>

        <section aria-labelledby="policy-scope">
          <h2 id="policy-scope" className="font-serif text-2xl">
            Scope and Applicability
          </h2>
          <p className="mt-3 text-muted-foreground">
            This policy applies to:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Children and minors accessing our website and mobile applications</li>
            <li>Family services that include children as participants</li>
            <li>Educational content designed for younger audiences</li>
            <li>Community forums and social features with potential minor participation</li>
            <li>Marketing and promotional materials targeting families</li>
          </ul>
          <p className="mt-3 text-muted-foreground">
            While our primary services are intended for adults, we recognize that children may access our platform through family accounts or educational programs.
          </p>
        </section>

        <section aria-labelledby="age-requirements">
          <h2 id="age-requirements" className="font-serif text-2xl">
            Age Requirements and Registration
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Minimum Age for Account Registration
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Our standard services require users to be at least 18 years old:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Account registration requires parental consent for users under 18</li>
                <li>Parental verification is required for minor accounts</li>
                <li>Minors may only access approved educational content</li>
                <li>Booking services require adult supervision and consent</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Parental Consent Requirements
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                For minor users, we require:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Explicit parental or guardian consent for account creation</li>
                <li>Verification of parental identity and relationship</li>
                <li>Agreement to this Child Protection Policy</li>
                <li>Understanding of privacy implications for minors</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="content-guidelines">
          <h2 id="content-guidelines" className="font-serif text-2xl">
            Content Guidelines for Minors
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Age-Appropriate Content
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                We ensure all content accessible to minors is:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Appropriate for the intended age group</li>
                <li>Culturally and religiously sensitive</li>
                <li>Free from harmful or inappropriate material</li>
                <li>Educational and spiritually enriching</li>
                <li>Reviewed by child development experts</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Educational Content Standards
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Our educational materials for children follow these standards:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Simple language and clear explanations</li>
                <li>Interactive and engaging presentation</li>
                <li>Accurate representation of traditions and practices</li>
                <li>Positive values and character development</li>
                <li>Safe and inclusive messaging</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Restricted Content
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                The following content is restricted from minor access:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Advanced ritual practices requiring adult supervision</li>
                <li>Complex astrological interpretations</li>
                <li>Commercial transactions and payment information</li>
                <li>Personal information sharing features</li>
                <li>Social networking and messaging functions</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="family-services">
          <h2 id="family-services" className="font-serif text-2xl">
            Family Services and Participation
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Family Ritual Services
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                When families participate in our services:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Adult family members must book and supervise services</li>
                <li>Children's participation is age-appropriate and supervised</li>
                <li>Special considerations for child safety and comfort</li>
                <li>Clear communication about ritual elements and expectations</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Child-Specific Ceremonies
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                For ceremonies specifically involving children:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Parental consent required for all child-focused rituals</li>
                <li>Trained pandits experienced with children</li>
                <li>Safe and comfortable environment for young participants</li>
                <li>Clear communication with parents about procedures</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="privacy-protection">
          <h2 id="privacy-protection" className="font-serif text-2xl">
            Privacy Protection for Minors
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Data Collection Limitations
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                We limit data collection for minor users:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Minimal personal information required for access</li>
                <li>No collection of sensitive data without parental consent</li>
                <li>Automatic deletion of minor data upon request</li>
                <li>Strict access controls for minor user information</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Parental Rights and Controls
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Parents have the following rights regarding minor accounts:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Access to view their child's account information</li>
                <li>Ability to modify or delete account data</li>
                <li>Control over communication preferences</li>
                <li>Opt-out of educational program participation</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="safety-measures">
          <h2 id="safety-measures" className="font-serif text-2xl">
            Safety and Security Measures
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Online Safety Protocols
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                We implement the following online safety measures:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Secure login and authentication for minor accounts</li>
                <li>Content filtering and age-appropriate restrictions</li>
                <li>Monitoring for inappropriate interactions or content</li>
                <li>Regular security audits of systems handling minor data</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Offline Safety Considerations
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                For in-person services involving children:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Background-checked and trained service providers</li>
                <li>Parental presence required for all child interactions</li>
                <li>Safe and appropriate physical environments</li>
                <li>Clear communication protocols with parents</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="reporting">
          <h2 id="reporting" className="font-serif text-2xl">
            Reporting and Response Procedures
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Concern Reporting
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                We provide multiple channels for reporting safety concerns:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Dedicated email: childprotection@navdhya.com</li>
                <li>24/7 helpline: +91-7289000377</li>
                <li>Anonymous reporting through our website</li>
                <li>Direct contact with customer support</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Response Protocols
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Our response to child protection concerns includes:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Immediate acknowledgment within 2 hours</li>
                <li>Thorough investigation within 24 hours</li>
                <li>Appropriate action within 48 hours</li>
                <li>Follow-up communication with reporting parties</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="training">
          <h2 id="training" className="font-serif text-2xl">
            Staff Training and Awareness
          </h2>
          <p className="mt-3 text-muted-foreground">
            All Navdhya staff and service providers receive training on:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Child protection laws and regulations</li>
            <li>Recognizing signs of abuse or neglect</li>
            <li>Appropriate interactions with minor users</li>
            <li>Reporting procedures and responsibilities</li>
            <li>Cultural sensitivity in family services</li>
          </ul>
        </section>

        <section aria-labelledby="community">
          <h2 id="community" className="font-serif text-2xl">
            Community and Social Responsibility
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Positive Value Promotion
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Our approach to family services emphasizes:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Traditional family values and respect</li>
                <li>Educational enrichment and character development</li>
                <li>Cultural preservation and understanding</li>
                <li>Spiritual growth and mindfulness</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Inclusive Access
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                We ensure our services are accessible to all families:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Multiple language options for content</li>
                <li>Different learning styles and abilities accommodated</li>
                <li>Economic accessibility through various program options</li>
                <li>Cultural sensitivity across diverse communities</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="policy-updates">
          <h2 id="policy-updates" className="font-serif text-2xl">
            Policy Updates and Review
          </h2>
          <p className="mt-3 text-muted-foreground">
            We regularly review and update this policy to:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Reflect changes in child protection laws</li>
            <li>Incorporate best practices in child safety</li>
            <li>Adapt to new technologies and platforms</li>
            <li>Respond to community feedback and concerns</li>
          </ul>
          <p className="mt-3 text-muted-foreground">
            Updates are reviewed by child protection experts and legal counsel.
          </p>
        </section>

        <section aria-labelledby="contact">
          <h2 id="contact" className="font-serif text-2xl">
            Contact Information
          </h2>
          <p className="mt-3 text-muted-foreground">
            For questions about this policy or to report child protection concerns, contact:
          </p>
          <address className="mt-3 not-italic text-muted-foreground">
            Navdhya Child Protection Team<br />
            Email: childprotection@navdhya.com<br />
            Phone: +91-7289000377<br />
            Postal Address: Navdhya Ritual Services, Pandav Nagar, Delhi 110092, India
          </address>
          <p className="mt-3 text-muted-foreground">
            Emergency situations should be reported to local authorities immediately.
          </p>
        </section>
      </main>
      <SiteFooter />
    </>
  )
}
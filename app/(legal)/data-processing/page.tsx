import { SEO_CONFIG, BREADCRUMB_SCHEMA } from "@/lib/seo-config"
import Script from "next/script"

// Add page-specific metadata
export const metadata = {
  title: "Data Processing Addendum | Navdhya",
  description: "Navdhya's Data Processing Addendum for compliance with data protection laws. Learn about our data processing practices and third-party processor relationships.",
  keywords: "data processing, DPA, GDPR compliance, data protection, third-party processors, privacy compliance",
  openGraph: {
    title: "Data Processing Addendum | Navdhya",
    description: "Navdhya's Data Processing Addendum for compliance with data protection laws. Learn about our data processing practices and third-party processor relationships.",
    url: "https://www.navdhya.com/legal/data-processing",
    siteName: SEO_CONFIG.siteName,
    images: [
      {
        url: SEO_CONFIG.defaultImage,
        width: 1200,
        height: 630,
        alt: "Navdhya Data Processing Addendum",
      },
    ],
    locale: SEO_CONFIG.locale,
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "Data Processing Addendum | Navdhya",
    description: "Navdhya's Data Processing Addendum for compliance with data protection laws. Learn about our data processing practices and third-party processor relationships.",
    images: [SEO_CONFIG.defaultImage],
    site: SEO_CONFIG.twitter.site,
  },
  alternates: {
    canonical: "https://www.navdhya.com/legal/data-processing",
  },
}

// Schema markup for the data processing page
const DATA_PROCESSING_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "WebPage",
  name: "Data Processing Addendum - Navdhya",
  url: "https://www.navdhya.com/legal/data-processing",
  description: "Navdhya's Data Processing Addendum for compliance with data protection laws. Learn about our data processing practices and third-party processor relationships.",
  breadcrumb: BREADCRUMB_SCHEMA([
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Legal", url: "https://www.navdhya.com/legal" },
    { name: "Data Processing Addendum", url: "https://www.navdhya.com/legal/data-processing" }
  ])
}

export default function DataProcessingPage() {
  const dataProcessingSchema = JSON.stringify(DATA_PROCESSING_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: dataProcessingSchema }}
      />
      <main className="mx-auto max-w-6xl px-4 py-12 space-y-10 overflow-x-hidden w-full">
        <section aria-labelledby="dpa-heading">
          <h1 id="dpa-heading" className="font-serif text-3xl">
            Data Processing Addendum
          </h1>
          <p className="mt-3 text-muted-foreground">
            This Data Processing Addendum ("Addendum") forms part of the agreement between Navdhya Ritual Services ("Navdhya," "we," "us," or "our") and our customers or partners ("Customer," "you," or "your") regarding the processing of personal data in connection with our services.
          </p>
          <p className="mt-3 text-muted-foreground">
            Last Updated: November 1, 2025
          </p>
        </section>

        <section aria-labelledby="definitions">
          <h2 id="definitions" className="font-serif text-2xl">
            Definitions
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Controller
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                The natural or legal person, public authority, agency or other body which, alone or jointly with others, determines the purposes and means of the processing of personal data.
              </p>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Processor
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                A natural or legal person, public authority, agency or other body which processes personal data on behalf of the controller.
              </p>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Personal Data
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Any information relating to an identified or identifiable natural person ("data subject").
              </p>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Processing
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Any operation or set of operations which is performed on personal data or on sets of personal data, whether or not by automated means.
              </p>
            </div>
          </div>
        </section>

        <section aria-labelledby="roles">
          <h2 id="roles" className="font-serif text-2xl">
            Roles and Responsibilities
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Navdhya as Processor
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                When Navdhya processes personal data on behalf of a customer:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Navdhya acts as a data processor</li>
                <li>The customer acts as the data controller</li>
                <li>Navdhya processes data only as instructed by the controller</li>
                <li>Navdhya maintains records of processing activities</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Navdhya as Controller
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                When Navdhya determines the purposes and means of processing:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Navdhya acts as the data controller</li>
                <li>Customers whose data we process are data subjects</li>
                <li>We are responsible for compliance with data protection laws</li>
                <li>We implement appropriate technical and organizational measures</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="processing-details">
          <h2 id="processing-details" className="font-serif text-2xl">
            Details of Processing
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Subject Matter
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                The subject matter of processing includes:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Provision of ritual and spiritual services</li>
                <li>Customer account management and support</li>
                <li>Booking and scheduling services</li>
                <li>Communication and marketing activities</li>
                <li>Analytics and business intelligence</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Duration
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                The duration of processing is:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>For the term of the customer relationship</li>
                <li>Plus retention period as required by law</li>
                <li>Subject to earlier termination upon request</li>
                <li>Archival for legal and compliance purposes</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Nature and Purpose
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                The nature and purpose of processing include:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Performance of contractual obligations</li>
                <li>Compliance with legal requirements</li>
                <li>Provision of requested services</li>
                <li>Customer communication and support</li>
                <li>Business improvement and analytics</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Types of Personal Data
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Categories of personal data processed:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Contact information (name, email, phone, address)</li>
                <li>Identity verification data (ID documents, photos)</li>
                <li>Service usage and interaction data</li>
                <li>Payment and financial information</li>
                <li>Preferences and communication history</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Categories of Data Subjects
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Individuals whose data is processed:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Customers and service users</li>
                <li>Prospective customers and leads</li>
                <li>Employees and contractors</li>
                <li>Family members and household contacts</li>
                <li>Website visitors and app users</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="processor-obligations">
          <h2 id="processor-obligations" className="font-serif text-2xl">
            Processor Obligations
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Processing Instructions
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Navdhya will process personal data only:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>In accordance with documented instructions</li>
                <li>As required by applicable law</li>
                <li>With prior written consent for new processing</li>
                <li>Subject to this Addendum and privacy policy</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Confidentiality
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Navdhya ensures confidentiality by:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Requiring confidentiality agreements from personnel</li>
                <li>Implementing access controls and authentication</li>
                <li>Restricting data access to authorized individuals</li>
                <li>Providing training on data protection obligations</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Security Measures
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Technical and organizational measures include:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Encryption of data in transit and at rest</li>
                <li>Regular security assessments and audits</li>
                <li>Incident response and breach notification procedures</li>
                <li>Access logging and monitoring systems</li>
                <li>Physical security for data storage facilities</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Sub-processing
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Use of third-party subprocessors requires:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Prior written consent from the controller</li>
                <li>Written contracts with equivalent obligations</li>
                <li>Regular monitoring of subprocessor compliance</li>
                <li>Immediate notification of subprocessor changes</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="controller-rights">
          <h2 id="controller-rights" className="font-serif text-2xl">
            Controller Rights and Obligations
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Data Subject Rights
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                The controller may exercise rights including:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Access to personal data being processed</li>
                <li>Rectification of inaccurate personal data</li>
                <li>Erasure of personal data when appropriate</li>
                <li>Restriction of processing in certain cases</li>
                <li>Data portability in structured formats</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Controller Obligations
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                The controller must:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Provide accurate and lawful personal data</li>
                <li>Ensure appropriate legal basis for processing</li>
                <li>Inform data subjects of processing activities</li>
                <li>Respond to data subject requests appropriately</li>
                <li>Comply with data protection laws and regulations</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="subprocessors">
          <h2 id="subprocessors" className="font-serif text-2xl">
            Third-Party Subprocessors
          </h2>
          <p className="mt-3 text-muted-foreground">
            Navdhya uses the following categories of subprocessors:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Cloud infrastructure providers (AWS, Google Cloud)</li>
            <li>Payment processing services (Razorpay, Stripe)</li>
            <li>Email and communication platforms (SendGrid, Twilio)</li>
            <li>Analytics and marketing tools (Google Analytics, Meta Pixel)</li>
            <li>Customer support and CRM systems (Zendesk, Salesforce)</li>
            <li>Security and monitoring services (DataDog, Sentry)</li>
          </ul>
          <p className="mt-3 text-muted-foreground">
            Current list of subprocessors is available upon request. We will notify controllers of any changes to subprocessors.
          </p>
        </section>

        <section aria-labelledby="data-transfers">
          <h2 id="data-transfers" className="font-serif text-2xl">
            International Data Transfers
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Transfer Mechanisms
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                For international data transfers, we implement:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Standard contractual clauses for EU transfers</li>
                <li>Binding corporate rules for internal transfers</li>
                <li>Adequacy decisions for approved countries</li>
                <li>Appropriate safeguards for high-risk transfers</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Data Localization
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Our data storage and processing locations:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Primary data centers in India</li>
                <li>Backup and disaster recovery in Singapore</li>
                <li>CDN and edge locations globally</li>
                <li>Processing in accordance with local laws</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="assistance">
          <h2 id="assistance" className="font-serif text-2xl">
            Assistance and Cooperation
          </h2>
          <p className="mt-3 text-muted-foreground">
            Navdhya will assist the controller with:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Fulfillment of data subject requests</li>
            <li>Data protection impact assessments</li>
            <li>Prior consultation with supervisory authorities</li>
            <li>Security breach notification and response</li>
            <li>Compliance with data protection obligations</li>
          </ul>
        </section>

        <section aria-labelledby="breach-notification">
          <h2 id="breach-notification" className="font-serif text-2xl">
            Data Breach Notification
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Notification Requirements
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Upon becoming aware of a personal data breach:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Notify the controller without undue delay</li>
                <li>Provide detailed information about the breach</li>
                <li>Include likely consequences and mitigation measures</li>
                <li>Document the breach and response actions</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Response Measures
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Our breach response procedures include:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Immediate containment and investigation</li>
                <li>Assessment of risks to data subjects</li>
                <li>Notification to affected individuals when required</li>
                <li>Reporting to supervisory authorities as needed</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="deletion">
          <h2 id="deletion" className="font-serif text-2xl">
            Data Deletion and Return
          </h2>
          <p className="mt-3 text-muted-foreground">
            Upon termination of services or at the controller's request:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Return or delete all personal data processed</li>
            <li>Certify destruction of copies and backups</li>
            <li>Retain data only as required by law</li>
            <li>Provide written confirmation of deletion</li>
          </ul>
        </section>

        <section aria-labelledby="audit">
          <h2 id="audit" className="font-serif text-2xl">
            Audits and Inspections
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Audit Rights
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                The controller may:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Request information about processing activities</li>
                <li>Conduct audits with reasonable notice</li>
                <li>Engage third-party auditors at their expense</li>
                <li>Review compliance documentation and records</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Compliance Measures
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Navdhya maintains:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Records of processing activities</li>
                <li>Security and compliance documentation</li>
                <li>Training records for personnel</li>
                <li>Incident response and breach logs</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="liability">
          <h2 id="liability" className="font-serif text-2xl">
            Liability and Indemnification
          </h2>
          <p className="mt-3 text-muted-foreground">
            Each party's liability is limited to:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>The amount paid under the agreement in the preceding 12 months</li>
            <li>Direct damages only, excluding indirect or consequential damages</li>
            <li>Indemnification for breaches of data protection obligations</li>
            <li>Compliance with statutory liability limitations</li>
          </ul>
        </section>

        <section aria-labelledby="governing-law">
          <h2 id="governing-law" className="font-serif text-2xl">
            Governing Law and Jurisdiction
          </h2>
          <p className="mt-3 text-muted-foreground">
            This Addendum is governed by:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>The laws of India, without regard to conflict of law principles</li>
            <li>GDPR for processing of EU personal data</li>
            <li>Applicable data protection laws in other jurisdictions</li>
            <li>International data transfer regulations</li>
          </ul>
        </section>

        <section aria-labelledby="contact">
          <h2 id="contact" className="font-serif text-2xl">
            Contact Information
          </h2>
          <p className="mt-3 text-muted-foreground">
            For questions about this Data Processing Addendum or to request information about our data processing practices, contact:
          </p>
          <address className="mt-3 not-italic text-muted-foreground">
            Navdhya Data Protection Officer<br />
            Email: dpo@navdhya.com<br />
            Phone: +91-7289000377<br />
            Postal Address: Navdhya Ritual Services, Pandav Nagar, Delhi 110092, India
          </address>
        </section>
      </main>
    </>
  )
}
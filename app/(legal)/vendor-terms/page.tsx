import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"

// Add page-specific metadata
export const metadata = seoManager.generateMetadata({
  title: "Vendor / Partner Terms",
  description: "Terms and conditions for third-party vendors and partners working with Navdhya. Covers service agreements, responsibilities, and partnership guidelines.",
  path: "/legal/vendor-terms",
  keywords: ["vendor terms", "partner agreement", "third-party services", "service provider terms", "Navdhya partnerships"]
})

// Schema markup for the vendor terms page
const VENDOR_TERMS_SCHEMA = seoManager.generateSchema({
  type: "WebPage",
  name: "Vendor / Partner Terms - Navdhya",
  description: "Terms and conditions for third-party vendors and partners working with Navdhya. Covers service agreements, responsibilities, and partnership guidelines.",
  path: "/legal/vendor-terms",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Legal", url: "https://www.navdhya.com/legal" },
    { name: "Vendor / Partner Terms", url: "https://www.navdhya.com/legal/vendor-terms" }
  ]
})

export default function VendorTermsPage() {
  const vendorTermsSchema = JSON.stringify(VENDOR_TERMS_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: vendorTermsSchema }}
      />
      <main className="mx-auto max-w-6xl px-4 py-12 space-y-10 overflow-x-hidden w-full">
        <section aria-labelledby="vendor-heading">
          <h1 id="vendor-heading" className="font-serif text-3xl">
            Vendor / Partner Terms
          </h1>
          <p className="mt-3 text-muted-foreground">
            These Vendor/Partner Terms ("Agreement") govern the relationship between Navdhya Ritual Services ("Navdhya," "we," "us," or "our") and third-party vendors, suppliers, and partners ("Vendors," "Partners," "you," or "your") who provide goods, services, or content to support our platform and customer offerings.
          </p>
          <p className="mt-3 text-muted-foreground">
            Last Updated: November 1, 2025
          </p>
        </section>

        <section aria-labelledby="scope">
          <h2 id="scope" className="font-serif text-2xl">
            Scope of Agreement
          </h2>
          <p className="mt-3 text-muted-foreground">
            This Agreement applies to all Vendors and Partners who provide goods, services, or content to Navdhya, including but not limited to:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Suppliers of puja samagri and ritual items</li>
            <li>Manufacturers of spiritual products and yantras</li>
            <li>Content creators and educational resource providers</li>
            <li>Technology service providers and platform integrators</li>
            <li>Logistics and fulfillment partners</li>
            <li>Marketing and promotional service providers</li>
            <li>Consulting and professional service providers</li>
          </ul>
        </section>

        <section aria-labelledby="vendor-obligations">
          <h2 id="vendor-obligations" className="font-serif text-2xl">
            Vendor Obligations and Responsibilities
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Quality Standards
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Provide goods and services that meet industry standards</li>
                <li>Ensure all products comply with applicable safety regulations</li>
                <li>Maintain consistent quality in all deliverables</li>
                <li>Provide accurate specifications and product information</li>
                <li>Address quality issues promptly and effectively</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Professional Conduct
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Conduct business ethically and professionally</li>
                <li>Respect confidential information shared by Navdhya</li>
                <li>Comply with all applicable laws and regulations</li>
                <li>Maintain appropriate licenses and certifications</li>
                <li>Provide qualified personnel for service delivery</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Performance Requirements
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Meet agreed-upon delivery schedules and timelines</li>
                <li>Communicate proactively about potential delays or issues</li>
                <li>Provide regular status updates as requested</li>
                <li>Respond to inquiries and support requests promptly</li>
                <li>Maintain adequate capacity for agreed service levels</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="product-requirements">
          <h2 id="product-requirements" className="font-serif text-2xl">
            Product and Service Requirements
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Puja Samagri and Ritual Items
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Source materials ethically and sustainably</li>
                <li>Ensure authenticity of traditional ingredients</li>
                <li>Provide proper storage and handling instructions</li>
                <li>Include complete ingredient lists and usage guidelines</li>
                <li>Package products securely to prevent damage</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Spiritual Products and Yantras
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Ensure religious and cultural appropriateness</li>
                <li>Provide accurate descriptions and specifications</li>
                <li>Include proper authentication and blessing certificates</li>
                <li>Maintain quality standards for sacred materials</li>
                <li>Respect traditional crafting methods and practices</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Digital Content and Educational Materials
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Ensure accuracy and authenticity of information</li>
                <li>Respect copyright and intellectual property rights</li>
                <li>Provide content in agreed formats and specifications</li>
                <li>Include proper attribution and source information</li>
                <li>Maintain cultural sensitivity and religious accuracy</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="compliance">
          <h2 id="compliance" className="font-serif text-2xl">
            Legal and Regulatory Compliance
          </h2>
          <p className="mt-3 text-muted-foreground">
            Vendors and Partners must comply with all applicable laws and regulations:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Business licensing and registration requirements</li>
            <li>Tax obligations and reporting requirements</li>
            <li>Product safety and consumer protection laws</li>
            <li>Environmental and sustainability regulations</li>
            <li>Data protection and privacy laws</li>
            <li>Employment and labor law compliance</li>
            <li>Industry-specific certifications and standards</li>
          </ul>
        </section>

        <section aria-labelledby="intellectual-property">
          <h2 id="intellectual-property" className="font-serif text-2xl">
            Intellectual Property Rights
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Vendor IP
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Vendors retain ownership of their proprietary intellectual property:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Trademarks, copyrights, and patents owned by the Vendor</li>
                <li>Trade secrets and proprietary processes</li>
                <li>Original content and creative works</li>
                <li>Product designs and specifications</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Navdhya IP
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Vendors must respect Navdhya's intellectual property rights:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Navdhya trademarks, logos, and branding</li>
                <li>Platform interfaces and user experience designs</li>
                <li>Proprietary systems and processes</li>
                <li>Customer data and business information</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Joint IP
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Rights to jointly developed materials will be specified in separate agreements:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Co-branded products and services</li>
                <li>Custom-developed software or systems</li>
                <li>Joint marketing materials and campaigns</li>
                <li>Collaborative content and educational resources</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="confidentiality">
          <h2 id="confidentiality" className="font-serif text-2xl">
            Confidentiality and Data Protection
          </h2>
          <p className="mt-3 text-muted-foreground">
            Vendors must maintain strict confidentiality of sensitive information:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Customer data and personal information</li>
            <li>Business strategies and operational details</li>
            <li>Financial information and pricing structures</li>
            <li>Technology systems and proprietary processes</li>
            <li>Marketing plans and competitive intelligence</li>
          </ul>
          <p className="mt-3 text-muted-foreground">
            Vendors must comply with applicable data protection laws and implement appropriate security measures.
          </p>
        </section>

        <section aria-labelledby="payment-terms">
          <h2 id="payment-terms" className="font-serif text-2xl">
            Payment Terms and Conditions
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Payment Processing
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Payments will be processed through agreed-upon methods</li>
                <li>Invoices must be submitted according to established procedures</li>
                <li>Payment terms will be specified in individual contracts</li>
                <li>Late payments may incur interest charges</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Taxes and Fees
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Vendors are responsible for all applicable taxes</li>
                <li>Proper tax documentation must be provided</li>
                <li>Any required withholding taxes will be deducted</li>
                <li>Compliance with GST and other tax regulations</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Disputes and Adjustments
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Disputes must be reported within 30 days of invoice date</li>
                <li>Credit requests require written justification</li>
                <li>Adjustments will be processed according to policy</li>
                <li>Documentation must support all dispute claims</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="warranties">
          <h2 id="warranties" className="font-serif text-2xl">
            Warranties and Liabilities
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Product Warranties
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Products must be free from defects in materials and workmanship</li>
                <li>Warranty periods will be specified in individual agreements</li>
                <li>Replacement or repair of defective products at Vendor's expense</li>
                <li>Indemnification for damages caused by defective products</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Service Guarantees
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Services must be performed according to agreed specifications</li>
                <li>Correction of deficiencies at no additional cost</li>
                <li>Timely completion of all contracted services</li>
                <li>Professional standards and quality requirements</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Limitation of Liability
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Liability limited to the value of the contract or project</li>
                <li>Exclusion of consequential and indirect damages</li>
                <li>Insurance requirements for high-risk activities</li>
                <li>Indemnification for third-party claims</li>
              </ul>
            </div>
          </div>
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
                Individual contracts will specify term lengths and renewal conditions.
              </p>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Termination for Cause
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Either party may terminate for material breach including:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Failure to meet quality or performance standards</li>
                <li>Breach of confidentiality or data protection requirements</li>
                <li>Violation of laws or regulations</li>
                <li>Insolvency or financial default</li>
                <li>Fraudulent or unethical conduct</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Termination for Convenience
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Navdhya may terminate contracts for business reasons with:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>30 days written notice for ongoing services</li>
                <li>Immediate termination for project-based work</li>
                <li>Payment for completed work through termination date</li>
                <li>Return of all Navdhya property and confidential information</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="dispute-resolution">
          <h2 id="dispute-resolution" className="font-serif text-2xl">
            Dispute Resolution
          </h2>
          <p className="mt-3 text-muted-foreground">
            Disputes will be resolved through the following process:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Good faith negotiation between parties</li>
            <li>Mediation through mutually agreed mediator</li>
            <li>Arbitration in Delhi, India if mediation fails</li>
            <li>Governed by Indian law and jurisdiction</li>
          </ul>
        </section>

        <section aria-labelledby="miscellaneous">
          <h2 id="miscellaneous" className="font-serif text-2xl">
            Miscellaneous Provisions
          </h2>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Entire agreement between parties supersedes all prior discussions</li>
            <li>Amendments require written agreement signed by both parties</li>
            <li>Assignment requires prior written consent</li>
            <li>Notices must be in writing to specified addresses</li>
            <li>Severability of provisions if any part is found invalid</li>
            <li>Waiver of rights requires express written consent</li>
          </ul>
        </section>

        <section aria-labelledby="contact">
          <h2 id="contact" className="font-serif text-2xl">
            Contact Information
          </h2>
          <p className="mt-3 text-muted-foreground">
            For questions about these terms or to discuss partnership opportunities, contact:
          </p>
          <address className="mt-3 not-italic text-muted-foreground">
            Navdhya Vendor Relations Team<br />
            Email: vendors@navdhya.com<br />
            Phone: +91-7289000377<br />
            Postal Address: Navdhya Ritual Services, Pandav Nagar, Delhi 110092, India
          </address>
        </section>
      </main>
    </>
  )
}
import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"

// Add page-specific metadata
export const metadata = seoManager.generateMetadata({
  title: "Intellectual Property Policy",
  description: "Navdhya's intellectual property policy. Learn how we protect our trademarks, content, recordings, and other proprietary assets.",
  path: "/legal/ip-policy",
  keywords: ["intellectual property", "IP policy", "copyright", "trademark", "content protection", "Navdhya IP"]
})

// Schema markup for the IP policy page
const IP_POLICY_SCHEMA = seoManager.generateSchema({
  type: "WebPage",
  name: "Intellectual Property Policy - Navdhya",
  description: "Navdhya's intellectual property policy. Learn how we protect our trademarks, content, recordings, and other proprietary assets.",
  path: "/legal/ip-policy",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Legal", url: "https://www.navdhya.com/legal" },
    { name: "Intellectual Property Policy", url: "https://www.navdhya.com/legal/ip-policy" }
  ]
})

export default function IPPolicyPage() {
  const ipPolicySchema = JSON.stringify(IP_POLICY_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: ipPolicySchema }}
      />
      <main className="mx-auto max-w-6xl px-4 py-12 space-y-10 overflow-x-hidden w-full">
        <section aria-labelledby="ip-heading">
          <h1 id="ip-heading" className="font-serif text-3xl">
            Intellectual Property Policy
          </h1>
          <p className="mt-3 text-muted-foreground">
            This Intellectual Property Policy ("Policy") outlines how Navdhya Ritual Services ("Navdhya," "we," "us," or "our") protects and manages our intellectual property assets, including trademarks, copyrights, trade secrets, and other proprietary rights.
          </p>
          <p className="mt-3 text-muted-foreground">
            Last Updated: November 1, 2025
          </p>
        </section>

        <section aria-labelledby="ownership">
          <h2 id="ownership" className="font-serif text-2xl">
            Ownership of Intellectual Property
          </h2>
          <p className="mt-3 text-muted-foreground">
            Navdhya owns and retains all rights, title, and interest in and to the following intellectual property:
          </p>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Trademarks and Service Marks
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>"Navdhya" and related logos and brand elements</li>
                <li>Taglines, slogans, and marketing phrases</li>
                <li>Product and service names</li>
                <li>Domain names and social media handles</li>
                <li>Trade dress and distinctive visual elements</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Copyrighted Materials
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Website content, text, images, and graphics</li>
                <li>Blog posts, articles, and educational materials</li>
                <li>Software code and platform interfaces</li>
                <li>Audio and video recordings</li>
                <li>Marketing and promotional materials</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Trade Secrets
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Business methods and processes</li>
                <li>Customer lists and preferences</li>
                <li>Technology and system architectures</li>
                <li>Financial and operational data</li>
                <li>Strategic plans and marketing approaches</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="logo-protection">
          <h2 id="logo-protection" className="font-serif text-2xl">
            Logo and Brand Protection
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Registered Trademarks
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Navdhya has registered or is in the process of registering the following trademarks:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>The "Navdhya" name and logo</li>
                <li>Distinctive brand elements and visual identifiers</li>
                <li>Service-specific marks for ritual services</li>
                <li>Product names and categories</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Proper Usage Guidelines
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                To maintain trademark protection, we require proper usage of our marks:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Use trademarks as adjectives, not nouns or verbs</li>
                <li>Include appropriate trademark symbols (™, ®) when required</li>
                <li>Do not alter, modify, or distort our logos</li>
                <li>Do not use our marks in a misleading or confusing manner</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Prohibited Uses
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                The following uses of our trademarks are strictly prohibited:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Incorporating our marks into other trademarks</li>
                <li>Using our marks as domain names or social media handles</li>
                <li>Creating derivative works based on our logos</li>
                <li>Using our marks in connection with illegal activities</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="content-protection">
          <h2 id="content-protection" className="font-serif text-2xl">
            Content and Media Protection
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Original Content
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                All original content created by or for Navdhya is protected by copyright:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Written articles, guides, and educational materials</li>
                <li>Photographs, illustrations, and graphic designs</li>
                <li>Audio recordings of mantras, chants, and music</li>
                <li>Video recordings of rituals and ceremonies</li>
                <li>Software code and technical documentation</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Ritual-Specific Content
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Special protections apply to our ritual-specific materials:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Traditional practices adapted for modern delivery</li>
                <li>Custom ritual procedures and methodologies</li>
                <li>Pandit training materials and certification content</li>
                <li>Ritual performance guidelines and standards</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                User-Generated Content
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Content created by users remains their property but is subject to our terms:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Users grant us license to display content on our platform</li>
                <li>Users must have rights to share content they post</li>
                <li>We may remove content that violates our policies</li>
                <li>Attribution is provided for user-generated content</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="chant-protection">
          <h2 id="chant-protection" className="font-serif text-2xl">
            Chants and Sacred Text Protection
          </h2>
          <p className="mt-3 text-muted-foreground">
            While traditional Hindu chants and texts are in the public domain, our specific arrangements and recordings are protected:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Original musical arrangements and compositions</li>
            <li>Professional recordings and audio productions</li>
            <li>Translations and interpretations with creative elements</li>
            <li>Compilation of chants for specific purposes</li>
            <li>Associated visual and multimedia presentations</li>
          </ul>
          <p className="mt-3 text-muted-foreground">
            We respect the sacred nature of these materials while protecting our creative contributions.
          </p>
        </section>

        <section aria-labelledby="third-party-ip">
          <h2 id="third-party-ip" className="font-serif text-2xl">
            Third-Party Intellectual Property
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Licensed Content
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                We respect third-party intellectual property rights:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Obtain proper licenses for all third-party content</li>
                <li>Provide attribution as required by licenses</li>
                <li>Comply with all license terms and restrictions</li>
                <li>Monitor and renew licenses as necessary</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Open Source Materials
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Our use of open source software complies with applicable licenses:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Provide attribution for open source components</li>
                <li>Comply with copyleft and distribution requirements</li>
                <li>Maintain records of open source usage</li>
                <li>Respect license restrictions on derivative works</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="infringement">
          <h2 id="infringement" className="font-serif text-2xl">
            Infringement Reporting and Response
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Reporting Infringement
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                To report intellectual property infringement, contact our designated agent:
              </p>
              <address className="mt-3 not-italic text-muted-foreground">
                Navdhya IP Protection Agent<br />
                Email: ip@navdhya.com<br />
                Postal Address: Navdhya Ritual Services, Pandav Nagar, Delhi 110092, India
              </address>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Required Information
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Reports must include the following information:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Description of the infringed work or material</li>
                <li>Location of the infringing content on our platform</li>
                <li>Contact information for the reporting party</li>
                <li>Statement of good faith belief in infringement</li>
                <li>Electronic or physical signature of the rights holder</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Response Process
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                We respond to infringement claims as follows:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Investigate claims within 5 business days</li>
                <li>Remove or disable access to infringing content</li>
                <li>Notify the content provider of removal</li>
                <li>Process counter-notifications appropriately</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="employee-obligations">
          <h2 id="employee-obligations" className="font-serif text-2xl">
            Employee and Contractor Obligations
          </h2>
          <p className="mt-3 text-muted-foreground">
            All employees and contractors must comply with our IP protection requirements:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Sign intellectual property assignment agreements</li>
            <li>Participate in IP awareness training</li>
            <li>Report potential IP issues immediately</li>
            <li>Respect confidentiality of proprietary information</li>
            <li>Obtain approval before using third-party materials</li>
          </ul>
        </section>

        <section aria-labelledby="enforcement">
          <h2 id="enforcement" className="font-serif text-2xl">
            Enforcement and Legal Action
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Monitoring and Detection
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                We actively monitor for unauthorized use of our intellectual property:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Regular web searches for trademark infringement</li>
                <li>Monitoring of domain name registrations</li>
                <li>Tracking of content usage and distribution</li>
                <li>Review of competitor activities and marketing</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Enforcement Actions
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                We take appropriate action to protect our intellectual property:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Send cease and desist letters</li>
                <li>File oppositions to trademark applications</li>
                <li>Pursue civil litigation when necessary</li>
                <li>Work with domain registrars to resolve disputes</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="international">
          <h2 id="international" className="font-serif text-2xl">
            International Protection
          </h2>
          <p className="mt-3 text-muted-foreground">
            Our intellectual property strategy includes international considerations:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Registration in key international markets</li>
            <li>Compliance with international IP treaties</li>
            <li>Monitoring of global trademark databases</li>
            <li>Adaptation to local IP laws and practices</li>
            <li>Coordination with international legal counsel</li>
          </ul>
        </section>

        <section aria-labelledby="policy-changes">
          <h2 id="policy-changes" className="font-serif text-2xl">
            Changes to This Policy
          </h2>
          <p className="mt-3 text-muted-foreground">
            We may update this policy to reflect changes in:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Intellectual property laws and regulations</li>
            <li>Our business practices and offerings</li>
            <li>Technology and digital content delivery</li>
            <li>Industry standards and best practices</li>
          </ul>
          <p className="mt-3 text-muted-foreground">
            We will notify stakeholders of significant changes through our website and update the "Last Updated" date.
          </p>
        </section>

        <section aria-labelledby="contact">
          <h2 id="contact" className="font-serif text-2xl">
            Contact Information
          </h2>
          <p className="mt-3 text-muted-foreground">
            For questions about this policy or to report intellectual property concerns, contact:
          </p>
          <address className="mt-3 not-italic text-muted-foreground">
            Navdhya Intellectual Property Team<br />
            Email: ip@navdhya.com<br />
            Phone: +91-7289000377<br />
            Postal Address: Navdhya Ritual Services, Pandav Nagar, Delhi 110092, India
          </address>
        </section>
      </main>
    </>
  )
}
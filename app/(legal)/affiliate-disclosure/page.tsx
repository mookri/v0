import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"

// Add page-specific metadata
export const metadata = seoManager.generateMetadata({
  title: "Affiliate Disclosure",
  description: "Navdhya's affiliate disclosure statement. Learn about our relationships with third-party products and services that may earn us commissions.",
  path: "/legal/affiliate-disclosure",
  keywords: ["affiliate disclosure", "affiliate links", "commission disclosure", "FTC compliance", "affiliate marketing"]
})

// Schema markup for the affiliate disclosure page
const AFFILIATE_DISCLOSURE_SCHEMA = seoManager.generateSchema({
  type: "WebPage",
  name: "Affiliate Disclosure - Navdhya",
  description: "Navdhya's affiliate disclosure statement. Learn about our relationships with third-party products and services that may earn us commissions.",
  path: "/legal/affiliate-disclosure",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Legal", url: "https://www.navdhya.com/legal" },
    { name: "Affiliate Disclosure", url: "https://www.navdhya.com/legal/affiliate-disclosure" }
  ]
})

export default function AffiliateDisclosurePage() {
  const affiliateDisclosureSchema = JSON.stringify(AFFILIATE_DISCLOSURE_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: affiliateDisclosureSchema }}
      />
      <main className="mx-auto max-w-6xl px-4 py-12 space-y-10 overflow-x-hidden w-full">
        <section aria-labelledby="disclosure-heading">
          <h1 id="disclosure-heading" className="font-serif text-3xl">
            Affiliate Disclosure
          </h1>
          <p className="mt-3 text-muted-foreground">
            This Affiliate Disclosure Statement ("Disclosure") explains how Navdhya Ritual Services ("Navdhya," "we," "us," or "our") may earn commissions through affiliate marketing relationships when you purchase products or services through links on our website.
          </p>
          <p className="mt-3 text-muted-foreground">
            Last Updated: November 1, 2025
          </p>
        </section>

        <section aria-labelledby="ftc-compliance">
          <h2 id="ftc-compliance" className="font-serif text-2xl">
            FTC Compliance Statement
          </h2>
          <p className="mt-3 text-muted-foreground">
            Navdhya is in compliance with the Federal Trade Commission's (FTC) guidelines regarding affiliate marketing and endorsements. We are transparent about our affiliate relationships and any compensation we may receive when you purchase products or services through our links.
          </p>
          <p className="mt-3 text-muted-foreground">
            As required by the FTC, we disclose that we may earn commissions from qualifying purchases made through links on our website. This disclosure is made in accordance with 16 CFR § 255.5: Disclosures by Participants in Marketing Campaigns.
          </p>
        </section>

        <section aria-labelledby="affiliate-relationships">
          <h2 id="affiliate-relationships" className="font-serif text-2xl">
            Affiliate Relationships
          </h2>
          <p className="mt-3 text-muted-foreground">
            Navdhya participates in various affiliate marketing programs with third-party companies. These relationships allow us to earn commissions when you make qualifying purchases through our links. Our affiliate relationships include but are not limited to:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Amazon Associates Program</li>
            <li>Flipkart Affiliate Program</li>
            <li>Reliance Digital Affiliate Program</li>
            <li>Spiritual and wellness product manufacturers</li>
            <li>Book publishers and authors</li>
            <li>Educational course providers</li>
            <li>Technology and app developers</li>
          </ul>
          <p className="mt-3 text-muted-foreground">
            We only partner with companies and promote products that align with our values and that we believe will be valuable to our customers.
          </p>
        </section>

        <section aria-labelledby="how-it-works">
          <h2 id="how-it-works" className="font-serif text-2xl">
            How Affiliate Marketing Works
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Link Structure
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Our affiliate links are clearly marked and follow standard affiliate URL structures. When you click on these links and make a purchase, we may earn a commission at no additional cost to you.
              </p>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Commission Structure
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Commission rates vary by merchant and product type. These rates are determined by the affiliate programs and are not negotiable by customers.
              </p>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Tracking and Attribution
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Affiliate programs use cookies to track purchases made within a specified time period (typically 24 hours to 90 days) after clicking an affiliate link.
              </p>
            </div>
          </div>
        </section>

        <section aria-labelledby="product-selection">
          <h2 id="product-selection" className="font-serif text-2xl">
            Product Selection and Endorsement
          </h2>
          <p className="mt-3 text-muted-foreground">
            Our product recommendations and endorsements are based on:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Relevance to our customers' spiritual and wellness needs</li>
            <li>Quality and authenticity of the products</li>
            <li>Alignment with traditional Hindu practices and values</li>
            <li>Positive customer reviews and feedback</li>
            <li>Competitive pricing and value proposition</li>
          </ul>
          <p className="mt-3 text-muted-foreground">
            We do not promote products solely because they offer higher commissions. Our primary goal is to provide value to our customers by recommending high-quality products that support their spiritual journey.
          </p>
        </section>

        <section aria-labelledby="transparency">
          <h2 id="transparency" className="font-serif text-2xl">
            Transparency and Disclosure Requirements
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Clear Disclosure
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                We clearly disclose our affiliate relationships in the following ways:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Prominent placement of disclosure statements on relevant pages</li>
                <li>Clear labeling of affiliate links where possible</li>
                <li>Inclusion of this comprehensive disclosure page</li>
                <li>Regular review and updating of disclosure practices</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                No Additional Cost to Customers
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Purchasing through our affiliate links does not increase the cost of products or services for customers. All prices remain the same as if purchased directly from the merchant.
              </p>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Editorial Independence
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Our editorial content is not influenced by affiliate relationships. We maintain complete independence in our product recommendations and reviews.
              </p>
            </div>
          </div>
        </section>

        <section aria-labelledby="types-of-links">
          <h2 id="types-of-links" className="font-serif text-2xl">
            Types of Links We Use
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Affiliate Links
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                These links contain affiliate identifiers that allow us to earn commissions on qualifying purchases. They are used for:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Product recommendations in blog posts and articles</li>
                <li>Resource pages and buying guides</li>
                <li>Equipment and supply recommendations</li>
                <li>Book and educational material suggestions</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Direct Links
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Some links may direct to merchant websites without affiliate identifiers. These are used when:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>We want to provide general information without commercial intent</li>
                <li>The merchant does not offer an affiliate program</li>
                <li>We are linking to official websites or resources</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="revenue-usage">
          <h2 id="revenue-usage" className="font-serif text-2xl">
            Use of Affiliate Revenue
          </h2>
          <p className="mt-3 text-muted-foreground">
            Revenue generated through affiliate marketing is used to:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Maintain and improve our website and platform</li>
            <li>Develop new content and educational resources</li>
            <li>Support our customer service and community initiatives</li>
            <li>Invest in technology and infrastructure improvements</li>
            <li>Expand our product offerings and services</li>
          </ul>
          <p className="mt-3 text-muted-foreground">
            Affiliate revenue helps us provide valuable content and services to our community at no direct cost to users.
          </p>
        </section>

        <section aria-labelledby="disclaimer">
          <h2 id="disclaimer" className="font-serif text-2xl">
            Disclaimer and Limitations
          </h2>
          <p className="mt-3 text-muted-foreground">
            Important limitations regarding our affiliate relationships:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>We are not responsible for the quality, safety, or performance of affiliate products</li>
            <li>We do not guarantee the accuracy of product information provided by merchants</li>
            <li>Commission rates and program terms may change without notice</li>
            <li>We may discontinue affiliate relationships at any time</li>
            <li>Not all products or services available through affiliate links</li>
          </ul>
        </section>

        <section aria-labelledby="third-party">
          <h2 id="third-party" className="font-serif text-2xl">
            Third-Party Merchant Policies
          </h2>
          <p className="mt-3 text-muted-foreground">
            When you purchase through our affiliate links, you are subject to the merchant's:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Return and refund policies</li>
            <li>Shipping and delivery terms</li>
            <li>Warranty and guarantee provisions</li>
            <li>Privacy and data protection policies</li>
            <li>Terms of service and user agreements</li>
          </ul>
          <p className="mt-3 text-muted-foreground">
            We encourage you to review these policies before making purchases.
          </p>
        </section>

        <section aria-labelledby="changes">
          <h2 id="changes" className="font-serif text-2xl">
            Changes to This Disclosure
          </h2>
          <p className="mt-3 text-muted-foreground">
            We may update this disclosure from time to time to reflect:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Changes in affiliate programs or relationships</li>
            <li>Updates to legal or regulatory requirements</li>
            <li>Improvements to our disclosure practices</li>
            <li>Changes in how we present affiliate links</li>
          </ul>
          <p className="mt-3 text-muted-foreground">
            We will notify users of significant changes through our website and update the "Last Updated" date.
          </p>
        </section>

        <section aria-labelledby="contact">
          <h2 id="contact" className="font-serif text-2xl">
            Contact Information
          </h2>
          <p className="mt-3 text-muted-foreground">
            If you have questions about our affiliate relationships or this disclosure, please contact:
          </p>
          <address className="mt-3 not-italic text-muted-foreground">
            Navdhya Legal Team<br />
            Email: legal@navdhya.com<br />
            Phone: +91-7289000377<br />
            Postal Address: Navdhya Ritual Services, Pandav Nagar, Delhi 110092, India
          </address>
        </section>
      </main>
    </>
  )
}
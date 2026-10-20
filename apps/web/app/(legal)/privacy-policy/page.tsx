import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import { ErrorBoundary } from '@/components/dlo-x/ErrorBoundary'
import { resolveContext } from '@/lib/context-resolver'

// Add page-specific metadata
export const metadata = seoManager.generateMetadata({
  title: "Privacy Policy",
  description: "Learn how Navdhya collects, uses, and protects your personal information. We are committed to safeguarding your privacy while providing authentic Hindu ritual services.",
  path: "/privacy-policy",
  keywords: ["privacy policy", "data protection", "personal information", "GDPR", "IT Act", "user privacy", "Navdhya"]
})

// Schema markup for the privacy policy page
const PRIVACY_SCHEMA = seoManager.generateSchema({
  type: "WebPage",
  name: "Privacy Policy - Navdhya",
  description: "Learn how Navdhya collects, uses, and protects your personal information. We are committed to safeguarding your privacy while providing authentic Hindu ritual services.",
  path: "/privacy-policy",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Privacy Policy", url: "https://www.navdhya.com/privacy-policy" }
  ]
})

export default async function PrivacyPolicyPage() {
  const privacySchema = JSON.stringify(PRIVACY_SCHEMA)
  
  // Resolve context using the new resolver
  const context = await resolveContext('/privacy-policy');
  
  return (
    <ErrorBoundary>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: privacySchema }}
        />
        <div className="mx-auto max-w-6xl px-4 py-12 space-y-10 overflow-x-hidden w-full">
          <section aria-labelledby="privacy-heading">
            <h1 id="privacy-heading" className="font-serif text-3xl">
              Privacy Policy
            </h1>
            <p className="mt-3 text-muted-foreground">
              Last Updated: November 1, 2025
            </p>
          </section>

          <section aria-labelledby="introduction">
            <h2 id="introduction" className="font-serif text-2xl">
              Introduction
            </h2>
            <p className="mt-3 text-muted-foreground">
              Navdhya ("we," "us," or "our") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website https://www.navdhya.com and use our services for authentic Hindu rituals and spiritual wellness.
            </p>
            <p className="mt-3 text-muted-foreground">
              Please read this privacy policy carefully. If you do not agree with the terms of this privacy policy, please do not access the site or use our services.
            </p>
          </section>

          <section aria-labelledby="information-collection">
            <h2 id="information-collection" className="font-serif text-2xl">
              Information We Collect
            </h2>
            <div className="mt-3 space-y-4">
              <div>
                <h3 className="font-serif text-xl">
                  Personal Information
                </h3>
                <p className="mt-3 text-muted-foreground text-sm">
                  We may collect personally identifiable information, such as your name, email address, phone number, postal address, payment information, and family details when you register, book services, or communicate with us.
                </p>
              </div>
              <div>
                <h3 className="font-serif text-xl">
                  Derivative Information
                </h3>
                <p className="mt-3 text-muted-foreground text-sm">
                  Information our servers automatically collect when you access our services, such as your IP address, browser type, operating system, access times, and the pages you have viewed directly before coming to our site.
                </p>
              </div>
              <div>
                <h3 className="font-serif text-xl">
                  Financial Information
                </h3>
                <p className="mt-3 text-muted-foreground text-sm">
                  We collect payment information when you make purchases, including credit card numbers and other financial data processed through secure third-party payment processors like Razorpay.
                </p>
              </div>
              <div>
                <h3 className="font-serif text-xl">
                  Cookies and Tracking Technologies
                </h3>
                <p className="mt-3 text-muted-foreground text-sm">
                  We use cookies and similar tracking technologies to track activity on our services and store certain information. You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent.
                </p>
              </div>
            </div>
          </section>

          <section aria-labelledby="information-use">
            <h2 id="information-use" className="font-serif text-2xl">
              How We Use Your Information
            </h2>
            <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
              <li>To provide, operate, and maintain our services</li>
              <li>To improve, personalize, and expand our services</li>
              <li>To understand and analyze how you use our services</li>
              <li>To develop new products, services, features, and functionality</li>
              <li>To communicate with you, either directly or through one of our partners</li>
              <li>To process your transactions and manage your orders</li>
              <li>To send you updates, marketing communications, and other information</li>
              <li>To find and prevent fraud</li>
            </ul>
          </section>

          <section aria-labelledby="third-party">
            <h2 id="third-party" className="font-serif text-2xl">
              Third-Party Services
            </h2>
            <p className="mt-3 text-muted-foreground">
              We may share your information with third-party service providers who perform services on our behalf, including:
            </p>
            <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
              <li>Payment processors like Razorpay for transaction processing</li>
              <li>Email marketing platforms for communication</li>
              <li>Analytics services like Google Analytics for understanding user behavior</li>
              <li>Cloud hosting services for data storage</li>
            </ul>
          </section>

          <section aria-labelledby="data-retention">
            <h2 id="data-retention" className="font-serif text-2xl">
              Data Retention
            </h2>
            <p className="mt-3 text-muted-foreground">
              We will retain your information for as long as necessary to fulfill the purposes outlined in this privacy policy unless a longer retention period is required or permitted by law. Typically, we retain:
            </p>
            <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
              <li>Transaction records for 7 years as required by tax laws</li>
              <li>Communication records for 3 years</li>
              <li>Service booking information for 5 years</li>
            </ul>
          </section>

          <section aria-labelledby="user-rights">
            <h2 id="user-rights" className="font-serif text-2xl">
              Your Rights
            </h2>
            <p className="mt-3 text-muted-foreground">
              Depending on your location, you may have the following rights regarding your personal information:
            </p>
            <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
              <li>The right to access, update, or delete the information we have on you</li>
              <li>The right to rectification if your data is inaccurate or incomplete</li>
              <li>The right to data portability in certain circumstances</li>
              <li>The right to restrict or object to our use of your data</li>
              <li>The right to withdraw consent where we rely on consent to process your data</li>
            </ul>
            <p className="mt-3 text-muted-foreground">
              To exercise any of these rights, please contact us at help@navdhya.com.
            </p>
          </section>

          <section aria-labelledby="security">
            <h2 id="security" className="font-serif text-2xl">
              Security of Your Information
            </h2>
            <p className="mt-3 text-muted-foreground">
              We use administrative, technical, and physical security measures to protect your personal information. However, no method of transmission over the Internet or method of electronic storage is 100% secure.
            </p>
          </section>

          <section aria-labelledby="children-privacy">
            <h2 id="children-privacy" className="font-serif text-2xl">
              Children's Privacy
            </h2>
            <p className="mt-3 text-muted-foreground">
              We do not knowingly collect personal information from children under the age of 13. If we become aware that a child under 13 has provided us with personal information, we will take steps to delete such information from our servers.
            </p>
          </section>

          <section aria-labelledby="changes-policy">
            <h2 id="changes-policy" className="font-serif text-2xl">
              Changes to This Privacy Policy
            </h2>
            <p className="mt-3 text-muted-foreground">
              We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last Updated" date.
            </p>
          </section>

          <section aria-labelledby="contact-us">
            <h2 id="contact-us" className="font-serif text-2xl">
              Contact Us
            </h2>
            <p className="mt-3 text-muted-foreground">
              If you have any questions about this Privacy Policy, please contact us at:
            </p>
            <div className="mt-3 text-muted-foreground">
              <p><strong>Email:</strong> help@navdhya.com</p>
              <p><strong>Phone:</strong> +91-7289000377</p>
              <p><strong>Address:</strong> Pandav Nagar, Delhi 110092</p>
            </div>
          </section>
        </div>
    </ErrorBoundary>
  )
}

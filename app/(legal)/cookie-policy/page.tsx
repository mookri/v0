import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"

// Add page-specific metadata
export const metadata = seoManager.generateMetadata({
  title: "Cookie Policy",
  description: "Learn how Navdhya uses cookies and similar tracking technologies to enhance your browsing experience and provide personalized services.",
  path: "/legal/cookie-policy",
  keywords: ["cookie policy", "cookies", "tracking technologies", "privacy", "website cookies", "Navdhya cookies"]
})

// Schema markup for the cookie policy page
const COOKIE_POLICY_SCHEMA = seoManager.generateSchema({
  type: "WebPage",
  name: "Cookie Policy - Navdhya",
  description: "Learn how Navdhya uses cookies and similar tracking technologies to enhance your browsing experience and provide personalized services.",
  path: "/legal/cookie-policy",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Legal", url: "https://www.navdhya.com/legal" },
    { name: "Cookie Policy", url: "https://www.navdhya.com/legal/cookie-policy" }
  ]
})

export default function CookiePolicyPage() {
  const cookiePolicySchema = JSON.stringify(COOKIE_POLICY_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: cookiePolicySchema }}
      />
      <main className="mx-auto max-w-6xl px-4 py-12 space-y-10 overflow-x-hidden w-full">
        <section aria-labelledby="cookie-heading">
          <h1 id="cookie-heading" className="font-serif text-3xl">
            Cookie Policy
          </h1>
          <p className="mt-3 text-muted-foreground">
            This Cookie Policy explains how Navdhya ("we," "us," or "our") uses cookies and similar tracking technologies when you visit our website and use our services. This policy is part of our Privacy Policy and should be read in conjunction with it.
          </p>
          <p className="mt-3 text-muted-foreground">
            Last Updated: November 1, 2025
          </p>
        </section>

        <section aria-labelledby="what-are-cookies">
          <h2 id="what-are-cookies" className="font-serif text-2xl">
            What Are Cookies?
          </h2>
          <p className="mt-3 text-muted-foreground">
            Cookies are small text files that are stored on your computer or mobile device when you visit a website. They are widely used to make websites work more efficiently and to provide information to the website owners.
          </p>
          <p className="mt-3 text-muted-foreground">
            Cookies can be "persistent" or "session" cookies. Persistent cookies remain on your device for a specified period of time, while session cookies are automatically deleted when you close your browser.
          </p>
        </section>

        <section aria-labelledby="how-we-use-cookies">
          <h2 id="how-we-use-cookies" className="font-serif text-2xl">
            How We Use Cookies
          </h2>
          <p className="mt-3 text-muted-foreground">
            We use cookies for various purposes to enhance your browsing experience and improve our services:
          </p>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Essential Cookies
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                These cookies are necessary for the website to function and cannot be switched off in our systems. They are usually only set in response to actions made by you which amount to a request for services, such as setting your privacy preferences, logging in, or filling in forms.
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Authentication cookies to keep you logged in</li>
                <li>Security cookies to protect your account</li>
                <li>Load balancing cookies to ensure site stability</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Performance Cookies
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                These cookies allow us to count visits and traffic sources so we can measure and improve the performance of our site. They help us to know which pages are the most and least popular and see how visitors move around the site.
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Analytics cookies to understand how visitors interact with our website</li>
                <li>Loading performance cookies to optimize site speed</li>
                <li>Error reporting cookies to identify and fix technical issues</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Functionality Cookies
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                These cookies enable the website to provide enhanced functionality and personalization. They may be set by us or by third-party providers whose services we have added to our pages.
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Language preference cookies</li>
                <li>Region and location settings</li>
                <li>Saved booking preferences</li>
                <li>Personalized content delivery</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Targeting Cookies
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                These cookies may be set through our site by our advertising partners. They may be used by those companies to build a profile of your interests and show you relevant advertisements on other sites.
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Retargeting cookies for personalized advertising</li>
                <li>Social media integration cookies</li>
                <li>Marketing campaign tracking cookies</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="specific-cookies">
          <h2 id="specific-cookies" className="font-serif text-2xl">
            Specific Cookies We Use
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                First-Party Cookies
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                These are cookies set by Navdhya directly:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Session management cookies to keep you logged in</li>
                <li>Preference cookies to remember your settings</li>
                <li>Security cookies to protect against fraud</li>
                <li>Performance cookies to analyze site usage</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Third-Party Cookies
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                These are cookies set by third-party services we use:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Google Analytics cookies for website analytics</li>
                <li>Razorpay cookies for payment processing</li>
                <li>Social media platform cookies for sharing features</li>
                <li>Cloud service provider cookies for hosting and delivery</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="cookie-controls">
          <h2 id="cookie-controls" className="font-serif text-2xl">
            Your Cookie Controls
          </h2>
          <p className="mt-3 text-muted-foreground">
            You have the right to decide whether to accept or reject cookies. You can manage your cookie preferences through:
          </p>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Cookie Consent Banner
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                When you first visit our website, you'll see a cookie consent banner that allows you to accept or reject different categories of cookies.
              </p>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Browser Settings
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Most web browsers allow you to control cookies through their settings preferences. However, if you choose to block or delete cookies, some parts of our website may not work properly.
              </p>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Cookie Policy Page
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                You can access and update your cookie preferences at any time through our dedicated cookie preference center.
              </p>
            </div>
          </div>
        </section>

        <section aria-labelledby="do-not-track">
          <h2 id="do-not-track" className="font-serif text-2xl">
            Do Not Track Signals
          </h2>
          <p className="mt-3 text-muted-foreground">
            Some web browsers may transmit "do-not-track" signals to websites. Because there is no consistent standard for how to respond to these signals, we do not currently respond to them. Instead, we provide you with the ability to manage your cookie preferences as described above.
          </p>
        </section>

        <section aria-labelledby="third-party-services">
          <h2 id="third-party-services" className="font-serif text-2xl">
            Third-Party Services
          </h2>
          <p className="mt-3 text-muted-foreground">
            We use third-party services that may set cookies on your device:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Google Analytics for website analytics and performance monitoring</li>
            <li>Razorpay for secure payment processing</li>
            <li>Social media platforms for sharing and engagement features</li>
            <li>Cloud hosting services for website delivery and performance</li>
            <li>Email marketing platforms for communication with users</li>
          </ul>
          <p className="mt-3 text-muted-foreground">
            These third parties have their own privacy policies and cookie practices. We recommend reviewing their policies to understand how they use cookies and your options for controlling them.
          </p>
        </section>

        <section aria-labelledby="cookie-security">
          <h2 id="cookie-security" className="font-serif text-2xl">
            Cookie Security
          </h2>
          <p className="mt-3 text-muted-foreground">
            We take appropriate security measures to protect against unauthorized access to or unauthorized alteration, disclosure, or destruction of data transmitted through cookies. These measures include:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Encryption of sensitive cookie data</li>
            <li>Secure transmission of cookie information</li>
            <li>Regular security audits of our cookie practices</li>
            <li>Access controls for cookie management systems</li>
          </ul>
        </section>

        <section aria-labelledby="policy-changes">
          <h2 id="policy-changes" className="font-serif text-2xl">
            Changes to This Cookie Policy
          </h2>
          <p className="mt-3 text-muted-foreground">
            We may update our Cookie Policy from time to time. We will notify you of any changes by posting the new Cookie Policy on this page and updating the "Last Updated" date.
          </p>
          <p className="mt-3 text-muted-foreground">
            You are advised to review this Cookie Policy periodically for any changes. Changes to this Cookie Policy are effective when they are posted on this page.
          </p>
        </section>

        <section aria-labelledby="contact-us">
          <h2 id="contact-us" className="font-serif text-2xl">
            Contact Us
          </h2>
          <p className="mt-3 text-muted-foreground">
            If you have any questions about our Cookie Policy, please contact us:
          </p>
          <address className="mt-3 not-italic text-muted-foreground">
            Navdhya Privacy Team<br />
            Email: privacy@navdhya.com<br />
            Phone: +91-7289000377<br />
            Postal Address: Navdhya Ritual Services, Pandav Nagar, Delhi 110092, India
          </address>
        </section>
      </main>
    </>
  )
}
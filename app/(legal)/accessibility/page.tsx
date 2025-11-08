import { SEO_CONFIG, BREADCRUMB_SCHEMA } from "@/lib/seo-config"
import Script from "next/script"

// Add page-specific metadata
export const metadata = {
  title: "Accessibility Statement | Navdhya",
  description: "Navdhya's commitment to digital accessibility for all users. We strive to make our platform usable by everyone, regardless of abilities or disabilities.",
  keywords: "accessibility, inclusive design, digital accessibility, web accessibility, Navdhya accessibility",
  openGraph: {
    title: "Accessibility Statement | Navdhya",
    description: "Navdhya's commitment to digital accessibility for all users. We strive to make our platform usable by everyone, regardless of abilities or disabilities.",
    url: "https://www.navdhya.com/legal/accessibility",
    siteName: SEO_CONFIG.siteName,
    images: [
      {
        url: SEO_CONFIG.defaultImage,
        width: 1200,
        height: 630,
        alt: "Navdhya Accessibility Commitment",
      },
    ],
    locale: SEO_CONFIG.locale,
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "Accessibility Statement | Navdhya",
    description: "Navdhya's commitment to digital accessibility for all users. We strive to make our platform usable by everyone, regardless of abilities or disabilities.",
    images: [SEO_CONFIG.defaultImage],
    site: SEO_CONFIG.twitter.site,
  },
  alternates: {
    canonical: "https://www.navdhya.com/legal/accessibility",
  },
}

// Schema markup for the accessibility statement page
const ACCESSIBILITY_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "WebPage",
  name: "Accessibility Statement - Navdhya",
  url: "https://www.navdhya.com/legal/accessibility",
  description: "Navdhya's commitment to digital accessibility for all users. We strive to make our platform usable by everyone, regardless of abilities or disabilities.",
  breadcrumb: BREADCRUMB_SCHEMA([
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Legal", url: "https://www.navdhya.com/legal" },
    { name: "Accessibility Statement", url: "https://www.navdhya.com/legal/accessibility" }
  ])
}

export default function AccessibilityPage() {
  const accessibilitySchema = JSON.stringify(ACCESSIBILITY_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: accessibilitySchema }}
      />
      <main className="mx-auto max-w-6xl px-4 py-12 space-y-10 overflow-x-hidden w-full">
        <section aria-labelledby="accessibility-heading">
          <h1 id="accessibility-heading" className="font-serif text-3xl">
            Accessibility Statement
          </h1>
          <p className="mt-3 text-muted-foreground">
            Navdhya is committed to ensuring digital accessibility for people with disabilities. We are continually improving the user experience for everyone and applying the relevant accessibility standards.
          </p>
        </section>

        <section aria-labelledby="our-commitment">
          <h2 id="our-commitment" className="font-serif text-2xl">
            Our Commitment
          </h2>
          <p className="mt-3 text-muted-foreground">
            We are committed to making our website and digital services accessible to all users, including those with disabilities. This commitment encompasses our efforts to ensure that our digital platforms comply with internationally recognized accessibility guidelines.
          </p>
          <p className="mt-3 text-muted-foreground">
            Our goal is to provide an inclusive and user-friendly experience for everyone, regardless of their abilities, devices, or browsing methods.
          </p>
        </section>

        <section aria-labelledby="accessibility-standards">
          <h2 id="accessibility-standards" className="font-serif text-2xl">
            Accessibility Standards
          </h2>
          <p className="mt-3 text-muted-foreground">
            We strive to conform to the Web Content Accessibility Guidelines (WCAG) 2.1 Level AA standards. These guidelines explain how to make web content more accessible for people with disabilities and user-friendly for all.
          </p>
          <p className="mt-3 text-muted-foreground">
            Our efforts to improve accessibility include:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Regular accessibility audits and assessments</li>
            <li>Implementation of semantic HTML for proper content structure</li>
            <li>Alternative text for images and non-text content</li>
            <li>Keyboard navigation support for all interactive elements</li>
            <li>Sufficient color contrast for text and background elements</li>
            <li>Resizable text without loss of content or functionality</li>
            <li>Closed captions for video content where applicable</li>
            <li>Clear and consistent navigation throughout the website</li>
          </ul>
        </section>

        <section aria-labelledby="technical-features">
          <h2 id="technical-features" className="font-serif text-2xl">
            Technical Accessibility Features
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Navigation and Structure
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Skip navigation links for keyboard users</li>
                <li>Logical heading structure (H1, H2, H3, etc.)</li>
                <li>Descriptive link text for better understanding</li>
                <li>Breadcrumb navigation for easy orientation</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Visual Design
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>High contrast color schemes</li>
                <li>Responsive design for all screen sizes</li>
                <li>Text alternatives for all non-text content</li>
                <li>Consistent layout and design patterns</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Forms and Interactions
              </h3>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Clear form labels and instructions</li>
                <li>Error identification and suggestions for correction</li>
                <li>Keyboard operable form controls</li>
                <li>Focus indicators for interactive elements</li>
              </ul>
            </div>
          </div>
        </section>

        <section aria-labelledby="assistive-technology">
          <h2 id="assistive-technology" className="font-serif text-2xl">
            Compatibility with Assistive Technology
          </h2>
          <p className="mt-3 text-muted-foreground">
            Our website is designed to work with commonly used assistive technologies, including:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Screen readers (NVDA, JAWS, VoiceOver)</li>
            <li>Screen magnification software</li>
            <li>Speech recognition software</li>
            <li>Keyboard-only navigation</li>
            <li>Voice control applications</li>
          </ul>
          <p className="mt-3 text-muted-foreground">
            We test our website with these technologies to ensure compatibility and usability.
          </p>
        </section>

        <section aria-labelledby="continuous-improvement">
          <h2 id="continuous-improvement" className="font-serif text-2xl">
            Continuous Improvement
          </h2>
          <p className="mt-3 text-muted-foreground">
            We are committed to continuous improvement of our website's accessibility. Our ongoing efforts include:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Regular accessibility training for our development team</li>
            <li>Automated and manual accessibility testing</li>
            <li>User feedback collection and implementation</li>
            <li>Periodic accessibility audits by third-party experts</li>
            <li>Updating our content management system for better accessibility features</li>
          </ul>
        </section>

        <section aria-labelledby="feedback">
          <h2 id="feedback" className="font-serif text-2xl">
            Feedback and Support
          </h2>
          <p className="mt-3 text-muted-foreground">
            We welcome your feedback on the accessibility of our website. If you encounter any accessibility barriers or have suggestions for improvement, please contact us:
          </p>
          <address className="mt-3 not-italic text-muted-foreground">
            Navdhya Accessibility Team<br />
            Email: accessibility@navdhya.com<br />
            Phone: +91-7289000377<br />
            Postal Address: Navdhya Ritual Services, Pandav Nagar, Delhi 110092, India
          </address>
          <p className="mt-3 text-muted-foreground">
            We aim to respond to accessibility issues within 2 business days and resolve them as quickly as possible.
          </p>
        </section>

        <section aria-labelledby="third-party-content">
          <h2 id="third-party-content" className="font-serif text-2xl">
            Third-Party Content
          </h2>
          <p className="mt-3 text-muted-foreground">
            While we strive to ensure all content on our website is accessible, some third-party content may have limitations:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>User-generated content from community forums</li>
            <li>Third-party widgets and plugins</li>
            <li>External links to partner websites</li>
            <li>Social media content embedded on our pages</li>
          </ul>
          <p className="mt-3 text-muted-foreground">
            We work with third-party providers to improve accessibility where possible and provide alternative access methods when needed.
          </p>
        </section>

        <section aria-labelledby="accessibility-contact">
          <h2 id="accessibility-contact" className="font-serif text-2xl">
            Contact Information
          </h2>
          <p className="mt-3 text-muted-foreground">
            For questions about our accessibility efforts or to request assistance with accessing our content, please contact:
          </p>
          <address className="mt-3 not-italic text-muted-foreground">
            Navdhya Accessibility Officer<br />
            Email: accessibility@navdhya.com<br />
            Phone: +91-7289000377<br />
            Customer Support Hours: Monday-Sunday, 9:00 AM - 9:00 PM IST
          </address>
          <p className="mt-3 text-muted-foreground">
            We are committed to working with you to provide the information you need in an accessible format.
          </p>
        </section>
      </main>
    </>
  )
}
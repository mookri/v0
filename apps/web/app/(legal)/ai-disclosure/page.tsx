import { seoManager } from "@/lib/seo-manager"

export const metadata = seoManager.generateMetadata({
  title: "AI Disclosure",
  description:
    "Learn how Navdhya uses software and automation across booking operations, support workflows, and published content.",
  path: "/ai-disclosure",
  keywords: ["ai disclosure", "automation policy", "content review", "navdhya trust"],
})

const AI_DISCLOSURE_SCHEMA = seoManager.generateSchema({
  type: "WebPage",
  name: "AI Disclosure - Navdhya",
  description:
    "Learn how Navdhya uses software and automation across booking operations, support workflows, and published content.",
  path: "/ai-disclosure",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "AI Disclosure", url: "https://www.navdhya.com/ai-disclosure" },
  ],
})

export default function AIDisclosurePage() {
  return (
    <>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(AI_DISCLOSURE_SCHEMA) }}
      />
      <main className="mx-auto max-w-4xl px-4 py-12 space-y-8">
        <section className="space-y-3">
          <h1 className="font-serif text-3xl">AI Disclosure</h1>
          <p className="text-muted-foreground">
            Navdhya uses software and automation to support booking operations, customer support workflows, and selected content maintenance tasks.
            Ritual execution, pandit assignment, service confirmation, and customer assistance remain human-led.
          </p>
        </section>

        <section className="space-y-3">
          <h2 className="font-serif text-2xl">Where automation is used</h2>
          <p className="text-muted-foreground">
            Automation may assist with inquiry routing, content formatting, scheduling support, reminders, and internal quality checks.
            These systems are used to speed up response times and reduce operational delays for families planning ceremonies.
          </p>
        </section>

        <section className="space-y-3">
          <h2 className="font-serif text-2xl">Human review and service delivery</h2>
          <p className="text-muted-foreground">
            Service recommendations, booking confirmation, ceremony planning, and pandit coordination are reviewed by the Navdhya team.
            Families can contact support directly whenever they need clarification on pricing, timing, materials, or ritual requirements.
          </p>
        </section>

        <section className="space-y-3">
          <h2 className="font-serif text-2xl">Content accuracy</h2>
          <p className="text-muted-foreground">
            Some digital content may be drafted or updated with software assistance, then reviewed before publication.
            If you notice anything inaccurate or unclear, please contact <a className="text-primary hover:underline" href="mailto:help@navdhya.com">help@navdhya.com</a> so the team can review it promptly.
          </p>
        </section>

        <section className="space-y-3">
          <h2 className="font-serif text-2xl">Last updated</h2>
          <p className="text-muted-foreground">May 25, 2026</p>
        </section>
      </main>
    </>
  )
}

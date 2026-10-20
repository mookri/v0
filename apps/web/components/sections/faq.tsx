import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion"
import Script from "next/script"
import { FAQ_SCHEMA } from "@/lib/seo-config"

export function FAQ() {
  const faqs = [
    [
      "How far in advance should I book a puja with Navdhya?",
      "We recommend booking 5\u20137 days in advance for the best availability. Same-day bookings are possible subject to pandit availability in your area.",
    ],
    [
      "What is included in Navdhya\u2019s puja package?",
      "Every Navdhya puja package includes a verified pandit, complete samagri, and basic setup. Mandap decoration is available as an add-on. The full ritual follows prescribed Vedic vidhi.",
    ],
    ["Can I customize the ritual?", "Yes. Navdhya respects regional traditions and can adapt rituals to match your family\u2019s specific customs and preferences."],
    ["Does Navdhya serve areas outside Delhi-NCR?", "Yes. Navdhya offers live online puja services for families anywhere in India. For in-person home pujas, we currently serve Delhi-NCR with pan-India expansion underway."],
    ["What if I need to reschedule?", "You can reschedule your Navdhya booking up to 24 hours before the ceremony at no additional charge."],
  ]

  // Create FAQ schema markup
  const faqSchema = FAQ_SCHEMA(faqs.map(([question, answer]) => ({ question, answer })))

  return (
    <section aria-labelledby="faq-title" className="bg-muted/50">
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(faqSchema) }}
      />
      <div className="mx-auto max-w-6xl px-4 py-12 overflow-x-hidden w-full">
        <div className="pb-4">
          <div className="inline-block">
            <p style={{
              color: '#6B7280',
              letterSpacing: '1px',
              lineHeight: '12px',
              textTransform: 'uppercase',
              fontWeight: 500,
              fontSize: '10px',
              marginBottom: '12px',
              fontFamily: "'DM Sans', sans-serif"
            }}
            className="text-left"
            >
              Common Queries - Clarified
            </p>
            <h2 
              id="faq-title"
              className="font-serif text-2xl md:text-3xl lg:text-3xl font-bold tracking-tight text-left mb-3 relative inline-block"
              style={{ color: '#111827' }}
            >
              Frequently Asked Questions
            </h2>
          </div>
        </div>
        <Accordion type="single" collapsible className="mt-6 w-full">
          {faqs.map(([q, a], i) => (
            <AccordionItem key={i} value={`item-${i}`} className="w-full">
              <AccordionTrigger className="text-left">{q}</AccordionTrigger>
              <AccordionContent className="text-sm text-muted-foreground">{a}</AccordionContent>
            </AccordionItem>
          ))}
        </Accordion>
      </div>
    </section>
  )
}
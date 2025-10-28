import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion"

export function FAQ() {
  const faqs = [
    [
      "How far in advance should I book?",
      "We recommend 5–7 days in advance. Same-day bookings are possible subject to availability.",
    ],
    [
      "What is included in the puja package?",
      "Experienced pandit, complete samagri, and basic setup. Mandap decoration available as an add-on.",
    ],
    ["Can I customize the ritual?", "Yes, we respect regional traditions and can adapt rituals accordingly."],
    ["Do pandits travel outside the city?", "Yes, we serve multiple cities with travel charges if applicable."],
    ["What if I need to reschedule?", "You can reschedule up to 24 hours before the event."],
  ]
  return (
    <section aria-labelledby="faq-title" className="bg-muted/50">
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
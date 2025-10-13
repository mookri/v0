import { SiteHeader } from "@/components/site-header"
import { SiteFooter } from "@/components/site-footer"
import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Button } from "@/components/ui/button"

export default function ContactPage() {
  return (
    <>
      <SiteHeader />
      <main className="mx-auto max-w-6xl px-4 py-12 grid md:grid-cols-2 gap-8">
        <section aria-labelledby="contact-us">
          <h1 id="contact-us" className="font-serif text-3xl">
            Contact Us
          </h1>
          <p className="mt-3 text-muted-foreground">
            We’re here to help you choose the right ritual and plan your ceremony.
          </p>
          <form className="mt-6 space-y-4">
            <div>
              <Label htmlFor="name">Full Name</Label>
              <Input id="name" required />
            </div>
            <div>
              <Label htmlFor="email">Email</Label>
              <Input id="email" type="email" required />
            </div>
            <div>
              <Label htmlFor="message">Message</Label>
              <Textarea id="message" required />
            </div>
            <Button type="submit" className="bg-primary text-primary-foreground">
              Send
            </Button>
          </form>
          <div className="mt-6 text-sm">
            Or reach us directly:{" "}
            <a className="underline" href="tel:+910000000000">
              +91 00000 00000
            </a>{" "}
            •{" "}
            <a className="underline" href="mailto:hello@navdhya.example">
              hello@navdhya.example
            </a>{" "}
            •{" "}
            <a className="underline" href="https://wa.me/910000000000" target="_blank" rel="noopener noreferrer">
              WhatsApp
            </a>
          </div>
        </section>
        <aside aria-label="Office and Map">
          <div className="rounded-lg border bg-card p-5">
            <h2 className="font-serif text-xl">Office Location</h2>
            <p className="text-sm text-muted-foreground mt-2">Registered Office — New Delhi</p>
            <img
              src="/office-location-map-new-delhi.jpg"
              alt="Map showing office location in New Delhi"
              className="rounded-md border mt-4"
            />
          </div>
        </aside>
      </main>
      <SiteFooter />
    </>
  )
}

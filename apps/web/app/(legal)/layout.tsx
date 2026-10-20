import Link from "next/link";
import { MainShell } from "@/components/dlo-x/shells/MainShell";

export default function LegalLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <MainShell>
      <main className="min-h-screen">
        {children}
        <section className="mx-auto max-w-6xl px-4 pb-16">
          <div className="rounded-xl border border-border bg-card/50 p-6">
            <h2 className="text-xl font-semibold">Helpful legal and support links</h2>
            <p className="mt-2 text-sm text-muted-foreground">
              Review related policies, contact our team, or return to booking and support pages.
            </p>
            <div className="mt-4 flex flex-wrap gap-3 text-sm">
              <Link href="/accessibility" className="text-primary hover:underline">Accessibility</Link>
              <Link href="/privacy-policy" className="text-primary hover:underline">Privacy Policy</Link>
              <Link href="/terms-of-service" className="text-primary hover:underline">Terms of Service</Link>
              <Link href="/cookie-policy" className="text-primary hover:underline">Cookie Policy</Link>
              <Link href="/contact" className="text-primary hover:underline">Contact Navdhya</Link>
              <Link href="/book" className="text-primary hover:underline">Book a puja</Link>
            </div>
          </div>
        </section>
      </main>
    </MainShell>
  )
}

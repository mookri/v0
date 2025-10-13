import Link from "next/link"

export function SiteFooter() {
  return (
    <footer className="bg-foreground text-background" role="contentinfo">
      <div className="mx-auto max-w-6xl px-4 py-12 grid md:grid-cols-4 gap-8">
        <div>
          <h3 className="font-serif text-lg">About Navdhya</h3>
          <p className="text-sm mt-2 opacity-90">
            Authentic Hindu rituals performed by certified Vedic scholars. Hassle-free arrangements with complete
            samagri and setup.
          </p>
          <div className="mt-3 text-sm opacity-90">Certifications • Trusted • Secure</div>
          <div className="mt-3 text-sm flex gap-3">
            <Link href="#" className="underline">
              Instagram
            </Link>
            <Link href="#" className="underline">
              Facebook
            </Link>
            <Link href="#" className="underline">
              YouTube
            </Link>
            <Link href="#" className="underline">
              Twitter
            </Link>
          </div>
        </div>

        <div>
          <h3 className="font-serif text-lg">Quick Links</h3>
          <ul className="text-sm mt-2 space-y-1 opacity-90">
            <li>
              <Link className="underline" href="/">
                Home
              </Link>
            </li>
            <li>
              <Link className="underline" href="/about">
                About
              </Link>
            </li>
            <li>
              <a className="underline" href="#catalog">
                Puja Catalog
              </a>
            </li>
            <li>
              <a className="underline" href="#blog">
                Blog
              </a>
            </li>
            <li>
              <Link className="underline" href="/contact">
                Contact
              </Link>
            </li>
          </ul>
        </div>

        <div>
          <h3 className="font-serif text-lg">Popular Services</h3>
          <ul className="text-sm mt-2 space-y-1 opacity-90">
            <li>
              <a className="underline" href="#catalog">
                Grihapravesh
              </a>
            </li>
            <li>
              <a className="underline" href="#catalog">
                Satyanarayan Puja
              </a>
            </li>
            <li>
              <a className="underline" href="#catalog">
                Wedding Rituals
              </a>
            </li>
            <li>
              <a className="underline" href="#catalog">
                Shraddh
              </a>
            </li>
            <li>
              <a className="underline" href="#catalog">
                Festival Pujas
              </a>
            </li>
          </ul>
        </div>

        <div>
          <h3 className="font-serif text-lg">Contact & Support</h3>
          <ul className="text-sm mt-2 space-y-1 opacity-90">
            <li>+91 00000 00000 (9am–8pm)</li>
            <li>
              <a className="underline" href="mailto:hello@navdhya.example">
                hello@navdhya.example
              </a>
            </li>
            <li>Registered Office — New Delhi</li>
            <li>Live chat: 9am–8pm</li>
          </ul>
        </div>
      </div>
      <div className="border-t border-background/20">
        <div className="mx-auto max-w-6xl px-4 py-4 flex items-center justify-between text-xs opacity-90">
          <div>© {new Date().getFullYear()} Navdhya. All rights reserved.</div>
          <div className="flex gap-3">
            <Link href="#" className="underline">
              Privacy
            </Link>
            <Link href="#" className="underline">
              Terms
            </Link>
            <Link href="#" className="underline">
              Refunds
            </Link>
          </div>
        </div>
      </div>
    </footer>
  )
}

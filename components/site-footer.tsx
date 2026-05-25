import Image from "next/image"
import Link from "next/link"
import { Facebook, Instagram, Mail, MapPin, Phone, Youtube } from "lucide-react"
import { footerData } from "@/lib/footerData"

const LEGAL_LINKS = [
  { href: "/privacy-policy", label: "Privacy Policy" },
  { href: "/terms-of-service", label: "Terms of Service" },
  { href: "/refund-policy", label: "Refund Policy" },
  { href: "/cookie-policy", label: "Cookie Policy" },
]

const SOCIAL_LINKS = [
  { href: "https://www.facebook.com/thenavdhya", label: "Facebook", icon: Facebook },
  { href: "https://www.instagram.com/thenavdhya", label: "Instagram", icon: Instagram },
  { href: "https://www.youtube.com/@thenavdhya", label: "YouTube", icon: Youtube },
]

const PAYMENT_LOGOS = [
  "/payments/payment.svg",
  "/payments/payment2.svg",
  "/payments/payment3.svg",
  "/payments/payment4.svg",
]

function FooterList({
  title,
  links,
}: {
  title: string
  links: Array<{ href: string; label: string }>
}) {
  return (
    <div>
      <h3 className="text-sm font-semibold mb-3 text-[#ff9933]">{title}</h3>
      <ul className="space-y-2">
        {links.map((link) => (
          <li key={link.href}>
            <Link href={link.href} className="text-sm text-gray-300 hover:text-white transition-colors">
              {link.label}
            </Link>
          </li>
        ))}
      </ul>
    </div>
  )
}

export function SiteFooter() {
  return (
    <footer className="bg-gradient-to-b from-[#0d1b2a] to-[#0a121f] text-white pb-20 md:pb-0">
      <div className="container mx-auto max-w-7xl px-4 py-10">
        <div className="grid gap-8 lg:grid-cols-[1.2fr_1fr_1fr_1fr_1fr]">
          <div className="space-y-4">
            <Image
              src="/navdhya-logo.png"
              alt="Navdhya Ritual Services Logo"
              width={192}
              height={64}
              className="h-12 w-auto"
            />
            <p className="text-sm text-gray-300 leading-relaxed">
              Verified pandit booking, home pujas, online rituals, and astrology support for families across Delhi NCR.
            </p>
            <div className="space-y-2 text-sm text-gray-300">
              <div className="flex items-start gap-2">
                <MapPin size={16} className="mt-0.5 text-[#ff9933]" />
                <span>{footerData.contact.address}</span>
              </div>
              <div className="flex items-center gap-2">
                <Phone size={16} className="text-[#ff9933]" />
                <a href={`tel:${footerData.contact.phone}`} className="hover:text-white transition-colors">
                  {footerData.contact.phone}
                </a>
              </div>
              <div className="flex items-center gap-2">
                <Mail size={16} className="text-[#ff9933]" />
                <a href={`mailto:${footerData.contact.email}`} className="hover:text-white transition-colors">
                  {footerData.contact.email}
                </a>
              </div>
            </div>
            <div className="flex items-center gap-3">
              {SOCIAL_LINKS.map(({ href, label, icon: Icon }) => (
                <a
                  key={href}
                  href={href}
                  target="_blank"
                  rel="noopener noreferrer"
                  aria-label={label}
                  className="h-9 w-9 rounded-lg border border-white/10 flex items-center justify-center hover:bg-white/5 transition-colors"
                >
                  <Icon size={16} className="text-[#ff9933]" />
                </a>
              ))}
            </div>
          </div>

          <FooterList title="Quick Links" links={footerData.quickLinks} />
          <FooterList title="Our Services" links={footerData.services} />
          <FooterList title="Astrology" links={footerData.astrology} />
          <FooterList title="Our Centers" links={footerData.centers} />
        </div>
      </div>

      <div className="border-t border-white/10 bg-[#0a121f]">
        <div className="container mx-auto max-w-7xl px-4 py-6 space-y-5">
          <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            {/* <p className="text-xs text-gray-400 leading-relaxed max-w-3xl">
              Navdhya uses software and automation to support booking operations, but ritual guidance and service delivery remain human-led.
              {" "}
              <Link href="/ai-disclosure" className="text-[#ff9933] hover:text-white transition-colors">
                Read our AI disclosure
              </Link>
              .
            </p> */}
            <div className="flex items-center gap-3">
              {PAYMENT_LOGOS.map((src, index) => (
                <Image
                  key={src}
                  src={src}
                  alt={`Accepted payment method ${index + 1}`}
                  width={40}
                  height={24}
                  className="h-6 w-auto"
                />
              ))}
            </div>
          </div>

          <div className="flex flex-wrap gap-x-4 gap-y-2">
            {LEGAL_LINKS.map((link) => (
              <Link
                key={link.href}
                href={link.href}
                className="text-[11px] text-gray-400 hover:text-[#ff9933] transition-colors"
              >
                {link.label}
              </Link>
            ))}
          </div>

          <p className="text-xs text-gray-500">
            © 2026 Navdhya. All rights reserved.
          </p>
        </div>
      </div>
    </footer>
  )
}

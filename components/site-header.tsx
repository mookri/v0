"use client"
import Link from "next/link"
import { useEffect, useState } from "react"
import { Button } from "@/components/ui/button"
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from "@/components/ui/dropdown-menu"
import { cn } from "@/lib/utils"
import { Sheet, SheetContent, SheetTrigger, SheetClose } from "@/components/ui/sheet"
import { Separator } from "@/components/ui/separator"
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion"

export function SiteHeader() {
  const [scrolled, setScrolled] = useState(false)
  const [mobileOpen, setMobileOpen] = useState(false) // control Sheet open for close-on-link
  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 8)
    onScroll()
    window.addEventListener("scroll", onScroll, { passive: true })
    return () => window.removeEventListener("scroll", onScroll)
  }, [])

  return (
    <header
      className={cn(
        "sticky top-0 z-50 transition-colors",
        scrolled
          ? "bg-background/80 backdrop-blur supports-[backdrop-filter]:bg-background/60 border-b"
          : "bg-transparent",
      )}
      role="banner"
    >
      <div className="mx-auto max-w-6xl px-4 py-3 flex items-center justify-between">
        <Link href="/" className="flex items-center gap-2" aria-label="Navdhya Home">
          <div className="size-8 rounded-md bg-primary/15 grid place-items-center">
            <span className="font-serif text-primary text-xl">ॐ</span>
          </div>
          <span className="font-serif text-xl tracking-wide">Navdhya</span>
        </Link>

        <nav aria-label="Primary" className="hidden md:flex items-center gap-6">
          <Link href="/" className="hover:text-primary transition-colors">
            Home
          </Link>
          <Link href="/about" className="hover:text-primary transition-colors">
            About
          </Link>

          <DropdownMenu>
            <DropdownMenuTrigger className="hover:text-primary outline-none">Services</DropdownMenuTrigger>
            <DropdownMenuContent className="grid grid-cols-3 gap-6 p-6 w-[700px] bg-card" align="start">
              <div>
                <p className="font-serif text-sm mb-2 text-muted-foreground">Home Rituals</p>
                <ul className="space-y-1 text-sm">
                  <li>
                    <Link href="#catalog" className="hover:text-primary">
                      Grihapravesh
                    </Link>
                  </li>
                  <li>
                    <Link href="#catalog" className="hover:text-primary">
                      Satyanarayan Puja
                    </Link>
                  </li>
                  <li>
                    <Link href="#catalog" className="hover:text-primary">
                      Vastu Puja
                    </Link>
                  </li>
                </ul>
              </div>
              <div>
                <p className="font-serif text-sm mb-2 text-muted-foreground">Life Events</p>
                <ul className="space-y-1 text-sm">
                  <li>
                    <Link href="#catalog" className="hover:text-primary">
                      Weddings
                    </Link>
                  </li>
                  <li>
                    <Link href="#catalog" className="hover:text-primary">
                      Naming Ceremony
                    </Link>
                  </li>
                  <li>
                    <Link href="#catalog" className="hover:text-primary">
                      Thread Ceremony
                    </Link>
                  </li>
                </ul>
              </div>
              <div>
                <p className="font-serif text-sm mb-2 text-muted-foreground">Special Occasions</p>
                <ul className="space-y-1 text-sm">
                  <li>
                    <Link href="#catalog" className="hover:text-primary">
                      Festival Pujas
                    </Link>
                  </li>
                  <li>
                    <Link href="#catalog" className="hover:text-primary">
                      Corporate Events
                    </Link>
                  </li>
                  <li>
                    <Link href="#catalog" className="hover:text-primary">
                      Shraddh
                    </Link>
                  </li>
                </ul>
              </div>
            </DropdownMenuContent>
          </DropdownMenu>

          <Link href="#process" className="hover:text-primary transition-colors">
            How It Works
          </Link>
          <Link href="#blog" className="hover:text-primary transition-colors">
            Blog
          </Link>
          <Link href="/contact" className="hover:text-primary transition-colors">
            Contact
          </Link>
        </nav>

        <div className="hidden md:flex items-center gap-4">
          <a href="tel:+910000000000" className="text-sm hover:text-primary">
            +91 00000 00000
          </a>
          <Button asChild className="bg-primary text-primary-foreground">
            <Link href="#booking">Book Pandit Ji</Link>
          </Button>
        </div>

        <div className="md:hidden">
          <Sheet open={mobileOpen} onOpenChange={setMobileOpen}>
            <SheetTrigger asChild>
              <Button variant="outline" size="sm" aria-label="Open menu" className="gap-2 bg-transparent">
                <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                </svg>
                Menu
              </Button>
            </SheetTrigger>
            <SheetContent side="right" className="w-80">
              <div className="flex items-center justify-between">
                <Link href="/" className="flex items-center gap-2" aria-label="Navdhya Home">
                  <div className="size-8 rounded-md bg-primary/15 grid place-items-center">
                    <span className="font-serif text-primary text-xl">ॐ</span>
                  </div>
                  <span className="font-serif text-lg tracking-wide">Navdhya</span>
                </Link>
              </div>

              <nav aria-label="Mobile" className="mt-6 space-y-4">
                <SheetClose asChild>
                  <Link href="/" className="block rounded px-1.5 py-1.5 hover:text-primary">
                    Home
                  </Link>
                </SheetClose>
                <SheetClose asChild>
                  <Link href="/about" className="block rounded px-1.5 py-1.5 hover:text-primary">
                    About
                  </Link>
                </SheetClose>

                <div>
                  <div className="text-sm font-serif text-muted-foreground">Services</div>
                  <Separator className="my-2" />
                  <Accordion type="single" collapsible className="w-full">
                    <AccordionItem value="home-rituals" className="border-none">
                      <AccordionTrigger className="py-2 text-base">Home Rituals</AccordionTrigger>
                      <AccordionContent className="pl-2">
                        <ul className="space-y-2 text-sm">
                          <li>
                            <SheetClose asChild>
                              <Link href="#catalog" className="block rounded px-1 py-1 hover:text-primary">
                                Grihapravesh
                              </Link>
                            </SheetClose>
                          </li>
                          <li>
                            <SheetClose asChild>
                              <Link href="#catalog" className="block rounded px-1 py-1 hover:text-primary">
                                Satyanarayan Puja
                              </Link>
                            </SheetClose>
                          </li>
                          <li>
                            <SheetClose asChild>
                              <Link href="#catalog" className="block rounded px-1 py-1 hover:text-primary">
                                Vastu Puja
                              </Link>
                            </SheetClose>
                          </li>
                        </ul>
                      </AccordionContent>
                    </AccordionItem>

                    <AccordionItem value="life-events" className="border-none">
                      <AccordionTrigger className="py-2 text-base">Life Events</AccordionTrigger>
                      <AccordionContent className="pl-2">
                        <ul className="space-y-2 text-sm">
                          <li>
                            <SheetClose asChild>
                              <Link href="#catalog" className="block rounded px-1 py-1 hover:text-primary">
                                Weddings
                              </Link>
                            </SheetClose>
                          </li>
                          <li>
                            <SheetClose asChild>
                              <Link href="#catalog" className="block rounded px-1 py-1 hover:text-primary">
                                Naming Ceremony
                              </Link>
                            </SheetClose>
                          </li>
                          <li>
                            <SheetClose asChild>
                              <Link href="#catalog" className="block rounded px-1 py-1 hover:text-primary">
                                Thread Ceremony
                              </Link>
                            </SheetClose>
                          </li>
                        </ul>
                      </AccordionContent>
                    </AccordionItem>

                    <AccordionItem value="special-occasions" className="border-none">
                      <AccordionTrigger className="py-2 text-base">Special Occasions</AccordionTrigger>
                      <AccordionContent className="pl-2">
                        <ul className="space-y-2 text-sm">
                          <li>
                            <SheetClose asChild>
                              <Link href="#catalog" className="block rounded px-1 py-1 hover:text-primary">
                                Festival Pujas
                              </Link>
                            </SheetClose>
                          </li>
                          <li>
                            <SheetClose asChild>
                              <Link href="#catalog" className="block rounded px-1 py-1 hover:text-primary">
                                Corporate Events
                              </Link>
                            </SheetClose>
                          </li>
                          <li>
                            <SheetClose asChild>
                              <Link href="#catalog" className="block rounded px-1 py-1 hover:text-primary">
                                Shraddh
                              </Link>
                            </SheetClose>
                          </li>
                        </ul>
                      </AccordionContent>
                    </AccordionItem>
                  </Accordion>
                </div>

                <SheetClose asChild>
                  <Link href="#process" className="block rounded px-1.5 py-1.5 hover:text-primary">
                    How It Works
                  </Link>
                </SheetClose>
                <SheetClose asChild>
                  <Link href="#blog" className="block rounded px-1.5 py-1.5 hover:text-primary">
                    Blog
                  </Link>
                </SheetClose>
                <SheetClose asChild>
                  <Link href="/contact" className="block rounded px-1.5 py-1.5 hover:text-primary">
                    Contact
                  </Link>
                </SheetClose>

                <div className="pt-4">
                  <SheetClose asChild>
                    <Button asChild className="w-full bg-primary text-primary-foreground">
                      <Link href="#booking">Book Pandit Ji</Link>
                    </Button>
                  </SheetClose>
                </div>
              </nav>
            </SheetContent>
          </Sheet>
        </div>
      </div>
    </header>
  )
}

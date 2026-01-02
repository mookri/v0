"use client"
import Link from "next/link"
import { useEffect, useState } from "react"
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from "@/components/ui/dropdown-menu"
import { Sheet, SheetContent, SheetTrigger, SheetClose } from "@/components/ui/sheet"
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion"
import { Search, Phone, User, Heart, ShoppingCart, Menu, X, ChevronDown } from 'lucide-react'
import { MENU_GROUPS } from "@/lib/menuData"

// Function to transform MENU_GROUPS data into the format needed for site header
const transformMenuData = () => {
  // Create a map of group names to their items for quick lookup
  const groupMap = MENU_GROUPS.reduce((acc, group) => {
    acc[group.group] = group.items;
    return acc;
  }, {} as Record<string, typeof MENU_GROUPS[0]['items']>);

  return [
    {
      name: 'Home',
      href: '/'
    },
    {
      name: 'Pujas',
      href: '/pujas',
      subItems: groupMap["Explore Rituals"]?.slice(0, 5).map(item => ({
        name: item.label,
        href: item.href
      })) || []
    },
    {
      name: 'Pandits',
      href: '/pandits',
      subItems: groupMap["Our Pandits"]?.map(item => ({
        name: item.label,
        href: item.href
      })) || []
    },
    {
      name: 'Learning',
      href: '/learn',
      subItems: groupMap["Learn & Spiritual Growth"]?.map(item => ({
        name: item.label,
        href: item.href
      })) || []
    },
    {
      name: 'Community',
      href: '/community',
      subItems: groupMap["Community"]?.map(item => ({
        name: item.label,
        href: item.href
      })) || []
    },
    {
      name: 'Resources',
      href: '/resources',
      subItems: groupMap["Resources"]?.map(item => ({
        name: item.label,
        href: item.href
      })) || []
    },
    {
      name: 'Contact',
      href: '/contact'
    }
  ];
};

export function SiteHeader() {
  const [scrolled, setScrolled] = useState(false)
  const [mobileOpen, setMobileOpen] = useState(false) // control Sheet open for close-on-link
  const [isSearchOpen, setIsSearchOpen] = useState(false)
  const [activeTab, setActiveTab] = useState('main') // 'main' or 'categories'

  // Transform menu data from single source
  const menuData = transformMenuData();

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 8)
    onScroll()
    window.addEventListener("scroll", onScroll, { passive: true })
    return () => window.removeEventListener("scroll", onScroll)
  }, [])

  // Close mobile menu when resizing to desktop
  useEffect(() => {
    const handleResize = () => {
      if (window.innerWidth >= 768) { // md breakpoint
        setMobileOpen(false);
        setIsSearchOpen(false);
      }
    };

    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, []);

  return (
    <div className="w-full bg-background">
      {/* Top Promotional Banner */}
      <div className="bg-red-50 border-b border-red-100">
        <div className="container mx-auto px-4 py-2 flex items-center justify-between text-sm">
          <div className="flex items-center gap-2">
            <svg className="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
            <span className="text-gray-700">We are Currently in Development Mode, Further Information You can call us</span>
          </div>
          <div className="hidden lg:flex items-center gap-6 text-gray-700">
            <a href="/about" className="hover:text-gray-900">About Us</a>
            <a href="#blog" className="hover:text-gray-900">Blog</a>
            <a href="/contact" className="hover:text-gray-900">Contact</a>
          </div>
        </div>
      </div>

      {/* Main Header */}
      <div className="border-b border-border">
        <div className="container mx-auto px-4 py-4">
          {/* Desktop Layout */}
          <div className="hidden lg:flex items-center justify-between">
            {/* Logo */}
            <div className="flex items-center gap-8">
              <Link href="/" className="flex items-center gap-2" aria-label="Navdhya Home">
                <img
                  src="/navdhya-logo.png"
                  alt="Navdhya Ritual Services Logo"
                  className="h-8 w-auto"
                />
              </Link>

              {/* Desktop Navigation Links */}
              <nav className="flex items-center gap-5 text-sm">
                {menuData.map((item, index) => (
                  item.subItems ? (
                    <DropdownMenu key={index}>
                      <DropdownMenuTrigger className="hover:text-primary outline-none font-medium flex items-center gap-1 text-sm">
                        {item.name}
                        <ChevronDown className="w-4 h-4" />
                      </DropdownMenuTrigger>
                      <DropdownMenuContent className="w-[300px] sm:w-[400px] md:w-[500px] max-w-[calc(100vw-2rem)] bg-card border-border" align="start">
                        <div className="p-4 space-y-2">
                          {item.subItems.map((subItem, subIndex) => (
                            <Link
                              key={subIndex}
                              href={subItem.href}
                              className="block py-2 text-muted-foreground hover:text-primary hover:underline text-sm"
                            >
                              {subItem.name}
                            </Link>
                          ))}
                        </div>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  ) : (
                    <Link
                      key={index}
                      href={item.href}
                      className="hover:text-primary transition-colors font-medium text-sm"
                    >
                      {item.name}
                    </Link>
                  )
                ))}
              </nav>
            </div>

            {/* Right Actions (Search hidden on desktop) */}
            <div className="flex items-center gap-4">
              <div className="flex items-center gap-2 text-sm">
                <Phone className="w-4 h-4" />
                <div>
                  <div className="text-muted-foreground">Need help?</div>
                  <div className="font-semibold">+91 7289000377</div>
                </div>
              </div>

              <button className="p-2 hover:bg-muted rounded">
                <User className="w-5 h-5" />
              </button>

              <button className="p-2 hover:bg-muted rounded relative">
                <Heart className="w-5 h-5" />
                <span className="absolute -top-1 -right-1 w-5 h-5 bg-primary text-primary-foreground text-xs rounded-full flex items-center justify-center">0</span>
              </button>

              <button className="p-2 hover:bg-muted rounded relative">
                <ShoppingCart className="w-5 h-5" />
                <span className="absolute -top-1 -right-1 w-5 h-5 bg-primary text-primary-foreground text-xs rounded-full flex items-center justify-center">0</span>
              </button>
            </div>
          </div>

          {/* Mobile Layout */}
          <div className="lg:hidden">
            <div className="flex items-center justify-between">
              {/* Hamburger Menu */}
              <Sheet open={mobileOpen} onOpenChange={setMobileOpen}>
                <SheetTrigger asChild>
                  <button className="p-2">
                    {mobileOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
                  </button>
                </SheetTrigger>
                <SheetContent side="left" className="w-full max-w-xs sm:max-w-sm p-0 border-border bg-background">
                  <div className="p-4">
                    <div className="flex items-center justify-between mb-6">
                      <Link href="/" className="flex items-center gap-2" aria-label="Navdhya Home">
                        <img
                          src="/navdhya-logo.png"
                          alt="Navdhya Ritual Services Logo"
                          className="h-8 w-auto"
                        />
                      </Link>
                      <SheetClose asChild>
                        <button>
                          <X className="w-6 h-6" />
                        </button>
                      </SheetClose>
                    </div>

                    <div className="flex gap-2 mb-6">
                      <button
                        className={`flex-1 py-2 px-4 rounded-full text-sm font-medium ${activeTab === 'main' ? 'bg-primary text-primary-foreground' : 'bg-muted text-foreground'}`}
                        onClick={() => setActiveTab('main')}
                      >
                        MAIN MENU
                      </button>
                      <button
                        className={`flex-1 py-2 px-4 rounded-full text-sm font-medium ${activeTab === 'categories' ? 'bg-primary text-primary-foreground' : 'bg-muted text-foreground'}`}
                        onClick={() => setActiveTab('categories')}
                      >
                        CATEGORIES
                      </button>
                    </div>

                    {/* Tab Content */}
                    {activeTab === 'main' ? (
                      <nav className="space-y-1">
                        {menuData.map((item, index) => (
                          item.subItems ? (
                            <div key={index} className="py-2 border-b border-border">
                              <Accordion type="single" collapsible className="w-full" defaultValue="">
                                <AccordionItem value={`item-${index}`} className="border-none">
                                  <AccordionTrigger className="py-3 text-base px-0 hover:no-underline font-medium flex justify-between items-center">
                                    <span>{item.name}</span>
                                  </AccordionTrigger>
                                  <AccordionContent className="pb-0">
                                    <div className="py-2 space-y-2">
                                      {item.subItems.map((subItem, subIndex) => (
                                        <SheetClose asChild key={subIndex}>
                                          <Link
                                            href={subItem.href}
                                            className="block py-2 text-muted-foreground hover:text-primary hover:underline"
                                            onClick={() => setMobileOpen(false)}
                                          >
                                            {subItem.name}
                                          </Link>
                                        </SheetClose>
                                      ))}
                                    </div>
                                  </AccordionContent>
                                </AccordionItem>
                              </Accordion>
                            </div>
                          ) : (
                            <SheetClose asChild key={index}>
                              <Link
                                href={item.href}
                                className="block py-3 border-b border-border"
                                onClick={() => setMobileOpen(false)}
                              >
                                {item.name}
                              </Link>
                            </SheetClose>
                          )
                        ))}
                      </nav>
                    ) : (
                      /* Categories Tab - Show all pujas */
                      <nav className="space-y-4">
                        <div className="border-b border-border pb-4">
                          <h3 className="font-medium text-foreground mb-3">Quick Links</h3>
                          <div className="grid grid-cols-2 gap-2">
                            {menuData.slice(1, 4).map((item, index) => (
                              <SheetClose asChild key={index}>
                                <Link
                                  href={item.href}
                                  className="block py-2 px-3 rounded-lg bg-muted hover:bg-muted/80 text-sm"
                                  onClick={() => setMobileOpen(false)}
                                >
                                  {item.name}
                                </Link>
                              </SheetClose>
                            ))}
                          </div>
                        </div>
                      </nav>
                    )}

                    <div className="mt-8 pt-6 border-t border-border">
                      <div className="flex items-center gap-2 text-sm mb-4">
                        <Heart className="w-5 h-5" />
                        <span>Wishlist (0)</span>
                      </div>

                      {/* Language dropdown instead of buttons */}
                      <div className="mb-4">
                        <select className="w-full p-2 border border-input rounded bg-background text-foreground">
                          <option>English</option>
                          <option>हिंदी</option>
                        </select>
                      </div>

                      <div className="flex gap-4">
                        <button className="text-sm text-muted-foreground hover:text-foreground">
                          ₹ INR
                        </button>
                        <button className="text-sm text-muted-foreground hover:text-foreground">
                          $ USD
                        </button>
                      </div>
                    </div>
                  </div>
                </SheetContent>
              </Sheet>

              {/* Logo - Centered */}
              <div className="flex-1 flex justify-center">
                <Link href="/" className="flex items-center gap-2" aria-label="Navdhya Home">
                  <img
                    src="/navdhya-logo.png"
                    alt="Navdhya Ritual Services Logo"
                    className="h-10 w-auto"
                  />
                </Link>
              </div>

              {/* Auth and Search Icons - Right */}
              <div className="flex items-center gap-2">
                <button className="p-2 hover:bg-muted rounded">
                  <User className="w-5 h-5" />
                </button>
                <button
                  className="p-2 hover:bg-muted rounded"
                  onClick={() => setIsSearchOpen(!isSearchOpen)}
                >
                  <Search className="w-5 h-5" />
                </button>
              </div>
            </div>

            {/* Mobile Search */}
            {isSearchOpen && (
              <div className="mt-4">
                <div className="relative">
                  <input
                    type="text"
                    placeholder="Search for pujas"
                    className="w-full px-4 py-2 pr-10 border border-input rounded-lg focus:outline-none focus:border-primary bg-background"
                  />
                  <Search className="absolute right-3 top-2.5 w-5 h-5 text-muted-foreground" />
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  )
}
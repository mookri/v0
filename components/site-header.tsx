"use client"
import Link from "next/link"
import { useEffect, useState } from "react"
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from "@/components/ui/dropdown-menu"
import { Sheet, SheetContent, SheetTrigger, SheetClose } from "@/components/ui/sheet"
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion"
import { Search, Phone, User, Heart, ShoppingCart, Menu, X, ChevronDown } from 'lucide-react'

export function SiteHeader() {
  const [scrolled, setScrolled] = useState(false)
  const [mobileOpen, setMobileOpen] = useState(false) // control Sheet open for close-on-link
  const [isSearchOpen, setIsSearchOpen] = useState(false)
  const [activeTab, setActiveTab] = useState('main') // 'main' or 'categories'
  
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

  const services = [
    { 
      name: 'Home Rituals', 
      items: [
        'Grihapravesh',
        'Satyanarayan Puja',
        'Vastu Puja'
      ]
    },
    { 
      name: 'Life Events', 
      items: [
        'Weddings',
        'Naming Ceremony',
        'Thread Ceremony'
      ]
    },
    { 
      name: 'Special Occasions', 
      items: [
        'Festival Pujas',
        'Corporate Events',
        'Shraddh'
      ]
    }
  ];

  // Different pujas for categories
  const categories = [
    { 
      name: 'Popular Pujas', 
      items: [
        'Lakshmi Puja',
        'Ganesh Puja',
        'Saraswati Puja'
      ]
    },
    { 
      name: 'Festival Pujas', 
      items: [
        'Diwali Special',
        'Durga Puja',
        'Navratri Puja'
      ]
    },
    { 
      name: 'Specialty Pujas', 
      items: [
        'Kalasha Sthapana',
        'Rudrabhishek',
        'Maha Mrityunjaya'
      ]
    }
  ];

  return (
    <div className="w-full bg-background">
      {/* Top Promotional Banner */}
      <div className="bg-red-50 border-b border-red-100">
        <div className="container mx-auto px-4 py-2 flex items-center justify-between text-sm">
          <div className="flex items-center gap-2">
            <svg className="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
            <span className="text-gray-700">Limited time offer <span className="text-red-600 font-semibold">-15%</span> on all pujas</span>
            <span className="mx-2 text-gray-400">⟶</span>
            <a href="#catalog" className="text-gray-900 underline hover:text-red-600">View Pujas</a>
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
          <div className="flex items-center justify-between">
            {/* Logo */}
            <div className="flex items-center gap-8">
              <Link href="/" className="flex items-center gap-2" aria-label="Navdhya Home">
                <div className="size-10 rounded-md bg-primary/15 grid place-items-center">
                  <span className="font-serif text-primary text-xl">ॐ</span>
                </div>
                <div className="flex flex-col leading-tight">
                  <span className="text-2xl font-bold text-foreground">Navdhya</span>
                </div>
              </Link>

              {/* Desktop Navigation Links */}
              <nav className="hidden lg:flex items-center gap-6 text-sm">
                <Link href="/" className="hover:text-primary transition-colors font-medium">
                  Home
                </Link>
                <Link href="/about" className="hover:text-primary transition-colors font-medium">
                  About
                </Link>
                {/* Services dropdown - same as mobile */}
                <DropdownMenu>
                  <DropdownMenuTrigger className="hover:text-primary outline-none font-medium flex items-center gap-1">
                    Services
                    <ChevronDown className="w-4 h-4" />
                  </DropdownMenuTrigger>
                  <DropdownMenuContent className="w-[300px] sm:w-[400px] md:w-[500px] max-w-[calc(100vw-2rem)] bg-card border-border" align="start">
                    <div className="p-4 space-y-4">
                      {services.map((service, index) => (
                        <div key={index}>
                          <h4 className="font-medium text-foreground mb-2">{service.name}</h4>
                          <ul className="space-y-1 ml-2">
                            {service.items.map((item, itemIndex) => (
                              <li key={itemIndex}>
                                <Link 
                                  href="#catalog" 
                                  className="block py-1 text-muted-foreground hover:text-primary hover:underline text-sm"
                                >
                                  {item}
                                </Link>
                              </li>
                            ))}
                          </ul>
                        </div>
                      ))}
                    </div>
                  </DropdownMenuContent>
                </DropdownMenu>
                
                {/* Categories dropdown - same as mobile */}
                <DropdownMenu>
                  <DropdownMenuTrigger className="hover:text-primary outline-none font-medium flex items-center gap-1">
                    Categories
                    <ChevronDown className="w-4 h-4" />
                  </DropdownMenuTrigger>
                  <DropdownMenuContent className="w-[350px] sm:w-[450px] md:w-[600px] max-w-[calc(100vw-2rem)] bg-card border-border" align="start">
                    <div className="p-4 space-y-4">
                      {/* Show all categories including services */}
                      {[...categories, ...services].map((category, index) => (
                        <div key={index}>
                          <h4 className="font-medium text-foreground mb-2">{category.name}</h4>
                          <div className="grid grid-cols-2 gap-1 ml-2">
                            {category.items.map((item, itemIndex) => (
                              <Link 
                                key={itemIndex}
                                href="#catalog" 
                                className="block py-1 text-muted-foreground hover:text-primary hover:underline text-sm"
                              >
                                {item}
                              </Link>
                            ))}
                          </div>
                        </div>
                      ))}
                    </div>
                  </DropdownMenuContent>
                </DropdownMenu>
                <Link href="#blog" className="hover:text-primary transition-colors font-medium">
                  Blog
                </Link>
                <Link href="/contact" className="hover:text-primary transition-colors font-medium">
                  Contact
                </Link>
              </nav>
            </div>

            {/* Search Bar - Desktop */}
            <div className="hidden md:flex items-center flex-1 max-w-md mx-8">
              <div className="relative w-full">
                <input
                  type="text"
                  placeholder="Search for pujas"
                  className="w-full px-4 py-2 pr-10 border border-input rounded-lg focus:outline-none focus:border-primary bg-background"
                />
                <Search className="absolute right-3 top-2.5 w-5 h-5 text-muted-foreground" />
              </div>
            </div>

            {/* Right Actions */}
            <div className="flex items-center gap-4">
              <button 
                className="md:hidden"
                onClick={() => setIsSearchOpen(!isSearchOpen)}
              >
                <Search className="w-5 h-5" />
              </button>
              
              <div className="hidden lg:flex items-center gap-2 text-sm">
                <Phone className="w-4 h-4" />
                <div>
                  <div className="text-muted-foreground">Need help?</div>
                  <div className="font-semibold">+91 00000 00000</div>
                </div>
              </div>

              <button className="p-2 hover:bg-muted rounded">
                <User className="w-5 h-5" />
              </button>

              {/* Hide cart and wishlist on mobile */}
              <div className="hidden md:block">
                <button className="p-2 hover:bg-muted rounded relative">
                  <Heart className="w-5 h-5" />
                  <span className="absolute -top-1 -right-1 w-5 h-5 bg-primary text-primary-foreground text-xs rounded-full flex items-center justify-center">0</span>
                </button>
              </div>

              <div className="hidden md:block">
                <button className="p-2 hover:bg-muted rounded relative">
                  <ShoppingCart className="w-5 h-5" />
                  <span className="absolute -top-1 -right-1 w-5 h-5 bg-primary text-primary-foreground text-xs rounded-full flex items-center justify-center">0</span>
                </button>
              </div>

              {/* Mobile Menu Trigger */}
              <Sheet open={mobileOpen} onOpenChange={setMobileOpen}>
                <SheetTrigger asChild>
                  <button className="lg:hidden p-2">
                    {mobileOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
                  </button>
                </SheetTrigger>
                <SheetContent side="left" className="w-full max-w-xs sm:max-w-sm p-0 border-border bg-background">
                  <div className="p-4">
                    <div className="flex items-center justify-between mb-6">
                      <Link href="/" className="flex items-center gap-2" aria-label="Navdhya Home">
                        <div className="size-10 rounded-md bg-primary/15 grid place-items-center">
                          <span className="font-serif text-primary text-xl">ॐ</span>
                        </div>
                        <div className="flex flex-col leading-tight">
                          <span className="text-xl font-bold text-foreground">Navdhya</span>
                        </div>
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
                        <SheetClose asChild>
                          <Link href="/" className="block py-3 border-b border-border">
                            Home
                          </Link>
                        </SheetClose>
                        <SheetClose asChild>
                          <Link href="/about" className="block py-3 border-b border-border">
                            About
                          </Link>
                        </SheetClose>
                        
                        {/* Services dropdown - default closed */}
                        <div className="py-2 border-b border-border">
                          <Accordion type="single" collapsible className="w-full" defaultValue="">
                            <AccordionItem value="services" className="border-none">
                              <AccordionTrigger className="py-3 text-base px-0 hover:no-underline font-medium flex justify-between items-center">
                                <span>Services</span>
                              </AccordionTrigger>
                              <AccordionContent className="pb-0">
                                <div className="py-2 space-y-3">
                                  {services.map((service, index) => (
                                    <div key={index}>
                                      <h4 className="font-medium text-foreground mb-2">{service.name}</h4>
                                      <ul className="space-y-2 ml-2">
                                        {service.items.map((item, itemIndex) => (
                                          <li key={itemIndex}>
                                            <SheetClose asChild>
                                              <Link 
                                                href="#catalog" 
                                                className="block py-1 text-muted-foreground hover:text-primary hover:underline"
                                              >
                                                {item}
                                              </Link>
                                            </SheetClose>
                                          </li>
                                        ))}
                                      </ul>
                                    </div>
                                  ))}
                                </div>
                              </AccordionContent>
                            </AccordionItem>
                          </Accordion>
                        </div>
                        <SheetClose asChild>
                          <Link href="#blog" className="block py-3 border-b border-border">
                            Blog
                          </Link>
                        </SheetClose>
                        <SheetClose asChild>
                          <Link href="/contact" className="block py-3 border-b border-border">
                            Contact
                          </Link>
                        </SheetClose>
                        {/* Removed About Us after Contact as requested */}
                      </nav>
                    ) : (
                      /* Categories Tab - Show all pujas */
                      <nav className="space-y-4">
                        {categories.map((category, index) => (
                          <div key={index} className="border-b border-border pb-4">
                            <h3 className="font-medium text-foreground mb-3">{category.name}</h3>
                            <div className="grid grid-cols-2 gap-2">
                              {category.items.map((item, itemIndex) => (
                                <SheetClose asChild key={itemIndex}>
                                  <Link 
                                    href="#catalog" 
                                    className="block py-2 px-3 rounded-lg bg-muted hover:bg-muted/80 text-sm"
                                    onClick={() => setMobileOpen(false)}
                                  >
                                    {item}
                                  </Link>
                                </SheetClose>
                              ))}
                            </div>
                          </div>
                        ))}
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
            </div>
          </div>

          {/* Mobile Search */}
          {isSearchOpen && (
            <div className="md:hidden mt-4">
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
  )
}
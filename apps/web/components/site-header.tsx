"use client"
import Image from "next/image"
import Link from "next/link"
import { useEffect, useState } from "react"
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger, DropdownMenuItem } from "@/components/ui/dropdown-menu"
import { Search, Phone, User, ShoppingCart, ChevronDown, Bell, MapPin } from 'lucide-react'
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
  const [selectedLocation, setSelectedLocation] = useState("Delhi NCR")

  // Transform menu data from single source
  const menuData = transformMenuData();

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 8)
    onScroll()
    window.addEventListener("scroll", onScroll, { passive: true })
    return () => window.removeEventListener("scroll", onScroll)
  }, [])

  // Close any overlays on resize if added later
  useEffect(() => {
    const handleResize = () => {
      // 
    };

    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, []);

  return (
    <div className="w-full bg-transparent">
      {/* Main Header */}
      <div className="border-b border-border">
        <div className="container mx-auto px-6 md:px-8 py-4">
          {/* Desktop Layout */}
          <div className="hidden lg:flex items-center gap-3 xl:gap-5 w-full">
            {/* 1. Logo */}
            <Link href="/" className="flex items-center shrink-0 mr-2" aria-label="Navdhya Home">
              <Image
                src="/navdhya-logo.png"
                alt="Navdhya Ritual Services Logo"
                width={160}
                height={48}
                className="h-8 w-auto"
              />
            </Link>

            {/* 2. Location Dropdown */}
            <div className="shrink-0">
              <DropdownMenu>
                <DropdownMenuTrigger className="flex items-center gap-2 rounded-sm border border-border bg-transparent px-3 py-2 text-[13px] transition-colors hover:bg-muted outline-none">
                  <MapPin className="h-4 w-4 text-[#2563EB]" />
                  <span className="font-semibold text-foreground">{selectedLocation}</span>
                  <ChevronDown className="h-3.5 w-3.5 text-muted-foreground" />
                </DropdownMenuTrigger>
                <DropdownMenuContent align="start" className="w-[180px]">
                  <DropdownMenuItem onClick={() => setSelectedLocation("Delhi NCR")} className="cursor-pointer">
                    Delhi NCR
                  </DropdownMenuItem>
                  <DropdownMenuItem onClick={() => setSelectedLocation("Mumbai")} className="cursor-pointer">
                    Mumbai
                  </DropdownMenuItem>
                  <DropdownMenuItem onClick={() => setSelectedLocation("Bangalore")} className="cursor-pointer">
                    Bangalore
                  </DropdownMenuItem>
                  <DropdownMenuItem onClick={() => setSelectedLocation("Hyderabad")} className="cursor-pointer">
                    Hyderabad
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>

            {/* 3. Search Bar */}
            <div className="flex-1 max-w-[450px]">
              <div className="relative flex items-center w-full">
                <Search className="absolute left-3 h-4 w-4 text-muted-foreground" />
                <input
                  type="text"
                  placeholder="Search for puja, pandit, temple or festival..."
                  className="w-full rounded-sm border border-border bg-transparent pl-9 pr-4 py-2 text-[13px] focus:border-[#FF4500] focus:outline-none focus:ring-1 focus:ring-[#FF4500]"
                />
              </div>
            </div>

            {/* Right Actions */}
            <div className="flex items-center gap-3 xl:gap-4 ml-auto shrink-0">
              {/* 6. Auth Buttons */}
              <div className="flex items-center gap-2">
                <button className="hidden xl:block text-[13px] font-semibold text-gray-700 hover:text-[#FF4500] transition-colors">
                  Login / Signup
                </button>
                <button className="p-1.5 hover:bg-muted rounded-full transition-colors" aria-label="User Account">
                  <User className="w-[18px] h-[18px] text-gray-600" />
                </button>
              </div>

              {/* 5. Notifications */}
              <div className="border-l border-border pl-3 xl:pl-4">
                <button className="p-2 hover:bg-muted rounded-full relative transition-colors" aria-label="Notifications">
                  <Bell className="w-5 h-5 text-gray-700" strokeWidth={1.5} />
                  <span className="absolute top-1.5 right-1.5 w-2 h-2 bg-[#FF4500] rounded-full border border-white"></span>
                </button>
              </div>

              {/* 7. Book Pooja Button */}
              <Link href="/book" className="ml-1">
                <button className="bg-[#FF4500] hover:bg-[#E63E00] text-white px-5 py-2 rounded-sm text-[13px] font-semibold transition-colors">
                  Book Pooja
                </button>
              </Link>
            </div>
          </div>

          {/* Mobile Layout */}
          <div className="lg:hidden">
            <div className="flex items-center justify-between">
              {/* Left Side: Logo */}
              <div className="flex items-center gap-2">
                {/* Logo - Left aligned */}
                <Link href="/" className="flex items-center" aria-label="Navdhya Home">
                  <Image
                    src="/navdhya-logo.png"
                    alt="Navdhya Ritual Services Logo"
                    width={180}
                    height={56}
                    className="h-9 w-auto object-contain"
                  />
                </Link>
              </div>

              {/* Right Side: Auth and Notification Icons (Circular BG) */}
              <div className="flex items-center gap-3">
                {/* Notification Icon (Just next to Profile) */}
                <button className="flex items-center justify-center w-9 h-9 bg-gray-100 hover:bg-gray-200 rounded-full transition-colors relative">
                  <Bell className="w-4 h-4 text-gray-700" />
                  <span className="absolute top-2 right-2.5 w-1.5 h-1.5 bg-[#FF4500] rounded-full"></span>
                </button>
                
                {/* Profile / Auth Icon (Right-most) */}
                <Link href="/settings" className="flex items-center justify-center w-9 h-9 bg-gray-100 hover:bg-gray-200 rounded-full transition-colors overflow-hidden border border-gray-200">
                  <User className="w-4 h-4 text-gray-700" />
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

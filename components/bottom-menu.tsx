"use client";

import React, { useState, useEffect } from "react";
import Link from "next/link";
import {
  Home,
  Menu,
  Search,
  ShoppingBag,
  ShoppingCart,
  User,
  X,
} from "lucide-react";

export const MobileDock = () => {
  const [isVisible, setIsVisible] = useState(false);
  const [isPopupOpen, setIsPopupOpen] = useState(false);

  useEffect(() => {
    const handleScroll = () => {
      // Show the menu after scrolling 500px (adjust this value based on your slider height)
      if (window.scrollY > 500) {
        setIsVisible(true);
      } else {
        setIsVisible(false);
        setIsPopupOpen(false); // Close popup when scrolling back up
      }
    };

    window.addEventListener("scroll", handleScroll);
    return () => window.removeEventListener("scroll", handleScroll);
  }, []);

  const navItems = [
    { href: "/", label: "Home", icon: Home },
    { href: "#menu", label: "Menu", icon: Menu },
    { href: "#", label: "Search", icon: Search },
    { href: "#", label: "Shop", icon: ShoppingBag },
    { href: "#", label: "Cart", icon: ShoppingCart },
    { href: "#", label: "Account", icon: User },
  ];

  const menuContent = [
    { href: "/pujas", label: "Puja Services" },
    { href: "/rituals", label: "Rituals" },
    { href: "/pandits", label: "Our Pandits" },
    { href: "/about", label: "About Us" },
    { href: "/contact", label: "Contact" },
    { href: "/blog", label: "Blog" },
    { href: "/testimonials", label: "Testimonials" },
    { href: "/faq", label: "FAQ" },
  ];

  return (
    <>
      <nav
        className={`
          md:hidden
          fixed bottom-0 left-0 w-full z-50
          flex justify-between items-center
          bg-[#0E3F2E] text-white
          px-2 py-2
          rounded-t-2xl
          transition-all duration-300 ease-in-out
          ${isVisible ? 'translate-y-0' : 'translate-y-full'}
        `}
      >
        {navItems.map(({ href, label, icon: Icon }) => (
          <Link
            key={label}
            href={href === "#menu" ? "#" : href}
            onClick={href === "#menu" ? (e) => {
              e.preventDefault();
              setIsPopupOpen(true);
            } : undefined}
            className="
              flex flex-col items-center justify-center gap-1
              w-1/6 h-11
              text-[10px] font-medium text-white
              hover:text-neutral-200 transition-colors duration-200
            "
          >
            <Icon className="h-5 w-auto stroke-[1.2] text-white" />
            <span className="leading-[10px]">{label}</span>
          </Link>
        ))}
      </nav>

      {/* Menu Popup */}
      {isPopupOpen && (
        <div className="md:hidden fixed inset-0 z-[9999] bg-white flex flex-col">
          <div className="flex justify-between items-center p-4 border-b border-gray-100">
            <h2 className="text-[#0E3F2E] text-xl font-bold tracking-tight">Menu</h2>
            <button 
              onClick={() => setIsPopupOpen(false)}
              className="text-[#0E3F2E] p-2 rounded-full hover:bg-gray-50 transition-colors"
            >
              <X size={24} />
            </button>
          </div>
          <div className="flex-1 overflow-y-auto p-6">
            <div className="space-y-4">
              {menuContent.map((item) => (
                <Link
                  key={item.label}
                  href={item.href}
                  onClick={() => setIsPopupOpen(false)}
                  className="block py-4 border-b border-gray-100 text-[#0E3F2E] hover:text-[#0C3726] transition-colors"
                >
                  <span className="font-medium text-lg">{item.label}</span>
                </Link>
              ))}
            </div>
          </div>
        </div>
      )}
    </>
  );
};

export default MobileDock;
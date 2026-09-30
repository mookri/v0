"use client";

import React, { useEffect, useState } from "react";
import Link from "next/link";
import { DOCK_ITEMS } from "@/lib/menuData";
import { MenuSheet } from "./MenuSheet";
import QuickBook from "./QuickBook";
import { motion } from "framer-motion";
import { FaWhatsapp } from "react-icons/fa6";
import { Sparkles, Compass, GraduationCap, MessageCircle } from "lucide-react";

/**
 * MobileDock: bottom mobile navigation
 * - shows/hides based on scroll (mobile-only)
 * - integrates QuickBook center CTA + Menu sheet
 */

export const MobileDock: React.FC = () => {
  const [isVisible, setIsVisible] = useState(false);
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [isQuickOpen, setIsQuickOpen] = useState(false);
  const [activePopup, setActivePopup] = useState<string | null>(null);

  useEffect(() => {
    const onScroll = () => {
      // Reveal after scrolling a hero/slider area — tune threshold for your site
      const threshold = 380;
      if (window.scrollY > threshold) setIsVisible(true);
      else {
        setIsVisible(false);
        setIsMenuOpen(false);
      }
    };
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  // Function to handle popup open
  const openPopup = (popupType: string) => {
    setActivePopup(popupType);
  };

  // Function to handle popup close
  const closePopup = () => {
    setActivePopup(null);
  };

  return (
    <>
      <MenuSheet open={isMenuOpen} onClose={() => setIsMenuOpen(false)} />
      <QuickBook open={isQuickOpen} onClose={() => setIsQuickOpen(false)} />
      
      {/* Explore Popup */}
      {activePopup === "explore" && (
        <div className="fixed inset-0 z-[9999] bg-black/40 backdrop-blur-sm flex items-end md:items-center justify-center p-4 md:p-8">
          <motion.div
            initial={{ y: "100%" }}
            animate={{ y: 0 }}
            exit={{ y: "100%" }}
            transition={{ type: "spring", stiffness: 300, damping: 30 }}
            className="w-full md:max-w-xl bg-white rounded-t-2xl md:rounded-2xl shadow-2xl overflow-hidden mb-4 md:mb-0"
          >
            <div className="flex items-center justify-between p-4 border-b border-gray-100 bg-gray-50">
              <div className="flex items-center gap-3">
                <Compass className="h-6 w-6 text-[#0E3F2E]" />
                <div>
                  <h3 className="text-[#0E3F2E] font-bold tracking-tight text-lg">Explore</h3>
                  <p className="text-xs text-gray-500">Discover all Pujas, Rituals, and Sacred Offerings</p>
                </div>
              </div>
              <button 
                onClick={closePopup}
                className="p-2 rounded-full hover:bg-gray-200 transition-colors"
                aria-label="Close"
              >
                <svg className="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <div className="p-3 overflow-y-auto max-h-[60vh]">
              <div className="grid grid-cols-2 gap-2">
                <Link 
                  href="/pujas" 
                  onClick={closePopup}
                  className="flex flex-col items-center justify-center p-3 rounded-xl border border-gray-200 hover:border-[#0E3F2E] hover:bg-gray-50 transition-all"
                >
                  <Sparkles className="h-6 w-6 text-[#D4AF37] mb-1" />
                  <span className="font-medium text-[#0E3F2E] tracking-tight text-sm">All Pujas</span>
                </Link>
                <Link 
                  href="/pujas/home" 
                  onClick={closePopup}
                  className="flex flex-col items-center justify-center p-3 rounded-xl border border-gray-200 hover:border-[#0E3F2E] hover:bg-gray-50 transition-all"
                >
                  <svg className="h-6 w-6 text-[#D4AF37] mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                  </svg>
                  <span className="font-medium text-[#0E3F2E] tracking-tight text-sm">Home Pujas</span>
                </Link>
                <Link 
                  href="/pujas/temple" 
                  onClick={closePopup}
                  className="flex flex-col items-center justify-center p-3 rounded-xl border border-gray-200 hover:border-[#0E3F2E] hover:bg-gray-50 transition-all"
                >
                  <svg className="h-6 w-6 text-[#D4AF37] mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  <span className="font-medium text-[#0E3F2E] tracking-tight text-sm">Temple Pujas</span>
                </Link>
                <Link 
                  href="/pujas/festivals" 
                  onClick={closePopup}
                  className="flex flex-col items-center justify-center p-3 rounded-xl border border-gray-200 hover:border-[#0E3F2E] hover:bg-gray-50 transition-all"
                >
                  <svg className="h-6 w-6 text-[#D4AF37] mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span className="font-medium text-[#0E3F2E] tracking-tight text-sm">Festival Pujas</span>
                </Link>
              </div>
            </div>
          </motion.div>
        </div>
      )}

      {/* Learn Popup */}
      {activePopup === "learn" && (
        <div className="fixed inset-0 z-[9999] bg-black/40 backdrop-blur-sm flex items-end md:items-center justify-center p-4 md:p-8">
          <motion.div
            initial={{ y: "100%" }}
            animate={{ y: 0 }}
            exit={{ y: "100%" }}
            transition={{ type: "spring", stiffness: 300, damping: 30 }}
            className="w-full md:max-w-xl bg-white rounded-t-2xl md:rounded-2xl shadow-2xl overflow-hidden mb-4 md:mb-0"
          >
            <div className="flex items-center justify-between p-4 border-b border-gray-100 bg-gray-50">
              <div className="flex items-center gap-3">
                <GraduationCap className="h-6 w-6 text-[#0E3F2E]" />
                <div>
                  <h3 className="text-[#0E3F2E] font-bold tracking-tight text-lg">Learn</h3>
                  <p className="text-xs text-gray-500">Explore Mantras, Articles, and Spiritual Guides</p>
                </div>
              </div>
              <button 
                onClick={closePopup}
                className="p-2 rounded-full hover:bg-gray-200 transition-colors"
                aria-label="Close"
              >
                <svg className="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <div className="p-3 overflow-y-auto max-h-[60vh]">
              <div className="grid grid-cols-2 gap-2">
                <Link 
                  href="/blog" 
                  onClick={closePopup}
                  className="flex flex-col items-center justify-center p-3 rounded-xl border border-gray-200 hover:border-[#0E3F2E] hover:bg-gray-50 transition-all"
                >
                  <svg className="h-6 w-6 text-[#D4AF37] mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                  </svg>
                  <span className="font-medium text-[#0E3F2E] tracking-tight text-sm">Blog & Articles</span>
                </Link>
                <Link 
                  href="/learn/mantras" 
                  onClick={closePopup}
                  className="flex flex-col items-center justify-center p-3 rounded-xl border border-gray-200 hover:border-[#0E3F2E] hover:bg-gray-50 transition-all"
                >
                  <svg className="h-6 w-6 text-[#D4AF37] mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                  </svg>
                  <span className="font-medium text-[#0E3F2E] tracking-tight text-sm">Mantras & Slokas</span>
                </Link>
                <Link 
                  href="/learn/videos" 
                  onClick={closePopup}
                  className="flex flex-col items-center justify-center p-3 rounded-xl border border-gray-200 hover:border-[#0E3F2E] hover:bg-gray-50 transition-all"
                >
                  <svg className="h-6 w-6 text-[#D4AF37] mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                  </svg>
                  <span className="font-medium text-[#0E3F2E] tracking-tight text-sm">Video Library</span>
                </Link>
                <Link 
                  href="/testimonials" 
                  onClick={closePopup}
                  className="flex flex-col items-center justify-center p-3 rounded-xl border border-gray-200 hover:border-[#0E3F2E] hover:bg-gray-50 transition-all"
                >
                  <svg className="h-6 w-6 text-[#D4AF37] mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                  </svg>
                  <span className="font-medium text-[#0E3F2E] tracking-tight text-sm">Testimonials</span>
                </Link>
              </div>
            </div>
          </motion.div>
        </div>
      )}

      {/* Chat Popup */}
      {activePopup === "chat" && (
        <div className="fixed inset-0 z-[9999] bg-black/40 backdrop-blur-sm flex items-end md:items-center justify-center p-4 md:p-8">
          <motion.div
            initial={{ y: "100%" }}
            animate={{ y: 0 }}
            exit={{ y: "100%" }}
            transition={{ type: "spring", stiffness: 300, damping: 30 }}
            className="w-full md:max-w-xl bg-white rounded-t-2xl md:rounded-2xl shadow-2xl overflow-hidden mb-4 md:mb-0"
          >
            <div className="flex items-center justify-between p-4 border-b border-gray-100 bg-[#0E3F2E]">
              <div className="flex items-center gap-3">
                <MessageCircle className="h-6 w-6 text-white" />
                <div>
                  <h3 className="text-white font-bold tracking-tight text-lg">Chat with Us</h3>
                  <p className="text-white/80 text-xs">Talk to Pandit or Support via WhatsApp</p>
                </div>
              </div>
              <button 
                onClick={closePopup}
                className="p-2 rounded-full hover:bg-white/20 transition-colors"
                aria-label="Close"
              >
                <svg className="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <div className="p-4">
              <div className="text-center mb-4">
                <p className="text-gray-600 mb-3 text-sm">Connect with our team via WhatsApp for immediate assistance</p>
                <button
                  onClick={() => {
                    window.open("https://wa.me/917289000377", "_blank");
                    closePopup();
                  }}
                  className="bg-[#0E3F2E] hover:bg-[#0C3726] text-white font-bold py-2.5 px-5 rounded-full flex items-center justify-center gap-2 mx-auto transition-colors tracking-tight text-sm"
                >
                  <FaWhatsapp className="h-5 w-5" />
                  <span>Open WhatsApp Chat</span>
                </button>
              </div>
              <div className="border-t border-gray-200 pt-3">
                <h4 className="font-bold text-[#0E3F2E] mb-2 tracking-tight text-sm">Other Contact Options</h4>
                <div className="grid grid-cols-2 gap-2">
                  <Link 
                    href="/contact" 
                    onClick={closePopup}
                    className="flex flex-col items-center justify-center p-2 rounded-lg border border-gray-200 hover:border-[#0E3F2E] hover:bg-gray-50 transition-colors"
                  >
                    <svg className="h-5 w-5 text-[#0E3F2E] mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    <span className="text-xs text-[#0E3F2E] tracking-tight">Contact Form</span>
                  </Link>
                  <Link 
                    href="/help" 
                    onClick={closePopup}
                    className="flex flex-col items-center justify-center p-2 rounded-lg border border-gray-200 hover:border-[#0E3F2E] hover:bg-gray-50 transition-colors"
                  >
                    <svg className="h-5 w-5 text-[#0E3F2E] mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span className="text-xs text-[#0E3F2E] tracking-tight">Help Center</span>
                  </Link>
                </div>
              </div>
            </div>
          </motion.div>
        </div>
      )}

      <nav
        className={`md:hidden fixed bottom-4 left-1/2 transform -translate-x-1/2 z-50 transition-all duration-500 w-[99%] max-w-md ${
          isVisible ? "opacity-100 translate-y-0" : "opacity-0 translate-y-12 pointer-events-none"
        }`}
        aria-hidden={!isVisible}
      >
        <div className="bg-transparent px-2">
          <div className="bg-[#0E3F2E] text-white rounded-xl shadow-lg flex items-center justify-between gap-3 px-2 py-2 border border-[#D4AF37]/10">
            {/* left icons */}
            <div className="flex items-center gap-4">
              {DOCK_ITEMS.slice(0, 2).map((it) => (
                <button
                  key={it.label}
                  onClick={(e) => {
                    if (it.label === "Explore") {
                      e.preventDefault();
                      openPopup("explore");
                    } else if (it.label === "Learn") {
                      e.preventDefault();
                      openPopup("learn");
                    } else if (it.label === "Menu") {
                      e.preventDefault();
                      setIsMenuOpen(true);
                    }
                  }}
                  className="flex flex-col items-center gap-1 text-sm text-white px-2 py-1.5 rounded-lg hover:bg-[#0C3726] transition-colors duration-200"
                >
                  <it.icon className="h-5 w-5" />
                  <span className="text-xs font-medium">{it.label}</span>
                </button>
              ))}
            </div>

            {/* center CTA - Quick Book */}
            <motion.button
              whileTap={{ scale: 0.98 }}
              whileHover={{ scale: 1.03 }}
              onClick={() => setIsQuickOpen(true)}
              className="bg-white text-[#0E3F2E] rounded-full shadow px-4 py-2.5 flex items-center gap-1.5 font-semibold hover:bg-gray-50 transition-all duration-300 w-40 justify-center text-xs"
              aria-label="Quick Book"
            >
              <Sparkles className="h-4 w-4 text-[#0E3F2E]" />
              <span className="font-bold">Quick Book</span>
            </motion.button>

            {/* right icons */}
            <div className="flex items-center gap-4">
              {/* Chat button */}
              <button
                onClick={(e) => {
                  e.preventDefault();
                  openPopup("chat");
                }}
                className="flex flex-col items-center gap-1 text-sm text-white px-2 py-1.5 rounded-lg hover:bg-[#0C3726] transition-colors duration-200"
              >
                <MessageCircle className="h-5 w-5" />
                <span className="text-xs font-medium">Chat</span>
              </button>

              {/* Menu button */}
              <button
                onClick={(e) => {
                  e.preventDefault();
                  setIsMenuOpen(true);
                }}
                className="flex flex-col items-center gap-1 text-sm text-white px-2 py-1.5 rounded-lg hover:bg-[#0C3726] transition-colors duration-200"
              >
                <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <span className="text-xs font-medium">Menu</span>
              </button>
            </div>
          </div>
        </div>
      </nav>
    </>
  );
};

export default MobileDock;
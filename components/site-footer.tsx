"use client";


import { Mail, Phone, MapPin, Heart, } from "lucide-react";
import { Facebook, Instagram, Twitter, Youtube } from "lucide-react";
import Link from "next/link";
const QuickLinks = [{ Label: "Home", href: "#" }, { Label: "About Us", href: "#" }, { Label: "Our Pandits", href: "#" }, { Label: "Testimonials", href: "#" }, { Label: "Gallery", href: "#" }, { Label: "FAQs", href: "#" }];
export function SiteFooter() {
  return (
    <footer className="bg-[#0d1b2a] text-white">
      {/* Main Footer Content */}
      <div className="container mx-auto px-4 py-14">
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-8">
          {/* Column 1 - Quick Links */}
          <div className="col-span-1">
            <h4 className="text-lg font-semibold mb-5 text-[#ff9933] relative pb-2 after:absolute after:left-0 after:bottom-0 after:h-0.5 after:w-10 after:bg-[#ff9933]">Quick Links</h4>
            <ul className="space-y-3">
              {QuickLinks.map((link) => (
                <li key={link.Label}>
                  <Link href={link.href} className="text-gray-200 hover:text-white transition-colors text-sm flex items-center group">
                    <span className="w-1.5 h-1.5 rounded-full bg-[#ff9933] mr-3 group-hover:animate-pulse"></span>
                    {link.Label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Column 2 - Services */}
          <div className="col-span-1">
            <h4 className="text-lg font-semibold mb-5 text-[#ff9933] relative pb-2 after:absolute after:left-0 after:bottom-0 after:h-0.5 after:w-10 after:bg-[#ff9933]">Our Services</h4>
            <ul className="space-y-3">
              {["Puja at Home", "Online Puja", "Temple Services", "Havan & Yagya", "Vedic Rituals", "Custom Pujas"].map((service) => (
                <li key={service}>
                  <a href="#" className="text-gray-200 hover:text-white transition-colors text-sm flex items-center group">
                    <span className="w-1.5 h-1.5 rounded-full bg-[#ff9933] mr-3 group-hover:animate-pulse"></span>
                    {service}
                  </a>
                </li>
              ))}
            </ul>
          </div>

          {/* Column 3 - Astrology */}
          <div className="col-span-1">
            <h4 className="text-lg font-semibold mb-5 text-[#ff9933] relative pb-2 after:absolute after:left-0 after:bottom-0 after:h-0.5 after:w-10 after:bg-[#ff9933]">Astrology</h4>
            <ul className="space-y-3">
              {["Kundli Analysis", "Matching", "Horoscope", "Gemstone", "Vastu", "Remedies"].map((item) => (
                <li key={item}>
                  <a href="#" className="text-gray-200 hover:text-white transition-colors text-sm flex items-center group">
                    <span className="w-1.5 h-1.5 rounded-full bg-[#ff9933] mr-3 group-hover:animate-pulse"></span>
                    {item}
                  </a>
                </li>
              ))}
            </ul>
          </div>

          {/* Column 4 - Locations */}
          <div className="col-span-1">
            <h4 className="text-lg font-semibold mb-5 text-[#ff9933] relative pb-2 after:absolute after:left-0 after:bottom-0 after:h-0.5 after:w-10 after:bg-[#ff9933]">Our Centers</h4>
            <ul className="space-y-3">
              {["New Delhi", "Mumbai", "Bangalore", "Chennai", "Kolkata", "Hyderabad"].map((city) => (
                <li key={city}>
                  <a href="#" className="text-gray-200 hover:text-white transition-colors text-sm flex items-center group">
                    <MapPin size={14} className="text-[#ff9933] mr-2" />
                    {city}
                  </a>
                </li>
              ))}
            </ul>
          </div>

          {/* Column 5 - Contact Info */}
          <div className="col-span-2 md:col-span-1">
            <h4 className="text-lg font-semibold mb-5 text-[#ff9933] relative pb-2 after:absolute after:left-0 after:bottom-0 after:h-0.5 after:w-10 after:bg-[#ff9933]">Contact Us</h4>
            <div className="space-y-4">
              <div className="flex items-start space-x-3">
                <MapPin size={18} className="text-[#ff9933] mt-0.5 flex-shrink-0" />
                <span className="text-gray-200 text-sm">123 Temple Street,<br />New Delhi 110001,<br />India</span>
              </div>
              <div className="flex items-center space-x-3">
                <Phone size={18} className="text-[#ff9933] flex-shrink-0" />
                <a href="tel:+919876543210" className="text-gray-200 hover:text-white text-sm">+91 98765 43210</a>
              </div>
              <div className="flex items-center space-x-3">
                <Mail size={18} className="text-[#ff9933] flex-shrink-0" />
                <a href="mailto:info@divineservices.com" className="text-gray-200 hover:text-white text-sm">info@divineservices.com</a>
              </div>
              <div className="pt-2">
                <p className="text-sm text-gray-300 mb-2">Follow Us:</p>
                <div className="flex space-x-3">
                  {[Facebook, Instagram, Twitter, Youtube].map((Icon, index) => (
                    <a key={index} href="#" className="h-9 w-9 rounded-full bg-[#ffffff1a] flex items-center justify-center hover:bg-[#ff9933] transition-colors duration-300">
                      <Icon size={16} className="text-white" />
                    </a>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Bottom Bar */}
      <div className="border-t border-[#ff9933]/20 py-6">
        <div className="container mx-auto px-4">
          <div className="flex flex-col md:flex-row justify-between items-center gap-4 text-sm">
            <p className="text-gray-300 text-center md:text-left">
              {new Date().getFullYear()} Navdhya Ritual Services. All rights reserved. Made with{" "}
              <Heart size={14} className="inline text-[#ff9933]" />
              for spiritual wellness
            </p>
            <div className="flex items-center space-x-6 text-gray-300">
              <a href="#" className="hover:text-white transition-colors text-xs" >
                Privacy Policy
              </a>
              <a href="#" className="hover:text-white transition-colors text-xs" >
                Terms of Service
              </a>
              <a href="#" className="hover:text-white transition-colors text-xs" >
                Refund Policy
              </a>
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
}
"use client";

import { Mail, Phone, MapPin, Heart, Shield, Lock } from "lucide-react";
import { Facebook, Instagram, Twitter, Youtube } from "lucide-react";
import Link from "next/link";
import { footerData } from "@/lib/footerData";

export function SiteFooter() {
  return (
    <footer className="bg-gradient-to-b from-[#0d1b2a] to-[#0a121f] text-white pb-20 md:pb-0">
      {/* Main Content Area */}
      <div className="container mx-auto px-4 py-5 max-w-7xl">
        {/* Desktop Layout - Horizontal */}
        <div className="hidden lg:grid lg:grid-cols-5 gap-8">
          {/* Contact Us */}
          <div className="lg:col-span-1 pr-8">
            <h3 className="text-base font-semibold mb-2 text-[#ff9933] relative pb-1.5 after:absolute after:left-0 after:bottom-0 after:h-0.5 after:w-10 after:bg-gradient-to-r from-[#ff9933] to-transparent">Contact Us</h3>
            <div className="space-y-1.5">
              <div className="flex items-start space-x-2.5 group">
                <MapPin size={14} className="text-primary mt-0.5 flex-shrink-0" />
                <span className="text-gray-300 text-xs">{footerData.contact.address}</span>
              </div>
              <div className="flex items-center space-x-2.5 group">
                <Phone size={14} className="text-primary flex-shrink-0" />
                <a href={`tel:${footerData.contact.phone}`} className="text-gray-300 hover:text-white text-xs transition-colors">{footerData.contact.phone}</a>
              </div>
              <div className="flex items-center space-x-2.5 group">
                <Mail size={14} className="text-primary flex-shrink-0" />
                <a href={`mailto:${footerData.contact.email}`} className="text-gray-300 hover:text-white text-xs transition-colors">{footerData.contact.email}</a>
              </div>
            </div>
          </div>

          {/* Quick Links */}
          <div className="lg:col-span-1 pl-4">
            <h3 className="text-base font-semibold mb-2 text-[#ff9933] relative pb-1.5 after:absolute after:left-0 after:bottom-0 after:h-0.5 after:w-10 after:bg-gradient-to-r from-[#ff9933] to-transparent">Quick Links</h3>
            <ul className="space-y-1">
              {footerData.quickLinks.map((link, index) => (
                <li key={index}>
                  <Link 
                    href={link.href} 
                    className="text-gray-300 hover:text-white transition-all duration-300 text-xs py-0.5 block"
                  >
                    {link.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Our Services */}
          <div className="lg:col-span-1 pl-4">
            <h3 className="text-base font-semibold mb-2 text-[#ff9933] relative pb-1.5 after:absolute after:left-0 after:bottom-0 after:h-0.5 after:w-10 after:bg-gradient-to-r from-[#ff9933] to-transparent">Our Services</h3>
            <ul className="space-y-1">
              {footerData.services.map((service, index) => (
                <li key={index}>
                  <Link 
                    href={service.href} 
                    className="text-gray-300 hover:text-white transition-all duration-300 text-xs py-0.5 block"
                  >
                    {service.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Astrology */}
          <div className="lg:col-span-1 pl-4">
            <h3 className="text-base font-semibold mb-2 text-[#ff9933] relative pb-1.5 after:absolute after:left-0 after:bottom-0 after:h-0.5 after:w-10 after:bg-gradient-to-r from-[#ff9933] to-transparent">Astrology</h3>
            <ul className="space-y-1">
              {footerData.astrology.map((item, index) => (
                <li key={index}>
                  <Link 
                    href={item.href} 
                    className="text-gray-300 hover:text-white transition-all duration-300 text-xs py-0.5 block"
                  >
                    {item.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Our Centers */}
          <div className="lg:col-span-1 pl-4">
            <h3 className="text-base font-semibold mb-2 text-[#ff9933] relative pb-1.5 after:absolute after:left-0 after:bottom-0 after:h-0.5 after:w-10 after:bg-gradient-to-r from-[#ff9933] to-transparent">Our Centers</h3>
            <ul className="space-y-1">
              {footerData.centers.map((city, index) => (
                <li key={index}>
                  <Link 
                    href={city.href} 
                    className="text-gray-300 hover:text-white transition-all duration-300 text-xs py-0.5 block"
                  >
                    {city.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>
        </div>

        {/* Mobile Layout - 2x2 Grid */}
        <div className="lg:hidden grid grid-cols-1 gap-2 px-4">
          {/* Contact Us - Full width at top */}
          <div className="px-1">
            <h3 className="text-xl font-semibold mb-3 text-[#ff9933] relative pb-2 after:absolute after:left-0 after:bottom-0 after:h-0.5 after:w-14 after:bg-gradient-to-r from-[#ff9933] to-transparent">Contact Us</h3>
            <div className="space-y-3">
              <div className="flex items-start space-x-3.5 group">
                <MapPin size={20} className="text-primary mt-0.5 flex-shrink-0" />
                <span className="text-gray-300 text-base leading-relaxed">{footerData.contact.address}</span>
              </div>
              <div className="flex items-center space-x-3.5 group">
                <Phone size={20} className="text-primary flex-shrink-0" />
                <a href={`tel:${footerData.contact.phone}`} className="text-gray-300 hover:text-white text-base transition-colors font-medium">{footerData.contact.phone}</a>
              </div>
              <div className="flex items-center space-x-3.5 group">
                <Mail size={20} className="text-primary flex-shrink-0" />
                <a href={`mailto:${footerData.contact.email}`} className="text-gray-300 hover:text-white text-base transition-colors font-medium">{footerData.contact.email}</a>
              </div>
            </div>
          </div>

          {/* Sleek horizontal divider line */}
          <div className="border-t border-[#ff9933]/10 pt-2"></div>

          {/* First Row: Quick Links and Our Services side-by-side */}
          <div className="grid grid-cols-2 gap-2">
            <div>
              <h3 className="text-sm font-semibold mb-2 text-[#ff9933] relative pb-1.5 after:absolute after:left-0 after:bottom-0 after:h-0.5 after:w-8 after:bg-gradient-to-r from-[#ff9933] to-transparent">Quick Links</h3>
              <ul className="space-y-1">
                {footerData.quickLinks.map((link, index) => (
                  <li key={index}>
                    <Link 
                      href={link.href} 
                      className="text-gray-300 hover:text-white transition-all duration-300 text-xs py-0.5 block"
                    >
                      {link.label}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>

            <div>
              <h3 className="text-sm font-semibold mb-2 text-[#ff9933] relative pb-1.5 after:absolute after:left-0 after:bottom-0 after:h-0.5 after:w-8 after:bg-gradient-to-r from-[#ff9933] to-transparent">Our Services</h3>
              <ul className="space-y-1">
                {footerData.services.map((service, index) => (
                  <li key={index}>
                    <Link 
                      href={service.href} 
                      className="text-gray-300 hover:text-white transition-all duration-300 text-xs py-0.5 block"
                    >
                      {service.label}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          </div>

          {/* Second Row: Astrology and Our Centers side-by-side */}
          <div className="grid grid-cols-2 gap-2">
            <div>
              <h3 className="text-sm font-semibold mb-2 text-[#ff9933] relative pb-1.5 after:absolute after:left-0 after:bottom-0 after:h-0.5 after:w-8 after:bg-gradient-to-r from-[#ff9933] to-transparent">Astrology</h3>
              <ul className="space-y-1">
                {footerData.astrology.map((item, index) => (
                  <li key={index}>
                    <Link 
                      href={item.href} 
                      className="text-gray-300 hover:text-white transition-all duration-300 text-xs py-0.5 block"
                    >
                      {item.label}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>

            <div>
              <h3 className="text-sm font-semibold mb-2 text-[#ff9933] relative pb-1.5 after:absolute after:left-0 after:bottom-0 after:h-0.5 after:w-8 after:bg-gradient-to-r from-[#ff9933] to-transparent">Our Centers</h3>
              <ul className="space-y-1">
                {footerData.centers.map((city, index) => (
                  <li key={index}>
                    <Link 
                      href={city.href} 
                      className="text-gray-300 hover:text-white transition-all duration-300 text-xs py-0.5 block"
                    >
                      {city.label}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          </div>
        </div>
      </div>

      {/* Brand Bar */}
      <div className="border-t border-[#ff9933]/10 py-3 bg-gradient-to-r from-[#0a121f]/50 to-[#0d1b2a]/50 backdrop-blur-sm">
        <div className="container mx-auto px-4 max-w-7xl">
          <div className="flex flex-col md:flex-row justify-between items-center gap-3">
            {/* Company Logo */}
            <div className="flex items-center">
              <img 
                src="/navdhya-logo.png" 
                alt="Navdhya Ritual Services Logo" 
                className="h-12 w-auto"
              />
            </div>

            {/* Social Media Icons */}
            <div className="flex items-center space-x-2.5">
              {[Facebook, Instagram, Twitter, Youtube].map((Icon, index) => (
                <a 
                  key={index} 
                  href="#" 
                  className="h-7 w-7 rounded-lg flex items-center justify-center hover:bg-[#0d1b2a]/80 transition-all duration-300"
                >
                  <Icon size={14} className="text-primary" />
                </a>
              ))}
            </div>

            {/* App Download */}
            <div style={{display: "flex", flexWrap: "wrap", alignItems: "center", gap: "8px", border: "0px solid rgb(229, 231, 235)", boxSizing: "border-box"}}>
              <span style={{fontSize: "11px", fontWeight: 600, color: "rgb(148, 163, 184)", border: "0px solid rgb(229, 231, 235)", boxSizing: "border-box"}}>Download App:</span>
              <div style={{display: "flex", alignItems: "center", flexWrap: "wrap", gap: "3px", flex: "1 0 0%", border: "0px solid rgb(229, 231, 235)", boxSizing: "border-box"}}>
                <div style={{display: "flex", alignItems: "center", flexShrink: 0, border: "0px solid rgb(229, 231, 235)", boxSizing: "border-box"}}>
                  <a href="" style={{textDecoration: "none", color: "rgb(0, 103, 178)", display: "flex", alignItems: "center", border: "0px solid rgb(229, 231, 235)", boxSizing: "border-box"}}>
                    <img src="/payments/google-white.png" style={{maxHeight: "20px", maxWidth: "100%", height: "auto", imageRendering: "-webkit-optimize-contrast", border: "0px solid rgb(229, 231, 235)", verticalAlign: "middle", boxSizing: "border-box"}} />
                  </a>
                </div>
                <div style={{display: "flex", alignItems: "center", flexShrink: 0, border: "0px solid rgb(229, 231, 235)", boxSizing: "border-box"}}>
                  <a href="" style={{textDecoration: "none", color: "rgb(0, 103, 178)", display: "flex", alignItems: "center", border: "0px solid rgb(229, 231, 235)", boxSizing: "border-box"}}>
                    <img src="/payments/apple-white.png" style={{maxHeight: "20px", maxWidth: "100%", height: "auto", imageRendering: "-webkit-optimize-contrast", border: "0px solid rgb(229, 231, 235)", verticalAlign: "middle", boxSizing: "border-box"}} />
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Copyright Bar */}
      <div className="border-t border-[#ff9933]/10 py-3 bg-[#0a121f]">
        <div className="container mx-auto px-4 max-w-7xl">
          <div className="flex flex-col md:flex-row justify-between items-center gap-3">
            {/* Copyright Text and Disclaimer */}
            <div className="flex flex-col items-start w-full">
              <p className="text-gray-400 text-xs text-center md:text-left">
                © 2025 Navdhya Ritual Services. All rights reserved. Made with <Heart size={10} className="inline text-primary" /> for spiritual wellness.
              </p>
            </div>

            {/* Payment Method Logos */}
            <div className="flex items-center space-x-3">
              <div className="flex items-center space-x-2.5">
                <div style={{border: "0px solid rgb(229, 231, 235)", boxSizing: "border-box"}}>
                  <img src="/payments/payment.svg" alt="payment" style={{maxWidth: "100%", height: "auto", imageRendering: "-webkit-optimize-contrast", border: "0px solid rgb(229, 231, 235)", verticalAlign: "middle", boxSizing: "border-box"}} />
                </div>
                <div style={{border: "0px solid rgb(229, 231, 235)", boxSizing: "border-box"}}>
                  <img src="/payments/payment2.svg" alt="payment" style={{maxWidth: "100%", height: "auto", imageRendering: "-webkit-optimize-contrast", border: "0px solid rgb(229, 231, 235)", verticalAlign: "middle", boxSizing: "border-box"}} />
                </div>
                <div style={{border: "0px solid rgb(229, 231, 235)", boxSizing: "border-box"}}>
                  <img src="/payments/payment3.svg" alt="payment" style={{maxWidth: "100%", height: "auto", imageRendering: "-webkit-optimize-contrast", border: "0px solid rgb(229, 231, 235)", verticalAlign: "middle", boxSizing: "border-box"}} />
                </div>
                <div style={{border: "0px solid rgb(229, 231, 235)", boxSizing: "border-box"}}>
                  <img src="/payments/payment4.svg" alt="payment" style={{maxWidth: "100%", height: "auto", imageRendering: "-webkit-optimize-contrast", border: "0px solid rgb(229, 231, 235)", verticalAlign: "middle", boxSizing: "border-box"}} />
                </div>
                <div style={{border: "0px solid rgb(229, 231, 235)", boxSizing: "border-box"}}>
                  <img src="/payments/klarna-white.webp" alt="payment" style={{maxWidth: "100%", height: "auto", imageRendering: "-webkit-optimize-contrast", border: "0px solid rgb(229, 231, 235)", verticalAlign: "middle", boxSizing: "border-box"}} />
                </div>
              </div>
            </div>
          </div>
          
          {/* Bottom Links */}
          <div className="flex flex-wrap justify-start gap-3 mt-3 pt-3 border-t border-[#ff9933]/5 w-full">
            <div className="flex flex-col w-full">
              <div className="rounded-lg p-4 mb-3">
                <p className="text-gray-400 text-[11px] leading-relaxed">
                  This service is operated by AI and may occasionally contain errors. Don't panic - our team is available 24/7 to assist you in-person. 
                  All rituals and services are performed by certified pandits with years of experience. While we strive for accuracy in all our digital 
                  interactions, we recommend confirming important details with our support team. Our AI assistant is continuously learning and improving 
                  to better serve your spiritual needs. For immediate assistance, please contact our helpline or use the WhatsApp chat feature.
                </p>
              </div>
              <div className="flex flex-wrap items-center justify-center gap-1.5 py-3 ml-0 md:ml-auto">
                <a href="/privacy-policy" className="text-[10px] text-gray-500 hover:text-[#ff9933] transition-colors">Privacy Policy</a>
                
                <a href="/terms-of-service" className="text-[10px] text-gray-500 hover:text-[#ff9933] transition-colors">Terms of Service</a>
                
                <a href="/refund-policy" className="text-[10px] text-gray-500 hover:text-[#ff9933] transition-colors">Refund Policy</a>
                
                <a href="/cancellation-policy" className="text-[10px] text-gray-500 hover:text-[#ff9933] transition-colors">Cancellation Policy</a>
                
                <a href="/pandit-verification" className="text-[10px] text-gray-500 hover:text-[#ff9933] transition-colors">Pandit Verification</a>
                
                <a href="/service-guarantee" className="text-[10px] text-gray-500 hover:text-[#ff9933] transition-colors">Service Guarantee</a>
                
                <a href="/accessibility" className="text-[10px] text-gray-500 hover:text-[#ff9933] transition-colors">Accessibility</a>
                
                <a href="/cookie-policy" className="text-[10px] text-gray-500 hover:text-[#ff9933] transition-colors">Cookie Policy</a>
                
                <a href="/grievance-officer" className="text-[10px] text-gray-500 hover:text-[#ff9933] transition-colors">Grievance Officer</a>
                
                <a href="/pandit-agreement" className="text-[10px] text-gray-500 hover:text-[#ff9933] transition-colors">Pandit Agreement</a>
                
                <a href="/vendor-terms" className="text-[10px] text-gray-500 hover:text-[#ff9933] transition-colors">Vendor Terms</a>
                
                <a href="/affiliate-disclosure" className="text-[10px] text-gray-500 hover:text-[#ff9933] transition-colors">Affiliate Disclosure</a>
                
                <a href="/ip-policy" className="text-[10px] text-gray-500 hover:text-[#ff9933] transition-colors">IP Policy</a>
                
                <a href="/child-protection" className="text-[10px] text-gray-500 hover:text-[#ff9933] transition-colors">Child Protection</a>
                
                <a href="/data-processing" className="text-[10px] text-gray-500 hover:text-[#ff9933] transition-colors">Data Processing</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
}
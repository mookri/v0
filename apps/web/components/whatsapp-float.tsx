'use client';

import React, { useState, useEffect, useRef } from "react";
import { MessageCircle, X, Phone, Mail, MapPin, Clock, Info } from "lucide-react";
import { FaWhatsapp } from "react-icons/fa6";
import { motion, AnimatePresence } from "framer-motion";

const FloatingWhatsApp = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [activeTab, setActiveTab] = useState<'quickChat' | 'contactInfo'>('quickChat');
  const [isVisible, setIsVisible] = useState(true);
  const lastScrollY = useRef(0);

  useEffect(() => {
    const handleScroll = () => {
      const currentScrollY = window.scrollY;
      const windowHeight = window.innerHeight;
      const documentHeight = document.documentElement.scrollHeight;
      const scrollBottom = documentHeight - (currentScrollY + windowHeight);
      
      // Check if we're at the top of the page (in slider area)
      const isInSliderArea = currentScrollY < 300; // Adjust this value based on your slider height
      
      // Check if we're at the bottom of the page (in footer area)
      const isInFooterArea = scrollBottom < 600;
      
      // Hide only when in slider area or footer area
      if (isInSliderArea || isInFooterArea) {
        setIsVisible(false);
      } else {
        setIsVisible(true);
      }
      
      lastScrollY.current = currentScrollY;
    };

    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  const contactInfo = {
    phone: "+917289000377",
    email: "help@navdhya.com",
    address: "Pandav Nagar Delhi 110092",
    hours: "24/7 Support"
  };

  const quickChats = [
    { id: 1, title: "Puja Inquiry", message: "Hello, I'd like to inquire about your puja services." },
    { id: 2, title: "Booking Help", message: "I need assistance with booking a ritual." },
    { id: 3, title: "Pandit Request", message: "I'd like to speak with a pandit about specific rituals." },
    { id: 4, title: "General Support", message: "I have a general question about your services." },
    { id: 5, title: "Ritual Consultation", message: "I'd like to know which rituals are suitable for my needs." },
    { id: 6, title: "Custom Puja", message: "I want to book a custom puja ceremony." }
  ];

  const handleQuickChat = (message: string) => {
    const whatsappUrl = `https://wa.me/${contactInfo.phone.replace(/\D/g, '')}?text=${encodeURIComponent(message)}`;
    window.open(whatsappUrl, '_blank');
    setIsOpen(false);
  };

  const handleDirectChat = () => {
    const whatsappUrl = `https://wa.me/${contactInfo.phone.replace(/\D/g, '')}`;
    window.open(whatsappUrl, '_blank');
    setIsOpen(false);
  };

  return (
    <div
      className={`lg:hidden fixed bottom-20 left-1/2 transform -translate-x-1/2 z-[9999] flex flex-col items-center gap-2 transition-all duration-300 ease-in-out ${
        isVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'
      }`}
    >
      {/* Chat Panel */}
      <AnimatePresence>
        {isOpen && (
          <motion.div
            initial={{ opacity: 0, y: 30, scale: 0.9 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: 30, scale: 0.9 }}
            transition={{
              type: "spring",
              damping: 25,
              stiffness: 300,
              opacity: { duration: 0.2 },
              y: { duration: 0.3 }
            }}
            className="bg-white/95 backdrop-blur-md rounded-xl shadow-2xl p-5 mb-2 border border-green-100 w-80 font-sans"
          >
            {/* Header */}
            <div className="flex items-center justify-between mb-3">
              <div className="flex items-center gap-2">
                <div className="bg-[#0E3F2E] p-2 rounded-lg shadow-sm">
                  <FaWhatsapp size={16} className="text-white" />
                </div>
                <div>
                  <h3 className="font-medium text-gray-800 text-sm tracking-tight">Navdhya Support</h3>
                  <p className="text-[11px] text-green-600 flex items-center gap-1">
                    <span className="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    Online now
                  </p>
                </div>
              </div>
              <button
                onClick={() => setIsOpen(false)}
                className="text-gray-400 hover:text-gray-600 transition-colors p-1 focus:outline-none"
                aria-label="Close chat"
              >
                <X size={16} />
              </button>
            </div>

            {/* Tabs */}
            <div className="flex border-b border-gray-200 mb-3">
              <button
                onClick={() => setActiveTab('quickChat')}
                className={`flex-1 py-2 text-xs font-medium flex items-center justify-center gap-1 ${
                  activeTab === 'quickChat'
                    ? 'text-[#0E3F2E] border-b-2 border-[#0E3F2E]'
                    : 'text-gray-500 hover:text-gray-700'
                }`}
              >
                <MessageCircle size={12} />
                Quick Chat
              </button>
              <button
                onClick={() => setActiveTab('contactInfo')}
                className={`flex-1 py-2 text-xs font-medium flex items-center justify-center gap-1 ${
                  activeTab === 'contactInfo'
                    ? 'text-[#0E3F2E] border-b-2 border-[#0E3F2E]'
                    : 'text-gray-500 hover:text-gray-700'
                }`}
              >
                <Info size={12} />
                Contact Info
              </button>
            </div>

            {/* Content */}
            <div className="mb-4 h-56 overflow-y-auto scrollbar-thin scrollbar-thumb-green-200 scrollbar-track-transparent">
              {activeTab === 'quickChat' ? (
                <div className="grid grid-cols-2 gap-2">
                  {quickChats.map((chat) => (
                    <motion.button
                      key={chat.id}
                      whileHover={{ scale: 1.03, y: -2 }}
                      whileTap={{ scale: 0.98 }}
                      initial={{ opacity: 0, y: 10 }}
                      animate={{ opacity: 1, y: 0 }}
                      transition={{ delay: chat.id * 0.05 }}
                      onClick={() => handleQuickChat(chat.message)}
                      className="bg-white border border-gray-200 rounded-lg p-2 text-left hover:border-green-300 hover:shadow-sm transition-all text-xs font-medium text-gray-800"
                    >
                      {chat.title}
                    </motion.button>
                  ))}
                </div>
              ) : (
                <div className="space-y-3 text-[10px] text-gray-800">
                  <div className="flex items-start gap-2">
                    <Phone size={12} className="text-[#0E3F2E] mt-0.5" />
                    <div>
                      <p className="text-gray-500">Phone</p>
                      <p>{contactInfo.phone}</p>
                    </div>
                  </div>
                  <div className="flex items-start gap-2">
                    <Mail size={12} className="text-[#0E3F2E] mt-0.5" />
                    <div>
                      <p className="text-gray-500">Email</p>
                      <p>{contactInfo.email}</p>
                    </div>
                  </div>
                  <div className="flex items-start gap-2">
                    <MapPin size={12} className="text-[#0E3F2E] mt-0.5" />
                    <div>
                      <p className="text-gray-500">Address</p>
                      <p>{contactInfo.address}</p>
                    </div>
                  </div>
                  <div className="flex items-start gap-2">
                    <Clock size={12} className="text-[#0E3F2E] mt-0.5" />
                    <div>
                      <p className="text-gray-500">Hours</p>
                      <p>{contactInfo.hours}</p>
                    </div>
                  </div>
                </div>
              )}
            </div>

            {/* Direct Chat Button */}
            <motion.button
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
              onClick={handleDirectChat}
              className="w-full bg-[#0E3F2E] hover:bg-[#0C3726] text-white py-2 px-3 rounded-lg flex items-center justify-center gap-2 transition-all shadow-md"
            >
              <FaWhatsapp size={14} />
              <span className="text-xs font-medium">Direct Chat</span>
            </motion.button>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Floating Button */}
      <motion.button
        whileHover={{ scale: 1.05 }}
        whileTap={{ scale: 0.95 }}
        initial={{ y: 50, opacity: 0 }}
        animate={{ y: 0, opacity: 1 }}
        transition={{ type: "spring", stiffness: 300, damping: 20 }}
        onClick={() => setIsOpen(!isOpen)}
        className="bg-[#0E3F2E] hover:bg-[#0C3726] text-white py-2.5 px-4 rounded-full shadow-lg flex items-center justify-center gap-2"
      >
        <FaWhatsapp size={20} />
        <span className="text-sm font-medium">Direct Chat</span>
      </motion.button>
    </div>
  );
};

export default FloatingWhatsApp;
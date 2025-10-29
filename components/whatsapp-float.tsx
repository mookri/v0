'use client'
import React, { useState } from "react";
import { MessageCircle, X, Phone, Mail, MapPin, Clock } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";

const FloatingWhatsApp = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [message, setMessage] = useState("");

  const contactInfo = {
    phone: "+917289000377",
    email: "help@navdhya.com",
    address: "Pandav Nagar Delhi 110092",
    hours: "24/7 Support"
  };

  const quickChats = [
    { 
      id: 1, 
      title: "Puja Inquiry", 
      message: "Hello, I'd like to inquire about your puja services.",
      icon: "🙏"
    },
    { 
      id: 2, 
      title: "Booking Help", 
      message: "I need assistance with booking a ritual.",
      icon: "📅"
    },
    { 
      id: 3, 
      title: "Pandit Request", 
      message: "I'd like to speak with a pandit about specific rituals.",
      icon: "👨‍🏫"
    },
    { 
      id: 4, 
      title: "General Support", 
      message: "I have a general question about your services.",
      icon: "❓"
    }
  ];

  const handleQuickChat = (message: string) => {
    const whatsappUrl = `https://wa.me/${contactInfo.phone.replace(/\s/g, '')}?text=${encodeURIComponent(message)}`;
    window.open(whatsappUrl, '_blank');
    setIsOpen(false);
  };

  const handleDirectChat = () => {
    const whatsappUrl = `https://wa.me/${contactInfo.phone.replace(/\s/g, '')}`;
    window.open(whatsappUrl, '_blank');
    setIsOpen(false);
  };

  return (
    <div className="fixed bottom-5 right-5 z-[9999] flex flex-col items-end gap-2">
      {/* Chat Panel */}
      <AnimatePresence>
        {isOpen && (
          <motion.div
            initial={{ opacity: 0, scale: 0.9, y: 10 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={{ opacity: 0, scale: 0.9, y: 10 }}
            transition={{ type: "spring", damping: 25, stiffness: 300 }}
            className="bg-white/95 backdrop-blur-sm rounded-xl shadow-lg p-4 mb-2 border border-green-100 w-72"
          >
            {/* Header */}
            <div className="flex items-center justify-between mb-3">
              <div className="flex items-center gap-2">
                <div className="bg-gradient-to-r from-green-500 to-green-600 p-1.5 rounded-lg shadow-sm">
                  <MessageCircle className="text-white" size={16} />
                </div>
                <div>
                  <h3 className="font-bold text-gray-800 text-sm tracking-tight">Navdhya Support</h3>
                  <p className="text-xs text-green-600 flex items-center gap-1">
                    <span className="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                    Online now
                  </p>
                </div>
              </div>
              <button 
                onClick={() => setIsOpen(false)}
                className="text-gray-400 hover:text-gray-600 transition-colors p-1"
              >
                <X size={16} />
              </button>
            </div>

            {/* Contact Info */}
            <div className="bg-gradient-to-r from-green-50 to-green-100 rounded-lg p-2.5 mb-3 border border-green-200">
              <div className="space-y-1.5 text-xs">
                <div className="flex items-center gap-2">
                  <Phone size={12} className="text-green-600 flex-shrink-0" />
                  <span className="text-gray-700 font-medium">{contactInfo.phone}</span>
                </div>
                <div className="flex items-center gap-2">
                  <Mail size={12} className="text-green-600 flex-shrink-0" />
                  <span className="text-gray-700 font-medium">{contactInfo.email}</span>
                </div>
                <div className="flex items-center gap-2">
                  <MapPin size={12} className="text-green-600 flex-shrink-0" />
                  <span className="text-gray-700 truncate font-medium">{contactInfo.address}</span>
                </div>
                <div className="flex items-center gap-2">
                  <Clock size={12} className="text-green-600 flex-shrink-0" />
                  <span className="text-gray-700 font-medium">{contactInfo.hours}</span>
                </div>
              </div>
            </div>

            {/* Quick Chat Options */}
            <div className="mb-3">
              <h4 className="text-xs font-bold text-gray-800 mb-2 tracking-tight">How can we help?</h4>
              <div className="grid grid-cols-2 gap-2">
                {quickChats.map((chat) => (
                  <motion.button
                    key={chat.id}
                    whileHover={{ scale: 1.02 }}
                    whileTap={{ scale: 0.98 }}
                    onClick={() => handleQuickChat(chat.message)}
                    className="bg-white border border-gray-200 rounded-lg p-2 text-left hover:border-green-300 hover:shadow-sm transition-all"
                  >
                    <div className="text-sm mb-1">{chat.icon}</div>
                    <div className="text-xs font-semibold text-gray-800">{chat.title}</div>
                  </motion.button>
                ))}
              </div>
            </div>

            {/* Direct Chat Button */}
            <motion.button
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
              onClick={handleDirectChat}
              className="w-full bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white text-sm font-bold py-2 px-3 rounded-lg flex items-center justify-center gap-2 transition-all shadow-sm"
            >
              <MessageCircle size={14} />
              Direct Chat
            </motion.button>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Floating Button */}
      <motion.button
        whileHover={{ scale: 1.05 }}
        whileTap={{ scale: 0.95 }}
        onClick={() => setIsOpen(!isOpen)}
        className="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white p-3 rounded-full shadow-lg flex items-center justify-center relative"
      >
        <MessageCircle size={20} />
        <motion.div
          animate={{ scale: [1, 1.1, 1] }}
          transition={{ repeat: Infinity, duration: 1.5 }}
          className="absolute inset-0 bg-green-500 rounded-full -z-10 opacity-30"
        />
      </motion.button>
    </div>
  );
};

export default FloatingWhatsApp;
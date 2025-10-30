'use client'
import React, { useState } from "react";
import { MessageCircle, X, Phone, Mail, MapPin, Clock, User, Info } from "lucide-react";
import { FaWhatsapp } from "react-icons/fa6";
import { motion, AnimatePresence } from "framer-motion";

const FloatingWhatsApp = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [activeTab, setActiveTab] = useState<'quickChat' | 'contactInfo'>('quickChat');

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
      message: "Hello, I'd like to inquire about your puja services."
    },
    { 
      id: 2, 
      title: "Booking Help", 
      message: "I need assistance with booking a ritual."
    },
    { 
      id: 3, 
      title: "Pandit Request", 
      message: "I'd like to speak with a pandit about specific rituals."
    },
    { 
      id: 4, 
      title: "General Support", 
      message: "I have a general question about your services."
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
    <div className="fixed bottom-4 right-4 z-[9999] flex flex-col items-end gap-1.5">
      {/* Chat Panel */}
      <AnimatePresence>
        {isOpen && (
          <motion.div
            initial={{ opacity: 0, scale: 0.9, y: 10 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={{ opacity: 0, scale: 0.9, y: 10 }}
            transition={{ type: "spring", damping: 25, stiffness: 300 }}
            className="bg-white/95 backdrop-blur-sm rounded-sm shadow-lg p-2.5 mb-1.5 border border-green-100 w-64 font-sans"
          >
            {/* Header */}
            <div className="flex items-center justify-between mb-1.5">
              <div className="flex items-center gap-1.5">
                <div className="bg-gradient-to-r from-green-500 to-green-600 p-1 rounded-sm shadow-sm">
                  <FaWhatsapp size={12} className="text-white" />
                </div>
                <div>
                  <h3 className="font-normal text-gray-800 text-xs tracking-tight">Navdhya Support</h3>
                  <p className="text-[10px] text-green-600 flex items-center gap-1">
                    <span className="w-1 h-1 bg-green-500 rounded-full animate-pulse"></span>
                    Online now
                  </p>
                </div>
              </div>
              <button 
                onClick={() => setIsOpen(false)}
                className="text-gray-400 hover:text-gray-600 transition-colors p-0.5"
              >
                <X size={12} />
              </button>
            </div>

            {/* Tab Navigation */}
            <div className="flex border-b border-gray-200 mb-1.5">
              <button
                onClick={() => setActiveTab('quickChat')}
                className={`flex-1 py-1.5 text-[10px] font-normal flex items-center justify-center gap-1 ${
                  activeTab === 'quickChat'
                    ? 'text-green-600 border-b border-green-600'
                    : 'text-gray-500 hover:text-gray-700'
                }`}
              >
                <MessageCircle size={10} />
                Quick Chat
              </button>
              <button
                onClick={() => setActiveTab('contactInfo')}
                className={`flex-1 py-1.5 text-[10px] font-normal flex items-center justify-center gap-1 ${
                  activeTab === 'contactInfo'
                    ? 'text-green-600 border-b border-green-600'
                    : 'text-gray-500 hover:text-gray-700'
                }`}
              >
                <Info size={10} />
                Contact Info
              </button>
            </div>

            {/* Tab Content */}
            <div className="mb-1.5 h-36">
              {activeTab === 'quickChat' ? (
                <div className="h-full">
                  <h4 className="text-[10px] font-normal text-gray-800 mb-1.5 tracking-tight">How can we help?</h4>
                  <div className="grid grid-cols-2 gap-1.5">
                    {quickChats.map((chat) => (
                      <motion.button
                        key={chat.id}
                        whileHover={{ scale: 1.02 }}
                        whileTap={{ scale: 0.98 }}
                        onClick={() => handleQuickChat(chat.message)}
                        className="bg-white border border-gray-200 rounded-sm p-1.5 text-left hover:border-green-300 hover:shadow-sm transition-all"
                      >
                        <div className="text-[10px] font-medium text-gray-800">{chat.title}</div>
                      </motion.button>
                    ))}
                  </div>
                </div>
              ) : (
                <div className="h-full">
                  <div className="space-y-1.5">
                    <div className="flex items-start gap-1.5">
                      <Phone size={10} className="text-green-600 flex-shrink-0 mt-0.5" />
                      <div>
                        <p className="text-[9px] text-gray-500">Phone</p>
                        <p className="text-[9px] text-gray-800">{contactInfo.phone}</p>
                      </div>
                    </div>
                    <div className="flex items-start gap-1.5">
                      <Mail size={10} className="text-green-600 flex-shrink-0 mt-0.5" />
                      <div>
                        <p className="text-[9px] text-gray-500">Email</p>
                        <p className="text-[9px] text-gray-800">{contactInfo.email}</p>
                      </div>
                    </div>
                    <div className="flex items-start gap-1.5">
                      <MapPin size={10} className="text-green-600 flex-shrink-0 mt-0.5" />
                      <div>
                        <p className="text-[9px] text-gray-500">Address</p>
                        <p className="text-[9px] text-gray-800">{contactInfo.address}</p>
                      </div>
                    </div>
                    <div className="flex items-start gap-1.5">
                      <Clock size={10} className="text-green-600 flex-shrink-0 mt-0.5" />
                      <div>
                        <p className="text-[9px] text-gray-500">Hours</p>
                        <p className="text-[9px] text-gray-800">{contactInfo.hours}</p>
                      </div>
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
              className="w-full bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white text-[10px] font-normal py-1.5 px-2 rounded-sm flex items-center justify-center gap-1 transition-all shadow-sm"
            >
              <FaWhatsapp size={10} />
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
        className="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white p-2.5 rounded-full shadow-lg flex items-center justify-center relative"
      >
        <FaWhatsapp size={20} className="text-white" />
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
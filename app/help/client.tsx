'use client'

import React, { useState } from 'react';
import Link from "next/link"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video, Search, MessageCircle, ThumbsUp, Reply, Flag, MoreHorizontal, User, HelpCircle, Shield, Lock, FileText as FileTextIcon, Home, Flame } from "lucide-react"

// FAQ data organized by categories
const faqCategories = [
  {
    id: "booking",
    title: "Booking & Services",
    icon: Calendar,
    faqs: [
      {
        question: "How do I book a puja service?",
        answer: "Booking is simple! You can either use our online booking system by selecting your preferred service, choosing a date and time, or call us directly at +91-7289000377. Our team will connect you with a certified pandit who will guide you through the process."
      },
      {
        question: "What is included in the puja service?",
        answer: "Our services include a certified pandit, all necessary items (puja samagri), and guidance throughout the ceremony. For home services, we also provide setup and cleanup to ensure a hassle-free experience."
      },
      {
        question: "Can I customize the rituals?",
        answer: "Absolutely! We understand that every family has unique traditions. Our pandits work with you to customize rituals according to your specific customs, beliefs, and requirements while maintaining the authenticity of Vedic practices."
      },
      {
        question: "How far in advance should I book?",
        answer: "We recommend booking at least 3-7 days in advance for most services to ensure availability of pandits and materials. For popular festivals or special occasions, booking 2-3 weeks ahead is advisable."
      }
    ]
  },
  {
    id: "pandits",
    title: "Our Pandits",
    icon: Users,
    faqs: [
      {
        question: "Are your pandits certified?",
        answer: "Yes, all our pandits are verified experts with authentic Vedic knowledge. They undergo a rigorous certification process that includes verification of credentials, experience, and expertise in performing various rituals."
      },
      {
        question: "Can I request a specific pandit?",
        answer: "Yes, you can request a specific pandit during the booking process. If the pandit is available on your preferred date and time, we'll do our best to accommodate your request."
      },
      {
        question: "What languages do your pandits speak?",
        answer: "Our pandits are multilingual and can conduct rituals in Sanskrit, Hindi, English, and regional languages like Tamil, Telugu, Bengali, and Gujarati based on your preference."
      }
    ]
  },
  {
    id: "pricing",
    title: "Pricing & Payments",
    icon: ShoppingCart,
    faqs: [
      {
        question: "What payment methods do you accept?",
        answer: "We accept all major credit/debit cards, UPI, net banking, and popular digital wallets. For corporate bookings, we also offer bank transfers and cheque payments."
      },
      {
        question: "Are there any hidden charges?",
        answer: "No, our pricing is completely transparent. The price you see is what you pay. All applicable taxes are included in the displayed price with no hidden costs."
      },
      {
        question: "Do you offer any discounts or packages?",
        answer: "Yes, we offer seasonal discounts, family packages, and corporate wellness programs. Subscribe to our newsletter to stay updated on special offers and promotions."
      }
    ]
  },
  {
    id: "cancellation",
    title: "Cancellation & Refunds",
    icon: X,
    faqs: [
      {
        question: "What is your cancellation policy?",
        answer: "You can cancel your booking up to 48 hours before the scheduled service for a full refund. Cancellations within 48 hours may incur a 25% cancellation fee. No-shows will be charged the full amount."
      },
      {
        question: "How do I request a refund?",
        answer: "Refunds can be requested through our customer support portal or by calling our helpline. The refund process typically takes 7-10 business days to complete."
      }
    ]
  }
];

// Help topics data
const helpTopics = [
  {
    id: 1,
    title: "Puja at Home",
    description: "Learn about our home puja services and what to expect during the ceremony.",
    icon: Home,
    href: "/pujas"
  },
  {
    id: 2,
    title: "Online Puja",
    description: "Participate in spiritual rituals remotely with our virtual puja services.",
    icon: Video,
    href: "/pujas/online"
  },
  {
    id: 3,
    title: "Temple Services",
    description: "Explore our offerings for temple-based rituals and ceremonies.",
    icon: BookOpen,
    href: "/pujas/temple"
  },
  {
    id: 4,
    title: "Havan & Yagya",
    description: "Understand the significance and process of sacred fire rituals.",
    icon: Flame,
    href: "/pujas/havan"
  },
  {
    id: 5,
    title: "Vedic Rituals",
    description: "Discover the ancient wisdom behind our traditional Vedic ceremonies.",
    icon: Shield,
    href: "/pujas/vedic"
  },
  {
    id: 6,
    title: "Custom Pujas",
    description: "Create personalized rituals tailored to your specific spiritual needs.",
    icon: Star,
    href: "/pujas/custom"
  },
  {
    id: 7,
    title: "Kundli Analysis",
    description: "Get expert interpretation of your birth chart for life guidance.",
    icon: FileTextIcon,
    href: "/astrology/kundli"
  },
  {
    id: 8,
    title: "Horoscope Matching",
    description: "Ensure compatibility for marriages with our detailed matching service.",
    icon: Heart,
    href: "/astrology/matchmaking"
  }
];

// FAQ Item Component with progressive disclosure
const FAQItem = ({ question, answer, isOpen, onClick }: { question: string; answer: string; isOpen: boolean; onClick: () => void }) => {
  return (
    <div className="bg-card border border-border rounded-lg overflow-hidden transition-all duration-200 hover:shadow-sm">
      <button
        className="flex justify-between items-center w-full p-4 text-left focus:outline-none"
        onClick={onClick}
        aria-expanded={isOpen}
      >
        <h3 className="text-sm font-medium text-foreground">{question}</h3>
        <ChevronDown 
          className={`w-5 h-5 text-muted-foreground transition-transform duration-200 ${isOpen ? 'rotate-180' : ''}`} 
        />
      </button>
      <div 
        className={`overflow-hidden transition-all duration-200 ease-in-out ${
          isOpen ? 'max-h-96 opacity-100 pb-4' : 'max-h-0 opacity-0'
        }`}
      >
        <div className="px-4 text-muted-foreground text-sm">
          <p>{answer}</p>
        </div>
      </div>
    </div>
  );
};

// Help Topic Card Component
const HelpTopicCard = ({ topic }: { topic: typeof helpTopics[0] }) => {
  return (
    <Link 
      href={topic.href}
      className="group border border-border rounded-lg overflow-hidden bg-card hover:shadow-lg transition-all duration-300 flex flex-col h-full"
    >
      <div className="p-5 flex-grow">
        <div className="w-10 h-10 rounded-lg bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mb-4">
          <topic.icon className="w-5 h-5" />
        </div>
        <h3 className="font-medium text-lg mb-2 group-hover:text-[#0E3F2E] transition-colors">
          {topic.title}
        </h3>
        <p className="text-sm text-muted-foreground">
          {topic.description}
        </p>
      </div>
      <div className="px-5 py-3 bg-muted/50 border-t border-border">
        <div className="flex items-center justify-between">
          <span className="text-sm font-medium text-[#0E3F2E]">Learn More</span>
          <ArrowRight className="w-4 h-4 text-muted-foreground group-hover:translate-x-1 transition-transform" />
        </div>
      </div>
    </Link>
  );
};

export default function HelpClient() {
  const [openFaqIndex, setOpenFaqIndex] = useState<{category: number, faq: number} | null>(null)
  const [activeCategory, setActiveCategory] = useState("all");

  const toggleFaq = (categoryIndex: number, faqIndex: number) => {
    if (openFaqIndex?.category === categoryIndex && openFaqIndex?.faq === faqIndex) {
      setOpenFaqIndex(null);
    } else {
      setOpenFaqIndex({ category: categoryIndex, faq: faqIndex });
    }
  };

  return (
    <div className="min-h-screen bg-background">
      {/* Breadcrumb */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
        <nav className="text-xs text-muted-foreground">
          <Link href="/" className="hover:text-foreground">Home</Link>
          <span className="mx-1.5">/</span>
          <span className="text-foreground">Help & Support</span>
        </nav>
      </div>

      {/* Hero Banner */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-8">
        <div className="text-center py-10">
          <div className="w-16 h-16 rounded-full bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-6">
            <HelpCircle className="w-8 h-8" />
          </div>
          <h1 className="text-3xl font-serif font-semibold mb-4">Help & Support</h1>
          <p className="text-lg text-muted-foreground max-w-2xl mx-auto mb-8">
            Find answers to your questions and get help with our services
          </p>
          
          <div className="relative max-w-xl mx-auto">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-muted-foreground" />
            <input
              type="text"
              placeholder="Search help topics..."
              className="w-full pl-10 pr-4 py-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]"
            />
          </div>
        </div>
      </div>

      {/* Stats Bar */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-10">
        <div className="bg-card border border-border rounded-lg p-6 grid grid-cols-2 md:grid-cols-4 gap-6">
          <div className="text-center">
            <div className="text-2xl font-bold text-[#0E3F2E] mb-2">24/7</div>
            <div className="text-sm text-muted-foreground">Support Available</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-[#0E3F2E] mb-2">150+</div>
            <div className="text-sm text-muted-foreground">Help Articles</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-[#0E3F2E] mb-2">98%</div>
            <div className="text-sm text-muted-foreground">Satisfaction Rate</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-[#0E3F2E] mb-2">2h</div>
            <div className="text-sm text-muted-foreground">Average Response</div>
          </div>
        </div>
      </div>

      {/* Popular Help Topics */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
        <div className="text-center mb-10">
          <h2 className="text-2xl font-serif font-semibold mb-4">Popular Help Topics</h2>
          <p className="text-muted-foreground max-w-2xl mx-auto">
            Explore our most frequently visited help topics
          </p>
        </div>
        
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {helpTopics.map(topic => (
            <HelpTopicCard key={topic.id} topic={topic} />
          ))}
        </div>
      </div>

      {/* Contact Support */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
        <div className="bg-card border border-border rounded-lg p-8 text-center">
          <div className="w-12 h-12 rounded-full bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-6">
            <Phone className="w-6 h-6" />
          </div>
          <h2 className="text-2xl font-serif font-semibold mb-4">Still Need Help?</h2>
          <p className="text-muted-foreground max-w-2xl mx-auto mb-6">
            Our support team is available 24/7 to assist you with any questions or concerns you may have.
          </p>
          
          <div className="flex flex-wrap justify-center gap-4">
            <a 
              href="tel:+91-7289000377" 
              className="inline-flex items-center gap-2 bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white font-medium py-3 px-6 rounded-lg transition-all duration-300 shadow-sm"
            >
              <Phone className="w-4 h-4" />
              <span>Call Us: +91-7289000377</span>
            </a>
            <a 
              href="mailto:help@navdhya.com" 
              className="inline-flex items-center gap-2 border border-border bg-background hover:bg-muted font-medium py-3 px-6 rounded-lg transition-all duration-300"
            >
              <Mail className="w-4 h-4" />
              <span>Email: help@navdhya.com</span>
            </a>
          </div>
        </div>
      </div>

      {/* FAQ Section */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 pb-12">
        <div className="text-center mb-10">
          <h2 className="text-2xl font-serif font-semibold mb-4">Frequently Asked Questions</h2>
          <p className="text-muted-foreground max-w-2xl mx-auto">
            Find answers to common questions about our services and processes
          </p>
        </div>
        
        <div className="space-y-8">
          {faqCategories.map((category, categoryIndex) => (
            <div key={category.id}>
              <div className="flex items-center gap-3 mb-6">
                <div className="w-10 h-10 rounded-lg bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center">
                  <category.icon className="w-5 h-5" />
                </div>
                <h3 className="text-xl font-semibold">{category.title}</h3>
              </div>
              
              <div className="space-y-4">
                {category.faqs.map((faq, faqIndex) => (
                  <FAQItem
                    key={faqIndex}
                    question={faq.question}
                    answer={faq.answer}
                    isOpen={openFaqIndex?.category === categoryIndex && openFaqIndex?.faq === faqIndex}
                    onClick={() => toggleFaq(categoryIndex, faqIndex)}
                  />
                ))}
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}
// components/data/menuData.ts
import {
  Sparkles,
  Home,
  Menu,
  Search,
  ShoppingCart,
  User,
  Calendar,
  BookOpen,
  Gift,
  Users,
  PhoneCall,
  Heart,
  Info,
  HelpCircle,
  Star,
  MapPin,
  Clock,
  Award,
  BookMarked,
  Compass,
  GraduationCap,
  MessageCircle,
} from "lucide-react";

// 🕉️ Main Mobile Dock Navigation (Bottom Bar)
export const DOCK_ITEMS = [
  {
    href: "/explore",
    label: "Explore",
    icon: Compass,
    description: "Discover all Pujas, Rituals, and Sacred Offerings",
  },
  {
    href: "/learn",
    label: "Learn",
    icon: GraduationCap,
    description: "Explore Mantras, Articles, and Spiritual Guides",
  },
  {
    href: "#menu",
    label: "Menu",
    icon: Menu,
    description: "All Navdhya Services",
  },
  {
    href: "/contact",
    label: "Chat",
    icon: MessageCircle,
    description: "Talk to Pandit or Support via WhatsApp",
  },
];

// 🌙 Menu Groups — for full popup menu, header, or sidebar
export const MENU_GROUPS = [
  {
    group: "Explore Rituals",
    description: "All Pujas, Dosha Nivaran, and Rituals for every occasion",
    items: [
      { label: "All Pujas", href: "/pujas", icon: Sparkles },
      { label: "Home Pujas", href: "/pujas/home", icon: Home },
      { label: "Temple Pujas", href: "/pujas/temple", icon: Calendar },
      { label: "Festival Pujas", href: "/pujas/festivals", icon: Calendar },
      { label: "Special Occasions", href: "/pujas/occasions", icon: Gift },
      { label: "Dosha Nivaran", href: "/pujas/dosha-nivaran", icon: Users },
      { label: "Custom Puja Request", href: "/pujas/custom", icon: BookOpen },
      { label: "Quick Book (3-step)", href: "/pujas/quick-book", icon: Sparkles },
    ],
  },
  {
    group: "Our Pandits",
    description: "Connect with verified and experienced Pandits across India",
    items: [
      { label: "Find a Pandit", href: "/pandits", icon: Users },
      { label: "Certified Pandits", href: "/pandits/certified", icon: Award },
      { label: "Book a Specific Pandit", href: "/pandits/book", icon: Calendar },
      { label: "Become a Pandit (Apply)", href: "/pandits/join", icon: BookMarked },
    ],
  },
  {
    group: "Astrology & Muhurat",
    description: "Astrological guidance and auspicious timing for your rituals",
    items: [
      { label: "Kundli Analysis", href: "/astrology/kundli", icon: BookOpen },
      { label: "Matchmaking", href: "/astrology/matchmaking", icon: Heart },
      { label: "Muhurat Finder", href: "/astrology/muhurat", icon: Clock },
      { label: "Vastu Consultation", href: "/vastu", icon: MapPin },
      { label: "Upload Kundli / Docs", href: "/astrology/upload", icon: BookOpen },
    ],
  },
  {
    group: "Sacred Store",
    description: "Buy authentic puja samagri, idols, and spiritual products",
    items: [
      { label: "Puja Samagri", href: "/shop/samagri", icon: ShoppingCart },
      { label: "Yantras & Idols", href: "/shop/yantras", icon: Gift },
      { label: "Rudraksha & Mala", href: "/shop/rudraksha", icon: Gift },
      { label: "Books & Guides", href: "/shop/books", icon: BookOpen },
      { label: "Gift Cards", href: "/shop/gift-cards", icon: Gift },
    ],
  },
  {
    group: "Learn & Spiritual Growth",
    description: "Mantras, Vedic knowledge, and teachings to enrich your path",
    items: [
      { label: "Blog & Articles", href: "/blog", icon: BookOpen },
      { label: "Mantras & Slokas", href: "/learn/mantras", icon: BookOpen },
      { label: "Video Library", href: "/learn/videos", icon: BookOpen },
      { label: "Testimonials", href: "/testimonials", icon: Star },
    ],
  },
  {
    group: "Connect & Support",
    description: "Help, account access, and communication with our team",
    items: [
      { label: "My Bookings", href: "/account/bookings", icon: Calendar },
      { label: "Profile & Family", href: "/account/profile", icon: User },
      { label: "Help & FAQ", href: "/help", icon: HelpCircle },
      { label: "Contact / WhatsApp", href: "/contact", icon: PhoneCall },
      { label: "About Navdhya", href: "/about", icon: Info },
      { label: "Privacy Policy", href: "/privacy-policy", icon: Info },
      { label: "Terms of Service", href: "/terms-of-service", icon: Info },
      { label: "Refund Policy", href: "/refund-policy", icon: Info },
      { label: "Cancellation Policy", href: "/cancellation-policy", icon: Info },
      { label: "Pandit Verification", href: "/legal/pandit-verification", icon: Info },
      { label: "Service Guarantee", href: "/legal/service-guarantee", icon: Info },
      { label: "Accessibility", href: "/legal/accessibility", icon: Info },
      { label: "Cookie Policy", href: "/legal/cookie-policy", icon: Info },
      { label: "Grievance Officer", href: "/legal/grievance-officer", icon: Info },
      { label: "Pandit Agreement", href: "/legal/pandit-agreement", icon: Info },
      { label: "Vendor Terms", href: "/legal/vendor-terms", icon: Info },
      { label: "Affiliate Disclosure", href: "/legal/affiliate-disclosure", icon: Info },
      { label: "IP Policy", href: "/legal/ip-policy", icon: Info },
      { label: "Child Protection", href: "/legal/child-protection", icon: Info },
      { label: "Data Processing", href: "/legal/data-processing", icon: Info },
    ],
  },
];

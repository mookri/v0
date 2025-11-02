import { notFound } from "next/navigation"
import { SiteHeader } from "@/components/site-header"
import { SiteFooter } from "@/components/site-footer"
import { SEO_CONFIG, BREADCRUMB_SCHEMA } from "@/lib/seo-config"
import Script from "next/script"
import PujaDetailsClient from "./client"

export async function generateMetadata({ params }: { params: { slug: string } }) {
  const { slug } = await params;
  const puja = DATA[slug]
  
  if (!puja) {
    return {
      title: "Puja Not Found | Navdhya",
      description: "The puja you're looking for could not be found."
    }
  }
  
  return {
    title: `${puja.title} | Navdhya – Delhi's Trusted Spiritual Wellness`,
    description: puja.description,
    keywords: [puja.title, "Puja Services in Delhi", "Hindu Rituals", "Vedic Pandits", "Spiritual Ceremonies"],
    openGraph: {
      title: `${puja.title} | Navdhya – Delhi's Trusted Spiritual Wellness`,
      description: puja.description,
      url: `https://www.navdhya.com/pujas/${slug}`,
      siteName: SEO_CONFIG.siteName,
      images: [
        {
          url: puja.image || SEO_CONFIG.defaultImage,
          width: 1200,
          height: 630,
          alt: `${puja.title} - Navdhya Puja Services`,
        },
      ],
      locale: SEO_CONFIG.locale,
      type: "website",
    },
    twitter: {
      card: "summary_large_image",
      title: `${puja.title} | Navdhya – Delhi's Trusted Spiritual Wellness`,
      description: puja.description,
      images: [puja.image || SEO_CONFIG.defaultImage],
      site: SEO_CONFIG.twitter.site,
    },
    alternates: {
      canonical: `https://www.navdhya.com/pujas/${slug}`,
    },
  }
}

type PujaData = {
  title: string;
  description: string;
  price: string;
  imgQuery: string;
  highlights: string[];
  longDescription: string;
  benefits: string[];
  process: { step: string; description: string }[];
  image: string;
  includes: string[];
  faqs: { question: string; answer: string }[];
};

const DATA: Record<string, PujaData> = {
  "griha-pravesh": {
    title: "Griha Pravesh Puja",
    description: "An auspicious ceremony performed before entering a new home to invoke blessings, prosperity, and harmony. Conducted by certified Pandits with proper Vidhi and Muhurat guidance.",
    price: "₹2,500 – ₹5,500",
    imgQuery: "griha%20pravesh%20puja%20mandap%20home%20entrance",
    highlights: ["Muhurat selection guidance", "All samagri support", "On-time service", "Post-puja clean-up"],
    longDescription: "Griha Pravesh is one of the most significant rituals in Hindu tradition, performed when entering a new home for the first time. This sacred ceremony invokes divine blessings for peace, prosperity, and harmony in the new dwelling. Our certified pandits conduct the complete vidhi with authentic mantras, ensuring every aspect follows Vedic traditions. The ceremony includes Vastu Purush worship, Ganesha Puja, Navagraha Shanti, and specific rituals to sanctify each room of your home.",
    benefits: [
      "Invokes divine blessings for peace and harmony in the new home",
      "Removes negative energies and creates a positive environment",
      "Ensures prosperity and well-being for all family members",
      "Establishes a sacred connection between the home and divine forces",
      "Follows authentic Vedic traditions with proper mantras and procedures"
    ],
    process: [
      { step: "Preparation", description: "Selection of auspicious Muhurat, preparation of puja samagri, and cleaning of the new home" },
      { step: "Kalasha Sthapana", description: "Installation of the sacred kalasha representing abundance and divine presence" },
      { step: "Vastu Purush Puja", description: "Worship of Vastu Purush to harmonize the energy of the space" },
      { step: "Ganesha Puja", description: "Invocation of Lord Ganesha to remove obstacles and ensure smooth proceedings" },
      { step: "Navagraha Shanti", description: "Pacification rituals for the nine celestial bodies for cosmic harmony" },
      { step: "Room Sanctification", description: "Specific rituals to bless each room with appropriate deities" },
      { step: "Aarti & Prasad", description: "Concluding rituals with devotional songs and distribution of blessed food" }
    ],
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    includes: [
      "Complete puja vidhi with Vedic mantras",
      "All essential puja samagri",
      "Professional pandit guidance",
      "Post-puja clean-up service",
      "Prasad distribution"
    ],
    faqs: [
      { question: "When is the best time to perform Griha Pravesh?", answer: "The best time is during an auspicious muhurat selected by our pandits based on your family's horoscope and the property's orientation." },
      { question: "How long does the ceremony take?", answer: "The complete Griha Pravesh ceremony typically takes 2-3 hours depending on the size of the property and specific requirements." },
      { question: "What should we prepare for the ceremony?", answer: "Our pandits will provide a detailed list of items needed. Generally, you'll need to arrange for flowers, fruits, and a clean space for the ceremony." },
      { question: "Can we perform this for rented properties?", answer: "Yes, Griha Pravesh can be performed for rented properties as well. Our pandits will guide you on the appropriate rituals for your situation." }
    ]
  },
  "satyanarayan-katha": {
    title: "Satyanarayan Puja",
    description: "A devotional ritual dedicated to Lord Vishnu for prosperity, peace, and fulfillment of wishes. Includes Katha, Aarti, and Prasad distribution.",
    price: "₹2,000 – ₹4,500",
    imgQuery: "satyanarayan%20katha%20devotional%20ritual",
    highlights: ["Professional Pandit", "Bhog & Aarti", "Simple setup", "Transparent pricing"],
    longDescription: "Satyanarayan Katha is a sacred ritual dedicated to Lord Vishnu, performed to seek his blessings for prosperity, peace, and the fulfillment of sincere wishes. The ceremony includes the recitation of the Satyanarayan Katha, which narrates the divine stories of Lord Vishnu's incarnations. This puja is particularly significant for new ventures, overcoming obstacles, and expressing gratitude for blessings received. Our pandits conduct the complete vidhi with devotion and authenticity.",
    benefits: [
      "Brings peace, prosperity, and harmony to the family",
      "Fulfills sincere wishes and removes obstacles in life",
      "Strengthens faith and spiritual connection with the divine",
      "Promotes harmony and unity among family members",
      "Bestows divine protection and blessings for new beginnings"
    ],
    process: [
      { step: "Ganesha Puja", description: "Invocation of Lord Ganesha to remove obstacles and ensure smooth proceedings" },
      { step: "Kalasha Sthapana", description: "Installation of the sacred kalasha representing abundance and divine presence" },
      { step: "Vishnu Puja", description: "Worship of Lord Vishnu with specific mantras and offerings" },
      { step: "Katha Recitation", description: "Narration of the divine stories of Lord Vishnu's incarnations" },
      { step: "Aarti & Bhog", description: "Devotional songs and offering of blessed food to the deity" },
      { step: "Prasad Distribution", description: "Distribution of blessed food among all participants" },
      { step: "Conclusion", description: "Final prayers and seeking continued divine blessings" }
    ],
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    includes: [
      "Complete Satyanarayan Katha recitation",
      "All puja samagri and offerings",
      "Professional pandit service",
      "Bhog and prasad distribution",
      "Aarti with devotional songs"
    ],
    faqs: [
      { question: "Who can perform Satyanarayan Katha?", answer: "Anyone can perform this puja - it's not restricted by gender, age, or caste. It's particularly beneficial for those seeking divine blessings for prosperity and peace." },
      { question: "How often should this puja be performed?", answer: "Traditionally, it's performed on full moon days (Purnima) or during special occasions. Many families perform it monthly or on important festivals." },
      { question: "Can it be done at home?", answer: "Yes, it can be performed at home with proper arrangements. Our pandits will guide you through the complete process." },
      { question: "What is the ideal duration for the katha?", answer: "The complete katha typically takes 1.5-2 hours. We can adjust the duration based on your requirements and availability." }
    ]
  },
  "lakshmi-ganesh-puja": {
    title: "Lakshmi Ganesh Puja",
    description: "Performed to seek blessings from Lord Ganesh and Goddess Lakshmi for removal of obstacles and prosperity.",
    price: "₹1,800 – ₹3,800",
    imgQuery: "ganesh%20puja%20traditional%20setup",
    highlights: ["Clean, quick setup", "Proper mantra chanting", "Auspicious timing", "Guided Aarti"],
    longDescription: "Lakshmi Ganesh Puja is a powerful combination ritual performed to invoke the blessings of Lord Ganesha, the remover of obstacles, and Goddess Lakshmi, the bestower of wealth and prosperity. This puja is ideal for new beginnings, business ventures, financial decisions, and seeking divine blessings for overall well-being. The ceremony includes specific mantras and offerings to both deities, ensuring comprehensive spiritual benefits.",
    benefits: [
      "Removes obstacles and challenges in personal and professional life",
      "Attracts wealth, prosperity, and financial stability",
      "Brings success in new ventures and business endeavors",
      "Enhances wisdom, knowledge, and decision-making abilities",
      "Creates a positive environment filled with divine energy and blessings"
    ],
    process: [
      { step: "Preparation", description: "Arrangement of idols or images of Lord Ganesha and Goddess Lakshmi" },
      { step: "Ganesha Puja", description: "Invocation and worship of Lord Ganesha with specific mantras" },
      { step: "Lakshmi Puja", description: "Worship of Goddess Lakshmi with lotus flowers and coins" },
      { step: "Mantra Chanting", description: "Recitation of powerful mantras dedicated to both deities" },
      { step: "Offerings", description: "Presentation of modaks, sweets, and other traditional offerings" },
      { step: "Aarti", description: "Devotional songs with lamps to honor the deities" },
      { step: "Prasad Distribution", description: "Distribution of blessed food among all participants" }
    ],
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg",
    includes: [
      "Idols or images of Lord Ganesha and Goddess Lakshmi",
      "All puja samagri and offerings",
      "Vedic mantra chanting",
      "Aarti with traditional lamps",
      "Prasad distribution"
    ],
    faqs: [
      { question: "When is the best time to perform this puja?", answer: "Friday is considered the most auspicious day for Lakshmi Puja, and Tuesday for Ganesha Puja. However, it can be performed on any day with proper guidance." },
      { question: "What materials are needed for the puja?", answer: "Our pandits will bring all essential materials. You may need to arrange for a clean space and some specific items based on your requirements." },
      { question: "How long does the ceremony take?", answer: "The complete puja typically takes 1-1.5 hours depending on the complexity and specific requirements." },
      { question: "Can this puja help with business prosperity?", answer: "Yes, this puja is specifically performed to attract wealth and remove business obstacles. Many entrepreneurs perform this regularly for success." }
    ]
  },
  "hindu-wedding": {
    title: "Hindu Wedding Ceremony",
    description: "Complete traditional wedding rituals with Vedic mantras, mandap, and proper vidhi performed by experienced Pandits.",
    price: "₹24,999 – ₹75,000",
    imgQuery: "hindu%20wedding%20mandap%20ritual%20sacred%20fire",
    highlights: ["Muhurat selection", "Full ceremony flow", "Mantra guidance", "Professional setup"],
    longDescription: "Our Hindu Wedding Ceremony package includes all traditional rituals performed by experienced Vedic pandits. From pre-wedding ceremonies like Muhurat selection to the complete wedding vidhi, we ensure every aspect follows authentic traditions. The ceremony includes Kanyadaan, Panigrahana, Saptpadi, and other sacred rituals that unite the couple in the divine presence of Agni Devta. Our comprehensive service includes mandap setup, puja samagri, and expert guidance throughout the process.",
    benefits: [
      "Sacred union blessed by Vedic traditions and divine witnesses",
      "Complete ritualistic ceremony ensuring spiritual and legal validity",
      "Expert guidance from certified pandits throughout the process",
      "Comprehensive service including mandap setup and all requirements",
      "Blessings for a harmonious, prosperous, and fulfilling married life"
    ],
    process: [
      { step: "Pre-Wedding", description: "Muhurat selection, pre-wedding rituals, and ceremony planning" },
      { step: "Baraat Reception", description: "Welcome rituals for the groom's procession at the venue" },
      { step: "Ganesh Puja", description: "Invocation of Lord Ganesha to remove obstacles" },
      { step: "Kanyadaan", description: "Sacred giving away of the bride by her parents" },
      { step: "Panigrahana", description: "Groom holding the bride's hand as a symbol of acceptance" },
      { step: "Saptpadi", description: "Seven sacred vows taken around the holy fire" },
      { step: "Aashirwad", description: "Blessings from elders and distribution of prasad" }
    ],
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    includes: [
      "Complete wedding ceremony with all rituals",
      "Experienced Vedic pandits",
      "Mandap setup and decoration",
      "All puja samagri and offerings",
      "Agni devta setup and maintenance",
      "Kanyadaan and Saptpadi rituals",
      "Post-ceremony clean-up"
    ],
    faqs: [
      { question: "How far in advance should we book?", answer: "We recommend booking at least 2-3 months in advance, especially for popular dates, to ensure availability of our senior pandits." },
      { question: "What is included in the package?", answer: "Our package includes all rituals, pandit services, mandap setup, and essential samagri. Additional items like flowers, catering, and photography are arranged separately." },
      { question: "Can we customize the rituals?", answer: "Yes, we can customize the ceremony based on your family traditions and specific requirements while maintaining the essential Vedic rituals." },
      { question: "Do you provide services outside Delhi?", answer: "Yes, we provide wedding services across Delhi NCR and can arrange services in other cities with prior notice." }
    ]
  },
  "baby-naming": {
    title: "Baby Naming (Namkaran)",
    description: "A sacred ceremony to give your child a divine name and seek blessings for their future.",
    price: "₹2,500 – ₹4,500",
    imgQuery: "baby%20naming%20ceremony%20hindu%20ritual",
    highlights: ["Astrological name selection", "Sacred ceremony", "Blessings for child", "Family participation"],
    longDescription: "The Baby Naming ceremony, or Namkaran, is a significant Hindu ritual performed to give your child their divine name and seek blessings for their future. This ceremony is typically conducted 11-21 days after birth, as per astrological calculations. Our pandits guide you through the process of selecting an auspicious name based on the child's nakshatra and perform the sacred ceremony with Vedic mantras. The ritual includes invoking divine blessings for the child's health, education, and overall well-being.",
    benefits: [
      "Gives the child their divine name as per Vedic traditions",
      "Invokes divine blessings for health, education, and prosperity",
      "Establishes the child's spiritual connection with the divine",
      "Brings family together to celebrate the new arrival",
      "Ensures positive energy and protection for the child's future"
    ],
    process: [
      { step: "Preparation", description: "Selection of auspicious date and time, preparation of samagri" },
      { step: "Ganesha Puja", description: "Invocation of Lord Ganesha to remove obstacles" },
      { step: "Name Selection", description: "Selection of divine name based on nakshatra and family traditions" },
      { step: "Nama Karan Vidhi", description: "Sacred ceremony of giving the child their name" },
      { step: "Mantra Chanting", description: "Recitation of protective mantras for the child's well-being" },
      { step: "Aarti & Prasad", description: "Devotional songs and distribution of blessed food" },
      { step: "Blessings", description: "Receiving blessings from elders and family members" }
    ],
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    includes: [
      "Astrological name selection based on nakshatra",
      "Sacred naming ceremony with Vedic mantras",
      "All puja samagri and offerings",
      "Professional pandit guidance",
      "Aarti and prasad distribution",
      "Certificate of the ceremony"
    ],
    faqs: [
      { question: "When should the ceremony be performed?", answer: "Ideally, it's performed between 11-21 days after birth, but can be done later as per family convenience and astrological guidance." },
      { question: "How is the name selected?", answer: "Our pandits analyze the child's nakshatra and select a name that begins with the appropriate letter as per Vedic traditions." },
      { question: "Can we have multiple names?", answer: "Yes, you can have multiple names - a formal name for official purposes and a nickname for daily use. Both can be blessed during the ceremony." },
      { question: "What should we prepare for the ceremony?", answer: "We'll provide a detailed list, but generally you'll need a clean space, flowers, fruits, and a small amount of ghee and rice for the rituals." }
    ]
  },
  "annaprashan": {
    title: "Annaprashan Ceremony",
    description: "The first feeding of solid food to your child, marking an important milestone in their growth.",
    price: "₹2,000 – ₹4,000",
    imgQuery: "annaprashan%20ceremony%20baby%20first%20food",
    highlights: ["Sacred first feeding", "Health blessings", "Family celebration", "Traditional rituals"],
    longDescription: "Annaprashan is a sacred Hindu ceremony marking the first feeding of solid food to your child, typically performed between 6 months to 1 year of age. This ritual signifies the child's transition to a new stage of growth and development. Our pandits conduct the ceremony with Vedic mantras, invoking divine blessings for the child's health, strength, and intellectual development. The ceremony includes worship of Goddess Annapurna, the deity of nourishment, and specific rituals to ensure the child's well-being.",
    benefits: [
      "Marks an important milestone in the child's physical development",
      "Invokes divine blessings for health, strength, and immunity",
      "Ensures proper nutrition and growth for the child",
      "Strengthens the child's connection with traditional values",
      "Brings family together to celebrate the child's growth"
    ],
    process: [
      { step: "Preparation", description: "Selection of auspicious date and time, preparation of food and samagri" },
      { step: "Ganesha Puja", description: "Invocation of Lord Ganesha to remove obstacles" },
      { step: "Annapurna Puja", description: "Worship of Goddess Annapurna, deity of nourishment" },
      { step: "Food Preparation", description: "Preparation of kheer or other traditional food with religious rituals" },
      { step: "Feeding Ceremony", description: "Sacred feeding of the child with Vedic mantras" },
      { step: "Aarti & Prasad", description: "Devotional songs and distribution of blessed food" },
      { step: "Blessings", description: "Receiving blessings from elders and family members" }
    ],
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg",
    includes: [
      "Sacred feeding ceremony with Vedic mantras",
      "Worship of Goddess Annapurna",
      "Traditional food preparation (kheer or cereal)",
      "All puja samagri and offerings",
      "Professional pandit service",
      "Aarti and prasad distribution"
    ],
    faqs: [
      { question: "What age is appropriate for Annaprashan?", answer: "The ceremony is typically performed when the child is between 6 months to 1 year old, based on family traditions and astrological guidance." },
      { question: "What food is fed to the child?", answer: "Traditionally, kheer (rice pudding) or a small amount of cereal is fed. The specific food can be customized based on family preferences and dietary requirements." },
      { question: "How long does the ceremony take?", answer: "The complete ceremony typically takes 1-1.5 hours including all rituals and blessings." },
      { question: "Can both parents participate?", answer: "Yes, both parents can participate in the ceremony. Traditionally, the father feeds the child while the mother holds them, but arrangements can be made as per family preferences." }
    ]
  },
  "office-opening": {
    title: "Office/Shop Opening Puja",
    description: "Bless your new business space with divine energy for success, prosperity, and protection. Conducted by certified Pandits with proper Vidhi and Muhurat guidance.",
    price: "₹3,500 – ₹7,500",
    imgQuery: "office%20opening%20puja%20business%20blessing",
    highlights: ["Muhurat selection guidance", "All samagri support", "On-time service", "Post-puja clean-up"],
    longDescription: "The Office/Shop Opening Puja is an auspicious ceremony performed to invoke divine blessings for success, prosperity, and protection in your new business venture. This sacred ritual helps remove obstacles, attract positive energy, and establish a harmonious environment for business growth. Our certified pandits conduct the complete vidhi with authentic mantras, ensuring every aspect follows Vedic traditions. The ceremony includes Ganesha Puja to remove obstacles, Lakshmi Puja for wealth and prosperity, and specific rituals to sanctify your business space.",
    benefits: [
      "Invokes divine blessings for business success and prosperity",
      "Removes negative energies and obstacles from the workplace",
      "Creates a positive and harmonious environment for business growth",
      "Attracts wealth, customers, and opportunities to your business",
      "Establishes a sacred connection between your business and divine forces",
      "Follows authentic Vedic traditions with proper mantras and procedures"
    ],
    process: [
      { step: "Preparation", description: "Selection of auspicious Muhurat, preparation of puja samagri, and cleaning of the business space" },
      { step: "Ganesha Puja", description: "Invocation of Lord Ganesha to remove obstacles and ensure smooth business operations" },
      { step: "Lakshmi Puja", description: "Worship of Goddess Lakshmi for wealth, prosperity, and financial success" },
      { step: "Navagraha Shanti", description: "Pacification rituals for the nine celestial bodies for cosmic harmony" },
      { step: "Space Sanctification", description: "Specific rituals to bless each area of your business premises" },
      { step: "Aarti & Prasad", description: "Concluding rituals with devotional songs and distribution of blessed food" }
    ],
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    includes: [
      "Complete puja vidhi with Vedic mantras",
      "All essential puja samagri",
      "Professional pandit guidance",
      "Post-puja clean-up service",
      "Prasad distribution"
    ],
    faqs: [
      { question: "When is the best time to perform Office Opening Puja?", answer: "The best time is during an auspicious muhurat selected by our pandits based on your business type and the property's orientation." },
      { question: "How long does the ceremony take?", answer: "The complete Office Opening Puja ceremony typically takes 2-3 hours depending on the size of the business space and specific requirements." },
      { question: "What should we prepare for the ceremony?", answer: "Our pandits will provide a detailed list of items needed. Generally, you'll need to arrange for flowers, fruits, and a clean space for the ceremony." },
      { question: "Can we perform this for rented business spaces?", answer: "Yes, Office Opening Puja can be performed for rented business spaces as well. Our pandits will guide you on the appropriate rituals for your situation." }
    ]
  },
  "vastu-shanti": {
    title: "Vastu Shanti Puja",
    description: "Harmonize your living or working space with Vastu principles for positive energy flow. Conducted by certified Pandits with proper Vidhi and Muhurat guidance.",
    price: "₹3,000 – ₹6,000",
    imgQuery: "vastu%20shanti%20puja%20ritual",
    highlights: ["Muhurat selection guidance", "All samagri support", "On-time service", "Post-puja clean-up"],
    longDescription: "Vastu Shanti Puja is performed to remove negative energies and obstacles from your living or working space, creating a harmonious environment that promotes peace, prosperity, and well-being. Our certified pandits conduct this sacred ritual following authentic Vedic traditions to align your space with positive cosmic energies. The ceremony includes worship of Vastu Purush, directional deities, and specific rituals to purify and energize each area of your premises.",
    benefits: [
      "Removes negative energies and obstacles from your living or working space",
      "Creates a harmonious environment that promotes peace and well-being",
      "Attracts positive cosmic energies for prosperity and success",
      "Aligns your space with Vastu principles for optimal energy flow",
      "Establishes divine blessings for harmony in your home or business",
      "Follows authentic Vedic traditions with proper mantras and procedures"
    ],
    process: [
      { step: "Preparation", description: "Selection of auspicious Muhurat, preparation of puja samagri, and cleaning of the space" },
      { step: "Ganesha Puja", description: "Invocation of Lord Ganesha to remove obstacles and ensure smooth proceedings" },
      { step: "Vastu Purush Puja", description: "Worship of Vastu Purush to harmonize the energy of the space" },
      { step: "Directional Worship", description: "Worship of deities associated with each direction for balanced energy" },
      { step: "Space Purification", description: "Specific rituals to purify and energize each area of your premises" },
      { step: "Aarti & Prasad", description: "Concluding rituals with devotional songs and distribution of blessed food" }
    ],
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    includes: [
      "Complete puja vidhi with Vedic mantras",
      "All essential puja samagri",
      "Professional pandit guidance",
      "Post-puja clean-up service",
      "Prasad distribution"
    ],
    faqs: [
      { question: "When is the best time to perform Vastu Shanti Puja?", answer: "The best time is during an auspicious muhurat selected by our pandits based on your property's orientation and your family's horoscope." },
      { question: "How long does the ceremony take?", answer: "The complete Vastu Shanti Puja ceremony typically takes 2-3 hours depending on the size of the property and specific requirements." },
      { question: "What should we prepare for the ceremony?", answer: "Our pandits will provide a detailed list of items needed. Generally, you'll need to arrange for flowers, fruits, and a clean space for the ceremony." },
      { question: "Can we perform this for rented properties?", answer: "Yes, Vastu Shanti Puja can be performed for rented properties as well. Our pandits will guide you on the appropriate rituals for your situation." }
    ]
  }
}

// Schema markup for individual puja pages
const generatePujaSchema = (puja: PujaData, slug: string) => ({
  "@context": "https://schema.org",
  "@type": "Service",
  name: puja.title,
  description: puja.description,
  provider: {
    "@type": "Organization",
    name: "Navdhya",
    url: "https://www.navdhya.com"
  },
  serviceType: "Hindu Puja Service",
  category: "Spiritual Services",
  offers: {
    "@type": "Offer",
    priceCurrency: "INR",
    price: puja.price
  },
  url: `https://www.navdhya.com/pujas/${slug}`,
  breadcrumb: BREADCRUMB_SCHEMA([
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Puja Services", url: "https://www.navdhya.com/pujas" },
    { name: puja.title, url: `https://www.navdhya.com/pujas/${slug}` }
  ])
})

export default async function PujaDetailsPage({ params }: { params: { slug: string } }) {
  const { slug } = await params;
  const puja = DATA[slug]
  
  if (!puja) return notFound()
  
  const pujaSchema = JSON.stringify(generatePujaSchema(puja, slug))
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: pujaSchema }}
      />
      <SiteHeader />
      <PujaDetailsClient puja={puja} slug={slug} />
      <SiteFooter />
    </>
  )
}
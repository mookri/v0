import { notFound } from "next/navigation"
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
      { step: "Saptpadi", description: "Seven sacred steps taken together around the holy fire" },
      { step: "Sindoor Daan", description: "Application of sindoor as a symbol of married status" },
      { step: "Aashirwad", description: "Blessings from elders and distribution of prasad" }
    ],
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    includes: [
      "Complete wedding ceremony with all Vedic rituals",
      "Professional pandit team for ceremony guidance",
      "Mandap setup with traditional decorations",
      "All puja samagri and ceremonial items",
      "Agni Devta setup and maintenance",
      "Prasad and refreshments for guests",
      "Post-ceremony clean-up service"
    ],
    faqs: [
      { question: "How far in advance should we book wedding services?", answer: "We recommend booking at least 2-3 months in advance to ensure availability of our pandits and proper ceremony planning." },
      { question: "Do you provide mandap and decoration services?", answer: "Yes, we provide complete mandap setup with traditional decorations. Custom designs can be arranged based on your preferences." },
      { question: "Can we customize the ceremony?", answer: "Absolutely. We work with families to customize rituals according to their traditions while maintaining Vedic authenticity." },
      { question: "What if we have specific family traditions?", answer: "Our pandits are experienced in accommodating various family traditions and regional customs within the Vedic framework." }
    ]
  },
  "baby-naming": {
    title: "Baby Naming Ceremony",
    description: "A sacred ceremony to give your child a divine name and seek blessings for their future.",
    price: "₹2,500 – ₹4,500",
    imgQuery: "baby%20naming%20ceremony%20hindu%20ritual",
    highlights: ["Personalized ceremony", "Name selection guidance", "Family participation", "Blessed prasad"],
    longDescription: "The Baby Naming Ceremony, known as Namkaran, is a significant Hindu ritual performed to give a child their divine name and seek blessings for their future. This ceremony is typically conducted within the first few weeks or months after birth. Our pandits guide families through the complete vidhi, including name selection based on astrological considerations, invocation of deities, and distribution of blessed prasad to family and friends.",
    benefits: [
      "Gives the child a meaningful, spiritually significant name",
      "Invokes divine blessings for the child's health and future",
      "Strengthens family bonds through shared spiritual experience",
      "Follows authentic Vedic traditions with proper mantras",
      "Creates positive energy and protection for the newborn"
    ],
    process: [
      { step: "Preparation", description: "Selection of auspicious date and time, preparation of ceremony items" },
      { step: "Ganesh Puja", description: "Invocation of Lord Ganesha to remove obstacles" },
      { step: "Name Selection", description: "Revealing the chosen name based on astrological guidance" },
      { step: "Deity Worship", description: "Worship of family deity or chosen divine form" },
      { step: "Name Writing", description: "Writing the name on a plate with rice or sand" },
      { step: "Aarti & Prasad", description: "Devotional songs and distribution of blessed food" },
      { step: "Blessings", description: "Receiving blessings from elders and family members" }
    ],
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    includes: [
      "Complete naming ceremony with Vedic mantras",
      "Professional pandit guidance throughout",
      "All puja samagri and ceremonial items",
      "Aarti with traditional lamps",
      "Prasad distribution to guests",
      "Blessed items for family keepsake"
    ],
    faqs: [
      { question: "When is the best time to perform this ceremony?", answer: "Traditionally within 11-41 days after birth, but can be performed later based on family convenience and astrological guidance." },
      { question: "How is the name selected?", answer: "Names are typically selected based on the child's nakshatra and varna, following traditional guidelines. Our pandits provide guidance on appropriate names." },
      { question: "Can extended family participate?", answer: "Yes, this is a family celebration. Extended family members are welcome to participate and give blessings." },
      { question: "What should we prepare for guests?", answer: "We recommend having refreshments for guests. Our pandits will guide you on specific ceremony requirements." }
    ]
  },
  "annaprashan": {
    title: "Annaprashan Ceremony",
    description: "The first feeding of solid food to your child, marking an important milestone in their growth.",
    price: "₹2,000 – ₹4,000",
    imgQuery: "annaprashan%20ceremony%20baby%20first%20food",
    highlights: ["Traditional ceremony", "Blessed food offering", "Family celebration", "Cultural significance"],
    longDescription: "Annaprashan is a sacred Hindu ceremony marking a child's first intake of solid food, typically performed between 6 months to 1 year of age. This ritual celebrates the child's growth and seeks divine blessings for their health and development. Our pandits conduct the complete vidhi with traditional offerings, ensuring the ceremony follows authentic customs while creating a memorable family experience.",
    benefits: [
      "Marks an important developmental milestone with spiritual significance",
      "Seeks divine blessings for the child's health and proper nutrition",
      "Strengthens family bonds through shared cultural traditions",
      "Follows Vedic customs with appropriate mantras and offerings",
      "Creates positive energy for the child's continued growth"
    ],
    process: [
      { step: "Preparation", description: "Selection of auspicious date and time, preparation of ceremony items" },
      { step: "Ganesh Puja", description: "Invocation of Lord Ganesha to remove obstacles" },
      { step: "Deity Worship", description: "Worship of Goddess Annapurna or family deity" },
      { step: "Food Preparation", description: "Preparation of kheer or other blessed food with specific ingredients" },
      { step: "Feeding Ceremony", description: "Feeding the child the first solid food with appropriate mantras" },
      { step: "Aarti & Prasad", description: "Devotional songs and distribution of blessed food" },
      { step: "Blessings", description: "Receiving blessings from elders and family members" }
    ],
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg",
    includes: [
      "Complete annaprashan ceremony with Vedic mantras",
      "Professional pandit guidance throughout",
      "All puja samagri and ceremonial items",
      "Preparation of blessed food (kheer or cereal)",
      "Aarti with traditional lamps",
      "Prasad distribution to guests"
    ],
    faqs: [
      { question: "When should this ceremony be performed?", answer: "Typically between 6 months to 1 year of age, with the exact timing determined by astrological guidance and family convenience." },
      { question: "What food is offered to the child?", answer: "Usually kheer (rice pudding) or a simple cereal mixed with ghee. The specific food can be customized based on family traditions." },
      { question: "Can we have a small gathering?", answer: "Yes, this can be a small family ceremony or a larger celebration based on your preferences." },
      { question: "What if the child refuses to eat?", answer: "That's completely normal. The ceremony is about the intention and blessings, not necessarily the child eating the food." }
    ]
  },
  "vastu-shanti": {
    title: "Vastu Shanti Puja",
    description: "Harmonize your living or working space with Vastu principles for positive energy flow.",
    price: "₹3,000 – ₹6,000",
    imgQuery: "vastu%20shanti%20puja%20home%20harmony",
    highlights: ["Space harmonization", "Energy balancing", "Vastu expert guidance", "Peaceful environment"],
    longDescription: "Vastu Shanti Puja is performed to harmonize the energy of a living or working space according to Vastu principles. This ceremony helps balance the five elements (earth, water, fire, air, and space) within the environment, creating positive energy flow and removing negative influences. Our pandits conduct specific rituals to purify the space and invoke divine blessings for harmony, prosperity, and well-being.",
    benefits: [
      "Harmonizes the energy of your living or working space",
      "Balances the five elements for positive energy flow",
      "Removes negative influences and obstacles in the environment",
      "Creates a peaceful, prosperous atmosphere for inhabitants",
      "Follows authentic Vastu principles with proper mantras"
    ],
    process: [
      { step: "Preparation", description: "Assessment of space and preparation of ceremony items" },
      { step: "Ganesh Puja", description: "Invocation of Lord Ganesha to remove obstacles" },
      { step: "Vastu Purush Puja", description: "Worship of Vastu Purush to harmonize the space" },
      { step: "Element Balancing", description: "Specific rituals for each of the five elements" },
      { step: "Space Purification", description: "Mantras and offerings to purify the entire area" },
      { step: "Aarti & Prasad", description: "Devotional songs and distribution of blessed food" },
      { step: "Consecration", description: "Final rituals to bless and protect the space" }
    ],
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_db05ba7c437e48abe6440798d2a33b09dr_bokdku.jpg",
    includes: [
      "Complete Vastu Shanti ceremony with Vedic mantras",
      "Professional pandit guidance throughout",
      "All puja samagri and ceremonial items",
      "Specific offerings for each element",
      "Aarti with traditional lamps",
      "Prasad distribution"
    ],
    faqs: [
      { question: "How often should this puja be performed?", answer: "Typically once when moving to a new space, and then every 2-3 years for continued harmony. Can also be performed when facing specific challenges." },
      { question: "Can this be done for office spaces?", answer: "Yes, this puja is beneficial for both residential and commercial spaces to create positive energy and remove obstacles." },
      { question: "Do we need to vacate the space during the ceremony?", answer: "Not necessary, but it's helpful to have a clean, organized space for the pandits to work. Family members can participate in the ceremony." },
      { question: "What preparations are needed?", answer: "A clean space and some specific items as guided by our pandits. We'll provide a detailed list based on your space." }
    ]
  },
  "navagraha-puja": {
    title: "Navagraha Puja",
    description: "Nine planetary worship for removing obstacles and bringing positive energy.",
    price: "₹4,500 – ₹6,000",
    imgQuery: "navagraha%20puja%20nine%20planets%20worship",
    highlights: ["Planetary harmony", "Obstacle removal", "Astrological guidance", "Divine protection"],
    longDescription: "Navagraha Puja is performed to worship the nine celestial bodies (planets) in Vedic astrology, seeking their blessings to remove obstacles and bring positive energy into one's life. This powerful ritual addresses specific planetary influences identified through kundli analysis, helping to mitigate negative effects and enhance positive ones. Our pandits conduct the complete vidhi with specific mantras for each graha, ensuring comprehensive spiritual benefits.",
    benefits: [
      "Removes obstacles and negative influences from planetary positions",
      "Brings harmony and balance to all areas of life",
      "Enhances positive planetary effects for success and well-being",
      "Provides divine protection and spiritual strength",
      "Follows authentic Vedic traditions with proper mantras"
    ],
    process: [
      { step: "Preparation", description: "Analysis of planetary positions and ceremony planning" },
      { step: "Ganesh Puja", description: "Invocation of Lord Ganesha to remove obstacles" },
      { step: "Surya Puja", description: "Worship of the Sun for vitality and leadership" },
      { step: "Chandra Puja", description: "Worship of the Moon for mental peace and emotions" },
      { step: "Mangal Puja", description: "Worship of Mars for courage and strength" },
      { step: "Budha Puja", description: "Worship of Mercury for communication and intelligence" },
      { step: "Brihaspati Puja", description: "Worship of Jupiter for wisdom and prosperity" },
      { step: "Shukra Puja", description: "Worship of Venus for love and luxury" },
      { step: "Shani Puja", description: "Worship of Saturn for discipline and patience" },
      { step: "Rahu-Ketu Puja", description: "Worship of the lunar nodes for spiritual growth" },
      { step: "Aarti & Prasad", description: "Devotional songs and distribution of blessed food" }
    ],
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg",
    includes: [
      "Complete Navagraha Puja with specific mantras for each planet",
      "Professional pandit guidance throughout",
      "All puja samagri and ceremonial items",
      "Specific offerings for each of the nine grahas",
      "Aarti with traditional lamps",
      "Prasad distribution"
    ],
    faqs: [
      { question: "When is the best time to perform this puja?", answer: "Typically on Saturdays or during specific planetary periods. Our pandits can determine the most auspicious timing based on your kundli." },
      { question: "How often should this be performed?", answer: "Generally once a year, but can be performed more frequently during challenging planetary periods or as recommended by astrological analysis." },
      { question: "Can this help with specific problems?", answer: "Yes, the puja can be customized to address specific challenges related to particular planetary influences in your life." },
      { question: "Do we need an astrological chart?", answer: "Having your birth details helps our pandits customize the ceremony, but it's not mandatory for the general puja." }
    ]
  }
};

export default function PujaDetailsPage({ params }: { params: { slug: string } }) {
  const { slug } = params;
  const puja = DATA[slug];
  
  if (!puja) {
    notFound();
  }
  
  const pujaSchema = JSON.stringify({
    "@context": "https://schema.org",
    "@type": "Service",
    "name": puja.title,
    "description": puja.description,
    "url": `https://www.navdhya.com/pujas/${slug}`,
    "provider": {
      "@type": "Organization",
      "name": "Navdhya",
      "url": "https://www.navdhya.com"
    },
    "offers": {
      "@type": "Offer",
      "priceCurrency": "INR",
      "price": puja.price.split(" – ")[0].replace(/[₹,]/g, ""),
      "priceSpecification": {
        "@type": "PriceSpecification",
        "minPrice": puja.price.split(" – ")[0].replace(/[₹,]/g, ""),
        "maxPrice": puja.price.split(" – ")[1].replace(/[₹,]/g, ""),
        "priceCurrency": "INR"
      }
    },
    "breadcrumb": BREADCRUMB_SCHEMA([
      { name: "Home", url: "https://www.navdhya.com" },
      { name: "Puja Services", url: "https://www.navdhya.com/pujas" },
      { name: puja.title, url: `https://www.navdhya.com/pujas/${slug}` }
    ])
  });
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: pujaSchema }}
      />
      <PujaDetailsClient puja={puja} slug={slug} />
    </>
  )
}
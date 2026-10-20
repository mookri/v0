export interface Puja {
  id: string;
  title: string;
  slug: string;
  description: string;
  category: string;
  
  price: string;
  originalPrice?: string;
  priceRange?: string;
  
  image: string;
  
  badge?: string;
  badgeType?: "green" | "blue" | "mint" | "orange";
  tag?: string;
  tagStyle?: string;
  
  rating?: string;
  reviews?: string;
  duration?: string;
  location?: string;
  
  panditName?: string;
  panditExperience?: string;
  panditType?: string;
  
  ritualType?: string;
  availability?: string;
  
  isFeatured?: boolean;
  isForYou?: boolean;
}

export const pujasData: Puja[] = [
  {
    id: "wedding",
    title: "Hindu Wedding Ceremony",
    slug: "hindu-wedding",
    description: "Delhi NCR wedding ritual support with Vedic vidhi, planning help, and ceremony-day coordination. Complete Vedic wedding rituals with experienced pandits.",
    category: "Life Events",
    price: "24,999",
    originalPrice: "75,000",
    priceRange: "Above Rs. 10,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    badge: "Most Booked",
    badgeType: "green",
    rating: "4.8",
    reviews: "1.2K reviews",
    duration: "3 - 5 hours",
    location: "At your venue",
    panditName: "Pandit Rajesh Sharma",
    panditExperience: "15+ years experience",
    panditType: "Senior Pandits",
    ritualType: "Vedic",
    availability: "Within 3 Days",
    isFeatured: true,
  },
  {
    id: "griha-pravesh",
    title: "Griha Pravesh Puja",
    slug: "griha-pravesh",
    description: "Move-in ceremony planning with muhurat guidance, samagri support, and verified pandits in Delhi NCR.",
    category: "Home & Family",
    price: "2,500",
    originalPrice: "5,500",
    priceRange: "Rs. 1,000 - Rs. 5,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    badge: "-11%",
    tag: "Peace",
    tagStyle: "bg-[#0c5647] text-white",
    rating: "4.9",
    reviews: "2.3K reviews",
    duration: "2 - 3 hours",
    location: "At your home",
    panditType: "Certified Pandits",
    ritualType: "Vedic",
    availability: "Immediate",
    isForYou: true,
  },
  {
    id: "satyanarayan-katha",
    title: "Satyanarayan Katha",
    slug: "satyanarayan-katha",
    description: "Devotional home puja with katha recitation, family participation, and transparent Delhi pricing.",
    category: "Home & Family",
    price: "2,000",
    originalPrice: "4,500",
    priceRange: "Rs. 1,000 - Rs. 5,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    badge: "-34%",
    tag: "Prosperity",
    tagStyle: "bg-[#ca7445] text-white",
    rating: "4.7",
    reviews: "850 reviews",
    duration: "1.5 - 2 hours",
    location: "At your home",
    panditType: "Experienced Pandits",
    ritualType: "Puranic",
    availability: "Within 3 Days",
    isForYou: true,
  },
  {
    id: "lakshmi-ganesh-puja",
    title: "Lakshmi Ganesh Puja",
    slug: "lakshmi-ganesh-puja",
    description: "A practical home blessing service for prosperity, festive worship, and new beginnings.",
    category: "Home & Family",
    price: "1,800",
    originalPrice: "3,800",
    priceRange: "Rs. 1,000 - Rs. 5,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg",
    badge: "-24%",
    tag: "Wealth",
    tagStyle: "bg-[#dc6d3d] text-white",
    rating: "4.8",
    reviews: "1.1K reviews",
    duration: "1 - 2 hours",
    location: "At your home",
    panditType: "Certified Pandits",
    ritualType: "Vedic",
    availability: "Within Week",
    isForYou: true,
  },
  {
    id: "baby-naming",
    title: "Baby Naming (Namkaran)",
    slug: "baby-naming",
    description: "Auspicious namkaran ritual based on Vedic traditions. A home-friendly naming ceremony with family blessings and practical ceremony guidance.",
    category: "Life Events",
    price: "2,500",
    originalPrice: "4,500",
    priceRange: "Rs. 1,000 - Rs. 5,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    badge: "Family Ritual",
    badgeType: "mint",
    rating: "4.8",
    reviews: "642 reviews",
    duration: "1 - 2 hours",
    location: "At your home",
    panditName: "Pandit Suresh Mishra",
    panditExperience: "18+ years experience",
    panditType: "Experienced Pandits",
    ritualType: "Custom",
    availability: "Immediate",
    isFeatured: true,
  },
  {
    id: "annaprashan",
    title: "Annaprashan Ceremony",
    slug: "annaprashan",
    description: "The first feeding ritual to mark an important milestone. First-food ceremony booking for families who want a clear ritual flow and practical support.",
    category: "Life Events",
    price: "2,000",
    originalPrice: "4,000",
    priceRange: "Rs. 1,000 - Rs. 5,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg",
    badge: "Child Milestone",
    badgeType: "orange",
    rating: "4.7",
    reviews: "512 reviews",
    duration: "1 - 2 hours",
    location: "At your home",
    panditName: "Pandit Vikram Joshi",
    panditExperience: "10+ years experience",
    panditType: "Certified Pandits",
    ritualType: "Puranic",
    availability: "Within Week",
    isFeatured: true,
  },
  {
    id: "office-opening",
    title: "Office Opening Puja",
    slug: "office-opening",
    description: "Bring prosperity and positive energy to your new business space. New office or shop blessing for Delhi NCR businesses that want an efficient ceremony setup.",
    category: "Business & Prosperity",
    price: "3,500",
    originalPrice: "7,500",
    priceRange: "Rs. 1,000 - Rs. 5,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    badge: "Business Special",
    badgeType: "blue",
    rating: "4.7",
    reviews: "856 reviews",
    duration: "2 - 3 hours",
    location: "At your office",
    panditName: "Pandit Amit Tiwari",
    panditExperience: "12+ years experience",
    panditType: "Senior Pandits",
    ritualType: "Tantric",
    availability: "Immediate",
    isFeatured: true,
  },
  {
    id: "vastu-shanti",
    title: "Vastu Shanti Puja",
    slug: "vastu-shanti",
    description: "Space-harmony ceremony for homes and offices with a strong Delhi NCR move-in use case.",
    category: "Business & Prosperity",
    price: "3,000",
    originalPrice: "6,000",
    priceRange: "Rs. 1,000 - Rs. 5,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    badge: "-22%",
    rating: "4.6",
    reviews: "340 reviews",
    duration: "2 hours",
    location: "At your property",
    panditType: "Experienced Pandits",
    ritualType: "Vedic",
    availability: "Within 3 Days",
  },
  {
    id: "havan",
    title: "Havan and Yagya",
    slug: "havan",
    description: "Home havan booking for purification, peace, family milestones, and apartment-friendly ritual planning.",
    category: "Home & Family",
    price: "2,200",
    originalPrice: "4,800",
    priceRange: "Rs. 1,000 - Rs. 5,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_db05ba7c437e48abe6440798d2a33b09dr_bokdku.jpg",
    badge: "-20%",
    rating: "4.8",
    reviews: "560 reviews",
    duration: "1.5 hours",
    location: "At your home",
    panditType: "Experienced Pandits",
    ritualType: "Vedic",
    availability: "Within 3 Days",
  },
  {
    id: "navagraha-puja",
    title: "Navagraha Puja",
    slug: "navagraha-puja",
    description: "Planetary harmony puja with astrology-led guidance and ceremony planning support.",
    category: "Special Rituals",
    price: "4,500",
    originalPrice: "6,000",
    priceRange: "Rs. 1,000 - Rs. 5,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg",
    badge: "-25%",
    rating: "4.9",
    reviews: "210 reviews",
    duration: "3 - 4 hours",
    location: "At your home",
    panditType: "Certified Pandits",
    ritualType: "Vedic",
    availability: "Within Week",
  },
  {
    id: "mundan",
    title: "Mundan Sanskar",
    slug: "mundan",
    description: "First-haircut ceremony booking with practical support for family participation and venue planning.",
    category: "Life Events",
    price: "2,800",
    originalPrice: "5,200",
    priceRange: "Rs. 1,000 - Rs. 5,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    badge: "-18%",
    rating: "4.7",
    reviews: "325 reviews",
    duration: "1 - 2 hours",
    location: "At your home or temple",
    panditType: "Experienced Pandits",
    ritualType: "Custom",
    availability: "Within Week",
  },
  {
    id: "bhoomi-pujan",
    title: "Bhoomi Pujan",
    slug: "bhoomi-pujan",
    description: "Land and foundation blessing for plots, renovations, commercial spaces, and new construction.",
    category: "Business & Prosperity",
    price: "3,200",
    originalPrice: "6,200",
    priceRange: "Rs. 1,000 - Rs. 5,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg",
    badge: "-19%",
    rating: "4.8",
    reviews: "189 reviews",
    duration: "2 hours",
    location: "At your plot",
    panditType: "Senior Pandits",
    ritualType: "Vedic",
    availability: "Within 3 Days",
  },
  {
    id: "ganesh-puja",
    title: "Ganesh Puja",
    slug: "ganesh-puja",
    description: "Vignaharta Ganesh Puja for removing obstacles and new beginnings.",
    category: "Home & Family",
    price: "1,500",
    originalPrice: "3,000",
    priceRange: "Rs. 1,000 - Rs. 5,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg",
    badge: "-50%",
    tag: "Most Booked",
    tagStyle: "bg-[#10263f] text-white",
    rating: "4.9",
    reviews: "3.2K reviews",
    duration: "1 - 1.5 hours",
    location: "At your home",
    panditType: "Certified Pandits",
    ritualType: "Vedic",
    availability: "Immediate",
    isForYou: true,
  }
];

export const getFeaturedPujas = () => pujasData.filter(p => p.isFeatured);
export const getForYouPujas = () => pujasData.filter(p => p.isForYou);
export const getAllPujas = () => pujasData;
export const getPujaBySlug = (slug: string) => pujasData.find(p => p.slug === slug);

// BFF catalogue shape for mock mode. The mock branch is a real, usable
// catalogue (not an empty list): it maps the same records the marketing
// surfaces use, keyed by slug so `/pujas/[pujaId]` links resolve.
export interface MockCatalogItem {
  id: string;
  title: string;
  kind: 'puja';
  sku: string;
  currency: string;
  price_minor: number;
  description?: string;
}

function priceMinor(price: string): number {
  const match = price.replace(/,/g, '').match(/\d+(?:\.\d+)?/);
  return match ? Math.round(Number(match[0]) * 100) : 0;
}

export function toMockCatalogItem(puja: Puja): MockCatalogItem {
  return {
    id: puja.slug || puja.id,
    title: puja.title,
    kind: 'puja',
    sku: `puja-${puja.id}`,
    currency: 'INR',
    price_minor: priceMinor(puja.price),
    description: puja.description,
  };
}

export function getMockCatalog(): MockCatalogItem[] {
  return pujasData.map(toMockCatalogItem);
}

export function findMockCatalogItem(idOrSlug: string): MockCatalogItem | null {
  const puja = pujasData.find((item) => item.slug === idOrSlug || item.id === idOrSlug);
  return puja ? toMockCatalogItem(puja) : null;
}

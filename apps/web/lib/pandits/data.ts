// lib/pandits/data.ts
// Mock directory of pandits. Single source of truth; consumed by
// both `app/find-pandit` (which shows extended cards with `about` /
// `certifications`) and `app/pandits` (the simpler directory list).
//
// IMPORTANT: when the real Drizzle/Postgres backend lands (PR 11),
// this file becomes the seed data for local dev only.

import type { Pandit } from "./types";

export const MOCK_PANDITS: readonly Pandit[] = [
  {
    id: 1,
    name: "Pandit Rajesh Sharma",
    specialization: "Vedic Rituals & Astrology Specialist",
    location: "Delhi",
    experience: "15+ years",
    rating: 4.9,
    image:
      "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    skills: ["Griha Pravesh", "Kundli Analysis", "Satyanarayan Katha"],
    price: "₹1,500/session",
    languages: ["Hindi", "English"],
    about:
      "Specializes in Vedic rituals and astrological consultations with over 15 years of experience. Certified in Advanced Jyotish from Kashi Vidya Peeth.",
    certifications: [
      "Advanced Jyotish Certification",
      "Vedic Rituals Mastery",
    ],
  },
  {
    id: 2,
    name: "Pandit Anil Verma",
    specialization: "Business & Prosperity Rituals",
    location: "Gurgaon",
    experience: "18+ years",
    rating: 4.9,
    image:
      "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg",
    skills: ["Lakshmi Puja", "Vastu Shanti", "Navagraha Homa"],
    price: "₹2,000/session",
    languages: ["Hindi", "English", "Punjabi"],
    about:
      "Expert in business prosperity rituals and Vastu consultations. Has conducted rituals for Fortune 500 companies and successful entrepreneurs.",
    certifications: [
      "Business Astrology Certification",
      "Vastu Consultant Certification",
    ],
  },
  {
    id: 3,
    name: "Pandit Suresh Iyer",
    specialization: "South Indian Rituals Specialist",
    location: "Noida",
    experience: "12+ years",
    rating: 4.8,
    image:
      "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    skills: ["Pongal", "Ganesh Puja", "Annaprashan"],
    price: "₹1,800/session",
    languages: ["Tamil", "Telugu", "English"],
    about:
      "Specialist in South Indian traditions and rituals. Conducts authentic ceremonies following traditional methods passed down through generations.",
    certifications: ["South Indian Rituals Certification", "Sanskrit Scholar"],
  },
  {
    id: 4,
    name: "Pandit Ramesh Patel",
    specialization: "Wedding & Life Event Ceremonies",
    location: "Delhi",
    experience: "20+ years",
    rating: 4.7,
    image:
      "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    skills: ["Wedding Rituals", "Upanayana", "Naming Ceremony"],
    price: "₹2,500/session",
    languages: ["Hindi", "Gujarati", "English"],
    about:
      "Master of wedding and life event ceremonies with over 20 years of experience. Known for conducting elaborate and meaningful rituals.",
    certifications: ["Wedding Ceremony Specialist", "Upanayana Ritual Master"],
  },
  {
    id: 5,
    name: "Pandit Kavita Menon",
    specialization: "Women's Wellness & Fertility Rituals",
    location: "Gurgaon",
    experience: "10+ years",
    rating: 4.9,
    image:
      "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    skills: ["Ganesh Homa", "Lakshmi Puja", "Navratri Special"],
    price: "₹1,700/session",
    languages: ["Malayalam", "Tamil", "English"],
    about:
      "Specializes in women's wellness and fertility rituals. Combines traditional knowledge with a modern understanding of women's health.",
    certifications: [
      "Women's Wellness Specialist",
      "Fertility Rituals Certification",
    ],
  },
  {
    id: 6,
    name: "Pandit Vikram Singh",
    specialization: "Health & Healing Ceremonies",
    location: "Noida",
    experience: "14+ years",
    rating: 4.6,
    image:
      "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg",
    skills: ["Rudra Abhishek", "Maha Mrityunjaya Jaap", "Ayurvedic Rituals"],
    price: "₹2,200/session",
    languages: ["Hindi", "English"],
    about:
      "Expert in healing ceremonies and Ayurvedic rituals. Focuses on holistic well-being through spiritual practices and traditional healing methods.",
    certifications: ["Healing Rituals Specialist", "Ayurvedic Practitioner"],
  },
] as const;

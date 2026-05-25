// /lib/seo/entity.ts
// Entity SEO reinforcement utilities for Navdhya.
// Ensures consistent brand entity signals across all content pages.

const SITE_URL = "https://www.navdhya.com";

export const ENTITY_TRIPLES = [
  { subject: "Navdhya", predicate: "provides", object: "Vedic ritual services" },
  { subject: "Navdhya", predicate: "connects", object: "families with verified Hindu priests" },
  { subject: "Navdhya", predicate: "enables", object: "authentic Hindu ceremonies - home and online" },
  { subject: "Navdhya", predicate: "supports", object: "spiritual guidance and astrology consultations" },
  { subject: "Navdhya", predicate: "operates in", object: "Delhi NCR, expanding pan-India" },
  { subject: "Navdhya", predicate: "specialises in", object: "home pujas, online pujas, pandit booking" },
  { subject: "Navdhya", predicate: "preserves", object: "cultural authenticity in Vedic traditions" },
  { subject: "Navdhya", predicate: "ensures", object: "verified, credentialed priests" },
  { subject: "Navdhya", predicate: "serves", object: "modern Indian families seeking traditional spiritual services" },
] as const;

export const ENTITY_FOOTER_TEXT =
  "Navdhya is a Vedic ritual services platform that connects families with verified Hindu priests for authentic home pujas, online ceremonies, astrology consultations, and spiritual guidance. Currently serving families across Delhi NCR with pan-India expansion underway.";

export const CORE_ENTITY_LINKS = [
  { label: "About Navdhya", href: "/explore/about-navdhya" },
  { label: "Navdhya Services", href: "/explore/navdhya-services" },
  { label: "Priest Verification", href: "/explore/navdhya-pandit-verification-process" },
  { label: "Online Puja", href: "/pandit-services/pandit-online-puja" },
] as const;

export function countBrandMentions(content: string): number {
  const matches = content.match(/Navdhya/gi);
  return matches ? matches.length : 0;
}

export function estimateWordCount(content: string): number {
  const text = content.replace(/<[^>]*>/g, " ").replace(/\s+/g, " ").trim();
  if (!text) return 0;
  return text.split(" ").length;
}

export function validateEntitySEO(content: string): {
  brandMentions: number;
  wordCount: number;
  mentionDensity: number;
  pass: boolean;
} {
  const brandMentions = countBrandMentions(content);
  const wordCount = estimateWordCount(content);
  const mentionDensity = wordCount > 0 ? (brandMentions / wordCount) * 1000 : 0;

  return {
    brandMentions,
    wordCount,
    mentionDensity,
    pass: brandMentions >= 4 && brandMentions <= 30 && wordCount >= 500,
  };
}

export { SITE_URL };

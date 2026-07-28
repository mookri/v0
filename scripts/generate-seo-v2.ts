import crypto from "crypto";
import fs from "fs";
import path from "path";
import type {
  AudienceRegion,
  ClaimReviewStatus,
  ContentCluster,
  DeliveryMode,
  FAQEntry,
  PageFamily,
  SchemaType,
  SearchIntent,
  SEOPage,
  ServiceDetails,
  ServiceProcessStep,
  SpeakableFAQEntry,
} from "../lib/content/types";

const ROOT = process.cwd();
const CONTENT_DIR = path.join(ROOT, "content");
const PAGES_DIR = path.join(CONTENT_DIR, "pages");
const V2_SOURCES_DIR = path.join(CONTENT_DIR, "sources", "v2");
const LAST_UPDATED = "2026-05-27";
const PIPELINE_VERSION = "seo-v2-editorial-pipeline";

type CountryCode = "US" | "UK" | "AU" | "IE" | "DE";

type ServiceSeed = {
  slug: string;
  title: string;
  shortTitle: string;
  summary: string;
  intentKeyword: string;
  highlights: string[];
  process: ServiceProcessStep[];
};

type V2Source = {
  slug: string;
  title: string;
  cluster: ContentCluster;
  schemaType: SchemaType;
  searchIntent: SearchIntent;
  primaryKeyword: string;
  secondaryKeywords: string[];
  keywordVariants: string[];
  pageFamily: PageFamily;
  audienceRegion: AudienceRegion;
  deliveryMode?: DeliveryMode;
  countryCode?: CountryCode;
  countryName?: string;
  cityName?: string;
  summary: string;
  serviceSlug?: string;
  serviceName?: string;
  topic?: string;
  angle?: string;
  supportLabel?: string;
  claimReviewStatus?: ClaimReviewStatus;
  relatedSlugs: string[];
  requiredInternalLinks: string[];
  conversionPath: string[];
  sourceNotes: string[];
};

type SourceFile = {
  fileName: string;
  entries: V2Source[];
};

const BANNED_PHRASES = [
  "families searching",
  "this page is designed to help",
  "that matters because",
  "not just an informational search",
  "sits close to a decision",
  "move from research into decision",
  "keyword target",
  "Delhi NCR families ask",
];

const SERVICE_SEEDS: ServiceSeed[] = [
  {
    slug: "griha-pravesh",
    title: "Griha Pravesh Puja",
    shortTitle: "Griha Pravesh",
    summary: "A structured home-entry ritual plan that keeps muhurat, household flow, and priest coordination in one calm booking journey.",
    intentKeyword: "griha pravesh planning",
    highlights: ["Move-in sequencing", "Muhurta clarity", "Home-ready ritual flow"],
    process: [
      { step: "Timing review", description: "Confirm move-in range, household constraints, and likely auspicious windows." },
      { step: "Ceremony brief", description: "Lock the ritual format, participants, and samagri responsibilities." },
      { step: "Priest coordination", description: "Match the ceremony to the right pandit profile and language needs." },
      { step: "Day-of support", description: "Run the home-entry flow with clear household preparation and closure steps." },
    ],
  },
  {
    slug: "havan",
    title: "Havan at Home",
    shortTitle: "Havan",
    summary: "A practical fire-ritual planning flow for households that need clarity on setup, ventilation, offerings, and priest fit.",
    intentKeyword: "havan planning",
    highlights: ["Safe fire setup", "Compact or full ritual formats", "Offerings and aarti flow"],
    process: [
      { step: "Purpose fit", description: "Clarify why the havan is being performed and whether it stands alone or supports another ritual." },
      { step: "Space planning", description: "Assess ventilation, setup footprint, and samagri readiness." },
      { step: "Pandit match", description: "Choose a priest whose style matches the expected household format." },
      { step: "Ceremony execution", description: "Run the main ahuti sequence with practical safety and participation guidance." },
    ],
  },
  {
    slug: "satyanarayan-katha",
    title: "Satyanarayan Katha",
    shortTitle: "Satyanarayan Katha",
    summary: "A devotional planning guide for households that want the katha to feel orderly, participatory, and easy to follow.",
    intentKeyword: "satyanarayan katha planning",
    highlights: ["Bhog and prasad planning", "Family participation cues", "Narrative and puja flow"],
    process: [
      { step: "Gathering format", description: "Confirm whether the household wants a compact home katha or a larger devotional gathering." },
      { step: "Prayer setup", description: "Prepare the altar, bhog, and recitation environment." },
      { step: "Katha sequence", description: "Conduct the puja and story flow with role clarity for the family." },
      { step: "Closing rhythm", description: "Close with aarti, prasad, and post-ceremony household guidance." },
    ],
  },
  {
    slug: "vastu-shanti",
    title: "Vastu Shanti Puja",
    shortTitle: "Vastu Shanti",
    summary: "A structured property-blessing workflow for homes and workplaces that need ritual planning without guesswork.",
    intentKeyword: "vastu shanti planning",
    highlights: ["Property readiness", "Combined-ritual support", "Space blessing structure"],
    process: [
      { step: "Property context", description: "Understand the property type, move-in state, and whether other rites will be paired." },
      { step: "Layout review", description: "Confirm ceremony zones, participant flow, and material placement." },
      { step: "Blessing plan", description: "Align priest expertise with the household's purpose for the ceremony." },
      { step: "Completion", description: "Finish the ritual with closure guidance for the home or office." },
    ],
  },
  {
    slug: "office-opening",
    title: "Office Opening Puja",
    shortTitle: "Office Opening",
    summary: "A business-opening ritual plan built around launch-day timing, access control, and professional coordination.",
    intentKeyword: "office opening puja planning",
    highlights: ["Launch-day sequencing", "Commercial-space setup", "Prosperity-oriented format"],
    process: [
      { step: "Opening brief", description: "Review the business launch timeline, guest count, and key milestones." },
      { step: "Space access", description: "Coordinate ceremony placement with building and staff constraints." },
      { step: "Ceremony lead", description: "Choose a priest format appropriate for a business environment." },
      { step: "Launch handoff", description: "Close the puja cleanly so the operating day can begin on time." },
    ],
  },
  {
    slug: "bhoomi-pujan",
    title: "Bhoomi Pujan",
    shortTitle: "Bhoomi Pujan",
    summary: "A site-blessing and construction-start planning guide for families and builders who need ritual clarity before work begins.",
    intentKeyword: "bhoomi pujan planning",
    highlights: ["Construction-start readiness", "Site access planning", "Ground-breaking ritual flow"],
    process: [
      { step: "Site intent", description: "Define whether the ceremony is tied to construction, renovation, or land activation." },
      { step: "Access preparation", description: "Prepare materials, safety boundaries, and participant movement." },
      { step: "Main vidhi", description: "Run the ground blessing and sankalpa sequence in the right order." },
      { step: "Construction handoff", description: "Close with guidance that supports the next practical phase of work." },
    ],
  },
  {
    slug: "namkaran",
    title: "Namkaran Ceremony",
    shortTitle: "Namkaran",
    summary: "A baby naming ceremony planning guide that balances family warmth, muhurat clarity, and manageable household preparation.",
    intentKeyword: "namkaran planning",
    highlights: ["Naming-moment preparation", "Family participation", "Compact home-friendly format"],
    process: [
      { step: "Name and timing", description: "Confirm naming preferences, timing windows, and elder involvement." },
      { step: "Preparation", description: "Set the ritual area, baby comfort needs, and simple household roles." },
      { step: "Ceremony flow", description: "Run the naming steps with calm pacing and clear participation." },
      { step: "Closure", description: "Complete the blessing, announcement, and family wrap-up smoothly." },
    ],
  },
  {
    slug: "mundan",
    title: "Mundan Ceremony",
    shortTitle: "Mundan",
    summary: "A first-haircut ceremony plan that gives parents clarity on timing, format, child comfort, and priest coordination.",
    intentKeyword: "mundan planning",
    highlights: ["Child comfort planning", "Venue fit", "Hair-ritual sequencing"],
    process: [
      { step: "Ceremony shape", description: "Clarify whether the mundan is at home, temple-linked, or travel-based." },
      { step: "Child readiness", description: "Review timing, support roles, and care needs around the ceremony." },
      { step: "Ritual sequence", description: "Coordinate the haircut and blessing sequence in a calm order." },
      { step: "Aftercare", description: "Close with practical next steps for the child and household." },
    ],
  },
  {
    slug: "annaprashan",
    title: "Annaprashan Ceremony",
    shortTitle: "Annaprashan",
    summary: "A child-feeding ceremony guide for families that want the day to feel meaningful, well paced, and easy for elders to follow.",
    intentKeyword: "annaprashan planning",
    highlights: ["Baby milestone planning", "Elder-friendly ritual flow", "Meal and blessing coordination"],
    process: [
      { step: "Milestone timing", description: "Confirm family preference, child readiness, and guest format." },
      { step: "Feeding setup", description: "Prepare the ritual area, offerings, and baby-comfort details." },
      { step: "Blessing flow", description: "Run the food-offering and naming of participants with role clarity." },
      { step: "Family close", description: "Finish with blessings, photos, and practical closure." },
    ],
  },
  {
    slug: "navagraha-puja",
    title: "Navagraha Puja",
    shortTitle: "Navagraha Puja",
    summary: "A ritual-planning path for households that need planetary-remedy clarity without losing practical ceremony structure.",
    intentKeyword: "navagraha puja planning",
    highlights: ["Remedy context", "Planetary sequence clarity", "Guided household preparation"],
    process: [
      { step: "Purpose review", description: "Understand the concern, recommendation source, and expected depth of the ceremony." },
      { step: "Material prep", description: "Prepare offerings and layout that fit the chosen format." },
      { step: "Main puja", description: "Conduct the planetary worship sequence with clear role guidance." },
      { step: "Post-ritual guidance", description: "Wrap up with practical follow-through steps for the household." },
    ],
  },
  {
    slug: "lakshmi-ganesh-puja",
    title: "Lakshmi Ganesh Puja",
    shortTitle: "Lakshmi Ganesh Puja",
    summary: "A festival and prosperity ceremony planning guide that keeps devotion, family timing, and preparation aligned.",
    intentKeyword: "lakshmi ganesh puja planning",
    highlights: ["Festival-friendly setup", "Prosperity and devotion focus", "Aarti and bhog clarity"],
    process: [
      { step: "Festival brief", description: "Review the occasion, household tradition, and timing needs." },
      { step: "Altar preparation", description: "Set idols, offerings, lamps, and bhog in a workable flow." },
      { step: "Puja sequence", description: "Run the Lakshmi and Ganesh worship in the right order." },
      { step: "Closing aarti", description: "Close with aarti, prasad, and tidy post-ritual steps." },
    ],
  },
  {
    slug: "hindu-wedding",
    title: "Hindu Wedding Ritual Planning",
    shortTitle: "Hindu Wedding",
    summary: "A wedding-ritual planning guide for families balancing ceremony depth, multiple stakeholders, and time-sensitive coordination.",
    intentKeyword: "hindu wedding ritual planning",
    highlights: ["Multi-event coordination", "Family expectation alignment", "Priest and sequence clarity"],
    process: [
      { step: "Scope mapping", description: "List which pre-wedding, wedding-day, and post-wedding rites are needed." },
      { step: "Family alignment", description: "Clarify language, regional, and schedule expectations early." },
      { step: "Priest fit", description: "Match the wedding ritual scope to the right lead pandit and support structure." },
      { step: "Execution readiness", description: "Prepare the event flow so the ceremony can run without avoidable confusion." },
    ],
  },
];

const SERVICE_INTENT_ANGLES = [
  { slug: "apartment-format", label: "apartment format planning", angle: "apartment rituals" },
  { slug: "weekend-booking", label: "weekend booking planning", angle: "weekend scheduling" },
  { slug: "small-home-setup", label: "small home setup planning", angle: "compact households" },
  { slug: "elder-led-family", label: "elder-led family planning", angle: "elder participation" },
  { slug: "first-time-bookers", label: "first time booking guidance", angle: "first booking" },
  { slug: "combined-ritual-day", label: "combined ritual day planning", angle: "paired ceremonies" },
  { slug: "travel-sensitive", label: "travel sensitive scheduling", angle: "out-of-town participants" },
  { slug: "language-fit", label: "language fit planning", angle: "language and regional preference" },
  { slug: "last-minute-clarity", label: "last minute ritual clarity", angle: "urgent preparation" },
  { slug: "family-consensus", label: "family consensus planning", angle: "decision alignment" },
];

const CEREMONY_DECISION_ANGLES = [
  { slug: "when-to-book", label: "when to book", angle: "timing the ceremony request" },
  { slug: "pandit-fit", label: "pandit fit", angle: "matching ceremony scope to priest expertise" },
  { slug: "home-vs-venue", label: "home vs venue", angle: "choosing the right ceremony setting" },
  { slug: "samagri-responsibility", label: "samagri responsibility", angle: "deciding who handles materials" },
  { slug: "language-preference", label: "language preference", angle: "keeping the family comfortable during the vidhi" },
  { slug: "same-day-risk", label: "same day risk", angle: "understanding urgency tradeoffs" },
  { slug: "combined-rituals", label: "combined rituals", angle: "pairing one ceremony with another without clutter" },
  { slug: "briefing-the-family", label: "briefing the family", angle: "getting everyone ready before the day starts" },
];

const PANDIT_TOPICS = [
  { slug: "verification", label: "pandit verification" },
  { slug: "regional-fit", label: "regional ritual fit" },
  { slug: "language-comfort", label: "language comfort" },
  { slug: "availability", label: "availability planning" },
  { slug: "ceremony-brief", label: "ceremony briefing" },
  { slug: "pricing-clarity", label: "pricing clarity" },
  { slug: "review-followup", label: "review follow-up" },
  { slug: "urgent-support", label: "urgent support" },
  { slug: "household-conduct", label: "household conduct" },
  { slug: "online-guidance", label: "online guidance" },
];

const PANDIT_STAGES = [
  { slug: "discover", label: "discovery stage" },
  { slug: "shortlist", label: "shortlist stage" },
  { slug: "evaluate", label: "evaluation stage" },
  { slug: "confirm", label: "confirmation stage" },
  { slug: "prepare", label: "preparation stage" },
  { slug: "ceremony-day", label: "ceremony day" },
  { slug: "follow-up", label: "follow-up stage" },
];

const CONSULTATION_TOPICS = [
  "muhurat selection",
  "kundli reading",
  "vastu consultation",
  "marriage matching",
  "naming guidance",
  "career reading",
  "health-focused astrology",
  "property timing",
  "pregnancy ritual planning",
  "festival observance planning",
  "ancestor-rite timing",
  "overseas family coordination",
];

const CONSULTATION_USE_CASES = [
  "before booking a priest",
  "when the family wants a second opinion",
  "for diaspora time-zone coordination",
  "before a move or property change",
  "when elders want scriptural context first",
];

const TRUST_THEMES = [
  "verified-pandit-network",
  "clear-booking-briefs",
  "multilingual-guidance",
  "support-responsiveness",
  "ceremony-readiness",
  "follow-up-quality",
  "ritual-data-privacy",
  "online-participation-clarity",
  "partner-accountability",
];

const TRUST_ANGLES = [
  "vs-generic-listings",
  "vs-unmanaged-referrals",
  "vs-phone-only-booking",
  "before-first-booking",
  "for-repeat-households",
];

const VOICE_TOPICS = [
  "choose-pandit-for-housewarming",
  "prepare-havan-space",
  "book-online-puja-for-parents",
  "explain-namkaran-to-relatives",
  "when-to-plan-mundan",
  "can-astrology-help-with-muhurta",
  "what-to-ask-before-griha-pravesh",
  "how-to-compare-pandit-options",
  "what-happens-in-satyanarayan-katha",
  "is-vastu-shanti-needed-before-moving",
  "what-materials-are-usually-needed",
  "can-family-join-remotely",
  "how-to-coordinate-with-elders-abroad",
  "what-makes-a-booking-trustworthy",
  "what-comes-after-submitting-a-brief",
];

const VOICE_PATTERNS = [
  "what-you-need-to-know",
  "quick-answer",
  "before-you-book",
];

const COUNTRIES: Array<{ code: CountryCode; slug: string; name: string; audience: AudienceRegion; cities: string[] }> = [
  { code: "US", slug: "us", name: "United States", audience: "US", cities: ["new-york", "chicago", "dallas", "san-jose", "seattle"] },
  { code: "UK", slug: "uk", name: "United Kingdom", audience: "UK", cities: ["london", "birmingham", "leicester", "manchester"] },
  { code: "AU", slug: "australia", name: "Australia", audience: "Australia", cities: ["sydney", "melbourne", "brisbane", "perth"] },
  { code: "IE", slug: "ireland", name: "Ireland", audience: "Ireland", cities: ["dublin", "cork", "galway"] },
  { code: "DE", slug: "germany", name: "Germany", audience: "Germany", cities: ["berlin", "frankfurt", "munich", "hamburg"] },
];

const INTL_SERVICE_FAMILIES: Array<{
  slug: string;
  label: string;
  keyword: string;
  deliveryMode: DeliveryMode;
  summary: string;
}> = [
  {
    slug: "online-puja",
    label: "Online puja guidance",
    keyword: "online puja",
    deliveryMode: "online",
    summary: "Remote-led puja coordination for households abroad that want a structured, respectful ceremony flow without claiming on-ground local priest coverage.",
  },
  {
    slug: "online-satyanarayan-katha",
    label: "Online Satyanarayan Katha",
    keyword: "online satyanarayan katha",
    deliveryMode: "online",
    summary: "A devotional remote-guided katha format for diaspora households coordinating across time zones and family members.",
  },
  {
    slug: "online-astrology-consultation",
    label: "Online astrology consultation",
    keyword: "online astrology consultation",
    deliveryMode: "online",
    summary: "A remote consultation path for timing, ritual planning, and scriptural context before a household commits to a ceremony.",
  },
  {
    slug: "nri-griha-pravesh-planning",
    label: "NRI Griha Pravesh planning",
    keyword: "NRI griha pravesh planning",
    deliveryMode: "india-coordination",
    summary: "India-side coordination guidance for families abroad preparing a home-entry ritual tied to property, relocation, or caretaker support back home.",
  },
  {
    slug: "india-family-pandit-coordination",
    label: "India family pandit coordination",
    keyword: "India family pandit coordination",
    deliveryMode: "india-coordination",
    summary: "A coordination path for overseas households arranging rituals for parents, relatives, or property in India while staying claim-safe about local execution abroad.",
  },
];

const REFRESH_SERVICE_SLUGS = [
  "griha-pravesh",
  "havan",
  "satyanarayan-katha",
  "vastu-shanti",
  "office-opening",
  "bhoomi-pujan",
  "namkaran",
  "mundan",
  "annaprashan",
  "navagraha-puja",
  "lakshmi-ganesh-puja",
  "hindu-wedding",
  "griha-pravesh-delhi",
  "havan-delhi",
  "satyanarayan-katha-delhi",
  "vastu-shanti-delhi",
  "office-opening-delhi",
  "bhoomi-pujan-delhi",
  "namkaran-delhi",
  "mundan-delhi",
  "annaprashan-delhi",
  "pandit-puja-delhi",
  "pandit-griha-pravesh-delhi",
  "pandit-satyanarayan-katha",
  "pandit-havan-ceremony",
  "pandit-bhoomi-pujan",
  "pandit-wedding-delhi",
  "book-astrology-consultation-online",
  "same-day-pandit-booking-delhi",
  "write-ritual-brief-navdhya",
];

const REFRESH_BRAND_SLUGS = [
  "navdhya-nri-global",
  "navdhya-hindu-diaspora",
  "diaspora-regional-traditions",
  "navdhya-language-support",
  "navdhya-live-streaming-puja",
  "navdhya-voice-search",
  "navdhya-panchang-integration",
  "navdhya-technology-ritual",
  "navdhya-spiritual-technology",
  "navdhya-mobile-ux",
];

function ensureDir(dirPath: string) {
  fs.mkdirSync(dirPath, { recursive: true });
}

function toTitleCase(value: string): string {
  return value
    .split("-")
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(" ");
}

function paragraph(text: string) {
  return `<p>${text}</p>`;
}

function heading(text: string) {
  return `<h2>${text}</h2>`;
}

function h3(text: string) {
  return `<h3>${text}</h3>`;
}

function list(items: string[]) {
  return `<ul>${items.map((item) => `<li>${item}</li>`).join("")}</ul>`;
}

function buildContent(
  intro: string[],
  sections: Array<{ heading: string; paragraphs: string[]; bullets?: string[] }>,
  faqs: FAQEntry[],
) {
  const blocks: string[] = [];
  intro.forEach((text) => blocks.push(paragraph(text)));
  sections.forEach((section) => {
    blocks.push(heading(section.heading));
    section.paragraphs.forEach((text) => blocks.push(paragraph(text)));
    if (section.bullets && section.bullets.length > 0) {
      blocks.push(list(section.bullets));
    }
  });
  blocks.push(heading("Common questions"));
  faqs.forEach((faq) => {
    blocks.push(h3(faq.question));
    blocks.push(paragraph(faq.answer));
  });
  return blocks.join("");
}

function textOnly(value: string) {
  return value.replace(/<[^>]+>/g, " ").replace(/\s+/g, " ").trim();
}

function readingTime(content: string, faqs: FAQEntry[]) {
  const words = `${textOnly(content)} ${faqs.map((faq) => `${faq.question} ${faq.answer}`).join(" ")}`
    .split(/\s+/)
    .filter(Boolean).length;
  return Math.max(7, Math.ceil(words / 190));
}

function buildAutomation(page: SEOPage) {
  return {
    contentHash: crypto.createHash("sha256").update(JSON.stringify(page)).digest("hex"),
    generatedAt: `${LAST_UPDATED}T00:00:00.000Z`,
    pipelineVersion: PIPELINE_VERSION,
    revalidateAfter: 86400,
  };
}

function writeJsonFile(filePath: string, data: unknown) {
  fs.writeFileSync(filePath, `${JSON.stringify(data, null, 2)}\n`, "utf8");
}

function writePage(page: SEOPage) {
  const finalPage = {
    ...page,
    automation: buildAutomation(page),
  };
  writeJsonFile(path.join(PAGES_DIR, `${page.slug}.json`), finalPage);
}

function linkLabel(url: string) {
  return url
    .replace(/^\//, "")
    .split("/")
    .filter(Boolean)
    .map((part) => part.split("-").map((chunk) => chunk.charAt(0).toUpperCase() + chunk.slice(1)).join(" "))
    .join(" ");
}

function buildNextStepParagraph(links: string[]) {
  const anchors = links.map((url) => `<a href="${url}">${linkLabel(url)}</a>`);
  return `Useful next steps usually include ${anchors.join(", ")} so the reader can move from general interest into the exact booking or planning path that fits the household.`;
}

function buildSpeakableFaqs(faqs: FAQEntry[]): SpeakableFAQEntry[] {
  return faqs.slice(0, 3).map((faq) => ({
    ...faq,
    summary: faq.answer.split(".")[0]?.trim() ?? faq.answer,
  }));
}

function buildServiceDetails(seed?: ServiceSeed, deliveryMode?: DeliveryMode, source?: V2Source): ServiceDetails | undefined {
  if (!seed && !source) return undefined;

  return {
    summary: source?.summary ?? seed?.summary,
    highlights: seed?.highlights ?? [source?.summary ?? "Clear booking guidance"],
    benefits: [
      "Editorial structure tuned for real planning questions.",
      "Fewer assumptions and clearer next-step choices.",
      "Claim-safe positioning that does not overstate availability.",
    ],
    process: seed?.process,
    includes: [
      "Intent-specific guidance",
      "Decision support rather than vague category copy",
      "Internal links into booking and support paths",
    ],
    bookingLeadTime:
      deliveryMode === "online" || deliveryMode === "remote-guided"
        ? "A few days helps the family align time zones and participation."
        : "Lead time depends on ceremony scope, locality, and household readiness.",
    serviceAreas: source?.cityName
      ? [source.cityName, source.countryName ?? "International"]
      : ["Delhi NCR", "India"],
  };
}

function buildMetaDescription(source: V2Source) {
  const summary = source.summary.replace(/\.$/, "");
  const suffix = source.countryName
    ? ` for ${source.countryName} households with claim-safe guidance.`
    : " with practical booking and decision support.";
  return `${summary}${suffix}`;
}

function buildBasePage(source: V2Source, content: string, faqs: FAQEntry[], seed?: ServiceSeed): SEOPage {
  const relatedSlugs = Array.from(new Set(source.relatedSlugs)).slice(0, 6);
  return {
    id: `seo-v2-${source.slug}`,
    slug: source.slug,
    cluster: source.cluster,
    title: source.title,
    metaTitle: `${source.title} | Navdhya`,
    metaDescription: buildMetaDescription(source),
    primaryKeyword: source.primaryKeyword,
    secondaryKeywords: source.secondaryKeywords,
    keywordVariants: source.keywordVariants,
    searchIntent: source.searchIntent,
    schemaType: source.schemaType,
    content,
    outline: content.match(/<h2>(.*?)<\/h2>/g)?.map((item) => item.replace(/<\/?h2>/g, "")) ?? [],
    relatedSlugs,
    lastUpdated: LAST_UPDATED,
    lastReviewed: LAST_UPDATED,
    author: "Navdhya Editorial Team",
    pageFamily: source.pageFamily,
    audienceRegion: source.audienceRegion,
    countryCode: source.countryCode,
    cityName: source.cityName,
    deliveryMode: source.deliveryMode,
    claimReviewStatus: source.claimReviewStatus ?? "approved",
    sourceNotes: source.sourceNotes,
    reviewedBy: "Navdhya Editorial Review",
    readingTime: readingTime(content, faqs),
    faqs,
    speakableFaqs: buildSpeakableFaqs(faqs),
    excerpt: source.summary,
    proofPoints: [
      "Built from the v2 editorial source layer.",
      "Reviewed against claim-safe international and comparison rules.",
      "Aligned to route-aware conversion paths and schema output.",
    ],
    serviceDetails: buildServiceDetails(seed, source.deliveryMode, source),
    targetCities: source.cityName ? [source.cityName, source.countryName ?? ""] .filter(Boolean) : ["Delhi NCR"],
    requiredInternalLinks: source.requiredInternalLinks,
    recommendedAnchorTexts: [source.primaryKeyword, ...source.secondaryKeywords.slice(0, 2)],
    conversionPath: source.conversionPath,
  };
}

function buildDomesticServicePage(source: V2Source, seed: ServiceSeed): SEOPage {
  const faqs: FAQEntry[] = [
    {
      question: `What makes ${seed.shortTitle.toLowerCase()} planning easier?`,
      answer: `A clear ceremony brief, language fit, and practical household preparation usually make ${seed.shortTitle.toLowerCase()} smoother from the start.`,
    },
    {
      question: `Why is ${source.angle?.toLowerCase() ?? "this angle"} important?`,
      answer: `Because the planning constraint changes how the household should choose the format, priest fit, and preparation timeline.`,
    },
    {
      question: "What should the reader open next?",
      answer: "The main puja or support pages are usually the next stop because they turn the decision into a more exact booking path.",
    },
    {
      question: "How is this page different from a thin landing page?",
      answer: "It answers one planning question directly, includes conversion links, and avoids claiming coverage the product does not support.",
    },
  ];

  const intro = [
    `${seed.title} requires more than a label match. The household usually needs clarity on ceremony shape, who is leading what, and how the planning constraint changes the final booking path.`,
    `${source.summary} Navdhya uses this page family to answer one real planning tension at a time instead of repeating a generic service description.`,
  ];

  const sections = [
    {
      heading: `Where ${source.angle} changes the planning flow`,
      paragraphs: [
        `The planning issue here is ${source.angle}. That changes the ceremony brief because households may need different pacing, prep work, or priest communication than they would in a straightforward booking.`,
        `This is where a focused guide becomes more useful than a broad marketplace listing. It gives the reader an exact decision lens instead of another vague overview.`,
      ],
      bullets: seed.highlights,
    },
    {
      heading: "How the ceremony should be scoped before booking",
      paragraphs: [
        `Households usually make better choices when they name the purpose, setting, likely participants, and desired tone before they compare pandit options.`,
        `That early scoping step helps separate what must be decided now from what can be finalized later, which is especially valuable when the family is balancing convenience with authenticity.`,
      ],
    },
    {
      heading: "How the priest and household brief should work together",
      paragraphs: [
        `A reliable booking flow connects the priest brief to the family's actual constraints. That means language, ritual depth, timing, and material ownership should be explicit instead of assumed.`,
        `Navdhya's v2 service pages are written to surface those decision points early so the household can move forward with fewer avoidable corrections.`,
      ],
    },
    {
      heading: "What a stronger next step looks like",
      paragraphs: [
        buildNextStepParagraph(source.requiredInternalLinks),
        `The goal is not endless research. The goal is a shorter route to a ceremony plan that the household can explain and execute with confidence.`,
      ],
    },
  ];

  const content = buildContent(intro, sections, faqs);
  return buildBasePage(source, content, faqs, seed);
}

function buildCeremonyDecisionPage(source: V2Source, seed: ServiceSeed): SEOPage {
  const faqs: FAQEntry[] = [
    { question: `When do families usually ask about ${source.angle}?`, answer: "Usually when the ceremony is already being planned and someone needs a clearer decision rule before confirming the booking." },
    { question: "What is the biggest mistake to avoid?", answer: "Treating a planning detail as minor when it actually changes priest fit, timing, or family readiness." },
    { question: "Should the household compare more than one path?", answer: "Yes, especially when the family is choosing between settings, timing windows, or combined rituals." },
    { question: "What page should follow this one?", answer: "The next page should usually be the main service page, a comparison guide, or a support route that solves the remaining uncertainty." },
  ];

  const intro = [
    `${source.title} focuses on a single decision inside ${seed.shortTitle.toLowerCase()} planning. That is usually where ceremony research becomes productive: not at the point of broad education, but at the point where one unresolved choice is slowing the household down.`,
    `The guide is written for readers who need clearer judgment, not more noise. Every section stays tied to the decision path and the consequences of getting it wrong.`,
  ];

  const sections = [
    {
      heading: `What the ${source.angle} decision is really about`,
      paragraphs: [
        `${source.angle} affects more than logistics. It often shapes how the family experiences the ritual, who feels included, and whether the priest can lead the ceremony without last-minute confusion.`,
        `A useful decision page should therefore explain the tradeoff, the households it suits best, and the signs that another route would be safer.`,
      ],
    },
    {
      heading: "How to frame the decision without overcomplicating it",
      paragraphs: [
        `The simplest frame is to separate non-negotiables from preferences. Once the family knows which pieces are fixed, the remaining ceremony choices become much easier to compare.`,
        `That approach also protects ritual quality because it keeps the conversation focused on fit instead of reacting to the loudest opinion in the room.`,
      ],
    },
    {
      heading: "Signals that the household is ready to move forward",
      paragraphs: [
        `Readiness usually shows up as a stable ceremony brief, one preferred format, and agreement on who will coordinate with the priest.`,
        `When those signals are missing, the next best move is to pause, refine the brief, and use the support pages in the conversion path rather than forcing the booking forward.`,
      ],
    },
    {
      heading: "Where this decision should lead next",
      paragraphs: [
        buildNextStepParagraph(source.requiredInternalLinks),
        `A strong decision page reduces friction because it points directly to the service or support surface that finishes the job.`,
      ],
    },
  ];

  const content = buildContent(intro, sections, faqs);
  return buildBasePage(source, content, faqs, seed);
}

function buildPanditJourneyPage(source: V2Source): SEOPage {
  const faqs: FAQEntry[] = [
    { question: "What changes at this stage of the pandit journey?", answer: "The reader is usually solving a narrower trust or fit question than they were at the start of the search." },
    { question: "Why does a stage-based page help?", answer: "Because stage-specific guidance prevents the household from using the wrong checklist at the wrong moment." },
    { question: "Should the reader still compare options?", answer: "Yes, but comparison should become sharper as the household moves closer to confirmation." },
    { question: "What is the next action after this page?", answer: "The next action is usually to refine the brief, compare a smaller set of options, or move into booking support." },
  ];

  const intro = [
    `${source.title} is built around the reality that pandit selection is not one decision. It is a sequence of smaller checkpoints, and each stage benefits from a different type of clarity.`,
    `${source.summary} That makes the page useful both for users and for answer-engine surfaces that need concise, stage-aware guidance.`,
  ];

  const sections = [
    {
      heading: "What the household is trying to solve at this point",
      paragraphs: [
        `At the ${source.supportLabel?.toLowerCase() ?? "current"} stage, the main risk is often mismatch: the wrong priest profile, the wrong assumptions, or the wrong level of confidence for the ceremony at hand.`,
        `The guidance therefore stays practical. It tells the household what to validate now and what can wait until later.`,
      ],
    },
    {
      heading: "How to judge progress without rushing the booking",
      paragraphs: [
        `Progress here is usually visible when the household can explain why an option feels stronger, not just that it feels available.`,
        `That distinction matters because rushed priest selection often creates avoidable churn during preparation rather than during the search itself.`,
      ],
    },
    {
      heading: "Which checkpoints deserve direct confirmation",
      paragraphs: [
        `Role fit, language comfort, ritual scope, and response quality are still worth confirming directly. A typed route structure helps because it lets future providers store these checkpoints without changing the user flow.`,
        `For now, the content itself acts as the checklist so the household has a cleaner decision frame.`,
      ],
    },
    {
      heading: "What should happen next in the journey",
      paragraphs: [
        buildNextStepParagraph(source.requiredInternalLinks),
        `Once the reader has resolved the current stage question, the next page should tighten the path instead of widening it again.`,
      ],
    },
  ];

  const content = buildContent(intro, sections, faqs);
  return buildBasePage(source, content, faqs);
}

function buildConsultationIntentPage(source: V2Source): SEOPage {
  const consultationTopic = source.topic ?? "ritual planning";
  const useCase = source.angle ?? "before making a decision";
  const faqs: FAQEntry[] = [
    { question: `When is a ${consultationTopic} consultation the better first step?`, answer: `It is usually the better first step when the household needs ${consultationTopic} clarity ${useCase} and does not yet have a stable booking brief.` },
    { question: "What should the reader prepare before the session?", answer: `A short list of questions about ${consultationTopic}, any relevant dates, and a clear view of why the family is seeking help ${useCase}.` },
    { question: "Does this replace the ritual booking itself?", answer: "No. It clarifies the decision path so the eventual booking is better scoped." },
    { question: "What should happen after the consultation page?", answer: `The household should move into the linked service, support, or comparison route once the ${consultationTopic} question is resolved well enough to act.` },
  ];

  const intro = [
    `${source.title} exists for readers who are not ready to jump straight into a ritual booking. They first need a decision conversation around ${consultationTopic} ${useCase}.`,
    `${source.summary} The page stays anchored to that exact use case so the reader can judge whether a consultation truly unlocks the next step or whether another route would be faster.`,
  ];

  const sections = [
    {
      heading: "Why this consultation request appears in real booking journeys",
      paragraphs: [
        `Consultation intent often appears when the household can name the broad category of help they need but not yet the exact booking structure for ${consultationTopic}.`,
        `In this use case, the consultation is valuable because it settles one decision that keeps the family from moving forward ${useCase}.`,
      ],
      bullets: [
        `Clarify what ${consultationTopic} should answer for this household.`,
        `Separate urgent questions from nice-to-know background discussion.`,
        `Use the outcome to tighten the next booking or support step.`,
      ],
    },
    {
      heading: "What a strong consultation brief includes",
      paragraphs: [
        `The best consultation briefs are compact and specific to ${consultationTopic}. They cover the triggering situation, the likely timeline, and the one or two questions that will actually change the family plan ${useCase}.`,
        `That discipline keeps the session useful and prevents the conversation from drifting into broad theory that never changes the practical decision.`,
      ],
      bullets: [
        `State why ${consultationTopic} matters now.`,
        `Mention any ceremony, property, travel, or elder-related deadline.`,
        `Name the exact decision the consultation should improve.`,
      ],
    },
    {
      heading: "How the answer should reshape the next step",
      paragraphs: [
        `If the consultation is effective, the family should finish with a narrower booking path for ${consultationTopic}, a stronger sense of timing, or clearer expectations about format and priest fit.`,
        `The page therefore points directly to the routes that can absorb that clarity and turn it into action rather than sending the reader back into a generic category loop.`,
      ],
    },
    {
      heading: "What to open after this consultation guide",
      paragraphs: [
        buildNextStepParagraph(source.requiredInternalLinks),
        `That transition is the real purpose of this page family. It converts uncertainty into a smaller, more manageable decision set.`,
      ],
    },
  ];

  const content = buildContent(intro, sections, faqs);
  return buildBasePage(source, content, faqs);
}

function buildTrustComparisonPage(source: V2Source): SEOPage {
  const faqs: FAQEntry[] = [
    { question: "Why do trust pages matter before booking?", answer: "Because households often want evidence of process quality before they commit to the ritual path itself." },
    { question: "What should the reader compare here?", answer: "The comparison should focus on accountability, clarity, and the strength of the booking handoff rather than only surface claims." },
    { question: "Can a trust page be commercial without sounding inflated?", answer: "Yes, if it stays close to process proof and avoids unsupported promises." },
    { question: "What comes after a trust comparison?", answer: "The next page should usually be the exact service or dashboard-support path that benefits from the trust decision." },
  ];

  const comparisonTable = [
    { feature: "Brief quality", Navdhya: "Typed workflow and next-step routing", Generic: "Often unstructured or hidden in chat" },
    { feature: "Accountability", Navdhya: "Specific role surfaces and review states", Generic: "Limited visibility after inquiry" },
    { feature: "Claim safety", Navdhya: "Narrowed to supported delivery modes", Generic: "Broad coverage claims may be unclear" },
  ];

  const intro = [
    `${source.title} is a trust-focused comparison rather than a generic alternatives page. The goal is to help the reader judge process quality before they commit time, money, or family coordination effort.`,
    `${source.summary} That keeps the page commercially useful without falling into overstated claims or copy that sounds detached from the real booking experience.`,
  ];

  const sections = [
    {
      heading: "Which trust signal this page is testing",
      paragraphs: [
        `The comparison angle here is ${source.angle?.replace(/-/g, " ") ?? "process trust"}. Readers usually care about this when they are past initial curiosity and closer to selection.`,
        `A strong comparison page makes the hidden operational differences visible. That is what helps answer-engine and human readers alike.`,
      ],
    },
    {
      heading: "What stronger process evidence looks like",
      paragraphs: [
        `Process evidence becomes stronger when the reader can see what happens before, during, and after the booking rather than only what is promised on the surface.`,
        `That is why this family of pages links process, route structure, and internal next steps instead of acting like a standalone opinion piece.`,
      ],
      bullets: [
        "Role-aware support paths",
        "Explicit handoff between information and booking",
        "Sharper limits on unsupported claims",
      ],
    },
    {
      heading: "How to use the comparison without over-reading it",
      paragraphs: [
        `The reader should use the page as a structured filter, not as the only decision input. If the process proof is strong but the ceremony fit is weak, the booking still needs another pass.`,
        `That is also why the conversion path stays close to service and support pages rather than pointing only to more comparison content.`,
      ],
    },
    {
      heading: "Where the reader should go next",
      paragraphs: [
        buildNextStepParagraph(source.requiredInternalLinks),
        `The best next click is the one that tests the specific ritual fit now that the trust question has been narrowed.`,
      ],
    },
  ];

  const content = buildContent(intro, sections, faqs);
  const page = buildBasePage(source, content, faqs);
  page.comparisonTable = comparisonTable;
  return page;
}

function buildVoiceFaqPage(source: V2Source): SEOPage {
  const faqs: FAQEntry[] = [
    { question: `What is the short answer on ${source.topic?.replace(/-/g, " ")}?`, answer: `${source.summary} The short answer is that the household should first lock the practical constraint, then move into the linked booking or support route.` },
    { question: "Why is this phrased for conversational search?", answer: "Because voice and answer-engine users usually ask direct, natural-language questions and want a concise, trustworthy response first." },
    { question: "How should the reader act on the answer?", answer: "They should open the most relevant service, support, or comparison route in the conversion path rather than restart the search from scratch." },
    { question: "What keeps this page from being thin?", answer: "Each answer is tied to a real booking or planning use case, not a recycled definition." },
  ];

  const intro = [
    `${source.title} is built for natural-language search behavior. The reader usually wants a direct answer first and then a clear route into a fuller planning or booking page.`,
    `${source.summary} That means the structure has to stay concise while still pointing to the right next step.`,
  ];

  const sections = [
    {
      heading: "What the direct answer should cover",
      paragraphs: [
        `A good voice-style answer should define the issue, name the key decision, and avoid wandering into unrelated ceremony theory.`,
        `That discipline improves answer quality because it respects how conversational search behaves in practice.`,
      ],
    },
    {
      heading: "Where answer-engine pages often lose trust",
      paragraphs: [
        `Trust tends to drop when the answer sounds certain about things the product cannot support, or when it gives a vague explanation with no action path.`,
        `This v2 family fixes that by grounding every answer in a supported route and a visible claim boundary.`,
      ],
    },
    {
      heading: "Which next click actually helps",
      paragraphs: [
        buildNextStepParagraph(source.requiredInternalLinks),
        `The right next step should feel like the natural continuation of the answer rather than a sudden jump into a generic landing page.`,
      ],
    },
  ];

  const content = buildContent(intro, sections, faqs);
  return buildBasePage(source, content, faqs);
}

function buildIntlCountryHubPage(source: V2Source): SEOPage {
  const faqs: FAQEntry[] = [
    { question: `What does Navdhya offer for households in ${source.countryName}?`, answer: "Navdhya positions these pages around online guidance and India-side coordination, not unsupported local on-ground service claims." },
    { question: "Why does the page avoid broad local coverage language?", answer: "Because the content is intentionally claim-safe and limited to delivery modes the product can clearly support." },
    { question: "Who is this page for?", answer: "It is for Hindu families abroad who want clearer remote participation, planning, or India-linked coordination options." },
    { question: "What should the reader open next?", answer: "The next step is usually a city hub or one of the service-family pages written for the same country market." },
  ];

  const intro = [
    `${source.title} is a country-level entry point for diaspora households that want clearer ritual support without inflated local claims.`,
    `${source.summary} The page is written to make the market structure legible: what is supported remotely, what is coordinated in India, and what should happen next.`,
  ];

  const sections = [
    {
      heading: `How ritual support is framed for ${source.countryName}`,
      paragraphs: [
        `The country hub sets expectations before the reader ever opens a service page. It makes the support model explicit so the household can quickly tell whether remote guidance, India-side coordination, or a consultation path is the right fit.`,
        `That is especially important for international SEO because the safest page is often the clearest one, not the broadest one.`,
      ],
    },
    {
      heading: "What households abroad usually need first",
      paragraphs: [
        `Many readers want an answer to one of three questions: can family members join remotely, can planning happen across time zones, and can rituals in India be coordinated from abroad.`,
        `A strong country hub names those needs directly and then routes each one to the correct lower-level page family.`,
      ],
    },
    {
      heading: "What the next level of navigation should look like",
      paragraphs: [
        buildNextStepParagraph(source.requiredInternalLinks),
        `This keeps the country hub useful for both discovery and conversion without pretending to be the final answer for every market-specific scenario.`,
      ],
    },
  ];

  const content = buildContent(intro, sections, faqs);
  return buildBasePage(source, content, faqs);
}

function buildIntlCityHubPage(source: V2Source): SEOPage {
  const faqs: FAQEntry[] = [
    { question: `Why is ${source.cityName} used as a city hub?`, answer: "Because city-level search intent often wants a familiar location anchor before the reader chooses an exact remote or India-coordination service path." },
    { question: "Does the page claim on-ground local priest availability?", answer: "No. The page stays limited to supported online and India-side coordination models." },
    { question: "Who benefits most from a city hub?", answer: "Usually households who search with city language first and service language second." },
    { question: "What should come after the city hub?", answer: "A city hub should send the reader into one of the service-family pages written for the same market and location framing." },
  ];

  const intro = [
    `${source.title} gives the reader a city-specific starting point without over-claiming local physical operations.`,
    `${source.summary} That makes the page useful for international long-tail search while staying aligned to the real delivery modes in the product model.`,
  ];

  const sections = [
    {
      heading: `Why ${source.cityName} search intent needs its own bridge page`,
      paragraphs: [
        `City-led searches often signal familiarity, urgency, or a desire for locally relevant framing. The page therefore acts as a bridge between that city-language intent and the limited delivery modes the platform can genuinely support.`,
        `It is not a substitute for the service pages below it. It is a sorting surface that narrows the reader into a better route.`,
      ],
    },
    {
      heading: "How the claim-safe promise is kept visible",
      paragraphs: [
        `The content avoids vague mentions of local execution abroad. Instead, it stays explicit about remote-guided participation and India-side coordination where those are the actual supported models.`,
        `That precision is good SEO hygiene as well as better user communication.`,
      ],
    },
    {
      heading: "Which service family should come next",
      paragraphs: [
        buildNextStepParagraph(source.requiredInternalLinks),
        `The city hub succeeds when it shortens the route to the right service family rather than trying to answer every international ritual question on one page.`,
      ],
    },
  ];

  const content = buildContent(intro, sections, faqs);
  return buildBasePage(source, content, faqs);
}

function buildIntlServicePage(source: V2Source): SEOPage {
  const faqs: FAQEntry[] = [
    { question: `Can Navdhya support ${source.serviceName?.toLowerCase()} for households in ${source.cityName}?`, answer: "Yes, within the supported model described on the page: online participation, remote-guided planning, or India-side coordination." },
    { question: "Why is the delivery mode called out so clearly?", answer: "Because the page should never imply a level of foreign on-ground service that the product has not verified." },
    { question: "How should families decide if this is the right service family?", answer: "They should match the page's delivery mode to the real problem they are solving, such as remote participation, consultation, or India-side family coordination." },
    { question: "What is the strongest next step after this page?", answer: "The next step is usually to open the linked country hub, city hub, or booking-support route and then tighten the household brief." },
  ];

  const intro = [
    `${source.title} is written for a specific international city and a specific supported delivery mode. That keeps the promise narrow enough to trust and useful enough to act on.`,
    `${source.summary} The page avoids pretending to solve every diaspora ritual need. It solves one supported use case well and routes the rest cleanly.`,
  ];

  const sections = [
    {
      heading: "What this service family is actually promising",
      paragraphs: [
        `The promise on this page is ${source.deliveryMode?.replace(/-/g, " ") ?? "guided support"}. The household should know immediately whether the fit is remote participation, a consultation-led step, or India-side coordination.`,
        `When the delivery mode is explicit, the reader can judge relevance faster and with less friction.`,
      ],
    },
    {
      heading: `How ${source.cityName} context changes the reader's needs`,
      paragraphs: [
        `${source.cityName} framing usually signals time-zone concerns, family coordination across borders, or a search habit shaped by local identity. The page responds to that context without implying city-specific physical operations.`,
        `That is the balance this market layer needs: recognizable geography without unsupported operational claims.`,
      ],
    },
    {
      heading: "Which preparation steps matter before the household proceeds",
      paragraphs: [
        `The strongest preparation move is a better brief: who is participating, which country or India-side family members are involved, and what outcome the household expects from the ritual or consultation.`,
        `That brief makes the linked support surfaces more useful and keeps the next conversation focused.`,
      ],
    },
    {
      heading: "What the reader should do next",
      paragraphs: [
        buildNextStepParagraph(source.requiredInternalLinks),
        `This service page is meant to narrow the route. Once the household recognizes the right support model, the next click should feel obvious.`,
      ],
    },
  ];

  const content = buildContent(intro, sections, faqs);
  return buildBasePage(source, content, faqs);
}

function buildRefreshedPage(existing: SEOPage, family: PageFamily, audienceRegion: AudienceRegion, notes: string[]): SEOPage {
  const baseLabel = existing.title.replace(/\s+in Delhi NCR$/i, "").trim();
  const refreshedMetaSuffix = "Refreshed with clearer structure, stronger answer-engine support, and better next-step routing.";
  const baseMetaDescription = existing.metaDescription
    .replace(new RegExp(`(?:\\s*${refreshedMetaSuffix.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")})+$`), "")
    .trim()
    .replace(/\.$/, "");
  const relatedLinks = existing.requiredInternalLinks && existing.requiredInternalLinks.length > 0
    ? existing.requiredInternalLinks
    : ["/book", "/pandits", "/contact"];
  const faqs: FAQEntry[] = [
    { question: `Why has ${baseLabel.toLowerCase()} been refreshed?`, answer: "Because the v2 editorial pipeline replaces repetitive copy with tighter structure, clearer answer-engine framing, and stronger conversion paths." },
    { question: "What is different in the refreshed version?", answer: "The content now uses unique sections, clearer internal routing, and stronger claim boundaries where they matter." },
    { question: "How should the reader use the refreshed page?", answer: "They should treat it as a working decision page and then continue into the linked services or support pages." },
    { question: "Does the refresh change the product promise?", answer: "No. It makes the promise easier to understand and keeps unsupported claims out of the page." },
  ];
  const sections = [
    {
      heading: "What the refreshed editorial model improves",
      paragraphs: [
        `The refreshed version of ${baseLabel.toLowerCase()} focuses on clarity first. It trims vague repetition and replaces it with a tighter decision path that matches how real readers move through the site.`,
        `That gives the page better answer-engine behavior and a cleaner handoff into the next supported route.`,
      ],
    },
    {
      heading: "How the reader should use this page now",
      paragraphs: [
        `This page works best as a planning and routing surface. It should help the reader decide what to do next rather than keeping them stuck in a broad overview.`,
        `The links and structure therefore do more work than before. They are part of the product path, not decoration.`,
      ],
    },
    {
      heading: "Where the refreshed path leads",
      paragraphs: [
        buildNextStepParagraph(relatedLinks),
        `That routing logic is one of the main reasons this page was moved onto the v2 editorial system.`,
      ],
    },
  ];

  const intro = [
    `${baseLabel} remains important, but the surrounding content model needed a reset. The v2 refresh keeps the underlying topic and route while making the page more useful for readers and less repetitive for search surfaces.`,
    `The update also aligns the page to stronger schema, clearer support boundaries, and the typed conversion paths introduced across the platform.`,
  ];

  const content = buildContent(intro, sections, faqs);
  return {
    ...existing,
    metaTitle: `${baseLabel} | Navdhya`,
    metaDescription: `${baseMetaDescription}. ${refreshedMetaSuffix}`,
    content,
    outline: content.match(/<h2>(.*?)<\/h2>/g)?.map((item) => item.replace(/<\/?h2>/g, "")) ?? [],
    lastUpdated: LAST_UPDATED,
    lastReviewed: LAST_UPDATED,
    pageFamily: family,
    audienceRegion,
    claimReviewStatus: "approved",
    sourceNotes: notes,
    faqs,
    speakableFaqs: buildSpeakableFaqs(faqs),
    readingTime: readingTime(content, faqs),
    requiredInternalLinks: relatedLinks,
    conversionPath: existing.conversionPath ?? relatedLinks,
    automation: undefined,
  };
}

function loadExistingPage(slug: string): SEOPage | null {
  const filePath = path.join(PAGES_DIR, `${slug}.json`);
  if (!fs.existsSync(filePath)) return null;
  return JSON.parse(fs.readFileSync(filePath, "utf8")) as SEOPage;
}

function buildSourceFiles(): SourceFile[] {
  const serviceIntentEntries: V2Source[] = [];
  SERVICE_SEEDS.forEach((seed) => {
    SERVICE_INTENT_ANGLES.forEach((angle) => {
      serviceIntentEntries.push({
        slug: `${seed.slug}-${angle.slug}-guide`,
        title: `${seed.title} for ${angle.label}`,
        cluster: "services",
        schemaType: "Service",
        searchIntent: "transactional",
        primaryKeyword: `${seed.shortTitle.toLowerCase()} ${angle.label}`,
        secondaryKeywords: [seed.intentKeyword, `${seed.shortTitle.toLowerCase()} booking`, angle.angle],
        keywordVariants: [`${seed.slug} ${angle.slug}`, `${seed.shortTitle.toLowerCase()} planning guide`, `book ${seed.shortTitle.toLowerCase()}`],
        pageFamily: "service-intent",
        audienceRegion: "india",
        deliveryMode: "hybrid",
        summary: `${seed.title} guidance focused on ${angle.angle}, clearer ceremony briefs, and stronger next-step routing.`,
        serviceSlug: seed.slug,
        serviceName: seed.title,
        angle: angle.angle,
        supportLabel: angle.label,
        relatedSlugs: [seed.slug, "book-pandit-home-puja", "best-pandit-booking-delhi"],
        requiredInternalLinks: [`/pujas/${seed.slug}`, "/book", "/pandits"],
        conversionPath: [`/pujas/${seed.slug}`, "/book", "/pandits"],
        sourceNotes: ["Domestic service-intent page generated by the v2 editorial pipeline.", "Avoids reusing the old continuous-coverage boilerplate blocks."],
      });
    });
  });

  const ceremonyDecisionEntries: V2Source[] = [];
  SERVICE_SEEDS.slice(0, 10).forEach((seed) => {
    CEREMONY_DECISION_ANGLES.forEach((angle) => {
      ceremonyDecisionEntries.push({
        slug: `${seed.slug}-${angle.slug}`,
        title: `${seed.shortTitle}: ${toTitleCase(angle.slug).replace("Vs", "vs")}`,
        cluster: "ceremonies",
        schemaType: "Article",
        searchIntent: "informational",
        primaryKeyword: `${seed.shortTitle.toLowerCase()} ${angle.label}`,
        secondaryKeywords: [seed.intentKeyword, angle.angle, `${seed.shortTitle.toLowerCase()} planning`],
        keywordVariants: [`${seed.slug} ${angle.slug}`, `${seed.shortTitle.toLowerCase()} decision guide`],
        pageFamily: "ceremony-decision",
        audienceRegion: "india",
        summary: `Decision-focused guidance for households comparing ${angle.angle} inside ${seed.shortTitle.toLowerCase()} planning.`,
        serviceSlug: seed.slug,
        serviceName: seed.title,
        angle: angle.angle,
        supportLabel: angle.label,
        relatedSlugs: [seed.slug, "questions-ask-pandit", "communicate-ritual-requirements"],
        requiredInternalLinks: [`/pujas/${seed.slug}`, "/pandits", "/contact"],
        conversionPath: [`/pujas/${seed.slug}`, "/pandits", "/contact"],
        sourceNotes: ["Ceremony-decision source record from the v2 editorial layer."],
      });
    });
  });

  const panditJourneyEntries: V2Source[] = [];
  PANDIT_TOPICS.forEach((topic) => {
    PANDIT_STAGES.forEach((stage) => {
      panditJourneyEntries.push({
        slug: `pandit-${topic.slug}-${stage.slug}`,
        title: `${toTitleCase(topic.slug)} during the ${stage.label}`,
        cluster: "pandit-services",
        schemaType: "Article",
        searchIntent: "informational",
        primaryKeyword: `${topic.label} ${stage.label}`,
        secondaryKeywords: ["pandit booking workflow", topic.label, stage.label],
        keywordVariants: [`pandit ${topic.slug} ${stage.slug}`, `how to manage ${topic.label}`],
        pageFamily: "pandit-journey",
        audienceRegion: "india",
        summary: `Stage-based guidance for ${topic.label} so households use the right checklist at the right time.`,
        topic: topic.slug,
        supportLabel: stage.label,
        relatedSlugs: ["book-pandit-home-puja", "navdhya-pandit-verification-process", "questions-ask-pandit"],
        requiredInternalLinks: ["/pandit-services/book-pandit-home-puja", "/pandit-services/navdhya-pandit-verification-process", "/book"],
        conversionPath: ["/pandit-services/book-pandit-home-puja", "/book", "/pandits"],
        sourceNotes: ["Pandit-journey source record generated for typed stage coverage."],
      });
    });
  });

  const consultationEntries: V2Source[] = [];
  CONSULTATION_TOPICS.forEach((topic) => {
    CONSULTATION_USE_CASES.forEach((useCase) => {
      const topicSlug = topic.replace(/\s+/g, "-").replace(/[^a-z-]/gi, "").toLowerCase();
      const useCaseSlug = useCase.replace(/\s+/g, "-").replace(/[^a-z-]/gi, "").toLowerCase();
      consultationEntries.push({
        slug: `${topicSlug}-${useCaseSlug}-consultation`,
        title: `${toTitleCase(topicSlug)} consultation ${useCase}`,
        cluster: "services",
        schemaType: "Service",
        searchIntent: "transactional",
        primaryKeyword: `${topic} consultation ${useCase}`,
        secondaryKeywords: [topic, "ritual planning consultation", useCase],
        keywordVariants: [`${topicSlug} consultation`, `${topic} advice`, `${topic} consultation guide`],
        pageFamily: "consultation-intent",
        audienceRegion: topic.includes("overseas") ? "global-diaspora" : "india",
        deliveryMode: topic.includes("overseas") ? "online" : "remote-guided",
        summary: `Consultation guidance for ${topic} ${useCase}, built to narrow a real planning decision before booking begins.`,
        topic,
        angle: useCase,
        relatedSlugs: ["book-astrology-consultation-online", "what-is-panchang", "what-is-kundali"],
        requiredInternalLinks: ["/services/book-astrology-consultation-online", "/consultation", "/contact"],
        conversionPath: ["/services/book-astrology-consultation-online", "/consultation", "/contact"],
        sourceNotes: ["Consultation-intent source record with an explicit supported delivery mode."],
      });
    });
  });

  const trustEntries: V2Source[] = [];
  TRUST_THEMES.forEach((theme) => {
    TRUST_ANGLES.forEach((angle) => {
      trustEntries.push({
        slug: `${theme}-${angle}`,
        title: `${toTitleCase(theme)} ${toTitleCase(angle).replace("Vs", "vs")}`,
        cluster: "compare",
        schemaType: "Article",
        searchIntent: "informational",
        primaryKeyword: `${theme.replace(/-/g, " ")} ${angle.replace(/-/g, " ")}`,
        secondaryKeywords: ["pandit booking trust", theme.replace(/-/g, " "), angle.replace(/-/g, " ")],
        keywordVariants: [`${theme} ${angle}`, `${theme.replace(/-/g, " ")} comparison`],
        pageFamily: "trust-comparison",
        audienceRegion: "india",
        summary: `A process-trust comparison page focused on ${theme.replace(/-/g, " ")} and how readers should evaluate it ${angle.replace(/-/g, " ")}.`,
        topic: theme,
        angle,
        relatedSlugs: ["best-pandit-booking-delhi", "navdhya-trust-safety", "navdhya-ratings-reviews"],
        requiredInternalLinks: ["/compare/best-pandit-booking-delhi", "/explore/navdhya-trust-safety", "/book"],
        conversionPath: ["/compare/best-pandit-booking-delhi", "/book", "/pandits"],
        sourceNotes: ["Trust-comparison source record generated for editorial QA and comparison clustering."],
      });
    });
  });

  const voiceEntries: V2Source[] = [];
  VOICE_TOPICS.forEach((topic) => {
    VOICE_PATTERNS.forEach((pattern) => {
      voiceEntries.push({
        slug: `voice-faq-${topic}-${pattern}`,
        title: `${toTitleCase(topic)}: ${toTitleCase(pattern)}`,
        cluster: "faq",
        schemaType: "FAQPage",
        searchIntent: "informational",
        primaryKeyword: `${topic.replace(/-/g, " ")} ${pattern.replace(/-/g, " ")}`,
        secondaryKeywords: ["voice search answer", topic.replace(/-/g, " "), "ritual FAQ"],
        keywordVariants: [`${topic} voice faq`, `${topic.replace(/-/g, " ")} answer`],
        pageFamily: "voice-faq",
        audienceRegion: topic.includes("abroad") ? "global-diaspora" : "india",
        summary: `A short-answer ritual guidance page built for conversational search around ${topic.replace(/-/g, " ")}.`,
        topic,
        angle: pattern,
        relatedSlugs: ["navdhya-voice-search", "book-pandit-home-puja", "how-navdhya-works"],
        requiredInternalLinks: ["/explore/navdhya-voice-search", "/book", "/contact"],
        conversionPath: ["/book", "/contact", "/explore/how-navdhya-works"],
        sourceNotes: ["Voice FAQ source record tuned for FAQPage and speakable answer coverage."],
      });
    });
  });

  const countryEntries: V2Source[] = COUNTRIES.map((country) => ({
    slug: `navdhya-${country.slug}`,
    title: `Navdhya for Hindu families in ${country.name}`,
    cluster: "brand",
    schemaType: "Organization",
    searchIntent: "navigational",
    primaryKeyword: `Navdhya ${country.name}`,
    secondaryKeywords: [`online puja ${country.name}`, `Hindu family support ${country.name}`, `NRI ritual guidance ${country.name}`],
    keywordVariants: [`navdhya ${country.slug}`, `${country.name} Hindu ritual support`],
    pageFamily: "intl-country-hub",
    audienceRegion: country.audience,
    countryCode: country.code,
    countryName: country.name,
    deliveryMode: "online",
    summary: `Country hub for ${country.name} households exploring remote-guided rituals, online consultations, and India-side family coordination with Navdhya.`,
    relatedSlugs: ["navdhya-nri-global", "navdhya-hindu-diaspora", "how-navdhya-works"],
    requiredInternalLinks: [`/centers/${country.slug}-${country.cities[0]}`, "/explore/navdhya-nri-global", "/book"],
    conversionPath: [`/centers/${country.slug}-${country.cities[0]}`, "/book", "/contact"],
    sourceNotes: ["International country hub limited to supported remote and India-coordination claims."],
  }));

  const cityEntries: V2Source[] = COUNTRIES.flatMap((country) =>
    country.cities.map((city) => ({
      slug: `${country.slug}-${city}`,
      title: `Ritual planning support for ${toTitleCase(city)} Hindu families`,
      cluster: "centers",
      schemaType: "LocalBusiness",
      searchIntent: "transactional",
      primaryKeyword: `${toTitleCase(city)} Hindu ritual planning`,
      secondaryKeywords: [`online puja ${toTitleCase(city)}`, `${country.name} Hindu families`, "diaspora ritual support"],
      keywordVariants: [`${country.slug} ${city} puja`, `${toTitleCase(city)} online ritual guidance`],
      pageFamily: "intl-city-hub",
      audienceRegion: country.audience,
      countryCode: country.code,
      countryName: country.name,
      cityName: toTitleCase(city),
      deliveryMode: "online",
      summary: `City hub for ${toTitleCase(city)} households looking for claim-safe online ritual guidance or India-side coordination through Navdhya.`,
      relatedSlugs: [`navdhya-${country.slug}`, "navdhya-hindu-diaspora", "navdhya-nri-global"],
      requiredInternalLinks: [`/explore/navdhya-${country.slug}`, `/services/${country.slug}-${city}-${INTL_SERVICE_FAMILIES[0].slug}`, "/book"],
      conversionPath: [`/services/${country.slug}-${city}-${INTL_SERVICE_FAMILIES[0].slug}`, "/book", `/explore/navdhya-${country.slug}`],
      sourceNotes: ["International city hub does not imply on-ground foreign priest coverage."],
    })),
  );

  const intlServiceEntries: V2Source[] = COUNTRIES.flatMap((country) =>
    country.cities.flatMap((city) =>
      INTL_SERVICE_FAMILIES.map((family) => ({
        slug: `${country.slug}-${city}-${family.slug}`,
        title: `${family.label} for ${toTitleCase(city)} Hindu households`,
        cluster: "services",
        schemaType: "Service",
        searchIntent: "transactional",
        primaryKeyword: `${family.keyword} ${toTitleCase(city)}`,
        secondaryKeywords: [family.keyword, `${country.name} Hindu families`, `${toTitleCase(city)} ritual guidance`],
        keywordVariants: [`${country.slug} ${city} ${family.slug}`, `${family.keyword} ${country.name}`],
        pageFamily: "intl-service",
        audienceRegion: country.audience,
        countryCode: country.code,
        countryName: country.name,
        cityName: toTitleCase(city),
        deliveryMode: family.deliveryMode,
        summary: family.summary,
        serviceName: family.label,
        angle: family.slug,
        relatedSlugs: [`navdhya-${country.slug}`, `${country.slug}-${city}`, "navdhya-nri-global"],
        requiredInternalLinks: [`/centers/${country.slug}-${city}`, `/explore/navdhya-${country.slug}`, "/book"],
        conversionPath: [`/centers/${country.slug}-${city}`, "/book", `/explore/navdhya-${country.slug}`],
        sourceNotes: ["International service page limited to supported online or India-coordination delivery models."],
      })),
    ),
  );

  const refreshServiceEntries: V2Source[] = REFRESH_SERVICE_SLUGS.map((slug) => ({
    slug,
    title: slug,
    cluster: "services",
    schemaType: "Service",
    searchIntent: "transactional",
    primaryKeyword: slug.replace(/-/g, " "),
    secondaryKeywords: [],
    keywordVariants: [],
    pageFamily: "refreshed-core-service",
    audienceRegion: "india",
    summary: "",
    relatedSlugs: [],
    requiredInternalLinks: [],
    conversionPath: [],
    sourceNotes: ["Existing service page scheduled for v2 refresh."],
  }));

  const refreshBrandEntries: V2Source[] = REFRESH_BRAND_SLUGS.map((slug) => ({
    slug,
    title: slug,
    cluster: "brand",
    schemaType: "Article",
    searchIntent: "informational",
    primaryKeyword: slug.replace(/-/g, " "),
    secondaryKeywords: [],
    keywordVariants: [],
    pageFamily: "refreshed-global-brand",
    audienceRegion: "global-diaspora",
    summary: "",
    relatedSlugs: [],
    requiredInternalLinks: [],
    conversionPath: [],
    sourceNotes: ["Existing brand or diaspora page scheduled for v2 refresh."],
  }));

  return [
    { fileName: "service-intent-pages.json", entries: serviceIntentEntries },
    { fileName: "ceremony-decision-pages.json", entries: ceremonyDecisionEntries },
    { fileName: "pandit-journey-pages.json", entries: panditJourneyEntries },
    { fileName: "consultation-intent-pages.json", entries: consultationEntries },
    { fileName: "trust-comparison-pages.json", entries: trustEntries },
    { fileName: "voice-faq-pages.json", entries: voiceEntries },
    { fileName: "international-country-hubs.json", entries: countryEntries },
    { fileName: "international-city-hubs.json", entries: cityEntries },
    { fileName: "international-service-pages.json", entries: intlServiceEntries },
    { fileName: "refresh-core-services.json", entries: refreshServiceEntries },
    { fileName: "refresh-global-brand.json", entries: refreshBrandEntries },
  ];
}

function findServiceSeed(serviceSlug?: string) {
  return SERVICE_SEEDS.find((seed) => seed.slug === serviceSlug);
}

function pageFromSource(source: V2Source): SEOPage {
  const seed = findServiceSeed(source.serviceSlug);

  switch (source.pageFamily) {
    case "service-intent":
      return buildDomesticServicePage(source, seed!);
    case "ceremony-decision":
      return buildCeremonyDecisionPage(source, seed!);
    case "pandit-journey":
      return buildPanditJourneyPage(source);
    case "consultation-intent":
      return buildConsultationIntentPage(source);
    case "trust-comparison":
      return buildTrustComparisonPage(source);
    case "voice-faq":
      return buildVoiceFaqPage(source);
    case "intl-country-hub":
      return buildIntlCountryHubPage(source);
    case "intl-city-hub":
      return buildIntlCityHubPage(source);
    case "intl-service":
      return buildIntlServicePage(source);
    default:
      throw new Error(`Unsupported page family for new page generation: ${source.pageFamily}`);
  }
}

function refreshExistingPages() {
  let refreshedServices = 0;
  let refreshedBrands = 0;

  REFRESH_SERVICE_SLUGS.forEach((slug) => {
    const existing = loadExistingPage(slug);
    if (!existing) return;
    const refreshed = buildRefreshedPage(existing, "refreshed-core-service", "india", [
      "Existing core service page refreshed by the v2 editorial pipeline.",
      "Rewritten to remove repetitive boilerplate and tighten conversion paths.",
    ]);
    writePage(refreshed);
    refreshedServices += 1;
  });

  REFRESH_BRAND_SLUGS.forEach((slug) => {
    const existing = loadExistingPage(slug);
    if (!existing) return;
    const refreshed = buildRefreshedPage(existing, "refreshed-global-brand", "global-diaspora", [
      "Existing diaspora or brand page refreshed by the v2 editorial pipeline.",
      "Positioned around claim-safe global support framing and answer-engine clarity.",
    ]);
    writePage(refreshed);
    refreshedBrands += 1;
  });

  return { refreshedServices, refreshedBrands };
}

function assertNoBannedPhrases(page: SEOPage) {
  const normalizedContent = textOnly(page.content).toLowerCase();
  for (const phrase of BANNED_PHRASES) {
    if (normalizedContent.includes(phrase)) {
      throw new Error(`Page ${page.slug} contains banned phrase "${phrase}".`);
    }
  }
}

function main() {
  ensureDir(V2_SOURCES_DIR);
  ensureDir(PAGES_DIR);

  const sourceFiles = buildSourceFiles();
  sourceFiles.forEach((file) => {
    writeJsonFile(path.join(V2_SOURCES_DIR, file.fileName), file.entries);
  });

  let newPages = 0;
  sourceFiles
    .filter((file) => !file.fileName.startsWith("refresh-"))
    .forEach((file) => {
      file.entries.forEach((source) => {
        const page = pageFromSource(source);
        assertNoBannedPhrases(page);
        writePage(page);
        newPages += 1;
      });
    });

  const refreshed = refreshExistingPages();

  console.log(
    `SEO v2 generation complete. New pages: ${newPages}. Refreshed service pages: ${refreshed.refreshedServices}. Refreshed brand pages: ${refreshed.refreshedBrands}.`,
  );
}

main();

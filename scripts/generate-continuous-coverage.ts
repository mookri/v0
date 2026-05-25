import fs from "fs";
import path from "path";
import crypto from "crypto";
import { getPage } from "../lib/content/loader";
import type { FAQEntry, SEOPage, ServiceProcessStep } from "../lib/content/types";

const ROOT = process.cwd();
const PAGES_DIR = path.join(ROOT, "content", "pages");
const SOURCES_DIR = path.join(ROOT, "content", "sources");
const LAST_UPDATED = "2026-05-25";

type SourceRow = Record<string, string>;

type ServiceSeed = {
  slug: string;
  title: string;
  priceRange: string;
  image: string;
  summary: string;
  process: ServiceProcessStep[];
};

const serviceCatalog: Record<string, ServiceSeed> = {
  "griha-pravesh": {
    slug: "griha-pravesh",
    title: "Griha Pravesh Puja",
    priceRange: "Rs. 2,500 - Rs. 5,500",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    summary: "A home-entry ceremony designed for families who want a calm, practical, and well-planned new beginning in Delhi NCR.",
    process: [
      { step: "Date planning", description: "Confirm the likely move-in date and any muhurat preference." },
      { step: "Preparation", description: "Review samagri, family participation, and access logistics." },
      { step: "Main puja", description: "Perform the home-entry ritual and room blessing sequence." },
      { step: "Closure", description: "End with aarti, prasad, and practical post-ceremony guidance." },
    ],
  },
  havan: {
    slug: "havan",
    title: "Havan at Home",
    priceRange: "Rs. 2,200 - Rs. 4,800",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_db05ba7c437e48abe6440798d2a33b09dr_bokdku.jpg",
    summary: "A home-focused havan service for peace, purification, and milestone-led family prayer.",
    process: [
      { step: "Purpose review", description: "Confirm the family intention for the havan." },
      { step: "Setup", description: "Prepare the havan space, offerings, and ventilation plan." },
      { step: "Sacred fire sequence", description: "Perform the main ahuti and mantra flow." },
      { step: "Closure", description: "Finish with aarti and a safe, practical wrap-up." },
    ],
  },
  "satyanarayan-katha": {
    slug: "satyanarayan-katha",
    title: "Satyanarayan Katha",
    priceRange: "Rs. 2,000 - Rs. 4,500",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    summary: "A family devotional ceremony for gratitude, peace, and milestone-led prayer in Delhi NCR homes.",
    process: [
      { step: "Format planning", description: "Confirm the family gathering format and the devotional purpose." },
      { step: "Preparation", description: "Review bhog, prayer setup, and family participation." },
      { step: "Katha flow", description: "Perform the puja and Satyanarayan Katha sequence." },
      { step: "Aarti and prasad", description: "Close the gathering with prasad and aarti." },
    ],
  },
  "vastu-shanti": {
    slug: "vastu-shanti",
    title: "Vastu Shanti Puja",
    priceRange: "Rs. 3,000 - Rs. 6,000",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_db05ba7c437e48abe6440798d2a33b09dr_bokdku.jpg",
    summary: "A space-blessing ceremony for homes, offices, and property milestones where layout and energy concerns need practical ritual support.",
    process: [
      { step: "Property review", description: "Confirm the home or office context and the reason for the ceremony." },
      { step: "Setup planning", description: "Review the ritual space, timing, and any combined ceremony needs." },
      { step: "Main blessing", description: "Perform the Vastu Shanti sequence and household participation flow." },
      { step: "Closure", description: "Close with aarti and next-step guidance for the space." },
    ],
  },
  "office-opening": {
    slug: "office-opening",
    title: "Office Opening Puja",
    priceRange: "Rs. 3,500 - Rs. 7,500",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    summary: "A business-opening ceremony for offices, shops, and studios that need a launch-day blessing and clearer planning support.",
    process: [
      { step: "Launch planning", description: "Review the business type and the opening-day timeline." },
      { step: "Space preparation", description: "Plan the ritual area and access requirements." },
      { step: "Main office blessing", description: "Perform the prosperity-led puja sequence." },
      { step: "Closure", description: "Close with aarti and a practical handoff to the operational day." },
    ],
  },
};

function titleFromSlug(value: string) {
  return value
    .split("-")
    .map((segment) => segment.charAt(0).toUpperCase() + segment.slice(1))
    .join(" ");
}

function resolveCenterSlug(citySlug: string) {
  if (["noida", "gurgaon", "ghaziabad"].includes(citySlug)) return citySlug;
  return "delhi";
}

function getServiceSeed(serviceSlug: string): ServiceSeed {
  const existing = getPage(serviceSlug);
  if (existing?.serviceDetails?.priceRange) {
    return {
      slug: serviceSlug,
      title: existing.title.replace(/\s+in Delhi NCR$/i, "").trim() || titleFromSlug(serviceSlug),
      priceRange: existing.serviceDetails.priceRange,
      image: existing.heroImage ?? existing.serviceDetails.image ?? serviceCatalog[serviceSlug]?.image ?? "",
      summary:
        existing.serviceDetails.summary ??
        existing.excerpt ??
        serviceCatalog[serviceSlug]?.summary ??
        `${titleFromSlug(serviceSlug)} support for Delhi NCR households that want clearer ritual planning and booking guidance.`,
      process:
        existing.serviceDetails.process ??
        serviceCatalog[serviceSlug]?.process ??
        [
          { step: "Planning", description: "Review the ceremony intent, locality, and likely timeline." },
          { step: "Preparation", description: "Confirm materials, family participation, and access details." },
          { step: "Main puja", description: "Perform the ceremony with a practical home or venue flow." },
          { step: "Closure", description: "End with aarti, prasad, and next-step guidance." },
        ],
    };
  }

  if (serviceCatalog[serviceSlug]) {
    return serviceCatalog[serviceSlug];
  }

  return {
    slug: serviceSlug,
    title: titleFromSlug(serviceSlug),
    priceRange: "Rs. 2,500 - Rs. 6,500",
    image: "",
    summary: `${titleFromSlug(serviceSlug)} support for Delhi NCR households that want clearer ritual planning and booking guidance.`,
    process: [
      { step: "Planning", description: "Review the ceremony intent, locality, and likely timeline." },
      { step: "Preparation", description: "Confirm materials, family participation, and access details." },
      { step: "Main puja", description: "Perform the ceremony with a practical home or venue flow." },
      { step: "Closure", description: "End with aarti, prasad, and next-step guidance." },
    ],
  };
}

function parseTsv(filePath: string): SourceRow[] {
  const raw = fs.readFileSync(filePath, "utf8").trim();
  if (!raw) return [];
  const [header, ...rows] = raw.split(/\r?\n/);
  const columns = header.split("\t");

  return rows
    .filter(Boolean)
    .map((row) => {
      const values = row.split("\t");
      return columns.reduce<SourceRow>((acc, column, index) => {
        acc[column] = values[index] ?? "";
        return acc;
      }, {});
    });
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

function buildContent(intro: string[], sections: Array<{ heading: string; paragraphs: string[] }>, faqs: FAQEntry[]) {
  const parts: string[] = [];
  intro.forEach((text) => parts.push(paragraph(text)));
  sections.forEach((section) => {
    parts.push(heading(section.heading));
    section.paragraphs.forEach((text) => parts.push(paragraph(text)));
  });
  parts.push(heading("Common questions Delhi NCR families ask"));
  faqs.forEach((faq) => {
    parts.push(h3(faq.question));
    parts.push(paragraph(faq.answer));
  });
  return parts.join("");
}

function readingTime(content: string, faqs: FAQEntry[]) {
  const text = `${content.replace(/<[^>]+>/g, " ")} ${faqs.map((faq) => `${faq.question} ${faq.answer}`).join(" ")}`
    .replace(/\s+/g, " ")
    .trim();
  return Math.max(8, Math.ceil((text ? text.split(" ").length : 0) / 190));
}

function automationMeta(page: SEOPage) {
  return {
    contentHash: crypto.createHash("sha256").update(JSON.stringify(page)).digest("hex"),
    generatedAt: `${LAST_UPDATED}T00:00:00.000Z`,
    pipelineVersion: "continuous-coverage-v1",
    revalidateAfter: 86400,
  };
}

function writePage(page: SEOPage) {
  const filePath = path.join(PAGES_DIR, `${page.slug}.json`);
  fs.writeFileSync(filePath, `${JSON.stringify({ ...page, automation: automationMeta(page) }, null, 2)}\n`, "utf8");
}

function serviceProof(service: ServiceSeed, city: string) {
  return [
    `${service.title} page written for ${city} booking intent rather than generic service browsing.`,
    "Clear next-step routing into comparison, city, and support pages.",
    "Built around real decision questions families ask before booking.",
  ];
}

function geoServicePage(row: SourceRow): SEOPage {
  const service = getServiceSeed(row.serviceSlug);
  const city = row.city;
  const centerSlug = resolveCenterSlug(row.citySlug || row.city.toLowerCase().replace(/\s+/g, "-"));
  const faqs: FAQEntry[] = [
    { question: `Can ${service.title.toLowerCase()} be booked in ${city}?`, answer: `Yes. This page is written for ${city} households that want a practical booking path for ${service.title.toLowerCase()} in Delhi NCR.` },
    { question: `Why does locality matter for ${service.title.toLowerCase()}?`, answer: `Because travel time, access rules, family schedules, and setup conditions can all affect whether the ceremony feels smooth on the day.` },
    { question: `What page should users open after this one?`, answer: `Usually the core /pujas/${service.slug} page, then a comparison or pricing guide if they still need more decision support.` },
    { question: `Why is this page useful for local SEO?`, answer: `It combines a clear ritual with a clear locality, which is often what higher-intent searchers need before they convert.` },
  ];
  const sections = [
    {
      heading: `Why ${city} families search for ${service.title}`,
      paragraphs: [
        `${service.title} searches in ${city} usually happen when the household already knows the broad ritual and now needs a service page that feels locally relevant. Navdhya improves this by combining the ritual intent with locality-based planning support instead of offering only a generic Delhi NCR service description.`,
        `That matters because users in ${city} are not only asking whether the ritual exists. They want to know whether the booking can work well where they live, how quickly it can be arranged, and which next page will help them make the decision confidently.`,
      ],
    },
    {
      heading: "What changes at the city level",
      paragraphs: [
        `Locality changes travel timing, building or venue access, household scheduling, and the kind of ceremony format that feels most practical. A page that understands ${city} better can help the user plan more effectively and trust the booking path more quickly.`,
        `Navdhya treats city-level service pages as decision support, not shallow geo swaps. The page needs to sound like something written for a real user who is balancing ceremony intent with practical constraints.`,
      ],
    },
    {
      heading: "What users usually compare before booking",
      paragraphs: [
        `Before booking, most users compare price range, what is included, how the ceremony works in a home or venue, and whether the service looks more useful than a broad marketplace listing. Navdhya improves this by giving users a clearer route into the main service page and any relevant pricing or comparison content.`,
        `That combination is valuable because users make stronger decisions when locality and ritual are considered together rather than separately.`,
      ],
    },
    {
      heading: "How the ceremony usually works",
      paragraphs: service.process.map((step) => `${step.step}: ${step.description}`),
    },
    {
      heading: `What to do after opening this ${city} page`,
      paragraphs: [
        `The best next step is usually the main /pujas/${service.slug} page because it explains the ritual in fuller detail. After that, a user should compare pricing, review trust signals, or move into the most relevant support page for the ceremony.`,
        `Navdhya uses the city page as a bridge from locality intent into the exact service page that can answer the rest of the user's decision questions.`,
      ],
    },
    {
      heading: "Why this page matters for continuous SEO coverage",
      paragraphs: [
        `Pages like this help the site capture geo plus ritual intent at scale without becoming thin if the copy stays specific and decision-oriented. The page succeeds when it sounds like local planning support, not like a generic city placeholder.`,
        `That is why Navdhya keeps the content tied to one service, one city, one primary keyword, and one clear next-step path.`,
      ],
    },
  ];

  const content = buildContent(
    [
      `Families searching for ${row.primaryKeyword} are usually close to action. If you want to <a href="/pujas">book a pandit in Delhi NCR</a> and narrow the right service path for ${city}, this page is designed to help with that specific decision.`,
      `${service.summary} This page gives ${city} users a more local entry point into that decision so they can move from general search intent into a real booking path with Navdhya.`,
    ],
    sections,
    faqs,
  );

  return {
    id: `continuous-geo-${row.slug}`,
    slug: row.slug,
    cluster: "services",
    title: `${service.title} in ${city}`,
    metaTitle: `${service.title} in ${city} | Book Pandit with Navdhya`,
    metaDescription: `Book ${service.title} in ${city} with verified pandit support, practical ceremony guidance, and a clear Delhi NCR booking path from Navdhya.`,
    primaryKeyword: row.primaryKeyword,
    secondaryKeywords: [service.title.toLowerCase(), `book pandit ${city.toLowerCase()}`, `${city.toLowerCase()} puja services`, "verified pandit booking"],
    keywordVariants: [`${service.slug} ${city.toLowerCase()}`, `${service.slug} delhi ncr`, `home puja ${city.toLowerCase()}`],
    searchIntent: "transactional",
    schemaType: "Service",
    content,
    outline: sections.map((section) => section.heading).concat("Common questions Delhi NCR families ask"),
    relatedSlugs: [service.slug, "best-pandit-booking-delhi", "puja-cost-guide-delhi", centerSlug],
    lastUpdated: LAST_UPDATED,
    lastReviewed: LAST_UPDATED,
    author: "Navdhya Editorial Team",
    reviewedBy: "Vinod Sharma",
    readingTime: readingTime(content, faqs),
    faqs,
    heroImage: service.image,
    proofPoints: serviceProof(service, city),
    pricingNote: `${service.title} pricing benchmarked from ${service.priceRange}.`,
    localAngle: `${city} users often need locality-aware planning, not only a broad Delhi NCR ritual description.`,
    targetCities: [city, "Delhi NCR"],
    requiredInternalLinks: ["/pujas", `/pujas/${service.slug}`, "/compare/best-pandit-booking-delhi"],
    recommendedAnchorTexts: [row.primaryKeyword, `${service.title.toLowerCase()} ${city.toLowerCase()}`, `book pandit ${city.toLowerCase()}`],
    serviceDetails: {
      summary: service.summary,
      priceRange: service.priceRange,
      image: service.image,
      process: service.process,
      highlights: [`${city} locality coverage`, "Verified pandit support", "Clear booking next steps", "Delhi NCR family guidance"],
      benefits: ["Locality-specific booking support.", "Useful for city plus service intent.", "Clear next-step links into the main puja page.", "Practical planning rather than thin geo content."],
      includes: ["Service-page guidance", "City-specific planning context", "Related-page routing", "Delhi NCR support framing"],
      serviceProof: serviceProof(service, city),
      bookingLeadTime: "2 to 5 days is ideal, though urgent requests may still be reviewed.",
      serviceAreas: [city, "Delhi NCR"],
    },
  };
}

function pricingPage(row: SourceRow): SEOPage {
  const service = row.serviceSlug ? getServiceSeed(row.serviceSlug) : null;
  const faqs: FAQEntry[] = [
    { question: `What affects ${row.topic.toLowerCase()} most?`, answer: "Ceremony type, locality, materials, family participation, and whether the setup is compact or elaborate all change the final range." },
    { question: "Why should users compare more than the headline price?", answer: "Because inclusions, preparation help, and practical coordination often matter as much as the visible starting number." },
    { question: "What page should users open after a pricing guide?", answer: "Usually the exact service page they want to book, followed by a comparison or city page if they still need more decision support." },
    { question: "Why is this page commercially useful?", answer: "Because pricing queries usually happen very close to decision time, and good cost guidance reduces hesitation." },
  ];
  const sections = [
    {
      heading: "Why pricing searches matter",
      paragraphs: [
        `${row.topic} queries are strong because the user is already evaluating a real ceremony, not browsing casually. Navdhya uses pricing pages to answer the practical budget question while still helping the household compare ceremony fit and inclusions.`,
        "A better pricing page builds trust by explaining the range instead of treating the user like they should simply accept a vague quote and move on.",
      ],
    },
    {
      heading: "What usually changes the range",
      paragraphs: [
        "Price changes with ceremony depth, locality, materials, family participation, travel complexity, and whether any related rituals are being combined. A transparent guide should teach users what those variables are before they start comparing providers.",
        "That makes the page more useful because it answers the structure of pricing, not only the number itself.",
      ],
    },
    {
      heading: "How to compare value well",
      paragraphs: [
        "Users should compare what is included, how the ceremony will be prepared, and whether the provider communicates clearly about the household's actual needs. Navdhya performs better when the pricing content helps users ask better questions, not only hunt for lower numbers.",
        "This gives the page both informational value and conversion value at the same time.",
      ],
    },
    {
      heading: "Where hidden-cost confusion starts",
      paragraphs: [
        "Confusion usually starts when the family assumes two packages include the same support or when the provider assumes the user already understands the ceremony requirements. A pricing page should reduce that gap directly.",
        "That is why this page pushes users into the next relevant service and comparison pages instead of acting like the decision is finished here.",
      ],
    },
    {
      heading: "Best next pages after this pricing guide",
      paragraphs: [
        "The next step is usually the exact puja page for the ceremony plus one comparison or city page. That combination gives the household enough context to move from budget concern into a real booking decision.",
        "Navdhya treats pricing pages as part of a decision system, not as isolated blog posts.",
      ],
    },
  ];

  const content = buildContent(
    [
      `Users searching ${row.primaryKeyword} are usually trying to make a real booking decision, not only collect a number. This page helps them understand what shapes the range and what they should compare next before they <a href="/pujas">book a pandit in Delhi NCR</a>.`,
      `Navdhya improves pricing content by explaining tradeoffs, not only by publishing a headline figure. That gives families a more trustworthy path into the right puja page.${service ? ` The closest core service page for this pricing topic is <a href="/pujas/${service.slug}">${service.title}</a>, where the ceremony scope and package depth are easier to compare.` : ""}`,
    ],
    sections,
    faqs,
  );

  return {
    id: `continuous-pricing-${row.slug}`,
    slug: row.slug,
    cluster: "blog",
    title: row.topic,
    metaTitle: `${row.topic} | Delhi NCR Pricing Guide`,
    metaDescription: `Understand ${row.topic.toLowerCase()} with a practical Delhi NCR cost guide from Navdhya, including what shapes the range and what to compare next.`,
    primaryKeyword: row.primaryKeyword,
    secondaryKeywords: ["puja pricing delhi ncr", "book pandit delhi", "ritual cost guide", "pandit booking pricing"],
    keywordVariants: ["puja cost delhi", "pandit price delhi", "delhi ncr puja pricing"],
    searchIntent: "transactional",
    schemaType: "Article",
    content,
    outline: sections.map((section) => section.heading).concat("Common questions Delhi NCR families ask"),
    relatedSlugs: [service?.slug ?? "puja-cost-guide-delhi", "best-pandit-booking-delhi", "how-to-verify-pandit", "delhi"],
    lastUpdated: LAST_UPDATED,
    lastReviewed: LAST_UPDATED,
    author: "Navdhya Editorial Team",
    reviewedBy: "Vinod Sharma",
    readingTime: readingTime(content, faqs),
    faqs,
    proofPoints: ["Commercial-intent pricing support.", "Built around clearer comparison logic.", "Routes directly into service and comparison pages."],
    localAngle: "Delhi NCR price searchers want both cost clarity and service context, so this page is designed as a practical decision aid.",
    targetCities: ["Delhi", "Gurgaon", "Noida", "Ghaziabad"],
    requiredInternalLinks: ["/pujas", service ? `/pujas/${service.slug}` : "/blog/puja-cost-guide-delhi", "/compare/best-pandit-booking-delhi", "/blog/how-to-verify-pandit"],
    recommendedAnchorTexts: [row.primaryKeyword, "puja pricing delhi ncr", "book pandit delhi"],
  };
}

function bookingModifierPage(row: SourceRow): SEOPage {
  const service = getServiceSeed(row.serviceSlug);
  const faqs: FAQEntry[] = [
    { question: `Can ${service.title.toLowerCase()} be booked for a ${row.modifier} request?`, answer: `Sometimes yes, but the answer depends on locality, timing, preparation, and whether the ceremony format is still realistic.` },
    { question: "Why do modifier pages convert well?", answer: "Because they answer a very specific real-world booking concern that often appears close to decision time." },
    { question: "What should the user check first?", answer: "The ceremony type, exact locality, timing window, and whether the setup requirements still fit the request." },
    { question: "What is the best next page after this one?", answer: `Usually the main /pujas/${service.slug} page followed by a city or pricing page if the user still needs more decision support.` },
  ];
  const sections = [
    {
      heading: `Why users search modifier-led ${service.title} pages`,
      paragraphs: [
        `Users who search for a ${row.modifier} version of ${service.title.toLowerCase()} are trying to solve a very specific booking constraint. Navdhya treats that as a strong signal because the user is already narrowing how the ceremony should work, not only whether it exists.`,
        "That makes the page more commercially useful when it answers the modifier clearly and routes the user toward the core puja and locality pages that complete the decision.",
      ],
    },
    {
      heading: "What changes because of the modifier",
      paragraphs: [
        "The modifier changes preparation, price expectations, timing flexibility, and whether the ceremony should stay compact or shift into a different format. A good page should explain those tradeoffs explicitly so the user knows whether the request is practical.",
        "Navdhya improves these pages by making them specific rather than generic keyword inserts.",
      ],
    },
    {
      heading: "How the user should evaluate fit",
      paragraphs: [
        "The best fit test is still practical: location, ceremony depth, household readiness, and any site or family constraints. Users trust these pages more when the answer is honest about where the modifier helps and where it creates limits.",
        "That honesty also improves search quality because the page better matches the real intent behind the query.",
      ],
    },
    {
      heading: "Best next pages after this modifier guide",
      paragraphs: [
        `The core /pujas/${service.slug} page should usually be the next stop because it explains the full ceremony more clearly. After that, a city page or pricing guide helps the user confirm whether the modifier still fits well in practice.`,
        "Navdhya keeps those next links close because modifier pages should reduce confusion, not create another research dead end.",
      ],
    },
    {
      heading: "Why this page matters for continuous coverage",
      paragraphs: [
        "Modifier pages help the site capture high-intent long-tail behavior while still staying useful if they are built around real decision constraints. They should feel like problem-solving pages, not thin keyword combinations.",
        "That is how Navdhya turns continuous SEO expansion into a quality system rather than a page-count exercise.",
      ],
    },
  ];

  const content = buildContent(
    [
      `Users searching ${row.primaryKeyword} are usually already close to booking. If you want to <a href="/pujas">book a pandit in Delhi NCR</a> and need clarity on how the ${row.modifier} request changes the decision, this page is meant to answer that directly.`,
      `${service.summary} Navdhya uses modifier pages like this to solve one user problem clearly instead of forcing every search into a generic service description.`,
    ],
    sections,
    faqs,
  );

  return {
    id: `continuous-modifier-${row.slug}`,
    slug: row.slug,
    cluster: "services",
    title: `${service.title} for ${row.modifier} requests in Delhi NCR`,
    metaTitle: `${service.title} for ${row.modifier} requests | Delhi NCR guide`,
    metaDescription: `Learn when ${service.title.toLowerCase()} is practical for ${row.modifier} requests in Delhi NCR and what to compare before booking with Navdhya.`,
    primaryKeyword: row.primaryKeyword,
    secondaryKeywords: [`${row.modifier} ${service.title.toLowerCase()}`, `${service.title.toLowerCase()} delhi ncr`, "book pandit delhi", "verified pandit booking"],
    keywordVariants: [`${service.slug} ${row.modifier}`, `${service.slug} urgent delhi`, `${service.slug} local booking`],
    searchIntent: "transactional",
    schemaType: "Service",
    content,
    outline: sections.map((section) => section.heading).concat("Common questions Delhi NCR families ask"),
    relatedSlugs: [service.slug, "same-day-pandit-booking-delhi", "puja-cost-guide-delhi", "delhi"],
    lastUpdated: LAST_UPDATED,
    lastReviewed: LAST_UPDATED,
    author: "Navdhya Editorial Team",
    reviewedBy: "Vinod Sharma",
    readingTime: readingTime(content, faqs),
    faqs,
    heroImage: service.image,
    proofPoints: ["Long-tail commercial support page.", "Built around one booking constraint per page.", "Routes clearly into the main service page."],
    localAngle: `This page answers a ${row.modifier} booking concern that often appears close to conversion and needs a practical Delhi NCR response.`,
    targetCities: ["Delhi", "Gurgaon", "Noida", "Ghaziabad"],
    requiredInternalLinks: ["/pujas", `/pujas/${service.slug}`, "/blog/puja-cost-guide-delhi"],
    recommendedAnchorTexts: [row.primaryKeyword, `${service.title.toLowerCase()} ${row.modifier}`, "book pandit delhi ncr"],
    serviceDetails: {
      summary: service.summary,
      priceRange: service.priceRange,
      image: service.image,
      process: service.process,
      highlights: [row.modifier, "Delhi NCR support", "Clear fit guidance", "Main service page routing"],
      benefits: ["Answers one real booking constraint clearly.", "Supports higher-intent long-tail search behavior.", "Connects modifier searches to the main puja page.", "Improves continuous coverage without shallow duplication."],
      includes: ["Modifier-specific guidance", "Service-page routing", "Cost-awareness prompts", "Delhi NCR planning notes"],
      serviceProof: ["Built around one user problem per page.", "Avoids shallow city or modifier stuffing.", "Supports continuous SEO coverage safely."],
      bookingLeadTime: "Lead time varies with the modifier and locality, so users should check fit before assuming availability.",
      serviceAreas: ["Delhi", "Gurgaon", "Noida", "Ghaziabad"],
    },
  };
}

function createPagesFromSource(fileName: string, factory: (row: SourceRow) => SEOPage) {
  const filePath = path.join(SOURCES_DIR, fileName);
  if (!fs.existsSync(filePath)) return 0;

  const rows = parseTsv(filePath);
  let written = 0;
  rows.forEach((row) => {
    if (getPage(row.slug)) return;
    writePage(factory(row));
    written += 1;
  });
  return written;
}

const written = [
  createPagesFromSource("geo-service-pages.tsv", geoServicePage),
  createPagesFromSource("pricing-pages.tsv", pricingPage),
  createPagesFromSource("booking-modifier-pages.tsv", bookingModifierPage),
].reduce((sum, value) => sum + value, 0);

console.log(`Continuous coverage generator created ${written} new pages.`);

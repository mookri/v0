import { SiteHeader } from "@/components/site-header"
import { SiteFooter } from "@/components/site-footer"
import { SEO_CONFIG, BREADCRUMB_SCHEMA } from "@/lib/seo-config"
import Script from "next/script"
import { ShieldCheck, Grid, Users, Award, HeartHandshake, Zap, Leaf, Target } from "lucide-react"

// Add page-specific metadata
export const metadata = {
  title: "About Us | Navdhya – Delhi's Trusted Puja & Spiritual Wellness",
  description: "Navdhya is Delhi's premier spiritual wellness platform connecting families with certified Vedic pandits for authentic puja services, astrology consultation, and spiritual guidance. Professional, reliable, and culturally authentic.",
  keywords: "Puja Services in Delhi, Online Puja Booking, Spiritual Wellness Experts, Vedic Pandits Delhi, Astrology Consultation, Hindu Ritual Services, Authentic Puja, Delhi Spiritual Services",
  openGraph: {
    title: "About Us | Navdhya – Delhi's Trusted Puja & Spiritual Wellness",
    description: "Navdhya is Delhi's premier spiritual wellness platform connecting families with certified Vedic pandits for authentic puja services, astrology consultation, and spiritual guidance. Professional, reliable, and culturally authentic.",
    url: `https://www.navdhya.com/about`,
    siteName: SEO_CONFIG.siteName,
    images: [
      {
        url: SEO_CONFIG.defaultImage,
        width: 1200,
        height: 630,
        alt: "About Navdhya - Delhi's Trusted Spiritual Wellness Experts",
      },
    ],
    locale: SEO_CONFIG.locale,
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "About Us | Navdhya – Delhi's Trusted Puja & Spiritual Wellness",
    description: "Navdhya is Delhi's premier spiritual wellness platform connecting families with certified Vedic pandits for authentic puja services, astrology consultation, and spiritual guidance. Professional, reliable, and culturally authentic.",
    images: [SEO_CONFIG.defaultImage],
    site: SEO_CONFIG.twitter.site,
  },
  alternates: {
    canonical: "https://www.navdhya.com/about",
  },
}

// Schema markup for the about page
const ABOUT_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "AboutPage",
  name: "About Us - Navdhya",
  url: `https://www.navdhya.com/about`,
  description: "Navdhya is Delhi's premier spiritual wellness platform connecting families with certified Vedic pandits for authentic puja services, astrology consultation, and spiritual guidance. Professional, reliable, and culturally authentic.",
  breadcrumb: BREADCRUMB_SCHEMA([
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "About Us", url: `https://www.navdhya.com/about` }
  ])
}

export default function AboutPage() {
  const aboutSchema = JSON.stringify(ABOUT_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: aboutSchema }}
      />
      <SiteHeader />
      <main className="mx-auto max-w-7xl py-12 px-4 sm:px-8 lg:space-y-20 overflow-x-hidden w-full">
        {/* Hero Section */}
        <section aria-labelledby="hero-heading" className="relative overflow-hidden rounded-t-3xl" style={{ minHeight: "550px" }}>
          {/* Background Gradient */}
          <div className="absolute inset-0 bg-gradient-to-b from-muted/80 to-background z-0"></div>
          
          <div className="relative z-10 max-w-7xl mx-auto px-6 sm:px-8 py-16 lg:py-20">
            <div className="flex flex-col h-full">
              {/* Badge */}
              <div className="self-start mb-6">
                <div className="border border-border/50 rounded-2xl py-1.5 px-5 bg-card/50 backdrop-blur-sm">
                  <h1 className="text-sm font-medium tracking-tight text-foreground/80 uppercase">
                    Who we are
                  </h1>
                </div>
              </div>
              
              {/* Heading */}
              <div className="max-w-3xl mb-10">
                <h2 id="hero-heading" className="text-4xl md:text-5xl lg:text-6xl font-normal tracking-tight text-foreground leading-tight">
                  Delhi's Trusted <span className="text-primary">Spiritual Wellness</span> Experts
                </h2>
              </div>
              
              {/* Image */}
              <div className="mt-auto">
                <div className="bg-gradient-to-br from-muted/50 to-muted/20 rounded-3xl overflow-hidden border border-border/30 h-80 md:h-96">
                  <img 
                    src="https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg" 
                    alt="Navdhya Puja Services in Delhi" 
                    className="w-full h-full object-cover"
                  />
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* Our Story */}
        <section aria-labelledby="our-story" className="py-10 lg:py-14">
          <div className="grid lg:grid-cols-2 gap-16 items-center">
            <div>
              <h2 id="our-story" className="font-serif text-3xl lg:text-4xl font-bold tracking-tight text-foreground mb-6">
                Our Story
              </h2>
              <p className="text-muted-foreground mb-5 text-base leading-relaxed">
                Founded in 2023 as part of UPFLAME ENTERPRISES LLP, Navdhya emerged from a recognition of the growing need for authentic, accessible spiritual services in Delhi's modern households.
              </p>
              <p className="text-muted-foreground mb-5 text-base leading-relaxed">
                Our journey began with a simple observation: traditional Vedic practices were becoming increasingly difficult for urban families to access and observe meaningfully. We set out to bridge this gap with professionalism, authenticity, and technological innovation.
              </p>
              <p className="text-muted-foreground text-base leading-relaxed">
                Today, Navdhya serves hundreds of families across Delhi NCR, connecting them with certified pandits who bring both scholarly knowledge and practical experience to every ritual.
              </p>
            </div>
            <div className="bg-muted/50 rounded-xl h-80 flex items-center justify-center shadow-sm border border-border/30 overflow-hidden">
              <img 
                src="https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg" 
                alt="Navdhya Founding Team" 
                className="w-full h-full object-cover"
              />
            </div>
          </div>
        </section>

        {/* Our Philosophy */}
        <section aria-labelledby="our-philosophy" className="py-10 lg:py-14">
          {/* Header Section */}
          <div className="mb-16">
            <div className="inline-block px-4 py-2 bg-card border border-border rounded-full text-sm text-muted-foreground mb-6">
              OUR CORE VALUES
            </div>
            
            <div className="grid lg:grid-cols-2 gap-12 items-start">
              <h2 id="our-philosophy" className="font-serif text-4xl lg:text-5xl font-light tracking-tight text-foreground leading-tight">
                We Operate with<br />
                Unwavering <span className="text-primary">Devotion</span> &<br />
                Authenticity
              </h2>
              
              <p className="text-muted-foreground text-lg leading-relaxed lg:mt-8">
                Our core principles guide every ritual we perform and every relationship we build. 
                We're committed to preserving Vedic traditions while making spiritual wellness 
                accessible to modern families across Delhi NCR.
              </p>
            </div>
          </div>

          {/* Cards Section */}
          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div className="bg-card rounded-3xl px-4 py-4 border border-border hover:shadow-md transition-all duration-300 flex flex-col justify-between" style={{ minHeight: "380px" }}>
              <div className="w-14 h-14 bg-[#0E3F2E] rounded-full flex items-center justify-center mb-24">
                <ShieldCheck className="w-6 h-6 text-white" />
              </div>
              
              <div className="bg-muted rounded-2xl p-6 flex-grow">
                <h3 className="text-xl font-medium text-foreground mb-3">
                  Sacred Trust
                </h3>
                
                <p className="text-muted-foreground text-sm leading-relaxed line-clamp-2">
                  We honor the sacred trust placed in us by families, performing every ritual with the utmost reverence and authenticity.
                </p>
              </div>
            </div>
            
            <div className="bg-card rounded-3xl px-4 py-4 border border-border hover:shadow-md transition-all duration-300 flex flex-col justify-between" style={{ minHeight: "380px" }}>
              <div className="w-14 h-14 bg-[#0E3F2E] rounded-full flex items-center justify-center mb-24">
                <Grid className="w-6 h-6 text-white" />
              </div>
              
              <div className="bg-muted rounded-2xl p-6 flex-grow">
                <h3 className="text-xl font-medium text-foreground mb-3">
                  Holistic Service
                </h3>
                
                <p className="text-muted-foreground text-sm leading-relaxed line-clamp-2">
                  From puja samagri to expert pandit guidance, we provide comprehensive spiritual solutions for every life occasion.
                </p>
              </div>
            </div>
            
            <div className="bg-card rounded-3xl px-4 py-4 border border-border hover:shadow-md transition-all duration-300 flex flex-col justify-between" style={{ minHeight: "380px" }}>
              <div className="w-14 h-14 bg-[#0E3F2E] rounded-full flex items-center justify-center mb-24">
                <Users className="w-6 h-6 text-white" />
              </div>
              
              <div className="bg-muted rounded-2xl p-6 flex-grow">
                <h3 className="text-xl font-medium text-foreground mb-3">
                  Personalized Care
                </h3>
                
                <p className="text-muted-foreground text-sm leading-relaxed line-clamp-2">
                  Each family's traditions are unique. We customize our services to honor your specific customs and beliefs.
                </p>
              </div>
            </div>
            
            <div className="bg-card rounded-3xl px-4 py-4 border border-border hover:shadow-md transition-all duration-300 flex flex-col justify-between" style={{ minHeight: "380px" }}>
              <div className="w-14 h-14 bg-[#0E3F2E] rounded-full flex items-center justify-center mb-24">
                <Award className="w-6 h-6 text-white" />
              </div>
              
              <div className="bg-muted rounded-2xl p-6 flex-grow">
                <h3 className="text-xl font-medium text-foreground mb-3">
                  Vedic Excellence
                </h3>
                
                <p className="text-muted-foreground text-sm leading-relaxed line-clamp-2">
                  Our certified pandits bring scholarly knowledge and practical experience to ensure authentic ritual performance.
                </p>
              </div>
            </div>
          </div>
        </section>

        {/* Our Offerings */}
        <section aria-labelledby="our-offerings" className="py-10 lg:py-14">
          <h2 id="our-offerings" className="font-serif text-3xl lg:text-4xl font-bold tracking-tight text-foreground text-center mb-5">
            Our Offerings
          </h2>
          <p className="text-muted-foreground text-center max-w-3xl mx-auto mb-14 text-lg leading-relaxed">
            Comprehensive spiritual services designed to meet the diverse needs of modern families while preserving traditional authenticity.
          </p>
          
          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div className="border border-border/50 rounded-xl p-6 bg-card hover:shadow-md transition-all duration-300 backdrop-blur-sm">
              <h3 className="font-serif text-xl font-bold tracking-tight text-foreground mb-4">Puja Services</h3>
              <p className="text-muted-foreground text-sm mb-5 leading-relaxed">
                Home pujas, temple services, festival celebrations, and special occasion rituals performed by certified Vedic pandits.
              </p>
              <ul className="text-sm text-muted-foreground space-y-2.5">
                <li className="flex items-start">
                  <span className="text-primary mr-2 mt-1">•</span>
                  <span>Grih Pravesh & House Warming</span>
                </li>
                <li className="flex items-start">
                  <span className="text-primary mr-2 mt-1">•</span>
                  <span>Baby Naming & Annaprashan</span>
                </li>
                <li className="flex items-start">
                  <span className="text-primary mr-2 mt-1">•</span>
                  <span>Wedding & Engagement Rituals</span>
                </li>
                <li className="flex items-start">
                  <span className="text-primary mr-2 mt-1">•</span>
                  <span>Festival Pujas</span>
                </li>
              </ul>
            </div>
            
            <div className="border border-border/50 rounded-xl p-6 bg-card hover:shadow-md transition-all duration-300 backdrop-blur-sm">
              <h3 className="font-serif text-xl font-bold tracking-tight text-foreground mb-4">Astrology Consultation</h3>
              <p className="text-muted-foreground text-sm mb-5 leading-relaxed">
                Expert astrological guidance for life decisions, compatibility analysis, and remedial solutions.
              </p>
              <ul className="text-sm text-muted-foreground space-y-2.5">
                <li className="flex items-start">
                  <span className="text-primary mr-2 mt-1">•</span>
                  <span>Kundli Analysis & Predictions</span>
                </li>
                <li className="flex items-start">
                  <span className="text-primary mr-2 mt-1">•</span>
                  <span>Marriage Compatibility</span>
                </li>
                <li className="flex items-start">
                  <span className="text-primary mr-2 mt-1">•</span>
                  <span>Muhurat Selection</span>
                </li>
                <li className="flex items-start">
                  <span className="text-primary mr-2 mt-1">•</span>
                  <span>Vastu Consultation</span>
                </li>
              </ul>
            </div>
            
            <div className="border border-border/50 rounded-xl p-6 bg-card hover:shadow-md transition-all duration-300 backdrop-blur-sm">
              <h3 className="font-serif text-xl font-bold tracking-tight text-foreground mb-4">Spiritual Products</h3>
              <p className="text-muted-foreground text-sm mb-5 leading-relaxed">
                Authentically sourced puja samagri, yantras, rudraksha, and spiritual literature for personal practice.
              </p>
              <ul className="text-sm text-muted-foreground space-y-2.5">
                <li className="flex items-start">
                  <span className="text-primary mr-2 mt-1">•</span>
                  <span>Puja Samagri Kits</span>
                </li>
                <li className="flex items-start">
                  <span className="text-primary mr-2 mt-1">•</span>
                  <span>Rudraksha & Gemstones</span>
                </li>
                <li className="flex items-start">
                  <span className="text-primary mr-2 mt-1">•</span>
                  <span>Yantras & Idols</span>
                </li>
                <li className="flex items-start">
                  <span className="text-primary mr-2 mt-1">•</span>
                  <span>Spiritual Books & Guides</span>
                </li>
              </ul>
            </div>
            
            <div className="border border-border/50 rounded-xl p-6 bg-card hover:shadow-md transition-all duration-300 backdrop-blur-sm">
              <h3 className="font-serif text-xl font-bold tracking-tight text-foreground mb-4">Educational Resources</h3>
              <p className="text-muted-foreground text-sm mb-5 leading-relaxed">
                Accessible learning materials to deepen understanding of Vedic traditions and practices.
              </p>
              <ul className="text-sm text-muted-foreground space-y-2.5">
                <li className="flex items-start">
                  <span className="text-primary mr-2 mt-1">•</span>
                  <span>Mantras & Slokas</span>
                </li>
                <li className="flex items-start">
                  <span className="text-primary mr-2 mt-1">•</span>
                  <span>Vedic Knowledge Repository</span>
                </li>
                <li className="flex items-start">
                  <span className="text-primary mr-2 mt-1">•</span>
                  <span>Ritual Guides</span>
                </li>
                <li className="flex items-start">
                  <span className="text-primary mr-2 mt-1">•</span>
                  <span>Workshops & Webinars</span>
                </li>
              </ul>
            </div>
          </div>
        </section>

        {/* Why Choose Navdhya */}
        <section aria-labelledby="why-navdhya" className="py-10 lg:py-14 bg-muted/30 rounded-2xl p-8 lg:p-12">
          <h2 id="why-navdhya" className="font-serif text-3xl lg:text-4xl font-bold tracking-tight text-foreground text-center mb-5">
            Why Choose Navdhya
          </h2>
          <p className="text-muted-foreground text-center max-w-3xl mx-auto mb-14 text-lg leading-relaxed">
            Our commitment to excellence is reflected in every aspect of our service delivery and client relationships.
          </p>
          
          <div className="grid lg:grid-cols-2 gap-12">
            <div>
              <h3 className="font-serif text-xl font-bold tracking-tight text-foreground mb-6">Professional Excellence</h3>
              <ul className="space-y-6">
                <li className="flex">
                  <div className="flex-shrink-0 h-7 w-7 rounded-full bg-gradient-to-br from-primary/10 to-secondary/10 flex items-center justify-center mr-4 mt-0.5">
                    <span className="text-primary text-sm">✓</span>
                  </div>
                  <div>
                    <p className="font-medium text-foreground">Certified Pandits</p>
                    <p className="text-muted-foreground text-sm mt-1">
                      All our pandits undergo rigorous verification of credentials and experience.
                    </p>
                  </div>
                </li>
                <li className="flex">
                  <div className="flex-shrink-0 h-7 w-7 rounded-full bg-gradient-to-br from-primary/10 to-secondary/10 flex items-center justify-center mr-4 mt-0.5">
                    <span className="text-primary text-sm">✓</span>
                  </div>
                  <div>
                    <p className="font-medium text-foreground">Quality Assurance</p>
                    <p className="text-muted-foreground text-sm mt-1">
                      Standardized procedures ensure consistent, high-quality service delivery.
                    </p>
                  </div>
                </li>
                <li className="flex">
                  <div className="flex-shrink-0 h-7 w-7 rounded-full bg-gradient-to-br from-primary/10 to-secondary/10 flex items-center justify-center mr-4 mt-0.5">
                    <span className="text-primary text-sm">✓</span>
                  </div>
                  <div>
                    <p className="font-medium text-foreground">Transparent Pricing</p>
                    <p className="text-muted-foreground text-sm mt-1">
                      Clear, upfront pricing with no hidden costs or last-minute additions.
                    </p>
                  </div>
                </li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl font-bold tracking-tight text-foreground mb-6">Client-Centric Approach</h3>
              <ul className="space-y-6">
                <li className="flex">
                  <div className="flex-shrink-0 h-7 w-7 rounded-full bg-gradient-to-br from-primary/10 to-secondary/10 flex items-center justify-center mr-4 mt-0.5">
                    <span className="text-primary text-sm">✓</span>
                  </div>
                  <div>
                    <p className="font-medium text-foreground">Personalized Service</p>
                    <p className="text-muted-foreground text-sm mt-1">
                      Customized rituals based on specific family traditions and requirements.
                    </p>
                  </div>
                </li>
                <li className="flex">
                  <div className="flex-shrink-0 h-7 w-7 rounded-full bg-gradient-to-br from-primary/10 to-secondary/10 flex items-center justify-center mr-4 mt-0.5">
                    <span className="text-primary text-sm">✓</span>
                  </div>
                  <div>
                    <p className="font-medium text-foreground">Seamless Experience</p>
                    <p className="text-muted-foreground text-sm mt-1">
                      End-to-end service management from booking to completion.
                    </p>
                  </div>
                </li>
                <li className="flex">
                  <div className="flex-shrink-0 h-7 w-7 rounded-full bg-gradient-to-br from-primary/10 to-secondary/10 flex items-center justify-center mr-4 mt-0.5">
                    <span className="text-primary text-sm">✓</span>
                  </div>
                  <div>
                    <p className="font-medium text-foreground">Ongoing Support</p>
                    <p className="text-muted-foreground text-sm mt-1">
                      Post-service consultation and guidance for continued spiritual wellness.
                    </p>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </section>

        {/* Leadership */}
        <section aria-labelledby="leadership" className="py-10 lg:py-14">
          <h2 id="leadership" className="font-serif text-3xl lg:text-4xl font-bold tracking-tight text-foreground text-center mb-5">
            Leadership Vision
          </h2>
          <p className="text-muted-foreground text-center max-w-3xl mx-auto mb-14 text-lg leading-relaxed">
            Guided by a commitment to preserve and propagate authentic Vedic traditions in contemporary settings.
          </p>
          
          <div className="max-w-5xl mx-auto">
            <div className="bg-card border border-border/50 rounded-xl p-8 lg:p-12 shadow-sm backdrop-blur-sm">
              <div className="flex flex-col lg:flex-row items-center">
                <div className="bg-muted/50 rounded-full w-40 h-40 flex items-center justify-center mb-8 lg:mb-0 lg:mr-12 border border-border/30 overflow-hidden flex-shrink-0">
                  <img 
                    src="https://res.cloudinary.com/dufzctlaj/image/upload/v1761960318/LoDi-Make_this_logo_exact_lnyy2a.svg" 
                    alt="Navdhya Leadership" 
                    className="w-full h-full object-cover"
                  />
                </div>
                <div>
                  <h3 className="font-serif text-2xl font-bold tracking-tight text-foreground">UPFLAME ENTERPRISES LLP</h3>
                  <p className="text-primary mb-5">Parent Organization</p>
                  <p className="text-muted-foreground leading-relaxed">
                    As part of UPFLAME ENTERPRISES LLP, Navdhya benefits from professional business infrastructure while maintaining its spiritual authenticity. Our leadership brings together expertise in traditional Vedic practices with modern business acumen to create a sustainable, scalable model for spiritual service delivery.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* Client Experiences */}
        <section aria-labelledby="client-experiences" className="py-10 lg:py-14">
          <h2 id="client-experiences" className="font-serif text-3xl lg:text-4xl font-bold tracking-tight text-foreground text-center mb-5">
            Client Experiences
          </h2>
          <p className="text-muted-foreground text-center max-w-3xl mx-auto mb-14 text-lg leading-relaxed">
            Hear from families who have experienced the Navdhya difference in their spiritual journey.
          </p>
          
          <div className="grid md:grid-cols-3 gap-8">
            <div className="border border-border/50 rounded-xl p-6 bg-card shadow-sm backdrop-blur-sm">
              <div className="flex items-center mb-5">
                <div className="flex text-yellow-400">
                  {[...Array(5)].map((_, i) => (
                    <span key={i}>★</span>
                  ))}
                </div>
              </div>
              <p className="text-muted-foreground italic mb-6 leading-relaxed">
                "The Grih Pravesh puja arranged by Navdhya was seamless from booking to completion. The pandit was knowledgeable, respectful, and took time to explain each step. Highly recommended for anyone seeking authentic rituals with modern convenience."
              </p>
              <p className="font-medium text-foreground">- Priya Sharma, South Delhi</p>
            </div>
            
            <div className="border border-border/50 rounded-xl p-6 bg-card shadow-sm backdrop-blur-sm">
              <div className="flex items-center mb-5">
                <div className="flex text-yellow-400">
                  {[...Array(5)].map((_, i) => (
                    <span key={i}>★</span>
                  ))}
                </div>
              </div>
              <p className="text-muted-foreground italic mb-6 leading-relaxed">
                "As a busy professional, I appreciated how Navdhya handled all the arrangements for my daughter's Annaprashan. The attention to detail and cultural authenticity exceeded my expectations. Will definitely use their services again."
              </p>
              <p className="font-medium text-foreground">- Rajesh Kumar, Gurgaon</p>
            </div>
            
            <div className="border border-border/50 rounded-xl p-6 bg-card shadow-sm backdrop-blur-sm">
              <div className="flex items-center mb-5">
                <div className="flex text-yellow-400">
                  {[...Array(5)].map((_, i) => (
                    <span key={i}>★</span>
                  ))}
                </div>
              </div>
              <p className="text-muted-foreground italic mb-6 leading-relaxed">
                "The astrology consultation helped me make important career decisions with clarity. The pandit's insights were profound yet practical. Navdhya's approach bridges ancient wisdom with contemporary understanding."
              </p>
              <p className="font-medium text-foreground">- Meera Patel, East Delhi</p>
            </div>
          </div>
        </section>

        {/* Community & Responsibility */}
        <section aria-labelledby="community-responsibility" className="py-10 lg:py-14">
          <div className="grid lg:grid-cols-2 gap-16 items-center">
            <div className="bg-muted/50 rounded-xl h-80 flex items-center justify-center order-2 lg:order-1 shadow-sm border border-border/30 overflow-hidden">
              <img 
                src="https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg" 
                alt="Community engagement and sustainability" 
                className="w-full h-full object-cover"
              />
            </div>
            <div className="order-1 lg:order-2">
              <h2 id="community-responsibility" className="font-serif text-3xl lg:text-4xl font-bold tracking-tight text-foreground mb-6">
                Community & Responsibility
              </h2>
              <p className="text-muted-foreground mb-5 text-base leading-relaxed">
                Navdhya is committed to sustainable practices and community engagement that honor both our spiritual traditions and environmental responsibilities.
              </p>
              <p className="text-muted-foreground mb-5 text-base leading-relaxed">
                Our CSR initiatives include supporting traditional artisans who create puja items, promoting eco-friendly ritual practices, and offering subsidized services for underprivileged communities during major festivals.
              </p>
              <p className="text-muted-foreground text-base leading-relaxed">
                We believe in preserving Vedic traditions while adapting to contemporary needs, ensuring that spiritual wellness remains accessible to all sections of society.
              </p>
            </div>
          </div>
        </section>

        {/* Closing Statement */}
        <section aria-labelledby="closing-statement" className="py-16 lg:py-20 text-center border-t border-b border-border/50 my-10">
          <h2 id="closing-statement" className="font-serif text-3xl lg:text-4xl font-bold tracking-tight text-foreground mb-8">
            Our Commitment to You
          </h2>
          <p className="text-muted-foreground max-w-4xl mx-auto text-lg mb-10 leading-relaxed">
            At Navdhya, we understand that spiritual practices are deeply personal and significant. Our commitment is to provide services that honor your traditions with the utmost respect, authenticity, and professionalism. Whether you're observing rituals for the first time or continuing generations-old practices, we're here to support your spiritual journey with integrity and care.
          </p>
          <a 
            href="/explore" 
            className="bg-primary hover:bg-primary/90 text-primary-foreground font-medium py-3 px-8 rounded-lg transition-all duration-300 shadow-sm hover:shadow-md inline-block focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
          >
            Discover Our Services
          </a>
        </section>

        {/* Footer Information */}
        <section aria-labelledby="company-info" className="py-10 lg:py-14 border-t border-border/50">
          <h2 id="company-info" className="font-serif text-2xl lg:text-3xl font-bold tracking-tight text-foreground mb-10 text-center">
            Company Information
          </h2>
          <div className="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
            <div className="bg-card border border-border/50 rounded-xl p-6 backdrop-blur-sm">
              <h3 className="font-bold text-lg mb-4 text-foreground">Registered Details</h3>
              <p className="text-muted-foreground">
                <strong>Brand:</strong> Navdhya – Delhi's Trusted Puja & Spiritual Wellness Experts<br/>
                <strong>Parent Company:</strong> UPFLAME ENTERPRISES LLP<br/>
                <strong>Jurisdiction:</strong> Delhi, India<br/>
              </p>
            </div>
            <div className="bg-card border border-border/50 rounded-xl p-6 backdrop-blur-sm">
              <h3 className="font-bold text-lg mb-4 text-foreground">Contact Information</h3>
              <p className="text-muted-foreground">
                <strong>Registered Address:</strong> [Address Placeholder]<br/>
                <strong>Email:</strong> <a href="mailto:support@navdhya.in" className="text-primary hover:underline">support@navdhya.in</a><br/>
                <strong>Phone:</strong> +91-7289000377<br/>
              </p>
            </div>
          </div>
        </section>
      </main>
      <div className="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10 pb-12">
        <p className="text-center text-muted-foreground italic text-lg">
          May peace, faith, and integrity guide every home. – Team Navdhya
        </p>
      </div>
      <SiteFooter />
    </>
  )
}
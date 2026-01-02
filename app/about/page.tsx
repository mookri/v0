import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import { ShieldCheck, Grid, Users, Award, HeartHandshake, Zap, Leaf, Target } from "lucide-react"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Add page-specific metadata
export const metadata = seoManager.generateMetadata({
  title: "About Us",
  description: "Navdhya is Delhi's premier spiritual wellness platform connecting families with certified Vedic pandits for authentic puja services, astrology consultation, and spiritual guidance. Professional, reliable, and culturally authentic.",
  path: "/about",
  keywords: [
    "Puja Services in Delhi", 
    "Online Puja Booking", 
    "Spiritual Wellness Experts", 
    "Vedic Pandits Delhi", 
    "Astrology Consultation", 
    "Hindu Ritual Services", 
    "Authentic Puja", 
    "Delhi Spiritual Services"
  ]
})

// Schema markup for the about page
const ABOUT_SCHEMA = seoManager.generateSchema({
  type: "AboutPage",
  name: "About Us - Navdhya",
  description: "Navdhya is Delhi's premier spiritual wellness platform connecting families with certified Vedic pandits for authentic puja services, astrology consultation, and spiritual guidance. Professional, reliable, and culturally authentic.",
  path: "/about",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "About Us", url: "https://www.navdhya.com/about" }
  ]
})

export default async function AboutPage() {
  const aboutSchema = JSON.stringify(ABOUT_SCHEMA)
  
  // Resolve context using the new resolver
  const context = await resolveContext('/about');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: aboutSchema }}
        />
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
                    Scholarly Excellence
                  </h3>
                  
                  <p className="text-muted-foreground text-sm leading-relaxed line-clamp-2">
                    Our pandits are certified experts with deep knowledge of Vedic traditions, ensuring authentic and meaningful rituals.
                  </p>
                </div>
              </div>
            </div>
          </section>

          {/* Our Mission */}
          <section aria-labelledby="our-mission" className="py-10 lg:py-14">
            <div className="grid lg:grid-cols-2 gap-16 items-center">
              <div className="order-2 lg:order-1">
                <div className="bg-muted/50 rounded-xl h-80 flex items-center justify-center shadow-sm border border-border/30 overflow-hidden">
                  <img 
                    src="https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg" 
                    alt="Navdhya Mission" 
                    className="w-full h-full object-cover"
                  />
                </div>
              </div>
              
              <div className="order-1 lg:order-2">
                <h2 id="our-mission" className="font-serif text-3xl lg:text-4xl font-bold tracking-tight text-foreground mb-6">
                  Our Mission
                </h2>
                <p className="text-muted-foreground mb-5 text-base leading-relaxed">
                  To make authentic Vedic spiritual practices accessible to modern urban families in Delhi NCR, preserving cultural traditions while adapting to contemporary needs.
                </p>
                <p className="text-muted-foreground mb-5 text-base leading-relaxed">
                  We strive to be the most trusted bridge between ancient wisdom and modern life, ensuring that every family can experience the peace, prosperity, and spiritual wellness that comes from properly conducted rituals.
                </p>
                <p className="text-muted-foreground text-base leading-relaxed">
                  Through professional service, certified expertise, and genuine care, we aim to bring the transformative power of Vedic traditions into every home.
                </p>
              </div>
            </div>
          </section>

          {/* Our Team */}
          <section aria-labelledby="our-team" className="py-10 lg:py-14">
            <div className="text-center mb-16">
              <h2 id="our-team" className="font-serif text-3xl lg:text-4xl font-bold tracking-tight text-foreground mb-6">
                Our Leadership
              </h2>
              <p className="text-muted-foreground max-w-3xl mx-auto text-lg">
                Guided by experts in Vedic traditions and modern business practices, our team combines ancient wisdom with contemporary excellence.
              </p>
            </div>
            
            <div className="grid md:grid-cols-3 gap-8">
              <div className="bg-card rounded-2xl p-8 border border-border text-center hover:shadow-md transition-all duration-300">
                <div className="w-24 h-24 rounded-full bg-muted mx-auto mb-6 overflow-hidden">
                  <img 
                    src="https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg" 
                    alt="Team Member" 
                    className="w-full h-full object-cover"
                  />
                </div>
                <h3 className="text-xl font-medium text-foreground mb-2">Mr. Vinod Sharma</h3>
                <p className="text-primary font-medium mb-3">Founder & CEO</p>
<p className="text-muted-foreground text-sm">
  Brings 15+ years of experience across leadership, operations, and organizational growth.
</p>

              </div>
              
              <div className="bg-card rounded-2xl p-8 border border-border text-center hover:shadow-md transition-all duration-300">
                <div className="w-24 h-24 rounded-full bg-muted mx-auto mb-6 overflow-hidden">
                  <img 
                    src="https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg" 
                    alt="Team Member" 
                    className="w-full h-full object-cover"
                  />
                </div>
<h3 className="text-xl font-medium text-foreground mb-2">Amit Kumar</h3>
<p className="text-primary font-medium mb-3">Operations & Partnerships</p>
<p className="text-muted-foreground text-sm">
  Manages partner relationships and operational coordination, ensuring consistent quality and reliable service delivery.
</p>
              </div>
            </div>
          </section>

          {/* Our Commitment */}
          <section aria-labelledby="our-commitment" className="py-10 lg:py-14">
            <div className="bg-gradient-to-r from-[#0E3F2E] to-[#1a5c45] rounded-3xl p-12 text-center">
              <h2 id="our-commitment" className="font-serif text-3xl lg:text-4xl font-bold text-white mb-6">
                Our Commitment to You
              </h2>
              <p className="text-white/90 max-w-3xl mx-auto text-lg mb-10">
                We're not just service providers – we're partners in your spiritual journey. Every ritual we conduct is performed with the same reverence and care we'd want for our own families.
              </p>
              
              <div className="grid md:grid-cols-3 gap-8 max-w-4xl mx-auto">
                <div className="bg-white/10 rounded-2xl p-6">
                  <HeartHandshake className="w-10 h-10 text-white mx-auto mb-4" />
                  <h3 className="text-xl font-medium text-white mb-3">Genuine Care</h3>
                  <p className="text-white/80 text-sm">
                    We treat every family's spiritual needs with the respect and attention they deserve.
                  </p>
                </div>
                
                <div className="bg-white/10 rounded-2xl p-6">
                  <Zap className="w-10 h-10 text-white mx-auto mb-4" />
                  <h3 className="text-xl font-medium text-white mb-3">Reliable Service</h3>
                  <p className="text-white/80 text-sm">
                    Punctual, professional, and consistent service you can count on for every occasion.
                  </p>
                </div>
                
                <div className="bg-white/10 rounded-2xl p-6">
                  <Leaf className="w-10 h-10 text-white mx-auto mb-4" />
                  <h3 className="text-xl font-medium text-white mb-3">Cultural Respect</h3>
                  <p className="text-white/80 text-sm">
                    We honor and preserve the authentic traditions of Vedic spiritual practices.
                  </p>
                </div>
              </div>
            </div>
          </section>

          {/* Our Vision */}
          <section aria-labelledby="our-vision" className="py-10 lg:py-14">
            <div className="grid lg:grid-cols-2 gap-16 items-center">
              <div>
                <h2 id="our-vision" className="font-serif text-3xl lg:text-4xl font-bold tracking-tight text-foreground mb-6">
                  Our Vision
                </h2>
                <p className="text-muted-foreground mb-5 text-base leading-relaxed">
                  To become Delhi's most trusted spiritual wellness platform, recognized for our unwavering commitment to authenticity, professionalism, and cultural preservation.
                </p>
                <p className="text-muted-foreground mb-5 text-base leading-relaxed">
                  We envision a future where every family in Delhi NCR has easy access to certified Vedic pandits who can guide them through life's important spiritual moments with wisdom and grace.
                </p>
                <p className="text-muted-foreground text-base leading-relaxed">
                  Through innovation and tradition, we're building a bridge between ancient Vedic knowledge and modern urban living, ensuring these precious traditions continue to thrive.
                </p>
              </div>
              <div>
                <div className="bg-muted/50 rounded-xl h-80 flex items-center justify-center shadow-sm border border-border/30 overflow-hidden">
                  <img 
                    src="https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_db05ba7c437e48abe6440798d2a33b09dr_bokdku.jpg" 
                    alt="Navdhya Vision" 
                    className="w-full h-full object-cover"
                  />
                </div>
              </div>
            </div>
          </section>

          {/* Values in Action */}
          <section aria-labelledby="values-in-action" className="py-10 lg:py-14">
            <div className="text-center mb-16">
              <h2 id="values-in-action" className="font-serif text-3xl lg:text-4xl font-bold tracking-tight text-foreground mb-6">
                Values in Action
              </h2>
              <p className="text-muted-foreground max-w-3xl mx-auto text-lg">
                How we bring our principles to life in everything we do.
              </p>
            </div>
            
            <div className="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
              <div className="bg-card rounded-2xl p-8 border border-border hover:shadow-md transition-all duration-300">
                <Target className="w-10 h-10 text-primary mb-4" />
                <h3 className="text-xl font-medium text-foreground mb-3">Quality Assurance</h3>
                <p className="text-muted-foreground">
                  Every pandit in our network is verified for certification, experience, and customer satisfaction. We conduct regular quality checks to ensure consistent excellence.
                </p>
              </div>
              
              <div className="bg-card rounded-2xl p-8 border border-border hover:shadow-md transition-all duration-300">
                <ShieldCheck className="w-10 h-10 text-primary mb-4" />
                <h3 className="text-xl font-medium text-foreground mb-3">Secure & Transparent</h3>
                <p className="text-muted-foreground">
                  Clear pricing, secure booking, and transparent processes. We protect your personal information and provide complete visibility into our services.
                </p>
              </div>
              
              <div className="bg-card rounded-2xl p-8 border border-border hover:shadow-md transition-all duration-300">
                <Users className="w-10 h-10 text-primary mb-4" />
                <h3 className="text-xl font-medium text-foreground mb-3">Community Focused</h3>
                <p className="text-muted-foreground">
                  We're deeply rooted in the Delhi community and committed to supporting local temples, artisans, and spiritual practitioners.
                </p>
              </div>
              
              <div className="bg-card rounded-2xl p-8 border border-border hover:shadow-md transition-all duration-300">
                <Zap className="w-10 h-10 text-primary mb-4" />
                <h3 className="text-xl font-medium text-foreground mb-3">Innovation with Tradition</h3>
                <p className="text-muted-foreground">
                  We use modern technology to make traditional services more accessible while preserving their sacred authenticity.
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
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}

import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Generate page metadata using SEO manager
export const metadata = seoManager.generateMetadata({
  title: "Kundli Analysis, Birth Chart Reading & Remedies",
  description: "Get kundli analysis, birth chart interpretation, and practical Vedic remedies from Navdhya for career, marriage, health, and timing questions.",
  path: "/astrology/kundli",
  keywords: [
    "kundli analysis",
    "birth chart",
    "astrology consultation",
    "vedic astrology",
    "janam kundli",
    "horoscope reading"
  ]
})

// Schema markup for the Kundli Analysis page
const KUNDLI_SCHEMA = seoManager.generateSchema({
  type: "Service",
  name: "Kundli Analysis - Navdhya",
  description: "Get expert interpretation of your birth chart for life guidance. Our certified astrologers provide detailed kundli analysis for career, marriage, and health predictions.",
  path: "/astrology/kundli",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Astrology Services", url: "https://www.navdhya.com/astrology" },
    { name: "Kundli Analysis", url: "https://www.navdhya.com/astrology/kundli" }
  ]
})

export default async function KundliAnalysisPage() {
  const kundliSchema = JSON.stringify(KUNDLI_SCHEMA)
  
  // Resolve context using the new resolver
  const context = await resolveContext('/astrology/kundli');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: kundliSchema }}
        />
        <div className="min-h-screen bg-background">
          {/* Breadcrumb */}
          <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
            <nav className="text-xs text-muted-foreground">
              <a href="/" className="hover:text-foreground">Home</a>
              <span className="mx-1.5">/</span>
              <a href="/astrology" className="hover:text-foreground">Astrology Services</a>
              <span className="mx-1.5">/</span>
              <span className="text-foreground">Kundli Analysis</span>
            </nav>
          </div>

          {/* Hero Banner */}
          <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-8">
            <div className="relative rounded-lg overflow-hidden bg-cover bg-center flex items-center h-80"
              style={{
                backgroundImage: "url('https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg')"
              }}
            >
              <div className="absolute inset-0 bg-gradient-to-r from-black/80 to-black/40"></div>
              <div className="relative z-10 text-white p-8 max-w-3xl">
                <div className="inline-block bg-white/20 backdrop-blur-sm text-white px-3 py-1 rounded-full text-sm font-medium mb-4">
                  <span className="inline mr-1">🔮</span>
                  Astrology Service
                </div>
                <h1 className="text-3xl font-serif font-semibold mb-4">Kundli Analysis</h1>
                <p className="text-lg mb-6">
                  Get expert interpretation of your birth chart for comprehensive life guidance. Our certified astrologers provide detailed kundli analysis for career, marriage, and health predictions.
                </p>
                <a 
                  href="/book"
                  className="inline-flex items-center gap-2 bg-white text-[#0E3F2E] hover:bg-gray-100 font-medium py-3 px-6 rounded-lg transition-all duration-300 shadow-sm"
                >
                  <span>Book Consultation</span>
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                  </svg>
                </a>
              </div>
            </div>
          </div>

          {/* Stats Bar */}
          <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-10">
            <div className="bg-card border border-border rounded-lg p-6 grid grid-cols-2 md:grid-cols-4 gap-6">
              <div className="text-center">
                <div className="text-2xl font-bold text-[#0E3F2E] mb-2">10000+</div>
                <div className="text-sm text-muted-foreground">Kundlis Analyzed</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold text-[#0E3F2E] mb-2">4.9/5</div>
                <div className="text-sm text-muted-foreground">Average Rating</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold text-[#0E3F2E] mb-2">150+</div>
                <div className="text-sm text-muted-foreground">Certified Astrologers</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold text-[#0E3F2E] mb-2">15+</div>
                <div className="text-sm text-muted-foreground">Years of Service</div>
              </div>
            </div>
          </div>

          {/* How It Works */}
          <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
            <div className="text-center mb-10">
              <h2 className="text-2xl font-serif font-semibold mb-4">How Kundli Analysis Works</h2>
              <p className="text-muted-foreground max-w-2xl mx-auto">
                Simple steps to unlock the secrets of your birth chart
              </p>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="border border-border rounded-lg p-6 bg-card text-center">
                <div className="w-12 h-12 rounded-full bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                  </svg>
                </div>
                <h3 className="font-medium text-lg mb-2">1. Provide Birth Details</h3>
                <p className="text-sm text-muted-foreground">
                  Share your date, time, and place of birth for accurate chart calculation.
                </p>
              </div>
              
              <div className="border border-border rounded-lg p-6 bg-card text-center">
                <div className="w-12 h-12 rounded-full bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                  </svg>
                </div>
                <h3 className="font-medium text-lg mb-2">2. Expert Analysis</h3>
                <p className="text-sm text-muted-foreground">
                  Our certified astrologers analyze your kundli using Vedic principles.
                </p>
              </div>
              
              <div className="border border-border rounded-lg p-6 bg-card text-center">
                <div className="w-12 h-12 rounded-full bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                  </svg>
                </div>
                <h3 className="font-medium text-lg mb-2">3. Detailed Report</h3>
                <p className="text-sm text-muted-foreground">
                  Receive a comprehensive report with personalized guidance and remedies.
                </p>
              </div>
            </div>
          </div>

          {/* Kundli Analysis Packages */}
          <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
            <div className="text-center mb-10">
              <h2 className="text-2xl font-serif font-semibold mb-4">Analysis Packages</h2>
              <p className="text-muted-foreground max-w-2xl mx-auto">
                Choose the perfect package for your astrological needs
              </p>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="border border-border rounded-lg overflow-hidden bg-card flex flex-col h-full">
                <div className="p-6 flex-grow">
                  <h3 className="font-medium text-lg mb-2">Basic Analysis</h3>
                  <div className="mb-4">
                    <span className="text-2xl font-bold text-[#0E3F2E]">₹999</span>
                  </div>
                  <p className="text-sm text-muted-foreground mb-4">Essential kundli reading with key predictions</p>
                  
                  <ul className="space-y-2 mb-6">
                    <li className="flex items-start gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4 h-4 text-[#0E3F2E] mt-0.5 flex-shrink-0">
                        <polyline points="20 6 9 17 4 12"></polyline>
                      </svg>
                      <span className="text-sm">Birth chart calculation</span>
                    </li>
                    <li className="flex items-start gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4 h-4 text-[#0E3F2E] mt-0.5 flex-shrink-0">
                        <polyline points="20 6 9 17 4 12"></polyline>
                      </svg>
                      <span className="text-sm">Sun-Moon sign analysis</span>
                    </li>
                    <li className="flex items-start gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4 h-4 text-[#0E3F2E] mt-0.5 flex-shrink-0">
                        <polyline points="20 6 9 17 4 12"></polyline>
                      </svg>
                      <span className="text-sm">Basic planetary positions</span>
                    </li>
                    <li className="flex items-start gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4 h-4 text-[#0E3F2E] mt-0.5 flex-shrink-0">
                        <polyline points="20 6 9 17 4 12"></polyline>
                      </svg>
                      <span className="text-sm">General life predictions</span>
                    </li>
                  </ul>
                </div>
                
                <div className="px-6 py-4 bg-muted/50 border-t border-border">
                  <button className="w-full bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white font-medium py-2.5 rounded-md text-sm transition-all duration-300">
                    Select Package
                  </button>
                </div>
              </div>
              
              <div className="border-2 border-[#0E3F2E] rounded-lg overflow-hidden bg-card flex flex-col h-full relative ring-2 ring-[#0E3F2E]">
                <div className="absolute top-4 right-4 z-10">
                  <span className="bg-[#0E3F2E] text-white px-3 py-1 rounded-full text-xs font-medium">
                    Most Popular
                  </span>
                </div>
                
                <div className="p-6 flex-grow">
                  <h3 className="font-medium text-lg mb-2">Comprehensive Analysis</h3>
                  <div className="mb-4">
                    <span className="text-2xl font-bold text-[#0E3F2E]">₹2,499</span>
                    <span className="text-sm text-muted-foreground line-through ml-2">₹3,499</span>
                  </div>
                  <p className="text-sm text-muted-foreground mb-4">Detailed kundli reading with in-depth predictions</p>
                  
                  <ul className="space-y-2 mb-6">
                    <li className="flex items-start gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4 h-4 text-[#0E3F2E] mt-0.5 flex-shrink-0">
                        <polyline points="20 6 9 17 4 12"></polyline>
                      </svg>
                      <span className="text-sm">Complete birth chart with dasha periods</span>
                    </li>
                    <li className="flex items-start gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4 h-4 text-[#0E3F2E] mt-0.5 flex-shrink-0">
                        <polyline points="20 6 9 17 4 12"></polyline>
                      </svg>
                      <span className="text-sm">Detailed planetary analysis</span>
                    </li>
                    <li className="flex items-start gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4 h-4 text-[#0E3F2E] mt-0.5 flex-shrink-0">
                        <polyline points="20 6 9 17 4 12"></polyline>
                      </svg>
                      <span className="text-sm">Career and financial predictions</span>
                    </li>
                    <li className="flex items-start gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4 h-4 text-[#0E3F2E] mt-0.5 flex-shrink-0">
                        <polyline points="20 6 9 17 4 12"></polyline>
                      </svg>
                      <span className="text-sm">Marriage and relationship analysis</span>
                    </li>
                    <li className="flex items-start gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4 h-4 text-[#0E3F2E] mt-0.5 flex-shrink-0">
                        <polyline points="20 6 9 17 4 12"></polyline>
                      </svg>
                      <span className="text-sm">Health predictions and remedies</span>
                    </li>
                    <li className="flex items-start gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4 h-4 text-[#0E3F2E] mt-0.5 flex-shrink-0">
                        <polyline points="20 6 9 17 4 12"></polyline>
                      </svg>
                      <span className="text-sm">Personalized remedies</span>
                    </li>
                  </ul>
                </div>
                
                <div className="px-6 py-4 bg-muted/50 border-t border-border">
                  <button className="w-full bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white font-medium py-2.5 rounded-md text-sm transition-all duration-300">
                    Select Package
                  </button>
                </div>
              </div>
              
              <div className="border border-border rounded-lg overflow-hidden bg-card flex flex-col h-full">
                <div className="p-6 flex-grow">
                  <h3 className="font-medium text-lg mb-2">Advanced Analysis</h3>
                  <div className="mb-4">
                    <span className="text-2xl font-bold text-[#0E3F2E]">₹4,999</span>
                    <span className="text-sm text-muted-foreground line-through ml-2">₹6,999</span>
                  </div>
                  <p className="text-sm text-muted-foreground mb-4">Extensive kundli reading with multiple consultations</p>
                  
                  <ul className="space-y-2 mb-6">
                    <li className="flex items-start gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4 h-4 text-[#0E3F2E] mt-0.5 flex-shrink-0">
                        <polyline points="20 6 9 17 4 12"></polyline>
                      </svg>
                      <span className="text-sm">All features from Comprehensive package</span>
                    </li>
                    <li className="flex items-start gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4 h-4 text-[#0E3F2E] mt-0.5 flex-shrink-0">
                        <polyline points="20 6 9 17 4 12"></polyline>
                      </svg>
                      <span className="text-sm">Yearly predictions and transit analysis</span>
                    </li>
                    <li className="flex items-start gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4 h-4 text-[#0E3F2E] mt-0.5 flex-shrink-0">
                        <polyline points="20 6 9 17 4 12"></polyline>
                      </svg>
                      <span className="text-sm">3 follow-up consultations</span>
                    </li>
                    <li className="flex items-start gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4 h-4 text-[#0E3F2E] mt-0.5 flex-shrink-0">
                        <polyline points="20 6 9 17 4 12"></polyline>
                      </svg>
                      <span className="text-sm">Personalized gemstone recommendations</span>
                    </li>
                    <li className="flex items-start gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4 h-4 text-[#0E3F2E] mt-0.5 flex-shrink-0">
                        <polyline points="20 6 9 17 4 12"></polyline>
                      </svg>
                      <span className="text-sm">Vastu consultation for home/office</span>
                    </li>
                  </ul>
                </div>
                
                <div className="px-6 py-4 bg-muted/50 border-t border-border">
                  <button className="w-full bg-background border border-border text-foreground hover:bg-muted font-medium py-2.5 rounded-md text-sm transition-all duration-300">
                    Select Package
                  </button>
                </div>
              </div>
            </div>
          </div>

          {/* Testimonials */}
          <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
            <div className="text-center mb-10">
              <h2 className="text-2xl font-serif font-semibold mb-4">What Our Customers Say</h2>
              <p className="text-muted-foreground max-w-2xl mx-auto">
                Hear from people who have benefited from our kundli analysis services
              </p>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="border border-border rounded-lg p-6 bg-card">
                <div className="flex items-center gap-1 mb-4">
                  {[...Array(5)].map((_, i) => (
                    <svg key={i} xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`w-4 h-4 ${i < 5 ? 'text-yellow-500 fill-current' : 'text-gray-300'}`}>
                      <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                  ))}
                </div>
                <p className="text-sm text-muted-foreground mb-4">
                  "The kundli analysis was incredibly detailed and accurate. The astrologer explained everything clearly and provided practical remedies that have helped me in my career."
                </p>
                <div className="flex items-center justify-between">
                  <div>
                    <div className="font-medium text-sm">Priya Sharma</div>
                    <div className="text-xs text-muted-foreground">Mumbai</div>
                  </div>
                  <div className="text-xs text-muted-foreground">
                    Oct 15, 2025
                  </div>
                </div>
              </div>
              
              <div className="border border-border rounded-lg p-6 bg-card">
                <div className="flex items-center gap-1 mb-4">
                  {[...Array(5)].map((_, i) => (
                    <svg key={i} xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`w-4 h-4 ${i < 5 ? 'text-yellow-500 fill-current' : 'text-gray-300'}`}>
                      <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                  ))}
                </div>
                <p className="text-sm text-muted-foreground mb-4">
                  "I was skeptical at first, but the predictions about my marriage timing were spot on. The remedies suggested have brought positive changes in my relationship."
                </p>
                <div className="flex items-center justify-between">
                  <div>
                    <div className="font-medium text-sm">Rajesh Kumar</div>
                    <div className="text-xs text-muted-foreground">Delhi</div>
                  </div>
                  <div className="text-xs text-muted-foreground">
                    Sep 22, 2025
                  </div>
                </div>
              </div>
              
              <div className="border border-border rounded-lg p-6 bg-card">
                <div className="flex items-center gap-1 mb-4">
                  {[...Array(5)].map((_, i) => (
                    <svg key={i} xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`w-4 h-4 ${i < 4 ? 'text-yellow-500 fill-current' : 'text-gray-300'}`}>
                      <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                  ))}
                </div>
                <p className="text-sm text-muted-foreground mb-4">
                  "The health predictions in my report helped me take preventive measures. The astrologer was knowledgeable and patient in answering all my questions."
                </p>
                <div className="flex items-center justify-between">
                  <div>
                    <div className="font-medium text-sm">Anita Desai</div>
                    <div className="text-xs text-muted-foreground">Bangalore</div>
                  </div>
                  <div className="text-xs text-muted-foreground">
                    Nov 5, 2025
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* FAQ Section */}
          <div className="max-w-7xl mx-auto px-4 sm:px-6 pb-12">
            <div className="text-center mb-10">
              <h2 className="text-2xl font-serif font-semibold mb-4">Frequently Asked Questions</h2>
              <p className="text-muted-foreground max-w-2xl mx-auto">
                Find answers to common questions about our kundli analysis services
              </p>
            </div>
            
            <div className="max-w-3xl mx-auto space-y-4">
              <div className="bg-card border border-border rounded-lg overflow-hidden transition-all duration-200 hover:shadow-sm">
                <button className="flex justify-between items-center w-full p-4 text-left focus:outline-none">
                  <h3 className="text-sm font-medium text-foreground">What information do you need for kundli analysis?</h3>
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-5 h-5 text-muted-foreground transition-transform duration-200">
                    <polyline points="6 9 12 15 18 9"></polyline>
                  </svg>
                </button>
                <div className="overflow-hidden transition-all duration-200 ease-in-out max-h-0 opacity-0">
                  <div className="px-4 text-muted-foreground text-sm pb-4">
                    <p>We require your exact date of birth (day, month, year), time of birth, and place of birth. The more accurate this information, the more precise your kundli analysis will be.</p>
                  </div>
                </div>
              </div>
              
              <div className="bg-card border border-border rounded-lg overflow-hidden transition-all duration-200 hover:shadow-sm">
                <button className="flex justify-between items-center w-full p-4 text-left focus:outline-none">
                  <h3 className="text-sm font-medium text-foreground">How long does it take to receive the analysis?</h3>
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-5 h-5 text-muted-foreground transition-transform duration-200">
                    <polyline points="6 9 12 15 18 9"></polyline>
                  </svg>
                </button>
                <div className="overflow-hidden transition-all duration-200 ease-in-out max-h-0 opacity-0">
                  <div className="px-4 text-muted-foreground text-sm pb-4">
                    <p>Basic analysis reports are delivered within 2-3 business days. Comprehensive and Advanced packages may take 3-5 business days due to the detailed nature of the analysis.</p>
                  </div>
                </div>
              </div>
              
              <div className="bg-card border border-border rounded-lg overflow-hidden transition-all duration-200 hover:shadow-sm">
                <button className="flex justify-between items-center w-full p-4 text-left focus:outline-none">
                  <h3 className="text-sm font-medium text-foreground">Can I get a consultation after receiving my report?</h3>
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-5 h-5 text-muted-foreground transition-transform duration-200">
                    <polyline points="6 9 12 15 18 9"></polyline>
                  </svg>
                </button>
                <div className="overflow-hidden transition-all duration-200 ease-in-out max-h-0 opacity-0">
                  <div className="px-4 text-muted-foreground text-sm pb-4">
                    <p>Yes, all our packages include consultation sessions. The Basic package includes one 30-minute session, Comprehensive includes one 60-minute session, and Advanced includes three 45-minute follow-up sessions.</p>
                  </div>
                </div>
              </div>
              
              <div className="bg-card border border-border rounded-lg overflow-hidden transition-all duration-200 hover:shadow-sm">
                <button className="flex justify-between items-center w-full p-4 text-left focus:outline-none">
                  <h3 className="text-sm font-medium text-foreground">Are remedies really effective?</h3>
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-5 h-5 text-muted-foreground transition-transform duration-200">
                    <polyline points="6 9 12 15 18 9"></polyline>
                  </svg>
                </button>
                <div className="overflow-hidden transition-all duration-200 ease-in-out max-h-0 opacity-0">
                  <div className="px-4 text-muted-foreground text-sm pb-4">
                    <p>Remedies are based on ancient Vedic principles and have helped many of our clients. Their effectiveness can vary from person to person, but when followed sincerely, they can bring positive changes in one's life.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}

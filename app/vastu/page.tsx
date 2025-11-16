import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"

// Generate page metadata using SEO manager
export const metadata = seoManager.generateMetadata({
  title: "Vastu Consultation",
  description: "Expert Vastu consultation for homes and businesses to create harmonious environments that promote health, wealth, and happiness.",
  path: "/vastu",
  keywords: [
    "vastu consultation",
    "vastu shastra",
    "home vastu",
    "office vastu",
    "vastu expert",
    "direction principles"
  ]
})

// Schema markup for the Vastu page
const VASTU_SCHEMA = seoManager.generateSchema({
  type: "Service",
  name: "Vastu Consultation - Navdhya",
  description: "Expert Vastu consultation for homes and businesses to create harmonious environments that promote health, wealth, and happiness.",
  path: "/vastu",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Vastu Consultation", url: "https://www.navdhya.com/vastu" }
  ]
})

export default function VastuPage() {
  const vastuSchema = JSON.stringify(VASTU_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: vastuSchema }}
      />
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <a href="/" className="hover:text-foreground">Home</a>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">Vastu Consultation</span>
          </nav>
        </div>

        {/* Hero Banner */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-8">
          <div className="relative rounded-lg overflow-hidden bg-cover bg-center flex items-center h-80"
            style={{
              backgroundImage: "url('https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg')"
            }}
          >
            <div className="absolute inset-0 bg-gradient-to-r from-black/80 to-black/40"></div>
            <div className="relative z-10 text-white p-8 max-w-3xl">
              <div className="inline-block bg-white/20 backdrop-blur-sm text-white px-3 py-1 rounded-full text-sm font-medium mb-4">
                <span className="inline mr-1">🏠</span>
                Vastu Service
              </div>
              <h1 className="text-3xl font-serif font-semibold mb-4">Vastu Consultation</h1>
              <p className="text-lg mb-6">
                Expert Vastu consultation for homes and businesses to create harmonious environments that promote health, wealth, and happiness.
              </p>
              <a 
                href="/booking"
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
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">5000+</div>
              <div className="text-sm text-muted-foreground">Vastu Consultations</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">4.8/5</div>
              <div className="text-sm text-muted-foreground">Average Rating</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">100+</div>
              <div className="text-sm text-muted-foreground">Certified Experts</div>
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
            <h2 className="text-2xl font-serif font-semibold mb-4">How Vastu Consultation Works</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Simple steps to harmonize your living or working space
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div className="border border-border rounded-lg p-6 bg-card text-center">
              <div className="w-12 h-12 rounded-full bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                  <circle cx="12" cy="10" r="3"></circle>
                </svg>
              </div>
              <h3 className="font-medium text-lg mb-2">1. Schedule Visit</h3>
              <p className="text-sm text-muted-foreground">
                Book an appointment for our expert to visit your property.
              </p>
            </div>
            
            <div className="border border-border rounded-lg p-6 bg-card text-center">
              <div className="w-12 h-12 rounded-full bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path>
                </svg>
              </div>
              <h3 className="font-medium text-lg mb-2">2. Property Analysis</h3>
              <p className="text-sm text-muted-foreground">
                Our expert analyzes the layout, direction, and energy flow.
              </p>
            </div>
            
            <div className="border border-border rounded-lg p-6 bg-card text-center">
              <div className="w-12 h-12 rounded-full bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path>
                </svg>
              </div>
              <h3 className="font-medium text-lg mb-2">3. Detailed Report</h3>
              <p className="text-sm text-muted-foreground">
                Receive a comprehensive report with personalized recommendations.
              </p>
            </div>
            
            <div className="border border-border rounded-lg p-6 bg-card text-center">
              <div className="w-12 h-12 rounded-full bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
              </div>
              <h3 className="font-medium text-lg mb-2">4. Implementation</h3>
              <p className="text-sm text-muted-foreground">
                Implement remedies to create a harmonious environment.
              </p>
            </div>
          </div>
        </div>

        {/* Directional Principles */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">Vastu Directional Principles</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Understanding the significance of directions in Vastu Shastra
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div className="border border-border rounded-lg p-6 bg-card">
              <div className="w-10 h-10 rounded-lg bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mb-4">
                <span>⬆️</span>
              </div>
              <h3 className="font-medium text-lg mb-2">North (Kubera)</h3>
              <p className="text-sm text-muted-foreground">
                Direction of wealth and prosperity. Ideal for main entrance and financial activities.
              </p>
            </div>
            
            <div className="border border-border rounded-lg p-6 bg-card">
              <div className="w-10 h-10 rounded-lg bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mb-4">
                <span>↗️</span>
              </div>
              <h3 className="font-medium text-lg mb-2">North-East (Isha)</h3>
              <p className="text-sm text-muted-foreground">
                Direction of knowledge and spirituality. Best for prayer room and study area.
              </p>
            </div>
            
            <div className="border border-border rounded-lg p-6 bg-card">
              <div className="w-10 h-10 rounded-lg bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mb-4">
                <span>➡️</span>
              </div>
              <h3 className="font-medium text-lg mb-2">East (Indra)</h3>
              <p className="text-sm text-muted-foreground">
                Direction of health and new beginnings. Perfect for main entrance and morning activities.
              </p>
            </div>
            
            <div className="border border-border rounded-lg p-6 bg-card">
              <div className="w-10 h-10 rounded-lg bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mb-4">
                <span>↘️</span>
              </div>
              <h3 className="font-medium text-lg mb-2">South-East (Agni)</h3>
              <p className="text-sm text-muted-foreground">
                Direction of fire and energy. Ideal for kitchen and electrical systems.
              </p>
            </div>
            
            <div className="border border-border rounded-lg p-6 bg-card">
              <div className="w-10 h-10 rounded-lg bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mb-4">
                <span>⬇️</span>
              </div>
              <h3 className="font-medium text-lg mb-2">South (Yama)</h3>
              <p className="text-sm text-muted-foreground">
                Direction of strength and stability. Suitable for storage and heavy items.
              </p>
            </div>
            
            <div className="border border-border rounded-lg p-6 bg-card">
              <div className="w-10 h-10 rounded-lg bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mb-4">
                <span>↙️</span>
              </div>
              <h3 className="font-medium text-lg mb-2">South-West (Niruthi)</h3>
              <p className="text-sm text-muted-foreground">
                Direction of stability and support. Best for master bedroom and heavy furniture.
              </p>
            </div>
            
            <div className="border border-border rounded-lg p-6 bg-card">
              <div className="w-10 h-10 rounded-lg bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mb-4">
                <span>⬅️</span>
              </div>
              <h3 className="font-medium text-lg mb-2">West (Varuna)</h3>
              <p className="text-sm text-muted-foreground">
                Direction of creativity and children. Perfect for children's room and creative activities.
              </p>
            </div>
            
            <div className="border border-border rounded-lg p-6 bg-card">
              <div className="w-10 h-10 rounded-lg bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mb-4">
                <span>↖️</span>
              </div>
              <h3 className="font-medium text-lg mb-2">North-West (Vayu)</h3>
              <p className="text-sm text-muted-foreground">
                Direction of communication and travel. Ideal for living room and communication devices.
              </p>
            </div>
          </div>
        </div>

        {/* Vastu Packages */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">Consultation Packages</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Choose the perfect package for your Vastu needs
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="border border-border rounded-lg overflow-hidden bg-card flex flex-col h-full">
              <div className="p-6 flex-grow">
                <h3 className="font-medium text-lg mb-2">Basic Consultation</h3>
                <div className="mb-4">
                  <span className="text-2xl font-bold text-[#0E3F2E]">₹1,999</span>
                </div>
                <p className="text-sm text-muted-foreground mb-4">Essential Vastu analysis for your property</p>
                
                <ul className="space-y-2 mb-6">
                  <li className="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4 h-4 text-[#0E3F2E] mt-0.5 flex-shrink-0">
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span className="text-sm">Property layout analysis</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4 h-4 text-[#0E3F2E] mt-0.5 flex-shrink-0">
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span className="text-sm">Directional assessment</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4 h-4 text-[#0E3F2E] mt-0.5 flex-shrink-0">
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span className="text-sm">Basic remedy suggestions</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4 h-4 text-[#0E3F2E] mt-0.5 flex-shrink-0">
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span className="text-sm">30-minute consultation</span>
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
                <h3 className="font-medium text-lg mb-2">Comprehensive Consultation</h3>
                <div className="mb-4">
                  <span className="text-2xl font-bold text-[#0E3F2E]">₹4,999</span>
                  <span className="text-sm text-muted-foreground line-through ml-2">₹6,999</span>
                </div>
                <p className="text-sm text-muted-foreground mb-4">Detailed Vastu analysis with personalized remedies</p>
                
                <ul className="space-y-2 mb-6">
                  <li className="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4 h-4 text-[#0E3F2E] mt-0.5 flex-shrink-0">
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span className="text-sm">Complete property analysis</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4 h-4 text-[#0E3F2E] mt-0.5 flex-shrink-0">
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span className="text-sm">Room-wise recommendations</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4 h-4 text-[#0E3F2E] mt-0.5 flex-shrink-0">
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span className="text-sm">Personalized remedy plan</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4 h-4 text-[#0E3F2E] mt-0.5 flex-shrink-0">
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span className="text-sm">60-minute detailed consultation</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4 h-4 text-[#0E3F2E] mt-0.5 flex-shrink-0">
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span className="text-sm">Follow-up support for 30 days</span>
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
                <h3 className="font-medium text-lg mb-2">Advanced Consultation</h3>
                <div className="mb-4">
                  <span className="text-2xl font-bold text-[#0E3F2E]">₹8,999</span>
                  <span className="text-sm text-muted-foreground line-through ml-2">₹12,999</span>
                </div>
                <p className="text-sm text-muted-foreground mb-4">Extensive Vastu analysis with multiple consultations</p>
                
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
                    <span className="text-sm">Pre-visit virtual consultation</span>
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
                    <span className="text-sm">Personalized yantra recommendations</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4 h-4 text-[#0E3F2E] mt-0.5 flex-shrink-0">
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span className="text-sm">60-day follow-up support</span>
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
              Hear from people who have benefited from our Vastu consultation services
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
                "After implementing the Vastu remedies suggested by Navdhya, there was a noticeable positive change in our business. The flow of customers increased significantly."
              </p>
              <div className="flex items-center justify-between">
                <div>
                  <div className="font-medium text-sm">Ramesh Patel</div>
                  <div className="text-xs text-muted-foreground">Ahmedabad</div>
                </div>
                <div className="text-xs text-muted-foreground">
                  Oct 10, 2025
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
                "The Vastu consultation for our new home was incredibly detailed. The expert pointed out issues we never would have noticed and provided practical solutions."
              </p>
              <div className="flex items-center justify-between">
                <div>
                  <div className="font-medium text-sm">Sunita Sharma</div>
                  <div className="text-xs text-muted-foreground">Pune</div>
                </div>
                <div className="text-xs text-muted-foreground">
                  Sep 28, 2025
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
                "Our family relationships improved significantly after following the Vastu recommendations. The expert was knowledgeable and explained everything clearly."
              </p>
              <div className="flex items-center justify-between">
                <div>
                  <div className="font-medium text-sm">Kavita Reddy</div>
                  <div className="text-xs text-muted-foreground">Hyderabad</div>
                </div>
                <div className="text-xs text-muted-foreground">
                  Nov 15, 2025
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
              Find answers to common questions about our Vastu consultation services
            </p>
          </div>
          
          <div className="max-w-3xl mx-auto space-y-4">
            <div className="bg-card border border-border rounded-lg overflow-hidden transition-all duration-200 hover:shadow-sm">
              <button className="flex justify-between items-center w-full p-4 text-left focus:outline-none">
                <h3 className="text-sm font-medium text-foreground">How long does a Vastu consultation take?</h3>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-5 h-5 text-muted-foreground transition-transform duration-200">
                  <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
              </button>
              <div className="overflow-hidden transition-all duration-200 ease-in-out max-h-0 opacity-0">
                <div className="px-4 text-muted-foreground text-sm pb-4">
                  <p>A typical Vastu consultation takes 1-2 hours depending on the size and complexity of your property. The expert will thoroughly analyze all aspects of your space.</p>
                </div>
              </div>
            </div>
            
            <div className="bg-card border border-border rounded-lg overflow-hidden transition-all duration-200 hover:shadow-sm">
              <button className="flex justify-between items-center w-full p-4 text-left focus:outline-none">
                <h3 className="text-sm font-medium text-foreground">Do you provide online Vastu consultations?</h3>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-5 h-5 text-muted-foreground transition-transform duration-200">
                  <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
              </button>
              <div className="overflow-hidden transition-all duration-200 ease-in-out max-h-0 opacity-0">
                <div className="px-4 text-muted-foreground text-sm pb-4">
                  <p>Yes, we offer both on-site and online consultations. For online consultations, we require detailed floor plans and photos of your property.</p>
                </div>
              </div>
            </div>
            
            <div className="bg-card border border-border rounded-lg overflow-hidden transition-all duration-200 hover:shadow-sm">
              <button className="flex justify-between items-center w-full p-4 text-left focus:outline-none">
                <h3 className="text-sm font-medium text-foreground">Are Vastu remedies expensive to implement?</h3>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-5 h-5 text-muted-foreground transition-transform duration-200">
                  <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
              </button>
              <div className="overflow-hidden transition-all duration-200 ease-in-out max-h-0 opacity-0">
                <div className="px-4 text-muted-foreground text-sm pb-4">
                  <p>Most Vastu remedies are cost-effective and focus on rearranging existing elements. When purchases are needed, our experts suggest budget-friendly options that align with your financial capacity.</p>
                </div>
              </div>
            </div>
            
            <div className="bg-card border border-border rounded-lg overflow-hidden transition-all duration-200 hover:shadow-sm">
              <button className="flex justify-between items-center w-full p-4 text-left focus:outline-none">
                <h3 className="text-sm font-medium text-foreground">How soon can I expect to see results after implementing Vastu remedies?</h3>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-5 h-5 text-muted-foreground transition-transform duration-200">
                  <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
              </button>
              <div className="overflow-hidden transition-all duration-200 ease-in-out max-h-0 opacity-0">
                <div className="px-4 text-muted-foreground text-sm pb-4">
                  <p>Results vary from person to person and situation to situation. Some people notice positive changes within a few weeks, while for others it may take a few months. Consistency in implementation is key.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  )
}
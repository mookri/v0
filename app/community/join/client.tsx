'use client'
import React, { useState } from 'react';
import Link from "next/link"
import Script from "next/script"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video, Search, MessageCircle, ThumbsUp, Reply, Flag, MoreHorizontal, User, Crown, Zap, Shield, Globe, CheckCircle, CreditCard, UserCheck } from "lucide-react"

interface MembershipPlan {
  id: number;
  name: string;
  price: string;
  period: string;
  description: string;
  features: string[];
  isPopular?: boolean;
  buttonText: string;
  buttonVariant: "primary" | "secondary";
}

interface Benefit {
  icon: React.ReactNode;
  title: string;
  description: string;
}

// Membership plans data
const membershipPlans: MembershipPlan[] = [
  {
    id: 1,
    name: "Seeker Circle",
    price: "Free",
    period: "Forever",
    description: "Perfect for those beginning their spiritual journey",
    features: [
      "Access to basic articles and resources",
      "Participation in community forums",
      "Monthly spiritual inspiration emails",
      "Basic event notifications"
    ],
    buttonText: "Join for Free",
    buttonVariant: "secondary"
  },
  {
    id: 2,
    name: "Devotee Circle",
    price: "₹299",
    period: "per month",
    description: "For committed practitioners seeking deeper engagement",
    features: [
      "All Seeker Circle benefits",
      "Access to exclusive video content",
      "Priority registration for events",
      "Monthly guided meditation sessions",
      "Personalized spiritual reminders",
      "Community discussion participation"
    ],
    isPopular: true,
    buttonText: "Join Devotee Circle",
    buttonVariant: "primary"
  },
  {
    id: 3,
    name: "Sage Circle",
    price: "₹899",
    period: "per month",
    description: "Premium access for dedicated spiritual seekers",
    features: [
      "All Devotee Circle benefits",
      "One-on-one consultation credits",
      "Exclusive workshops and masterclasses",
      "Early access to new resources",
      "Personalized ritual recommendations",
      "Priority support from pandits",
      "Access to advanced learning materials",
      "VIP event invitations"
    ],
    buttonText: "Join Sage Circle",
    buttonVariant: "secondary"
  }
];

const communityBenefits: Benefit[] = [
  {
    icon: <Users className="w-6 h-6" />,
    title: "Community Connection",
    description: "Connect with like-minded spiritual seekers from around the world"
  },
  {
    icon: <BookOpen className="w-6 h-6" />,
    title: "Exclusive Resources",
    description: "Access premium content not available to the general public"
  },
  {
    icon: <Zap className="w-6 h-6" />,
    title: "Personalized Guidance",
    description: "Receive tailored recommendations based on your spiritual path"
  },
  {
    icon: <Calendar className="w-6 h-6" />,
    title: "Priority Access",
    description: "Get early access to events, workshops, and special programs"
  },
  {
    icon: <Shield className="w-6 h-6" />,
    title: "Verified Experts",
    description: "Connect with certified pandits and spiritual teachers"
  },
  {
    icon: <Globe className="w-6 h-6" />,
    title: "Global Network",
    description: "Be part of an international community of practitioners"
  }
];

export default function JoinClient({ joinSchema }: { joinSchema: string }) {
  const [selectedPlan, setSelectedPlan] = useState<number>(2); // Default to Devotee Circle
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: joinSchema }}
      />
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <Link href="/" className="hover:text-foreground">Home</Link>
            <span className="mx-1.5">/</span>
            <Link href="/community" className="hover:text-foreground">Community</Link>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">Join Navdhya</span>
          </nav>
        </div>

        {/* Hero Banner */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div 
            className="relative h-64 rounded-lg overflow-hidden bg-cover bg-center flex items-center"
            style={{
              backgroundImage: "url('https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg')"
            }}
          >
            <div className="absolute inset-0 bg-gradient-to-r from-black/80 to-black/40"></div>
            <div className="relative z-10 text-white p-8 max-w-3xl">
              <h1 className="text-3xl font-serif font-semibold mb-4">Join Navdhya Circle</h1>
              <p className="text-lg mb-6">
                Circle Membership gateway to exclusive spiritual resources, personalized guidance, and a supportive community of like-minded seekers.
              </p>
              <Link 
                href="#plans"
                className="inline-flex items-center gap-2 bg-white text-[#0E3F2E] hover:bg-gray-100 font-medium py-2 px-6 rounded-md transition-all duration-300 shadow-sm"
              >
                <span>Explore Membership Plans</span>
                <ArrowRight className="w-4 h-4" />
              </Link>
            </div>
          </div>
        </div>

        {/* Stats Bar */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-10">
          <div className="bg-card border border-border rounded-lg p-6 grid grid-cols-2 md:grid-cols-4 gap-6">
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">15K+</div>
              <div className="text-sm text-muted-foreground">Circle Members</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">500+</div>
              <div className="text-sm text-muted-foreground">Exclusive Resources</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">4.9/5</div>
              <div className="text-sm text-muted-foreground">Member Satisfaction</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-[#0E3F2E] mb-2">25+</div>
              <div className="text-sm text-muted-foreground">Expert Mentors</div>
            </div>
          </div>
        </div>

        {/* Community Benefits */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">Why Join Our Community?</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Being part of the Navdhya Circle means joining a supportive network of spiritual seekers dedicated to growth and transformation.
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {communityBenefits.map((benefit, index) => (
              <div key={index} className="border border-border rounded-lg p-6 bg-card hover:shadow-lg transition-all duration-300">
                <div className="w-12 h-12 rounded-lg bg-[#0E3F2E]/10 text-[#0E3F2E] flex items-center justify-center mb-4">
                  {benefit.icon}
                </div>
                <h3 className="font-medium text-lg mb-2">{benefit.title}</h3>
                <p className="text-sm text-muted-foreground">
                  {benefit.description}
                </p>
              </div>
            ))}
          </div>
        </div>

        {/* Testimonials */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">What Our Members Say</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Hear from members of our spiritual community about their experiences and transformations.
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="border border-border rounded-lg p-6 bg-card">
              <div className="flex items-center gap-1 mb-4">
                {[...Array(5)].map((_, i) => (
                  <Star key={i} className="w-4 h-4 text-yellow-500 fill-current" />
                ))}
              </div>
              <p className="text-sm text-muted-foreground mb-4">
                "The Devotee Circle has transformed my spiritual practice. The guided meditations and exclusive content have deepened my understanding significantly."
              </p>
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-full bg-muted flex items-center justify-center">
                  <span className="text-xs font-medium">SP</span>
                </div>
                <div>
                  <div className="text-sm font-medium">Sneha Patel</div>
                  <div className="text-xs text-muted-foreground">Devotee Circle Member</div>
                </div>
              </div>
            </div>
            
            <div className="border border-border rounded-lg p-6 bg-card">
              <div className="flex items-center gap-1 mb-4">
                {[...Array(5)].map((_, i) => (
                  <Star key={i} className="w-4 h-4 text-yellow-500 fill-current" />
                ))}
              </div>
              <p className="text-sm text-muted-foreground mb-4">
                "As a Sage Circle member, I've had incredible one-on-one sessions with verified pandits. The personalized guidance has been invaluable for my spiritual journey."
              </p>
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-full bg-muted flex items-center justify-center">
                  <span className="text-xs font-medium">RK</span>
                </div>
                <div>
                  <div className="text-sm font-medium">Rajesh Kumar</div>
                  <div className="text-xs text-muted-foreground">Sage Circle Member</div>
                </div>
              </div>
            </div>
            
            <div className="border border-border rounded-lg p-6 bg-card">
              <div className="flex items-center gap-1 mb-4">
                {[...Array(5)].map((_, i) => (
                  <Star key={i} className="w-4 h-4 text-yellow-500 fill-current" />
                ))}
              </div>
              <p className="text-sm text-muted-foreground mb-4">
                "The community forums in the Seeker Circle helped me connect with others on similar paths. It's comforting to know I'm not alone in this journey."
              </p>
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-full bg-muted flex items-center justify-center">
                  <span className="text-xs font-medium">MP</span>
                </div>
                <div>
                  <div className="text-sm font-medium">Meera Pillai</div>
                  <div className="text-xs text-muted-foreground">Seeker Circle Member</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Membership Plans */}
        <div id="plans" className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-serif font-semibold mb-4">Circle Membership Plans</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Choose the membership plan that best fits your spiritual journey and commitment level.
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {membershipPlans.map(plan => (
              <div 
                key={plan.id} 
                className={`border border-border rounded-lg overflow-hidden bg-card flex flex-col h-full relative ${
                  plan.isPopular ? 'ring-2 ring-[#0E3F2E] ring-offset-2' : ''
                }`}
              >
                {plan.isPopular && (
                  <div className="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                    <span className="px-4 py-1 bg-[#0E3F2E] text-white text-xs font-medium rounded-full flex items-center gap-1">
                      <Crown className="w-3 h-3" />
                      MOST POPULAR
                    </span>
                  </div>
                )}
                
                <div className="p-6 border-b border-border">
                  <h3 className="text-xl font-semibold mb-2">{plan.name}</h3>
                  <div className="mb-4">
                    <span className="text-3xl font-bold text-[#0E3F2E]">{plan.price}</span>
                    {plan.price !== "Free" && (
                      <span className="text-muted-foreground">/{plan.period}</span>
                    )}
                  </div>
                  <p className="text-sm text-muted-foreground">
                    {plan.description}
                  </p>
                </div>
                
                <div className="p-6 flex-grow">
                  <ul className="space-y-3 mb-6">
                    {plan.features.map((feature, index) => (
                      <li key={index} className="flex items-start gap-2">
                        <CheckCircle className="w-4 h-4 text-[#0E3F2E] mt-0.5 flex-shrink-0" />
                        <span className="text-sm">{feature}</span>
                      </li>
                    ))}
                  </ul>
                  
                  <button 
                    className={`w-full py-2 rounded-md text-sm font-medium transition-colors ${
                      plan.buttonVariant === "primary" 
                        ? 'bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white' 
                        : 'border border-border hover:bg-muted text-foreground'
                    }`}
                    onClick={() => setSelectedPlan(plan.id)}
                  >
                    {plan.buttonText}
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* FAQ Section */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
          <div className="bg-card border border-border rounded-lg p-6">
            <h2 className="text-2xl font-serif font-semibold mb-6 text-center">Frequently Asked Questions</h2>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h3 className="font-medium mb-2">What is included in each membership tier?</h3>
                <p className="text-sm text-muted-foreground">
                  Each tier builds upon the previous one. Seeker Circle provides basic access, Devotee Circle adds exclusive content and events, and Sage Circle includes personalized guidance and premium resources.
                </p>
              </div>
              
              <div>
                <h3 className="font-medium mb-2">Can I change my membership plan later?</h3>
                <p className="text-sm text-muted-foreground">
                  Yes, you can upgrade or downgrade your membership at any time. Changes take effect at the end of your current billing period.
                </p>
              </div>
              
              <div>
                <h3 className="font-medium mb-2">Is there a money-back guarantee?</h3>
                <p className="text-sm text-muted-foreground">
                  We offer a 30-day satisfaction guarantee. If you're not happy with your membership, contact us for a full refund.
                </p>
              </div>
              
              <div>
                <h3 className="font-medium mb-2">How do I access exclusive content?</h3>
                <p className="text-sm text-muted-foreground">
                  Once you join, you'll receive login credentials to access our member portal where all exclusive content is organized by category.
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* CTA Section */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 pb-12">
          <div className="bg-gradient-to-r from-[#0E3F2E] to-[#0E3F2E]/80 rounded-lg p-8 text-center">
            <h2 className="text-2xl font-serif font-semibold text-white mb-4">Ready to Begin Your Spiritual Journey?</h2>
            <p className="text-white/90 mb-6 max-w-2xl mx-auto">
              Join thousands of like-minded seekers in our community and gain access to exclusive resources, personalized guidance, and transformative experiences.
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <button className="px-6 py-3 bg-white text-[#0E3F2E] hover:bg-gray-100 font-medium rounded-md transition-colors">
                <div className="flex items-center gap-2">
                  <UserCheck className="w-5 h-5" />
                  <span>Create Your Account</span>
                </div>
              </button>
              <button className="px-6 py-3 border border-white text-white hover:bg-white/10 font-medium rounded-md transition-colors">
                <div className="flex items-center gap-2">
                  <CreditCard className="w-5 h-5" />
                  <span>Subscribe to Membership</span>
                </div>
              </button>
            </div>
          </div>
        </div>
      </div>
    </>
  )
}
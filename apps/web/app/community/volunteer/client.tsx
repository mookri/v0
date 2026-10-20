'use client'
import React, { useState } from 'react';
import Link from "next/link"
import Script from "next/script"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video, Search, MessageCircle, ThumbsUp, Reply, Flag, MoreHorizontal, User, Handshake, Target, Globe, CalendarDays } from "lucide-react"

interface VolunteerProgram {
  id: number;
  title: string;
  excerpt: string;
  organization: string;
  location: string;
  duration: string;
  commitment: string;
  volunteers: number;
  maxVolunteers: number;
  category: string;
  image: string;
  skills: string[];
}

// Volunteer programs data
const volunteerPrograms: VolunteerProgram[] = [
  {
    id: 1,
    title: "Teaching Spirituality in Rural Schools",
    excerpt: "Help us bring spiritual education to underprivileged children in rural areas through our structured curriculum.",
    organization: "Navdhya Education Initiative",
    location: "Uttar Pradesh, India",
    duration: "3 months",
    commitment: "Weekends",
    volunteers: 12,
    maxVolunteers: 20,
    category: "Education",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    skills: ["Teaching", "Communication", "Patience"]
  },
  {
    id: 2,
    title: "Temple Maintenance & Renovation",
    excerpt: "Assist in maintaining and renovating ancient temples to preserve our cultural heritage for future generations.",
    organization: "Heritage Conservation Trust",
    location: "Varanasi, India",
    duration: "Ongoing",
    commitment: "Flexible",
    volunteers: 28,
    maxVolunteers: 35,
    category: "Heritage",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg",
    skills: ["Construction", "Physical Work", "Teamwork"]
  },
  {
    id: 3,
    title: "Community Health & Wellness Camps",
    excerpt: "Organize and participate in health camps that provide basic medical checkups and wellness guidance.",
    organization: "Navdhya Health Initiative",
    location: "Rajasthan, India",
    duration: "6 months",
    commitment: "Monthly",
    volunteers: 18,
    maxVolunteers: 25,
    category: "Health",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    skills: ["Healthcare", "Organization", "Empathy"]
  },
  {
    id: 4,
    title: "Digital Content Creation for Spiritual Library",
    excerpt: "Create digital content, videos, and articles to expand our online spiritual resources library.",
    organization: "Navdhya Digital Media",
    location: "Remote",
    duration: "Flexible",
    commitment: "Weekly",
    volunteers: 15,
    maxVolunteers: 30,
    category: "Digital",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg",
    skills: ["Content Creation", "Video Editing", "Writing"]
  },
  {
    id: 5,
    title: "Organizing Spiritual Discourse Events",
    excerpt: "Help organize and manage events where spiritual leaders share wisdom with the community.",
    organization: "Navdhya Events Team",
    location: "Delhi, India",
    duration: "6 months",
    commitment: "Event-based",
    volunteers: 22,
    maxVolunteers: 40,
    category: "Events",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_db05ba7c437e48abe6440798d2a33b09dr_bokdku.jpg",
    skills: ["Event Management", "Coordination", "Leadership"]
  },
  {
    id: 6,
    title: "Environmental Conservation & Tree Plantation",
    excerpt: "Participate in eco-friendly initiatives that align with Vedic principles of environmental protection.",
    organization: "Green Vedic Initiative",
    location: "Multiple Locations",
    duration: "1 year",
    commitment: "Monthly",
    volunteers: 35,
    maxVolunteers: 50,
    category: "Environment",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg",
    skills: ["Environmental Awareness", "Physical Work", "Teamwork"]
  }
];

const categories = [
  "All", "Education", "Heritage", "Health", "Digital", "Events", "Environment"
];

const skills = [
  "Teaching", "Communication", "Event Management", "Content Creation", "Healthcare", "Construction", "Leadership", "Writing", "Video Editing"
];

export default function VolunteerClient({ volunteerSchema }: { volunteerSchema: string }) {
  const [searchTerm, setSearchTerm] = useState('')
  const [categoryFilter, setCategoryFilter] = useState("All");
  const [skillFilter, setSkillFilter] = useState("All Skills");
  
  const filteredPrograms = volunteerPrograms.filter(program => 
    (program.title.toLowerCase().includes(searchTerm.toLowerCase()) || 
     program.excerpt.toLowerCase().includes(searchTerm.toLowerCase()) ||
     program.organization.toLowerCase().includes(searchTerm.toLowerCase())) &&
    (categoryFilter === "All" || program.category === categoryFilter) &&
    (skillFilter === "All Skills" || program.skills.includes(skillFilter))
  );
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: volunteerSchema }}
      />
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <Link href="/" className="hover:text-foreground">Home</Link>
            <span className="mx-1.5">/</span>
            <Link href="/community" className="hover:text-foreground">Community</Link>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">Volunteer Programs</span>
          </nav>
        </div>

        {/* Hero Banner */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div 
            className="relative h-48 rounded-lg overflow-hidden bg-cover bg-center flex items-end"
            style={{
              backgroundImage: "url('https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg')"
            }}
          >
            <div className="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
            <div className="relative z-10 text-white p-4 max-w-2xl">
              <h1 className="text-2xl font-serif font-semibold">Volunteer Programs</h1>
              <p className="text-sm mt-1">
                CSR & initiatives that make a difference. Join our volunteer programs to contribute to community welfare and spiritual education.
              </p>
            </div>
          </div>
        </div>

        {/* Stats Bar */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div className="bg-card border border-border rounded-lg p-4 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">50+</div>
              <div className="text-xs text-muted-foreground">Active Programs</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">2000+</div>
              <div className="text-xs text-muted-foreground">Volunteers</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">4.9/5</div>
              <div className="text-xs text-muted-foreground">Program Rating</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">15+</div>
              <div className="text-xs text-muted-foreground">States Covered</div>
            </div>
          </div>
        </div>

        {/* Search and Filters */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div className="bg-card border border-border rounded-lg p-4">
            <div className="flex flex-col md:flex-row gap-4">
              {/* Search Bar */}
              <div className="relative flex-grow">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                <input
                  type="text"
                  placeholder="Search volunteer programs..."
                  className="w-full pl-10 pr-4 py-2 border border-border rounded-md bg-background focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                />
              </div>
              
              {/* Action Buttons */}
              <button className="px-4 py-2 bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white rounded-md text-sm font-medium transition-colors">
                Start Volunteering
              </button>
            </div>
            
            {/* Filters */}
            <div className="mt-4 pt-4 border-t border-border">
              <div className="flex flex-wrap gap-4">
                <div>
                  <div className="text-xs font-medium mb-2">Category</div>
                  <div className="flex flex-wrap gap-2">
                    {categories.map((category, index) => (
                      <button
                        key={index}
                        onClick={() => setCategoryFilter(category)}
                        className={`px-3 py-1.5 text-xs rounded-full transition-colors ${
                          categoryFilter === category
                            ? 'bg-[#0E3F2E] text-white' 
                            : 'bg-background hover:bg-muted border border-border text-foreground'
                        }`}
                      >
                        {category}
                      </button>
                    ))}
                  </div>
                </div>
                
                <div>
                  <div className="text-xs font-medium mb-2">Skills</div>
                  <div className="flex flex-wrap gap-2">
                    {skills.map((skill, index) => (
                      <button
                        key={index}
                        onClick={() => setSkillFilter(skill)}
                        className={`px-3 py-1.5 text-xs rounded-full transition-colors ${
                          skillFilter === skill
                            ? 'bg-[#0E3F2E] text-white' 
                            : 'bg-background hover:bg-muted border border-border text-foreground'
                        }`}
                      >
                        {skill}
                      </button>
                    ))}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Benefits Section */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-8">
          <div className="bg-card border border-border rounded-lg p-6">
            <h2 className="text-xl font-semibold mb-4">Why Volunteer With Us?</h2>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="flex items-start gap-3">
                <Handshake className="w-6 h-6 text-[#0E3F2E] mt-1 flex-shrink-0" />
                <div>
                  <h3 className="font-medium text-sm mb-1">Meaningful Impact</h3>
                  <p className="text-xs text-muted-foreground">Contribute to initiatives that create real positive change in communities</p>
                </div>
              </div>
              <div className="flex items-start gap-3">
                <Target className="w-6 h-6 text-[#0E3F2E] mt-1 flex-shrink-0" />
                <div>
                  <h3 className="font-medium text-sm mb-1">Skill Development</h3>
                  <p className="text-xs text-muted-foreground">Gain valuable experience and develop new skills through hands-on work</p>
                </div>
              </div>
              <div className="flex items-start gap-3">
                <Globe className="w-6 h-6 text-[#0E3F2E] mt-1 flex-shrink-0" />
                <div>
                  <h3 className="font-medium text-sm mb-1">Community Connection</h3>
                  <p className="text-xs text-muted-foreground">Connect with like-minded individuals passionate about social welfare</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Programs Grid */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 pb-10">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {filteredPrograms.map(program => (
              <div key={program.id} className="border border-border rounded-lg overflow-hidden bg-card flex flex-col h-full">
                <div className="bg-muted flex-shrink-0">
                  <img 
                    src={program.image} 
                    alt={program.title}
                    className="w-full h-40 object-cover"
                  />
                </div>
                
                <div className="p-4 space-y-3 flex flex-col flex-grow">
                  <div>
                    <span className="px-2 py-1 bg-[#0E3F2E]/10 text-[#0E3F2E] text-[10px] rounded-full">
                      {program.category}
                    </span>
                    <h3 className="font-medium hover:text-[#0E3F2E] transition-colors line-clamp-2 text-sm mt-2">
                      {program.title}
                    </h3>
                  </div>
                  
                  <p className="text-[11px] text-muted-foreground line-clamp-3 flex-grow">
                    {program.excerpt}
                  </p>
                  
                  <div className="space-y-2 text-[10px] text-muted-foreground">
                    <div className="flex items-center gap-2">
                      <Users className="w-3 h-3" />
                      <span>{program.organization}</span>
                    </div>
                    <div className="flex items-center gap-2">
                      <MapPin className="w-3 h-3" />
                      <span>{program.location}</span>
                    </div>
                    <div className="flex items-center gap-2">
                      <Calendar className="w-3 h-3" />
                      <span>{program.duration} • {program.commitment}</span>
                    </div>
                    <div className="flex items-center gap-2">
                      <Users className="w-3 h-3" />
                      <span>{program.volunteers}/{program.maxVolunteers} volunteers</span>
                    </div>
                  </div>
                  
                  <div className="pt-2">
                    <div className="flex flex-wrap gap-1 mb-3">
                      {program.skills.slice(0, 3).map((skill, index) => (
                        <span key={index} className="px-2 py-1 bg-muted text-foreground text-[10px] rounded-full">
                          {skill}
                        </span>
                      ))}
                      {program.skills.length > 3 && (
                        <span className="px-2 py-1 bg-muted text-foreground text-[10px] rounded-full">
                          +{program.skills.length - 3} more
                        </span>
                      )}
                    </div>
                  </div>
                  
                  <Link 
                    href={`/community/volunteer/${program.id}`}
                    className="inline-flex items-center justify-center gap-1 bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white font-medium py-2 px-3 rounded-md transition-all duration-300 text-center text-[11px]"
                  >
                    <span>Join Program</span>
                    <ArrowRight className="w-3 h-3" />
                  </Link>
                </div>
              </div>
            ))}
          </div>
          
          {filteredPrograms.length === 0 && (
            <div className="text-center py-10">
              <Handshake className="w-12 h-12 text-muted-foreground mx-auto mb-4" />
              <h3 className="text-lg font-medium mb-2">No programs found</h3>
              <p className="text-muted-foreground">Try adjusting your search or filter criteria</p>
            </div>
          )}
        </div>
      </div>
    </>
  )
}
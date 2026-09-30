'use client'
import React, { useState, useMemo } from 'react';
import Link from "next/link"
import Script from "next/script"
import { ChevronDown, Heart, Eye, ShoppingCart, Filter, X, ArrowRight, MapPin, Star, Award, Calendar, Users, Phone, Mail, Clock, BookOpen, Lightbulb, TrendingUp, Play, FileText, Video } from "lucide-react"

interface Course {
  id: number;
  title: string;
  excerpt: string;
  instructor: string;
  duration: string;
  lessons: number;
  level: string;
  rating: number;
  price: string;
  originalPrice?: string;
  image: string;
  category: string;
}

// Course data
const courses: Course[] = [
  {
    id: 1,
    title: "Bhagavad Gita Essentials",
    excerpt: "A comprehensive introduction to the core teachings of the Bhagavad Gita and their practical application in daily life.",
    instructor: "Dr. Priya Sharma",
    duration: "6 weeks",
    lessons: 12,
    level: "Beginner",
    rating: 4.9,
    price: "₹2,999",
    originalPrice: "₹4,999",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg",
    category: "Gita Studies"
  },
  {
    id: 2,
    title: "Advanced Meditation Techniques",
    excerpt: "Master advanced meditation practices from Vedic traditions including Trataka, Pranayama, and Mantra meditation.",
    instructor: "Swami Anand",
    duration: "8 weeks",
    lessons: 16,
    level: "Intermediate",
    rating: 4.8,
    price: "₹3,999",
    originalPrice: "₹6,999",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    category: "Meditation"
  },
  {
    id: 3,
    title: "Ritual Science Fundamentals",
    excerpt: "Understand the scientific principles behind Vedic rituals and their psychological and spiritual benefits.",
    instructor: "Pandit Rajesh Sharma",
    duration: "10 weeks",
    lessons: 20,
    level: "Intermediate",
    rating: 4.9,
    price: "₹4,499",
    originalPrice: "₹7,999",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679730/Whisk_e8aade6d734184789c64dedc33fec8c2dr_livh1r.jpg",
    category: "Ritual Science"
  },
  {
    id: 4,
    title: "Vedanta Philosophy",
    excerpt: "Explore the profound philosophical concepts of Vedanta and their relevance in contemporary spiritual practice.",
    instructor: "Prof. Ramesh Iyer",
    duration: "12 weeks",
    lessons: 24,
    level: "Advanced",
    rating: 4.7,
    price: "₹5,999",
    originalPrice: "₹9,999",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_db05ba7c437e48abe6440798d2a33b09dr_bokdku.jpg",
    category: "Philosophy"
  },
  {
    id: 5,
    title: "Bhakti Yoga Practices",
    excerpt: "Learn the path of devotion through traditional bhakti practices, kirtan, and devotional literature.",
    instructor: "Swami Prema",
    duration: "6 weeks",
    lessons: 12,
    level: "Beginner",
    rating: 4.8,
    price: "₹2,499",
    originalPrice: "₹3,999",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761679025/Whisk_74db7ede20dd9ab86654355ac2ceeb04dr_wgtjc6.jpg",
    category: "Yoga"
  },
  {
    id: 6,
    title: "Mantra Science & Chanting",
    excerpt: "Discover the power of sacred sounds and learn proper pronunciation and timing for key Vedic mantras.",
    instructor: "Pandit Suresh Patel",
    duration: "4 weeks",
    lessons: 8,
    level: "Beginner",
    rating: 4.9,
    price: "₹1,999",
    originalPrice: "₹2,999",
    image: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761960096/Whisk_9080931cbd0a4e69592487dd741d9b57dr_m8c7b7.jpg",
    category: "Mantras"
  }
];

const categories = [
  "All", "Gita Studies", "Meditation", "Ritual Science", "Philosophy", "Yoga", "Mantras"
];

const levels = [
  "All Levels", "Beginner", "Intermediate", "Advanced"
];

export default function CoursesClient({ coursesSchema }: { coursesSchema: string }) {
  const [categoryFilter, setCategoryFilter] = useState("All");
  const [levelFilter, setLevelFilter] = useState("All Levels");
  
  const filteredCourses = useMemo(() => courses.filter(course => 
    (categoryFilter === "All" || course.category === categoryFilter) &&
    (levelFilter === "All Levels" || course.level === levelFilter)
  ), [courses, categoryFilter, levelFilter]);
  
  const CourseCard = ({ course }: { course: Course }) => {
    return (
      <div className="group border border-border rounded-lg overflow-hidden bg-card flex flex-col h-full">
        <div className="relative bg-muted flex-shrink-0">
          <img 
            src={course.image} 
            alt={course.title}
            className="w-full h-40 object-cover group-hover:scale-105 transition-transform duration-300"
          />
          <div className="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
          
          {course.originalPrice && (
            <div className="absolute top-2 right-2">
              <span className="px-2 py-1 bg-red-600 text-white text-[10px] rounded-full">
                Save ₹{parseInt(course.originalPrice.replace(/[₹,]/g, "")) - parseInt(course.price.replace(/[₹,]/g, ""))}
              </span>
            </div>
          )}
          
          <div className="absolute bottom-2 left-2 right-2">
            <div className="flex items-center justify-between">
              <span className="px-2 py-1 bg-[#0E3F2E]/80 text-white text-[10px] rounded-full">
                {course.category}
              </span>
              <span className="px-2 py-1 bg-background/80 text-foreground text-[10px] rounded-full">
                {course.level}
              </span>
            </div>
          </div>
        </div>
        
        <div className="p-4 space-y-3 flex flex-col flex-grow">
          <div className="flex justify-between items-start">
            <h3 className="font-medium hover:text-[#0E3F2E] transition-colors line-clamp-2 text-sm flex-grow">
              {course.title}
            </h3>
            <div className="flex items-center gap-1 ml-2">
              <Star className="w-3 h-3 text-yellow-500 fill-current" />
              <span className="text-[10px] font-medium">{course.rating}</span>
            </div>
          </div>
          
          <p className="text-[11px] text-muted-foreground line-clamp-2">
            {course.excerpt}
          </p>
          
          <div className="flex items-center gap-2 text-[10px] text-muted-foreground">
            <div className="flex items-center gap-1">
              <Play className="w-3 h-3" />
              <span>{course.lessons} lessons</span>
            </div>
            <span>•</span>
            <div className="flex items-center gap-1">
              <Clock className="w-3 h-3" />
              <span>{course.duration}</span>
            </div>
          </div>
          
          <div className="flex items-center justify-between pt-2">
            <div className="flex items-center gap-2">
              <div className="w-6 h-6 rounded-full bg-muted flex items-center justify-center">
                <span className="text-[8px] font-medium">
                  {course.instructor.split(' ').map(n => n[0]).join('')}
                </span>
              </div>
              <div className="text-[10px] font-medium">{course.instructor}</div>
            </div>
          </div>
          
          <div className="flex items-center justify-between pt-2">
            <div className="flex items-center gap-1">
              {course.originalPrice && (
                <span className="text-[10px] text-muted-foreground line-through">{course.originalPrice}</span>
              )}
              <span className={`font-medium text-sm ${course.originalPrice ? 'text-red-600' : 'text-foreground'}`}>
                {course.price}
              </span>
            </div>
            <Link 
              href={`/learn/courses/${course.id}`}
              className="inline-flex items-center justify-center gap-1 bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 text-white font-medium py-1.5 px-3 rounded-md transition-all duration-300 text-center text-[11px]"
            >
              <span>Enroll Now</span>
              <ArrowRight className="w-3 h-3" />
            </Link>
          </div>
        </div>
      </div>
    );
  };

  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: coursesSchema }}
      />
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 mt-4">
          <nav className="text-xs text-muted-foreground">
            <Link href="/" className="hover:text-foreground">Home</Link>
            <span className="mx-1.5">/</span>
            <Link href="/learn" className="hover:text-foreground">Learning</Link>
            <span className="mx-1.5">/</span>
            <span className="text-foreground">Courses</span>
          </nav>
        </div>

        {/* Hero Banner */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div 
            className="relative h-48 rounded-lg overflow-hidden bg-cover bg-center flex items-end"
            style={{
              backgroundImage: "url('https://res.cloudinary.com/dufzctlaj/image/upload/v1760358799/Whisk_d461026b0de4446b8f7406fdbf51bff7dr_ao407y.jpg')"
            }}
          >
            <div className="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
            <div className="relative z-10 text-white p-4 max-w-2xl">
              <h1 className="text-2xl font-serif font-semibold">Spiritual Courses</h1>
              <p className="text-sm mt-1">
                Enroll in our comprehensive courses covering Gita Studies, Meditation, and Ritual Science. Learn from certified experts and deepen your understanding of Vedic wisdom.
              </p>
            </div>
          </div>
        </div>

        {/* Stats Bar */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
          <div className="bg-card border border-border rounded-lg p-4 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">25+</div>
              <div className="text-xs text-muted-foreground">Courses Available</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">500+</div>
              <div className="text-xs text-muted-foreground">Active Students</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">4.8/5</div>
              <div className="text-xs text-muted-foreground">Average Rating</div>
            </div>
            <div className="text-center">
              <div className="text-xl font-bold text-[#0E3F2E]">100%</div>
              <div className="text-xs text-muted-foreground">Satisfaction Rate</div>
            </div>
          </div>
        </div>

        {/* Filters */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 mb-6">
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
              <div className="text-xs font-medium mb-2">Level</div>
              <div className="flex flex-wrap gap-2">
                {levels.map((level, index) => (
                  <button
                    key={index}
                    onClick={() => setLevelFilter(level)}
                    className={`px-3 py-1.5 text-xs rounded-full transition-colors ${
                      levelFilter === level
                        ? 'bg-[#0E3F2E] text-white' 
                        : 'bg-background hover:bg-muted border border-border text-foreground'
                    }`}
                  >
                    {level}
                  </button>
                ))}
              </div>
            </div>
          </div>
        </div>

        {/* Courses Grid */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 pb-10">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {filteredCourses.map(course => (
              <div key={course.id} className="flex">
                <CourseCard course={course} />
              </div>
            ))}
          </div>
        </div>
      </div>
    </>
  )
}
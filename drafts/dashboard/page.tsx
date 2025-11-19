'use client'

import { useState, useEffect } from 'react'
import Link from 'next/link'
import { useRouter } from 'next/navigation'
import { 
  User, 
  Calendar, 
  BookOpen, 
  CreditCard, 
  Settings, 
  LogOut, 
  Bell, 
  Heart, 
  Star,
  MapPin,
  Phone,
  Mail
} from 'lucide-react'

export default function DashboardPage() {
  const router = useRouter()
  const [user, setUser] = useState({
    name: 'John Doe',
    email: 'john.doe@example.com',
    phone: '+1 (555) 123-4567',
    memberSince: 'January 2023',
    location: 'Delhi, India'
  })

  const [upcomingBookings] = useState([
    {
      id: 1,
      service: 'Griha Pravesh Puja',
      date: '2023-06-15',
      time: '10:00 AM',
      pandit: 'Pandit Rajesh Sharma',
      status: 'Confirmed'
    },
    {
      id: 2,
      service: 'Astrology Consultation',
      date: '2023-06-20',
      time: '2:00 PM',
      pandit: 'Pandit Anil Verma',
      status: 'Pending'
    }
  ])

  const [recentActivity] = useState([
    {
      id: 1,
      action: 'Booked a puja service',
      service: 'Satyanarayan Katha',
      date: '2023-05-15'
    },
    {
      id: 2,
      action: 'Left a review',
      service: 'Wedding Ceremony',
      date: '2023-05-10'
    },
    {
      id: 3,
      action: 'Updated profile information',
      service: '',
      date: '2023-05-05'
    }
  ])

  const handleLogout = () => {
    // Simulate logout
    router.push('/auth/login')
  }
  
  return (
    <div className="min-h-screen bg-background">
      {/* Header */}
      <header className="bg-card border-b border-border">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between h-16">
            <div className="flex items-center">
              <h1 className="text-xl font-serif font-bold text-foreground">Dashboard</h1>
            </div>
            <div className="flex items-center space-x-4">
              <button className="p-2 rounded-full text-muted-foreground hover:text-foreground focus:outline-none">
                <Bell className="h-5 w-5" />
              </button>
              <button 
                onClick={handleLogout}
                className="flex items-center text-sm font-medium text-muted-foreground hover:text-foreground"
              >
                <LogOut className="h-4 w-4 mr-1" />
                Logout
              </button>
            </div>
          </div>
        </div>
      </header>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Sidebar */}
          <div className="lg:col-span-1">
            <div className="bg-card border border-border rounded-xl shadow-sm p-6">
              <div className="flex items-center">
                <div className="flex-shrink-0">
                  <div className="h-16 w-16 rounded-full bg-[#0E3F2E] flex items-center justify-center">
                    <User className="h-8 w-8 text-white" />
                  </div>
                </div>
                <div className="ml-4">
                  <h2 className="text-lg font-semibold text-foreground">{user.name}</h2>
                  <p className="text-sm text-muted-foreground">{user.email}</p>
                </div>
              </div>

              <div className="mt-6 space-y-1">
                <div className="flex items-center text-sm text-muted-foreground">
                  <MapPin className="h-4 w-4 mr-2" />
                  <span>{user.location}</span>
                </div>
                <div className="flex items-center text-sm text-muted-foreground">
                  <Phone className="h-4 w-4 mr-2" />
                  <span>{user.phone}</span>
                </div>
                <div className="flex items-center text-sm text-muted-foreground">
                  <Calendar className="h-4 w-4 mr-2" />
                  <span>Member since {user.memberSince}</span>
                </div>
              </div>

              <div className="mt-8 space-y-2">
                <Link 
                  href="/dashboard/profile" 
                  className="flex items-center px-3 py-2 text-sm font-medium rounded-lg text-foreground bg-muted"
                >
                  <User className="h-5 w-5 mr-3" />
                  My Profile
                </Link>
                <Link 
                  href="/dashboard/bookings" 
                  className="flex items-center px-3 py-2 text-sm font-medium rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground"
                >
                  <Calendar className="h-5 w-5 mr-3" />
                  My Bookings
                </Link>
                <Link 
                  href="/dashboard/favorites" 
                  className="flex items-center px-3 py-2 text-sm font-medium rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground"
                >
                  <Heart className="h-5 w-5 mr-3" />
                  Favorites
                </Link>
                <Link 
                  href="/dashboard/reviews" 
                  className="flex items-center px-3 py-2 text-sm font-medium rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground"
                >
                  <Star className="h-5 w-5 mr-3" />
                  My Reviews
                </Link>
                <Link 
                  href="/dashboard/payments" 
                  className="flex items-center px-3 py-2 text-sm font-medium rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground"
                >
                  <CreditCard className="h-5 w-5 mr-3" />
                  Payment Methods
                </Link>
                <Link 
                  href="/dashboard/settings" 
                  className="flex items-center px-3 py-2 text-sm font-medium rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground"
                >
                  <Settings className="h-5 w-5 mr-3" />
                  Settings
                </Link>
              </div>
            </div>
          </div>

          {/* Main Content */}
          <div className="lg:col-span-2 space-y-8">
            {/* Welcome Section */}
            <div className="bg-card border border-border rounded-xl shadow-sm p-6">
              <h2 className="text-xl font-semibold text-foreground">Welcome back, {user.name.split(' ')[0]}!</h2>
              <p className="mt-1 text-muted-foreground">
                Here's what's happening with your account today.
              </p>
            </div>

            {/* Upcoming Bookings */}
            <div className="bg-card border border-border rounded-xl shadow-sm p-6">
              <div className="flex items-center justify-between">
                <h2 className="text-lg font-semibold text-foreground">Upcoming Bookings</h2>
                <Link 
                  href="/dashboard/bookings" 
                  className="text-sm font-medium text-[#0E3F2E] hover:text-[#0E3F2E]/90"
                >
                  View all
                </Link>
              </div>

              {upcomingBookings.length > 0 ? (
                <div className="mt-4 space-y-4">
                  {upcomingBookings.map((booking) => (
                    <div key={booking.id} className="border border-border rounded-lg p-4">
                      <div className="flex justify-between">
                        <div>
                          <h3 className="font-medium text-foreground">{booking.service}</h3>
                          <p className="text-sm text-muted-foreground mt-1">
                            {booking.date} at {booking.time}
                          </p>
                          <p className="text-sm text-muted-foreground mt-1">
                            with {booking.pandit}
                          </p>
                        </div>
                        <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                          booking.status === 'Confirmed' 
                            ? 'bg-green-100 text-green-800' 
                            : 'bg-yellow-100 text-yellow-800'
                        }`}>
                          {booking.status}
                        </span>
                      </div>
                      <div className="mt-3 flex space-x-2">
                        <button className="text-xs font-medium text-[#0E3F2E] hover:text-[#0E3F2E]/90">
                          View Details
                        </button>
                        <button className="text-xs font-medium text-muted-foreground hover:text-foreground">
                          Reschedule
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="mt-4 text-center py-8">
                  <Calendar className="h-12 w-12 mx-auto text-muted-foreground" />
                  <h3 className="mt-2 text-sm font-medium text-foreground">No upcoming bookings</h3>
                  <p className="mt-1 text-sm text-muted-foreground">
                    You don't have any upcoming bookings yet.
                  </p>
                  <div className="mt-4">
                    <Link 
                      href="/pandits" 
                      className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 focus:outline-none"
                    >
                      Book a Service
                    </Link>
                  </div>
                </div>
              )}
            </div>

            {/* Recent Activity */}
            <div className="bg-card border border-border rounded-xl shadow-sm p-6">
              <h2 className="text-lg font-semibold text-foreground">Recent Activity</h2>

              {recentActivity.length > 0 ? (
                <div className="mt-4 space-y-4">
                  {recentActivity.map((activity) => (
                    <div key={activity.id} className="flex">
                      <div className="flex-shrink-0">
                        <div className="h-8 w-8 rounded-full bg-muted flex items-center justify-center">
                          <div className="h-2 w-2 rounded-full bg-[#0E3F2E]"></div>
                        </div>
                      </div>
                      <div className="ml-3">
                        <p className="text-sm text-foreground">
                          {activity.action} {activity.service && <span className="font-medium">{activity.service}</span>}
                        </p>
                        <p className="text-xs text-muted-foreground mt-1">
                          {activity.date}
                        </p>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="mt-4 text-center py-8">
                  <div className="h-12 w-12 mx-auto rounded-full bg-muted flex items-center justify-center">
                    <Bell className="h-6 w-6 text-muted-foreground" />
                  </div>
                  <h3 className="mt-2 text-sm font-medium text-foreground">No recent activity</h3>
                  <p className="mt-1 text-sm text-muted-foreground">
                    Your recent activity will appear here.
                  </p>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
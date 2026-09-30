import Link from 'next/link'
import type { Role } from '@/lib/dlo-x/types'
import {
  getMockBookings,
  getMockManagerQueue,
  getMockPanditLeads,
  getMockProfile,
  getMockSeoQueue,
  getMockSummary,
  getMockVendorOrders,
} from '@/lib/auth/mock-session'
import { getRoleSection, getWorkspaceConfig } from '@/lib/product/role-config'
import type { RouteBreadcrumb } from '@/lib/routes/metadata'
import { StatCard } from '@/components/dashboard/StatCard'
import { StatusBadge } from '@/components/dashboard/StatusBadge'
import { ProgressBar } from '@/components/dashboard/ProgressBar'
import { QuickActions } from '@/components/dashboard/QuickActions'
import { TimelineItem } from '@/components/dashboard/TimelineItem'
import {
  Calendar, Users, MapPin, TrendingUp, IndianRupee, Star,
  ShoppingBag, Package, Truck, FileText, Shield, BarChart3,
  Search, Settings, Headphones, Plus, Send, Code, Server,
  CreditCard, ClipboardList, Crown, Megaphone, Bell,
} from 'lucide-react'

interface RoleWorkspacePageProps {
  role: Role
  sectionSlug?: string
  breadcrumbs: RouteBreadcrumb[]
}

function formatTitle(text: string) {
  return text.split('-').map((part) => part.charAt(0).toUpperCase() + part.slice(1)).join(' ')
}

function UserDashboard() {
  const profile = getMockProfile('User')
  const summary = getMockSummary('User')
  const bookings = getMockBookings()

  return (
    <div className="space-y-6">
      {/* Welcome header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-serif font-bold">Welcome back, {profile.name.split(' ')[0]}</h1>
          <p className="text-muted-foreground text-sm">Here&apos;s what&apos;s happening with your rituals</p>
        </div>
        <div className="flex gap-2">
          <select className="form-input py-2 w-auto">
            <option>This Week</option>
            <option>This Month</option>
            <option>This Year</option>
          </select>
          <Link href="/pujas" className="btn-primary">
            <Plus className="size-4 mr-1" /> Book Puja
          </Link>
        </div>
      </div>

      {/* Stat Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard icon={<IndianRupee className="size-5" />} label="Total Spent" value="₹12,400" change="↑12% from last month" changeType="positive" />
        <StatCard icon={<Calendar className="size-5" />} label="Upcoming Ceremonies" value="3" change="+1 new" changeType="positive" />
        <StatCard icon={<Users className="size-5" />} label="Saved Pandits" value="6" change="+2 new" changeType="positive" />
        <StatCard icon={<Headphones className="size-5" />} label="Open Issues" value="1" change="-1 from last week" changeType="positive" />
      </div>

      {/* Upcoming Ceremonies + Saved Pandits */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 stat-card">
          <h3 className="text-lg font-semibold mb-4">Upcoming Ceremonies</h3>
          <div className="space-y-3">
            {bookings.map((booking) => (
              <div key={booking.id} className="flex items-center gap-4 p-3 rounded-xl border border-border">
                <div className="size-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                  <Calendar className="size-5" />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="font-medium text-sm">{booking.title}</p>
                  <p className="text-xs text-muted-foreground">{booking.location} • {booking.counterpartName}</p>
                </div>
                <StatusBadge status={booking.status} />
              </div>
            ))}
          </div>
        </div>

        <div className="stat-card">
          <h3 className="text-lg font-semibold mb-4">Saved Pandits</h3>
          <div className="space-y-3">
            {[
              { name: 'Pandit Ravi Mishra', rating: 4.9, city: 'Ghaziabad', reviews: 122 },
              { name: 'Pandit Suresh', rating: 4.8, city: 'Noida', reviews: 98 },
              { name: 'Pandit Anand', rating: 4.7, city: 'Delhi', reviews: 87 },
            ].map((pandit) => (
              <div key={pandit.name} className="flex items-center gap-3 p-3 rounded-xl border border-border">
                <div className="size-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-semibold text-sm">
                  {pandit.name.charAt(0)}
                </div>
                <div className="flex-1 min-w-0">
                  <p className="font-medium text-sm truncate">{pandit.name}</p>
                  <div className="flex items-center gap-1 text-xs text-muted-foreground">
                    <Star className="size-3 fill-amber-400 text-amber-400" />
                    {pandit.rating} • {pandit.city}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  )
}

function PanditDashboard() {
  const profile = getMockProfile('Pandit')
  const summary = getMockSummary('Pandit')
  const leads = getMockPanditLeads()

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-serif font-bold">Namaste, {profile.name.split(' ')[1] || profile.name}</h1>
          <p className="text-muted-foreground text-sm">Here&apos;s your ceremony lead overview</p>
        </div>
        <div className="flex gap-2">
          <select className="form-input py-2 w-auto">
            <option>This Month</option>
            <option>This Week</option>
          </select>
          <button className="btn-primary"><Plus className="size-4 mr-1" /> Add Service</button>
        </div>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard icon={<Megaphone className="size-5" />} label="New Leads" value="14" change="+4 since yesterday" changeType="positive" />
        <StatCard icon={<Calendar className="size-5" />} label="This Week" value="9" change="4 confirmed" changeType="neutral" />
        <StatCard icon={<Star className="size-5" />} label="Rating" value="4.9" change="122 reviews" changeType="positive" />
        <StatCard icon={<Shield className="size-5" />} label="Profile Complete" value="98%" change="+2% this month" changeType="positive" />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 stat-card">
          <h3 className="text-lg font-semibold mb-4">New Leads</h3>
          <div className="space-y-3">
            {leads.map((lead) => (
              <div key={lead.id} className="flex items-center gap-4 p-3 rounded-xl border border-border">
                <div className="size-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                  <Megaphone className="size-5" />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="font-medium text-sm">{lead.ceremony}</p>
                  <p className="text-xs text-muted-foreground">{lead.city} • {lead.householdName}</p>
                </div>
                <StatusBadge status={lead.status} />
              </div>
            ))}
          </div>
        </div>

        <div className="space-y-6">
          <div className="stat-card">
            <h3 className="text-lg font-semibold mb-4">Earnings</h3>
            <div className="space-y-3">
              <ProgressBar label="This Month" value={85} color="bg-primary" />
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Target</span>
                <span className="font-medium">₹45,200 / ₹53,000</span>
              </div>
            </div>
          </div>
          <div className="stat-card">
            <h3 className="text-lg font-semibold mb-4">Verification</h3>
            <div className="space-y-2">
              <div className="flex items-center gap-2 text-sm">
                <span className="text-emerald-500">✓</span> Documents Complete
              </div>
              <div className="flex items-center gap-2 text-sm">
                <span className="text-emerald-500">✓</span> Training Verified
              </div>
              <div className="flex items-center gap-2 text-sm">
                <span className="text-emerald-500">✓</span> Background Clear
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

function VendorDashboard() {
  const profile = getMockProfile('Vendor')
  const summary = getMockSummary('Vendor')
  const orders = getMockVendorOrders()

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-serif font-bold">{profile.name}</h1>
          <p className="text-muted-foreground text-sm">Your supply chain overview</p>
        </div>
        <button className="btn-primary"><Plus className="size-4 mr-1" /> New Order</button>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard icon={<ShoppingBag className="size-5" />} label="Active Orders" value="18" change="+3 today" changeType="positive" />
        <StatCard icon={<Package className="size-5" />} label="Catalog Health" value="95%" change="245 items" changeType="positive" />
        <StatCard icon={<FileText className="size-5" />} label="Due Invoices" value="4" change="₹18,200" changeType="warning" />
        <StatCard icon={<Truck className="size-5" />} label="SLA Met" value="97%" change="Last 30 days" changeType="positive" />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 stat-card">
          <h3 className="text-lg font-semibold mb-4">Active Orders</h3>
          <div className="space-y-3">
            {orders.map((order) => (
              <div key={order.id} className="flex items-center gap-4 p-3 rounded-xl border border-border">
                <div className="size-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                  <Package className="size-5" />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="font-medium text-sm">{order.category}</p>
                  <p className="text-xs text-muted-foreground">{order.destination} • {order.itemCount} items</p>
                </div>
                <StatusBadge status={order.status} />
              </div>
            ))}
          </div>
        </div>

        <div className="stat-card">
          <h3 className="text-lg font-semibold mb-4">Fulfillment Pipeline</h3>
          <div className="space-y-3">
            <ProgressBar label="Queued" value={3} maxValue={10} color="bg-sky-500" />
            <ProgressBar label="Packing" value={4} maxValue={10} color="bg-amber-500" />
            <ProgressBar label="Dispatched" value={5} maxValue={10} color="bg-blue-500" />
            <ProgressBar label="Delivered" value={8} maxValue={10} color="bg-emerald-500" />
          </div>
        </div>
      </div>
    </div>
  )
}

function ManagerDashboard() {
  const profile = getMockProfile('Manager')
  const summary = getMockSummary('Manager')
  const queue = getMockManagerQueue()

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-serif font-bold">Operations Hub</h1>
          <p className="text-muted-foreground text-sm">Welcome back, {profile.name.split(' ')[0]}</p>
        </div>
        <button className="btn-primary"><Plus className="size-4 mr-1" /> New Ticket</button>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard icon={<Settings className="size-5" />} label="Open Queues" value="11" change="-2 from last week" changeType="positive" />
        <StatCard icon={<MapPin className="size-5" />} label="City Launches" value="5" change="2 blocked" changeType="warning" />
        <StatCard icon={<Shield className="size-5" />} label="Quality Escalations" value="3" change="Same-day action" changeType="danger" />
        <StatCard icon={<Users className="size-5" />} label="Assignment Load" value="27" change="Across 3 teams" changeType="neutral" />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 stat-card">
          <h3 className="text-lg font-semibold mb-4">Operations Queue</h3>
          <div className="space-y-3">
            {queue.map((item) => (
              <div key={item.id} className="flex items-center gap-4 p-3 rounded-xl border border-border">
                <div className={`size-10 rounded-lg flex items-center justify-center ${
                  item.status === 'active' ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600'
                }`}>
                  <Settings className="size-5" />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="font-medium text-sm truncate">{item.title}</p>
                  <p className="text-xs text-muted-foreground">{formatTitle(item.queue)} • {item.owner}</p>
                </div>
                <StatusBadge status={item.status} />
              </div>
            ))}
          </div>
        </div>

        <div className="space-y-6">
          <div className="stat-card">
            <h3 className="text-lg font-semibold mb-4">Assignment Load</h3>
            <div className="space-y-3">
              <ProgressBar label="Ops Team" value={80} color="bg-primary" />
              <ProgressBar label="Content" value={60} color="bg-secondary" />
              <ProgressBar label="Support" value={90} color="bg-accent" />
            </div>
          </div>
          <div className="stat-card">
            <h3 className="text-lg font-semibold mb-4">City Launches</h3>
            <div className="space-y-2">
              {['Delhi', 'Mumbai', 'Bangalore', 'Chennai', 'Hyderabad'].map((city, i) => (
                <div key={city} className="flex items-center justify-between text-sm">
                  <span>{city}</span>
                  <StatusBadge status={i < 2 ? 'active' : i < 4 ? 'blocked' : 'launching'} />
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

function AdminDashboard() {
  const profile = getMockProfile('Admin')
  const summary = getMockSummary('Admin')
  const seoQueue = getMockSeoQueue()

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-serif font-bold">Platform Admin</h1>
          <p className="text-muted-foreground text-sm">Platform-wide operations overview</p>
        </div>
        <button className="btn-primary"><Plus className="size-4 mr-1" /> Quick Action</button>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard icon={<BarChart3 className="size-5" />} label="Platform Actions" value="42" change="Across all areas" changeType="neutral" />
        <StatCard icon={<Search className="size-5" />} label="SEO Queue" value="58" change="Awaiting QA" changeType="warning" />
        <StatCard icon={<Users className="size-5" />} label="User Issues" value="7" change="Escalations" changeType="danger" />
        <StatCard icon={<IndianRupee className="size-5" />} label="Payout Holds" value="2" change="Manual verify" changeType="warning" />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 stat-card">
          <h3 className="text-lg font-semibold mb-4">SEO Queue</h3>
          <div className="space-y-3">
            {seoQueue.map((item) => (
              <div key={item.id} className="flex items-center gap-4 p-3 rounded-xl border border-border">
                <div className="size-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                  <Search className="size-5" />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="font-medium text-sm truncate">{item.slug}</p>
                  <p className="text-xs text-muted-foreground">{item.cluster} • {item.market}</p>
                </div>
                <StatusBadge status={item.status} />
              </div>
            ))}
          </div>
        </div>

        <div className="space-y-6">
          <div className="stat-card">
            <h3 className="text-lg font-semibold mb-4">Payout Overview</h3>
            <div className="space-y-2">
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Processed</span>
                <span className="font-medium text-emerald-600">₹2,45,380</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Pending</span>
                <span className="font-medium text-amber-600">₹18,200</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">On Hold</span>
                <span className="font-medium text-red-600">₹4,100</span>
              </div>
            </div>
          </div>
          <div className="stat-card">
            <h3 className="text-lg font-semibold mb-4">Content Status</h3>
            <div className="space-y-2">
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Queued</span>
                <span className="font-medium">12</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Published</span>
                <span className="font-medium">45</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">In Review</span>
                <span className="font-medium">8</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

function SuperAdminDashboard() {
  const profile = getMockProfile('SuperAdmin')
  const summary = getMockSummary('SuperAdmin')

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-serif font-bold flex items-center gap-2">
            <Crown className="size-6 text-primary" /> Platform Control
          </h1>
          <p className="text-muted-foreground text-sm">Global platform metrics and health</p>
        </div>
        <button className="btn-primary"><Plus className="size-4 mr-1" /> Quick Action</button>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard icon={<Users className="size-5" />} label="Total Users" value="12,400" change="↑12.5% from last month" changeType="positive" />
        <StatCard icon={<IndianRupee className="size-5" />} label="Monthly Revenue" value="₹8.2L" change="↑18.3% growth" changeType="positive" />
        <StatCard icon={<Server className="size-5" />} label="System Uptime" value="99.9%" change="Last 30 days" changeType="positive" />
        <StatCard icon={<MapPin className="size-5" />} label="Active Cities" value="24" change="+2 new this month" changeType="positive" />
      </div>

      {/* System Health */}
      <div className="stat-card">
        <h3 className="text-lg font-semibold mb-4">System Health</h3>
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div className="flex items-center gap-3 p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20">
            <span className="size-3 rounded-full bg-emerald-500" />
            <div>
              <p className="text-sm font-medium">API Response</p>
              <p className="text-xs text-muted-foreground">45ms</p>
            </div>
          </div>
          <div className="flex items-center gap-3 p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20">
            <span className="size-3 rounded-full bg-emerald-500" />
            <div>
              <p className="text-sm font-medium">Database</p>
              <p className="text-xs text-muted-foreground">12ms</p>
            </div>
          </div>
          <div className="flex items-center gap-3 p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20">
            <span className="size-3 rounded-full bg-emerald-500" />
            <div>
              <p className="text-sm font-medium">Cache</p>
              <p className="text-xs text-muted-foreground">2ms</p>
            </div>
          </div>
        </div>
        <div className="mt-4 space-y-3">
          <ProgressBar label="CPU" value={78} color="bg-primary" />
          <ProgressBar label="Memory" value={62} color="bg-secondary" />
          <ProgressBar label="Storage" value={82} color="bg-accent" />
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Geographic Spread */}
        <div className="stat-card">
          <h3 className="text-lg font-semibold mb-4">Geographic Spread</h3>
          <div className="space-y-2">
            {[
              { city: 'Delhi', users: '2,100', percent: 70 },
              { city: 'Mumbai', users: '1,200', percent: 40 },
              { city: 'Bangalore', users: '890', percent: 30 },
              { city: 'Chennai', users: '456', percent: 15 },
              { city: 'Hyderabad', users: '340', percent: 11 },
            ].map((item) => (
              <div key={item.city} className="flex items-center justify-between text-sm">
                <div className="flex items-center gap-2">
                  <MapPin className="size-3 text-muted-foreground" />
                  <span>{item.city}</span>
                </div>
                <span className="text-muted-foreground">{item.users}</span>
              </div>
            ))}
          </div>
        </div>

        {/* Security Status */}
        <div className="stat-card">
          <h3 className="text-lg font-semibold mb-4">Security Status</h3>
          <div className="space-y-3">
            <div className="flex items-center gap-2 text-sm">
              <span className="text-emerald-500">✓</span> No threats detected
            </div>
            <div className="flex items-center justify-between text-sm">
              <span className="text-muted-foreground">2FA Adoption</span>
              <span className="font-medium">67%</span>
            </div>
            <div className="flex items-center justify-between text-sm">
              <span className="text-muted-foreground">Failed Logins Today</span>
              <span className="font-medium">12</span>
            </div>
            <div className="flex items-center justify-between text-sm">
              <span className="text-muted-foreground">Active Sessions</span>
              <span className="font-medium">1,247</span>
            </div>
          </div>
        </div>

        {/* Quick Actions */}
        <QuickActions
          actions={[
            { label: 'Create Admin', icon: <Shield className="size-4" /> },
            { label: 'Send Broadcast', icon: <Send className="size-4" /> },
            { label: 'Feature Flag', icon: <Code className="size-4" /> },
            { label: 'System Announcement', icon: <Bell className="size-4" /> },
          ]}
        />
      </div>
    </div>
  )
}

const DASHBOARD_MAP: Record<Role, React.ComponentType> = {
  User: UserDashboard,
  Pandit: PanditDashboard,
  Vendor: VendorDashboard,
  Manager: ManagerDashboard,
  Admin: AdminDashboard,
  SuperAdmin: SuperAdminDashboard,
}

export function RoleWorkspacePage({ role, sectionSlug, breadcrumbs }: RoleWorkspacePageProps) {
  const section = getRoleSection(role, sectionSlug)
  const DashboardComponent = DASHBOARD_MAP[role]

  // If it's the overview section, render the role-specific dashboard
  if (section.slug === 'overview' && DashboardComponent) {
    return (
      <main className="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <DashboardComponent />
      </main>
    )
  }

  // For other sections, render a placeholder
  return (
    <main className="mx-auto flex w-full max-w-7xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
      <div className="rounded-2xl border border-border bg-card p-6 shadow-sm">
        <p className="text-xs font-semibold uppercase tracking-[0.24em] text-primary">
          {getWorkspaceConfig(role).label}
        </p>
        <h1 className="mt-3 text-3xl font-serif font-bold text-foreground">
          {section.title}
        </h1>
        <p className="mt-2 max-w-3xl text-sm leading-6 text-muted-foreground">
          {section.description}
        </p>
      </div>
      <div className="stat-card text-center py-12">
        <p className="text-muted-foreground">This section is under development.</p>
      </div>
    </main>
  )
}

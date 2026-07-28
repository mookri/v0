// components/dlo-x/shells/DashboardShell.tsx
'use client'

import { ReactNode, useState } from 'react'
import Link from 'next/link'
import { usePathname, useRouter } from 'next/navigation'
import { DevRoleSwitcher } from '@/components/dev/DevRoleSwitcher'
import { getMockSessionSync } from '@/lib/auth/mock-session-client'
import { getWorkspaceConfig } from '@/lib/product/role-config'
import type { Role } from '@/lib/dlo-x/types'
import {
  LayoutDashboard, Calendar, Megaphone, CalendarDays, Sparkles, IndianRupee,
  ShoppingBag, Package, Truck, Users, FileText, Search, Settings, Shield,
  MapPin, BarChart3, Headphones, UserCircle, Bell, Maximize, ChevronDown,
  Menu, PanelLeftClose, Sun, Moon, LogOut, HelpCircle, Code, CreditCard,
  Server, Lock, ClipboardList, Crown,
} from 'lucide-react'

interface DashboardShellProps {
  children: ReactNode
}

const ICON_MAP: Record<string, React.ComponentType<any>> = {
  overview: LayoutDashboard,
  bookings: Calendar,
  favorites: IndianRupee,
  reviews: BarChart3,
  payments: IndianRupee,
  profile: UserCircle,
  settings: Settings,
  support: Headphones,
  leads: Megaphone,
  calendar: CalendarDays,
  services: Sparkles,
  earnings: IndianRupee,
  verification: Shield,
  orders: ShoppingBag,
  catalog: Package,
  fulfillment: Truck,
  invoices: FileText,
  operations: Settings,
  assignments: Users,
  quality: Shield,
  cities: MapPin,
  reports: BarChart3,
  users: Users,
  pandits: Users,
  vendors: Users,
  managers: Users,
  content: FileText,
  seo: Search,
  payouts: IndianRupee,
  system: Server,
  roles: Shield,
  security: Lock,
  billing: CreditCard,
  api: Code,
  logs: ClipboardList,
}

export function DashboardShell({ children }: DashboardShellProps) {
  const pathname = usePathname()
  const router = useRouter()
  const [sidebarOpen, setSidebarOpen] = useState(true)
  const [mobileOpen, setMobileOpen] = useState(false)
  const [userDropdownOpen, setUserDropdownOpen] = useState(false)

  // Get session from cookie (client-side)
  const session = getMockSessionSync()
  const role = session.role as Role
  const workspace = getWorkspaceConfig(role)

  const pathSegments = pathname.split('/').filter(Boolean)
  const currentSection = pathSegments[2] || pathSegments[1] || 'overview'

  return (
    <div className="min-h-screen bg-background">
      {/* Mobile overlay */}
      {mobileOpen && (
        <div
          className="fixed inset-0 z-40 bg-black/50 lg:hidden"
          onClick={() => setMobileOpen(false)}
        />
      )}

      {/* Sidebar */}
      <aside
        className={`fixed inset-y-0 left-0 z-50 flex flex-col bg-secondary text-white transition-all duration-300 ${
          sidebarOpen ? 'w-64' : 'w-20'
        } ${mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'}`}
      >
        {/* Logo */}
        <div className="flex items-center gap-3 px-5 h-16 border-b border-white/10">
          <div className="size-8 rounded-lg bg-white/20 flex items-center justify-center flex-shrink-0">
            <Crown className="size-5 text-white" />
          </div>
          {sidebarOpen && (
            <span className="font-serif font-bold text-lg">Navdhya</span>
          )}
        </div>

        {/* Navigation */}
        <div className="flex-1 overflow-y-auto px-3 py-4">
          <div className="space-y-1">
            {workspace.sections.map((section) => {
              const Icon = ICON_MAP[section.slug] || LayoutDashboard
              const href = `${workspace.basePath}/${section.slug}`
              const isActive = section.slug === currentSection

              return (
                <Link
                  key={section.slug}
                  href={href}
                  onClick={() => setMobileOpen(false)}
                  className={`sidebar-nav-item ${isActive ? 'sidebar-nav-item-active' : ''}`}
                  title={!sidebarOpen ? section.title : undefined}
                >
                  <Icon className="size-5 flex-shrink-0" />
                  {sidebarOpen && <span>{section.title}</span>}
                </Link>
              )
            })}
          </div>

          <div className="mt-6 space-y-1">
            <p className="sidebar-nav-label">Account</p>
            <Link href={`${workspace.basePath}/settings`} className="sidebar-nav-item">
              <Settings className="size-5 flex-shrink-0" />
              {sidebarOpen && <span>Settings</span>}
            </Link>
            <Link href="/help" className="sidebar-nav-item">
              <HelpCircle className="size-5 flex-shrink-0" />
              {sidebarOpen && <span>Help & Support</span>}
            </Link>
          </div>
        </div>

        {/* User card */}
        <div className="border-t border-white/10 p-4">
          <div className="flex items-center gap-3">
            <div className="relative flex-shrink-0">
              <div className="size-9 rounded-full bg-white/20 flex items-center justify-center text-sm font-semibold">
                {session.email?.charAt(0).toUpperCase()}
              </div>
              <span className="absolute -bottom-0.5 -right-0.5 size-3 bg-emerald-400 rounded-full border-2 border-secondary" />
            </div>
            {sidebarOpen && (
              <div className="min-w-0">
                <p className="text-sm font-medium truncate">{session.email?.split('@')[0]}</p>
                <p className="text-xs text-white/60">{workspace.label}</p>
              </div>
            )}
          </div>
        </div>
      </aside>

      {/* Main content */}
      <div className={`transition-all duration-300 ${sidebarOpen ? 'lg:ml-64' : 'lg:ml-20'}`}>
        {/* Topbar */}
        <header className="topbar">
          <div className="flex items-center gap-3">
            <button
              onClick={() => setMobileOpen(true)}
              className="lg:hidden p-2 rounded-lg hover:bg-muted"
            >
              <Menu className="size-5" />
            </button>
            <button
              onClick={() => setSidebarOpen(!sidebarOpen)}
              className="hidden lg:flex p-2 rounded-lg hover:bg-muted"
            >
              <PanelLeftClose className="size-5" />
            </button>

            {/* Search */}
            <div className="relative hidden md:block">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
              <input
                type="search"
                placeholder="Quick Search..."
                className="pl-10 pr-4 py-2 rounded-full border border-input bg-background text-sm w-56 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
              />
            </div>
          </div>

          <div className="flex items-center gap-2">
            {/* Theme toggle */}
            <button className="p-2 rounded-lg hover:bg-muted">
              <Sun className="size-5" />
            </button>

            {/* Notifications */}
            <div className="relative">
              <button className="p-2 rounded-lg hover:bg-muted">
                <Bell className="size-5" />
              </button>
              <span className="absolute -top-1 -right-1 size-4 bg-danger text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                5
              </span>
            </div>

            {/* Fullscreen */}
            <button className="p-2 rounded-lg hover:bg-muted hidden sm:flex">
              <Maximize className="size-5" />
            </button>

            {/* User dropdown */}
            <div className="relative">
              <button
                onClick={() => setUserDropdownOpen(!userDropdownOpen)}
                className="flex items-center gap-2 p-1 rounded-lg hover:bg-muted"
              >
                <div className="size-8 rounded-full bg-primary/10 flex items-center justify-center text-sm font-semibold text-primary">
                  {session.email?.charAt(0).toUpperCase()}
                </div>
                <span className="hidden lg:block text-sm font-medium">{session.email?.split('@')[0]}</span>
                <ChevronDown className="size-4 hidden lg:block" />
              </button>

              {userDropdownOpen && (
                <>
                  <div className="fixed inset-0 z-40" onClick={() => setUserDropdownOpen(false)} />
                  <div className="dropdown right-0 z-50">
                    <div className="py-2 px-3.5 border-b border-border">
                      <p className="text-xs text-muted-foreground">Welcome back 👋!</p>
                    </div>
                    <Link href={`${workspace.basePath}/profile`} className="dropdown-item">
                      <UserCircle className="size-4" /> Profile
                    </Link>
                    <Link href={`${workspace.basePath}/settings`} className="dropdown-item">
                      <Settings className="size-4" /> Settings
                    </Link>
                    <Link href="/help" className="dropdown-item">
                      <Headphones className="size-4" /> Support
                    </Link>
                    <div className="dropdown-divider" />
                    <button className="dropdown-item w-full text-left">
                      <LogOut className="size-4" /> Logout
                    </button>
                  </div>
                </>
              )}
            </div>
          </div>
        </header>

        {/* Page content */}
        <main className="p-4 sm:p-6">
          {children}
        </main>
      </div>

      {/* Dev Role Switcher */}
      <div className="fixed bottom-5 right-5 z-50">
        <DevRoleSwitcher currentRole={role} />
      </div>
    </div>
  )
}

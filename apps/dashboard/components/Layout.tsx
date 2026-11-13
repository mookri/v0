'use client';

import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import { useAuth } from '@/hooks/useAuth';

export function Layout({ children }: { children: React.ReactNode }) {
  const { user, logout } = useAuth();
  const pathname = usePathname();
  const router = useRouter();

  const handleLogout = () => {
    logout();
    router.push('/login');
  };

  const navItems = [
    { path: '/', label: 'KPIs & Overview', icon: '◈' },
    { path: '/bookings', label: 'Bookings', icon: '📅' },
    { path: '/dispatch', label: 'Dispatch', icon: '🪔' },
    { path: '/payouts', label: 'Payouts', icon: '🔐' },
    { path: '/reconciliation', label: 'Reconciliation', icon: '⚖️' },
    { path: '/tax', label: 'Tax Register', icon: '📜' },
    { path: '/billing', label: 'SaaS Billing', icon: '🧾' },
    { path: '/pujas', label: 'Pujas', icon: '🕉️' },
    { path: '/customers', label: 'Customers', icon: '👥' },
    { path: '/settings', label: 'Settings', icon: '⚙️' },
  ];

  const isActive = (path: string) =>
    path === '/' ? pathname === '/' : pathname === path || pathname.startsWith(`${path}/`);

  return (
    <div className="min-h-screen flex bg-[#FFF7ED] text-[#2A2421]">
      <aside className="w-64 bg-[#2C2033] text-[#FFF7ED] flex flex-col border-r border-[#EFE3D3]/20">
        <div className="p-6 border-b border-[#FFF7ED]/15">
          <div className="flex items-center gap-2">
            <span className="inline-block h-3 w-3 rounded-full bg-[#F16A24]" aria-hidden="true" />
            <h1 className="text-xl font-bold tracking-tight text-[#FFF7ED]">Navdhya Ops</h1>
          </div>
          <p className="mt-1 text-xs text-[#EFE3D3]">Dual-Custody & Control Plane</p>
        </div>
        <nav className="flex-1 p-4 space-y-1.5 overflow-y-auto" aria-label="Primary dashboard navigation">
          {navItems.map((item) => (
            <Link
              key={item.path}
              href={item.path}
              className={`flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm transition-colors ${
                isActive(item.path)
                  ? 'bg-[#F16A24] text-[#FFF7ED] font-semibold'
                  : 'text-[#EFE3D3] hover:bg-[#FFF7ED]/10 hover:text-[#FFF7ED]'
              }`}
            >
              <span className="text-base" aria-hidden="true">{item.icon}</span>
              <span>{item.label}</span>
            </Link>
          ))}
        </nav>
        <div className="p-4 border-t border-[#FFF7ED]/15">
          <div className="flex items-center gap-3 px-3 py-2 text-sm">
            <div className="w-8 h-8 rounded-full bg-[#F16A24] flex items-center justify-center text-[#FFF7ED] font-semibold">
              {user?.name?.charAt(0).toUpperCase() || 'U'}
            </div>
            <div className="flex-1 min-w-0">
              <p className="font-medium truncate text-[#FFF7ED]">{user?.name || 'Operator'}</p>
              <p className="text-xs text-[#EFE3D3] truncate">{user?.email}</p>
            </div>
          </div>
          <button
            type="button"
            onClick={handleLogout}
            className="mt-3 w-full px-4 py-2 text-sm text-[#EFE3D3] hover:text-[#FFF7ED] hover:bg-[#FFF7ED]/10 rounded-lg transition-colors text-left"
          >
            Sign out
          </button>
        </div>
      </aside>
      <main className="flex-1 p-8 overflow-auto bg-[#FFF7ED]">
        {children}
      </main>
    </div>
  );
}

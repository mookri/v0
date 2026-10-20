import Link from 'next/link';

// Tab strip shared by every /vendors/:vendorId/* page (server and client
// views alike) — mirrors the sectioned temple layout with an explicit
// active prop since sub-routes are separate pages.

export type VendorTabId = 'overview' | 'products' | 'inventory' | 'orders' | 'payouts' | 'settings';

const TABS: Array<{ id: VendorTabId; label: string; suffix: string }> = [
  { id: 'overview', label: 'Overview', suffix: '' },
  { id: 'products', label: 'Products', suffix: '/products' },
  { id: 'inventory', label: 'Inventory', suffix: '/inventory' },
  { id: 'orders', label: 'Orders', suffix: '/orders' },
  { id: 'payouts', label: 'Payouts', suffix: '/payouts' },
  { id: 'settings', label: 'Settings', suffix: '/settings' },
];

export function VendorTabs({ vendorId, active }: { vendorId: string; active: VendorTabId }) {
  return (
    <nav className="flex flex-wrap gap-1 border-b border-gray-200 mb-6" aria-label="Vendor sections">
      {TABS.map((tab) => (
        <Link
          key={tab.id}
          href={`/vendors/${vendorId}${tab.suffix}`}
          aria-current={active === tab.id ? 'page' : undefined}
          className={`px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors ${
            active === tab.id
              ? 'border-indigo-600 text-indigo-700'
              : 'border-transparent text-gray-500 hover:text-gray-800 hover:border-gray-300'
          }`}
        >
          {tab.label}
        </Link>
      ))}
    </nav>
  );
}

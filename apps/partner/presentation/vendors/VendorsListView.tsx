import Link from 'next/link';
import type { VendorProfile } from '@/application/vendor';

// Vendor directory: cards for the actor's vendors (list is already
// owner-scoped by the BFF) with an empty state pointing at /vendors/new.

function Badge({ value, tone }: { value: string; tone: 'green' | 'yellow' | 'gray' | 'red' }) {
  const tones = {
    green: 'bg-green-100 text-green-800',
    yellow: 'bg-yellow-100 text-yellow-800',
    gray: 'bg-gray-100 text-gray-800',
    red: 'bg-red-100 text-red-800',
  } as const;
  return (
    <span className={`inline-flex items-center px-2 py-1 text-xs font-medium rounded-full capitalize ${tones[tone]}`}>
      {value.toLowerCase()}
    </span>
  );
}

export function VendorsListView({ vendors }: { vendors: VendorProfile[] }) {
  return (
    <main className="p-6 max-w-4xl mx-auto">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold">My Vendors</h1>
        <Link
          href="/vendors/new"
          className="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
        >
          <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
          </svg>
          Add Vendor
        </Link>
      </div>

      {vendors.length === 0 ? (
        <div className="text-center py-12">
          <p className="text-gray-500 mb-4">
            No vendors yet. Your approved partner-vendor application unlocks vendor creation.
          </p>
          <Link
            href="/vendors/new"
            className="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
          >
            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
            </svg>
            Create Vendor
          </Link>
        </div>
      ) : (
        <ul className="space-y-4">
          {vendors.map((vendor) => (
            <li key={vendor.id}>
              <Link
                href={`/vendors/${vendor.id}`}
                className="block p-5 bg-white border border-gray-200 rounded-lg hover:border-indigo-300 hover:shadow-md transition-all"
              >
                <div className="flex items-start gap-4">
                  <div className="flex-shrink-0 w-16 h-16 bg-indigo-100 rounded-lg flex items-center justify-center">
                    <span className="text-2xl font-bold text-indigo-700">
                      {vendor.name.charAt(0).toUpperCase()}
                    </span>
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center justify-between gap-4">
                      <div>
                        <h3 className="font-semibold text-gray-900 truncate">{vendor.name}</h3>
                        <p className="text-sm text-gray-500 mt-1 truncate">{vendor.description ?? 'Vendor profile'}</p>
                      </div>
                      <div className="flex items-center gap-2">
                        <Badge
                          value={vendor.verification_status}
                          tone={vendor.verification_status === 'VERIFIED' ? 'green' : 'yellow'}
                        />
                        <Badge value={vendor.status} tone={vendor.status === 'ACTIVE' ? 'green' : 'red'} />
                      </div>
                    </div>
                    <div className="mt-3 flex flex-wrap items-center gap-4 text-sm text-gray-500">
                      {(vendor.city || vendor.state) && (
                        <span className="flex items-center gap-1">
                          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                              strokeLinecap="round"
                              strokeLinejoin="round"
                              strokeWidth={2}
                              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                            />
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                          </svg>
                          {[vendor.city, vendor.state].filter(Boolean).join(', ')}
                        </span>
                      )}
                      <span className="flex items-center gap-1">
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"
                          />
                        </svg>
                        Created {new Date(vendor.created_at).toLocaleDateString('en-IN')}
                      </span>
                    </div>
                  </div>
                </div>
              </Link>
            </li>
          ))}
        </ul>
      )}
    </main>
  );
}

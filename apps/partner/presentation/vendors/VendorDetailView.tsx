import Link from 'next/link';
import type { VendorProfile } from '@/application/vendor';
import { VendorTabs } from './VendorTabs';
import { formatDate } from './format';

// Vendor overview: profile card, verification state, quick links into
// the management tabs.

export function VendorDetailView({ vendor }: { vendor: VendorProfile }) {
  return (
    <main className="p-6 max-w-4xl mx-auto">
      <Link href="/vendors" className="inline-flex items-center text-indigo-600 hover:text-indigo-800 mb-6">
        <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
        </svg>
        Back to Vendors
      </Link>

      <div className="bg-white rounded-xl p-6 md:p-8 shadow-sm mb-6">
        <div className="flex items-start justify-between gap-4 mb-4">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">{vendor.name}</h1>
            <p className="text-gray-500 mt-1">{vendor.description ?? 'No description yet.'}</p>
          </div>
          <div className="flex items-center gap-2">
            <span
              className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium capitalize ${
                vendor.verification_status === 'VERIFIED'
                  ? 'bg-green-100 text-green-800'
                  : 'bg-yellow-100 text-yellow-800'
              }`}
            >
              {vendor.verification_status.toLowerCase()}
            </span>
            <span
              className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium capitalize ${
                vendor.status === 'ACTIVE' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800'
              }`}
            >
              {vendor.status.toLowerCase()}
            </span>
          </div>
        </div>

        {vendor.verification_status === 'PENDING' && (
          <p className="text-sm text-yellow-800 bg-yellow-50 border border-yellow-200 rounded-lg px-3 py-2 mb-4">
            Verification pending — your profile is active while we complete checks.
          </p>
        )}

        <dl className="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
          <div>
            <dt className="text-gray-500">Location</dt>
            <dd className="text-gray-900 font-medium">{[vendor.city, vendor.state].filter(Boolean).join(', ') || '—'}</dd>
          </div>
          <div>
            <dt className="text-gray-500">Application</dt>
            <dd className="text-gray-900 font-medium truncate">{vendor.application_id}</dd>
          </div>
          <div>
            <dt className="text-gray-500">Member since</dt>
            <dd className="text-gray-900 font-medium">{formatDate(vendor.created_at)}</dd>
          </div>
        </dl>
      </div>

      <VendorTabs vendorId={vendor.id} active="overview" />
    </main>
  );
}

import type { VendorPayout } from '@/application/vendor';
import { VendorTabs } from './VendorTabs';
import { formatDate, formatMinor } from './format';

// Payouts (server, read-only): stub ledger — rows are seeded backend-side
// after settlement, so the empty state explains the cadence.

export function VendorPayoutsView({ vendorId, payouts }: { vendorId: string; payouts: VendorPayout[] }) {
  return (
    <main className="p-6 max-w-4xl mx-auto">
      <VendorTabs vendorId={vendorId} active="payouts" />

      <div className="bg-white rounded-xl p-6 md:p-8 shadow-sm mb-6">
        <h1 className="text-2xl font-bold text-gray-900 mb-2">Payouts</h1>
        <p className="text-sm text-gray-500">
          Payouts are generated after order settlement and released to your registered bank account.
        </p>
      </div>

      {payouts.length === 0 ? (
        <div className="text-center py-12 text-gray-500">No payouts yet.</div>
      ) : (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
          <table className="min-w-full text-sm">
            <thead>
              <tr className="text-left text-gray-500 border-b border-gray-200">
                <th className="px-4 py-3 font-medium">Period</th>
                <th className="px-4 py-3 font-medium">Status</th>
                <th className="px-4 py-3 font-medium">Amount</th>
                <th className="px-4 py-3 font-medium">Reference</th>
                <th className="px-4 py-3 font-medium">Created</th>
              </tr>
            </thead>
            <tbody>
              {payouts.map((payout) => (
                <tr key={payout.id} className="border-b border-gray-100 last:border-0">
                  <td className="px-4 py-3 font-medium text-gray-900">{payout.period}</td>
                  <td className="px-4 py-3">
                    <span
                      className={`inline-flex px-2 py-1 text-xs font-medium rounded-full ${
                        payout.status === 'PAID' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                      }`}
                    >
                      {payout.status.toLowerCase()}
                    </span>
                  </td>
                  <td className="px-4 py-3">{formatMinor(payout.amount_minor)}</td>
                  <td className="px-4 py-3 text-gray-500">{payout.reference ?? '—'}</td>
                  <td className="px-4 py-3 text-gray-500">{formatDate(payout.created_at)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </main>
  );
}

import { PanditEarningsClient } from '@/presentation/pandit/PanditEarningsClient';

export const dynamic = 'force-dynamic';

export default function PartnerEarningsPage() {
  return (
    <main className="space-y-6">
      <div className="rounded-lg border border-[#EFE3D3] bg-[#FFF7ED] p-6">
        <h1 className="text-2xl font-bold text-[#2C2033]">Partner Earnings &amp; TDS Ledger</h1>
        <p className="mt-1 text-sm text-[#2A2421]">
          Live subledger settlement obligations, commission deductions, TDS u/s 194C/194J withholdings, and approved payouts.
        </p>
      </div>

      <PanditEarningsClient />
    </main>
  );
}


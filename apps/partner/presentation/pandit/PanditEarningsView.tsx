export interface PanditLedgerSummary {
  gross_amount_minor: number;
  commission_minor: number;
  tax_withheld_minor: number;
  net_payable_minor: number;
  paid_out_minor: number;
  pending_payout_minor: number;
  headline_net_minor: number;
  headline_net_formatted: string;
}

export interface PanditObligationItem {
  id: string;
  booking_id?: string | null;
  gross_amount_minor: number;
  commission_minor: number;
  tax_withheld_minor: number;
  net_payable_minor: number;
  currency: string;
  status: string;
  created_at?: string | null;
}

export interface PanditPayoutItem {
  id: string;
  amount_minor: number;
  currency: string;
  status: string;
  payout_reference?: string | null;
  approved_at?: string | null;
  created_at?: string | null;
}

export interface PanditEarningsPayload {
  pandit_profile_id: string;
  source?: 'subledger' | 'assignment_accrual';
  currency?: string;
  total_earnings_minor: number;
  total_earnings_formatted: string;
  completed_assignments_count: number;
  pending_earnings_minor: number;
  pending_earnings_formatted: string;
  pending_assignments_count: number;
  ledger?: PanditLedgerSummary;
  obligations?: PanditObligationItem[];
  payouts?: PanditPayoutItem[];
}

function formatInrMinor(minor: number): string {
  return `₹${(minor / 100).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

export function PanditEarningsView({
  earnings,
  error,
}: {
  earnings: PanditEarningsPayload | null;
  error?: string | null;
}) {
  if (error) {
    return (
      <div
        role="alert"
        data-testid="earnings-unavailable-alert"
        className="rounded-lg border border-[#B74717] bg-[#FFF7ED] p-5 text-[#2A2421]"
      >
        <p className="text-base font-semibold text-[#B74717]">Earnings ledger unavailable</p>
        <p className="mt-1 text-sm text-[#2A2421]">{error}</p>
      </div>
    );
  }

  if (!earnings) {
    return (
      <div className="rounded-lg border border-dashed border-[#EFE3D3] bg-[#FFF7ED] p-6 text-sm text-[#2A2421]">
        No verified settlement records found for this profile yet.
      </div>
    );
  }

  const ledger = earnings.ledger;
  const obligations = earnings.obligations ?? [];
  const payouts = earnings.payouts ?? [];

  return (
    <div className="space-y-6 text-[#2A2421]">
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div className="rounded-lg border border-[#EFE3D3] bg-[#FFF7ED] p-4">
          <div className="text-xs font-semibold uppercase tracking-wide text-[#2C2033]">
            Net Payable ({earnings.source === 'subledger' ? 'Subledger' : 'Accrual'})
          </div>
          <div className="mt-1 text-2xl font-bold text-[#2C2033]">
            {ledger ? formatInrMinor(ledger.headline_net_minor) : `₹${earnings.total_earnings_formatted}`}
          </div>
          <div className="mt-1 text-xs text-[#2A2421]/80">
            {earnings.completed_assignments_count} completed rituals
          </div>
        </div>

        <div className="rounded-lg border border-[#EFE3D3] bg-[#FFF7ED] p-4">
          <div className="text-xs font-semibold uppercase tracking-wide text-[#2C2033]">Gross &amp; Commission</div>
          <div className="mt-1 text-2xl font-bold text-[#2C2033]">
            {formatInrMinor(ledger?.gross_amount_minor ?? earnings.total_earnings_minor)}
          </div>
          <div className="mt-1 text-xs text-[#2A2421]/80">
            Platform fee deducted: {formatInrMinor(ledger?.commission_minor ?? 0)}
          </div>
        </div>

        <div className="rounded-lg border border-[#EFE3D3] bg-[#FFF7ED] p-4">
          <div className="text-xs font-semibold uppercase tracking-wide text-[#B74717]">
            TDS Withheld (u/s 194C / 194J)
          </div>
          <div className="mt-1 text-2xl font-bold text-[#B74717]">
            {formatInrMinor(ledger?.tax_withheld_minor ?? 0)}
          </div>
          <div className="mt-1 text-xs text-[#2A2421]/80">
            Form 16A certificate issued quarterly via Tax Ledger
          </div>
        </div>

        <div className="rounded-lg border border-[#EFE3D3] bg-[#EFE3D3]/45 p-4">
          <div className="text-xs font-semibold uppercase tracking-wide text-[#2C2033]">Paid Out &amp; Upcoming</div>
          <div className="mt-1 text-2xl font-bold text-[#2C2033]">
            {formatInrMinor(ledger?.paid_out_minor ?? 0)}
          </div>
          <div className="mt-1 text-xs text-[#2A2421]/80">
            Upcoming bookings: ₹{earnings.pending_earnings_formatted} ({earnings.pending_assignments_count})
          </div>
        </div>
      </div>

      {obligations.length > 0 ? (
        <section className="rounded-lg border border-[#EFE3D3] bg-[#FFF7ED] p-4">
          <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-[#2C2033]">
            Settlement Obligations (Gross → Withholding → Net)
          </h3>
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead className="border-b border-[#EFE3D3] text-xs uppercase text-[#2C2033]">
                <tr>
                  <th className="py-2 pr-4">Booking</th>
                  <th className="py-2 pr-4">Gross</th>
                  <th className="py-2 pr-4">Fee</th>
                  <th className="py-2 pr-4">TDS Withheld</th>
                  <th className="py-2 pr-4">Net Payable</th>
                  <th className="py-2">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#EFE3D3]">
                {obligations.map((row) => (
                  <tr key={row.id}>
                    <td className="py-2 pr-4 font-mono text-xs">{row.booking_id ?? row.id}</td>
                    <td className="py-2 pr-4">{formatInrMinor(row.gross_amount_minor)}</td>
                    <td className="py-2 pr-4">{formatInrMinor(row.commission_minor)}</td>
                    <td className="py-2 pr-4 text-[#B74717]">{formatInrMinor(row.tax_withheld_minor)}</td>
                    <td className="py-2 pr-4 font-semibold text-[#2C2033]">{formatInrMinor(row.net_payable_minor)}</td>
                    <td className="py-2">
                      <span className="rounded-full border border-[#2C2033]/20 bg-[#EFE3D3] px-2.5 py-0.5 text-xs font-medium text-[#2C2033]">
                        {row.status}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </section>
      ) : null}

      {payouts.length > 0 ? (
        <section className="rounded-lg border border-[#EFE3D3] bg-[#FFF7ED] p-4">
          <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-[#2C2033]">
            Dual-Custody Disbursements
          </h3>
          <ul className="divide-y divide-[#EFE3D3] text-sm">
            {payouts.map((p) => (
              <li key={p.id} className="flex flex-wrap items-center justify-between gap-2 py-2.5">
                <div>
                  <span className="font-semibold text-[#2C2033]">{formatInrMinor(p.amount_minor)}</span>
                  {p.payout_reference ? (
                    <span className="ml-2 font-mono text-xs text-[#2A2421]/80">UTR: {p.payout_reference}</span>
                  ) : null}
                </div>
                <span className="rounded-full bg-[#2C2033] px-3 py-0.5 text-xs font-medium text-[#FFF7ED]">
                  {p.status}
                </span>
              </li>
            ))}
          </ul>
        </section>
      ) : null}
    </div>
  );
}


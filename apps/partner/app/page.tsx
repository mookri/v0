import Link from 'next/link';

export default function PartnerHome() {
  return (
    <main className="space-y-6 text-[#2A2421]">
      <section className="rounded-lg border border-[#EFE3D3] bg-[#FFF7ED] p-6">
        <h1 className="text-2xl font-bold text-[#2C2033]">Today&apos;s Courtyard Work</h1>
        <p className="mt-1 text-sm text-[#2A2421]">
          High-contrast ritual dispatch mode for Pandits, Temple Managers, and Samagri Vendors.
        </p>

        <nav className="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5" aria-label="Courtyard quick links">
          <Link
            href="/pandit"
            className="flex min-h-[48px] items-center justify-center rounded-md bg-[#2C2033] px-4 py-3 text-sm font-semibold text-[#FFF7ED] hover:bg-[#2C2033]/90"
          >
            Pandit Courtyard
          </Link>
          <Link
            href="/assignments"
            className="flex min-h-[48px] items-center justify-center rounded-md bg-[#F16A24] px-4 py-3 text-sm font-semibold text-[#FFF7ED] hover:bg-[#B74717]"
          >
            Assignments
          </Link>
          <Link
            href="/calendar"
            className="flex min-h-[48px] items-center justify-center rounded-md border border-[#2C2033]/30 bg-[#EFE3D3] px-4 py-3 text-sm font-semibold text-[#2C2033] hover:bg-[#EFE3D3]/80"
          >
            Ritual Calendar
          </Link>
          <Link
            href="/temples"
            className="flex min-h-[48px] items-center justify-center rounded-md border border-[#2C2033]/30 bg-[#FFF7ED] px-4 py-3 text-sm font-semibold text-[#2C2033] hover:bg-[#EFE3D3]"
          >
            Temples
          </Link>
          <Link
            href="/earnings"
            className="flex min-h-[48px] items-center justify-center rounded-md border border-[#B74717]/40 bg-[#FFF7ED] px-4 py-3 text-sm font-semibold text-[#B74717] hover:bg-[#EFE3D3]"
          >
            Ledger Earnings
          </Link>
        </nav>
      </section>
    </main>
  );
}

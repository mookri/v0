export const dynamic = 'force-dynamic';

export default function PartnerCalendarPage() {
  return (
    <main className="space-y-6 text-[#2A2421]">
      <section className="rounded-lg border border-[#EFE3D3] bg-[#FFF7ED] p-6">
        <h1 className="text-2xl font-bold text-[#2C2033]">Courtyard Ritual Schedule</h1>
        <p className="mt-1 text-sm text-[#2A2421]">
          Read-only schedule shell is offline-safe for courtyard preview. Confirmations, starts, and completions require live online sync.
        </p>
        <div className="mt-4 flex flex-wrap gap-3">
          <a
            href="/pandit"
            className="inline-flex min-h-[48px] items-center rounded-md bg-[#2C2033] px-4 py-2 text-sm font-semibold text-[#FFF7ED] hover:bg-[#2C2033]/90"
          >
            Manage Availability &amp; Assignments
          </a>
        </div>
      </section>
    </main>
  );
}


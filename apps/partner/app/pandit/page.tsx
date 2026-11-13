import { partnerApi } from '@/adapters/backend/partner-api-client';
import { PanditProfileView } from '@/presentation/pandit/PanditProfileView';
import { PanditAvailabilityView } from '@/presentation/pandit/PanditAvailabilityView';
import { PanditAssignmentsView } from '@/presentation/pandit/PanditAssignmentsView';
import { PanditReviewsView } from '@/presentation/pandit/PanditReviewsView';
import { PanditEarningsClient } from '@/presentation/pandit/PanditEarningsClient';
import type { PanditEarningsPayload } from '@/presentation/pandit/PanditEarningsView';

// Per-request data. Prerendering this page at build time made `next build`
// block for 60s per attempt against an API the builder cannot reach, and
// would in any case bake one pandit's data into the image.
export const dynamic = 'force-dynamic';

export default async function PanditDashboardPage() {
  let profile: Record<string, unknown> | null = null;
  let assignments: unknown[] = [];
  let earnings: PanditEarningsPayload | null = null;
  let earningsError: string | null = null;
  let availability: { day: string; start: string; end: string }[] = [];
  let reviews: unknown[] = [];

  try {
    profile = (await partnerApi.getPanditProfile()) as Record<string, unknown>;
  } catch {
    profile = null;
  }

  try {
    const a = await partnerApi.getPanditAssignments({ limit: 20 });
    assignments = Array.isArray(a) ? a : (a as { data?: unknown[] })?.data ?? [];
  } catch {
    assignments = [];
  }

  try {
    earnings = (await partnerApi.getPanditEarnings()) as PanditEarningsPayload;
  } catch {
    earnings = null;
    earningsError = 'Live earnings ledger unavailable during server render; checking BFF ledger…';
  }

  try {
    const av = await partnerApi.getPanditAvailability();
    availability = Array.isArray(av) ? av : (av as { data?: unknown[] })?.data ?? [];
  } catch {
    availability = [];
  }

  try {
    const r = await partnerApi.getPanditReviews();
    reviews = Array.isArray(r) ? r : (r as { data?: unknown[] })?.data ?? [];
  } catch {
    reviews = [];
  }

  return (
    <div className="space-y-8 text-[#2A2421]">
      <h1 className="text-2xl font-bold text-[#2C2033]">Pandit Courtyard Dashboard</h1>

      <section className="rounded-lg border border-[#EFE3D3] bg-[#FFF7ED] p-4">
        <PanditProfileView profile={profile as Parameters<typeof PanditProfileView>[0]['profile']} />
      </section>

      <section className="rounded-lg border border-[#EFE3D3] bg-[#FFF7ED] p-4">
        <PanditAvailabilityView slots={availability} />
      </section>

      <section className="rounded-lg border border-[#EFE3D3] bg-[#FFF7ED] p-4">
        <h2 className="mb-2 text-lg font-semibold text-[#2C2033]">Assignments</h2>
        <PanditAssignmentsView assignments={assignments as Parameters<typeof PanditAssignmentsView>[0]['assignments']} />
      </section>

      <section className="rounded-lg border border-[#EFE3D3] bg-[#FFF7ED] p-4">
        <h2 className="mb-2 text-lg font-semibold text-[#2C2033]">Reviews</h2>
        <PanditReviewsView reviews={reviews as Parameters<typeof PanditReviewsView>[0]['reviews']} />
      </section>

      <section className="rounded-lg border border-[#EFE3D3] bg-[#FFF7ED] p-4">
        <h2 className="mb-2 text-lg font-semibold text-[#2C2033]">Earnings &amp; Settlements</h2>
        <PanditEarningsClient initialEarnings={earnings} initialError={earnings ? null : earningsError} />
      </section>
    </div>
  );
}

import { partnerApi } from '@/adapters/backend/partner-api-client';
import { PanditProfileView } from '@/presentation/pandit/PanditProfileView';
import { PanditAvailabilityView } from '@/presentation/pandit/PanditAvailabilityView';
import { PanditAssignmentsView } from '@/presentation/pandit/PanditAssignmentsView';
import { PanditReviewsView } from '@/presentation/pandit/PanditReviewsView';

export default async function PanditDashboardPage() {
  let profile: Record<string, unknown> | null = null;
  let assignments: unknown[] = [];
  let earnings: Record<string, unknown> | null = null;
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
    earnings = (await partnerApi.getPanditEarnings()) as Record<string, unknown>;
  } catch {
    earnings = null;
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
    <div className="p-6 max-w-4xl mx-auto space-y-8">
      <h1 className="text-2xl font-bold">Pandit Dashboard</h1>

      <section className="rounded-lg border border-gray-200 p-4">
        <PanditProfileView profile={profile as Parameters<typeof PanditProfileView>[0]['profile']} />
      </section>

      <section className="rounded-lg border border-gray-200 p-4">
        <PanditAvailabilityView slots={availability} />
      </section>

      <section className="rounded-lg border border-gray-200 p-4">
        <h2 className="text-lg font-semibold mb-2">Assignments</h2>
        <PanditAssignmentsView assignments={assignments as Parameters<typeof PanditAssignmentsView>[0]['assignments']} />
      </section>

      <section className="rounded-lg border border-gray-200 p-4">
        <h2 className="text-lg font-semibold mb-2">Reviews</h2>
        <PanditReviewsView reviews={reviews as Parameters<typeof PanditReviewsView>[0]['reviews']} />
      </section>

      <section className="rounded-lg border border-gray-200 p-4">
        <h2 className="text-lg font-semibold mb-2">Earnings</h2>
        {earnings ? (
          <pre className="text-xs text-gray-600 overflow-auto">{JSON.stringify(earnings, null, 2)}</pre>
        ) : (
          <p className="text-gray-500">No earnings data yet.</p>
        )}
      </section>
    </div>
  );
}

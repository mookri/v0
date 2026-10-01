import { headers } from 'next/headers';
import { notFound } from 'next/navigation';
import { onboardingActors, onboardingApps, getEvidence } from '@/server/onboarding';
import { isReviewer, reviewAudit } from '@/server/review';
import { getTrackDefinition } from '@navdhya/partner/src/onboarding/tracks';
import { missingSteps } from '@navdhya/partner/src/onboarding/steps';
import { DecisionForm } from '@/presentation/review/DecisionForm';
import { assertSameTenant } from '@navdhya/identity/src/ports/actor-resolver';

export default async function ReviewDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const auth = (await headers()).get('authorization') ?? '';
  const actor = await onboardingActors.resolve({ authorization: auth || undefined });
  if (!actor || !isReviewer(actor.roles)) {
    return (
      <main>
        <h1>Review</h1>
        <p>Sign in with a reviewer account to continue.</p>
      </main>
    );
  }
  const app = await onboardingApps.findById(id);
  if (!app) notFound();
  try {
    assertSameTenant(actor, { market: app.market });
  } catch {
    return (
      <main>
        <h1>Review</h1>
        <p>Not allowed.</p>
      </main>
    );
  }
  const def = getTrackDefinition(app.track, app.trackVersion);
  const evidence = getEvidence(id);
  const records = await reviewAudit.listByApplication(id);
  return (
    <main>
      <h1>
        {app.id} · {app.track} · {app.status}
      </h1>
      <h2>Evidence</h2>
      <pre>{JSON.stringify(evidence, null, 2)}</pre>
      <h2>Checklist</h2>
      <p>Missing: {missingSteps(def, evidence).join(', ') || 'none'}</p>
      <h2>Audit trail</h2>
      <ol>
        {records.map((r) => (
          <li key={r.id}>
            {r.atIso} · {r.reviewerId} · {r.decision}
            {r.reasonCode ? ` · ${r.reasonCode}` : ''}
            {r.note ? ` · ${r.note}` : ''}
          </li>
        ))}
      </ol>
      <h2>Decision</h2>
      <DecisionForm applicationId={id} />
      <a href="/review">Back to queue</a>
    </main>
  );
}

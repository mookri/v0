import { notFound, redirect } from 'next/navigation';
import { onboardingApps, getEvidence } from '@/server/onboarding';
import { getTrackDefinition } from '@navdhya/partner/src/onboarding/tracks';
import { isSubmittable, missingSteps } from '@navdhya/partner/src/onboarding/steps';
import { resolveOnboardingLocale, onboardingString } from '@/presentation/onboarding/strings';
import { ReviewClient } from '@/presentation/onboarding/ReviewClient';

export default async function OnboardingReviewPage({
  params,
  searchParams,
}: {
  params: Promise<{ id: string }>;
  searchParams: Promise<{ hl?: string }>;
}) {
  const { id } = await params;
  const { hl } = await searchParams;
  const locale = resolveOnboardingLocale(hl);
  const app = await onboardingApps.findById(id);
  if (!app) notFound();

  const def = getTrackDefinition(app.track, app.trackVersion);
  const evidence = getEvidence(id);
  const missing = missingSteps(def, evidence);

  if (missing.length > 0) {
    redirect(`/onboarding/${id}?hl=${locale}`);
  }

  const identity = (evidence['identity'] as { phone?: string; name?: string } | undefined) ?? {};
  const contactPhone = identity.phone || '';

  return (
    <main style={{ padding: '32px 16px', maxWidth: '800px', margin: '0 auto' }}>
      <h1 style={{ fontSize: '24px', fontWeight: '700', marginBottom: '24px' }}>
        {onboardingString(locale, 'steps.review.title')}
      </h1>
      <ReviewClient
        applicationId={id}
        contactPhone={contactPhone}
        track={app.track}
        evidence={evidence}
        locale={locale}
      />
    </main>
  );
}

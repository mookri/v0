import { notFound } from 'next/navigation';
import { onboardingApps, getEvidence } from '@/server/onboarding';
import { getTrackDefinition } from '@navdhya/partner/src/onboarding/tracks';
import { missingSteps } from '@navdhya/partner/src/onboarding/steps';
import { STEP_FIELDS } from '@/presentation/onboarding/step-fields';
import { StepForm } from '@/presentation/onboarding/StepForm';
import { onboardingString, resolveOnboardingLocale } from '@/presentation/onboarding/strings';

export default async function OnboardingStepPage({
  params,
  searchParams,
}: {
  params: Promise<{ id: string; step: string }>;
  searchParams: Promise<{ hl?: string }>;
}) {
  const { id, step } = await params;
  const { hl } = await searchParams;
  const locale = resolveOnboardingLocale(hl);
  const app = await onboardingApps.findById(id);
  if (!app) notFound();
  const def = getTrackDefinition(app.track, app.trackVersion);
  if (!def.steps.some((s) => s.id === step) || !STEP_FIELDS[step]) notFound();
  const evidence = getEvidence(id);
  const needs = missingSteps(def, evidence);
  const titleKey = `steps.${step}.title` as Parameters<typeof onboardingString>[1];

  return (
    <main>
      <h1>{onboardingString(locale, titleKey)}</h1>
      {needs.length > 0 ? (
        <p>
          {onboardingString(locale, 'common.needs')}: {needs.join(', ')}
        </p>
      ) : null}
      <StepForm applicationId={id} step={step} locale={locale} initial={evidence[step]} />
      <a href={`/onboarding/${id}`}>{onboardingString(locale, 'common.back')}</a>
    </main>
  );
}

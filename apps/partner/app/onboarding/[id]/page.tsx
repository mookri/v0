import { notFound } from 'next/navigation';
import { onboardingApps, getEvidence } from '@/server/onboarding';
import { getTrackDefinition } from '@navdhya/partner/src/onboarding/tracks';
import { missingSteps } from '@navdhya/partner/src/onboarding/steps';
import { onboardingString, resolveOnboardingLocale } from '@/presentation/onboarding/strings';

export default async function OnboardingOverviewPage({
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
  const needs = missingSteps(def, getEvidence(id));

  return (
    <main>
      <h1>
        {app.track} · {app.status}
      </h1>
      <ol>
        {def.steps.map((s) => (
          <li key={s.id}>
            <a href={`/onboarding/${id}/${s.id}?hl=${locale}`}>
              {s.id}
              {needs.includes(s.id) ? ' *' : ''}
            </a>
          </li>
        ))}
      </ol>
      {needs.length > 0 ? (
        <p>
          {onboardingString(locale, 'common.needs')}: {needs.join(', ')}
        </p>
      ) : (
        <a href={`/onboarding/${id}/review?hl=${locale}`}>{onboardingString(locale, 'common.continue')}</a>
      )}
    </main>
  );
}

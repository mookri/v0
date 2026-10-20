'use client';

import { useState } from 'react';
import { STEP_FIELDS, assembleStepValue, labelKey } from '@/presentation/onboarding/step-fields';
import { onboardingString, OnboardingLocale, OnboardingStringKey } from '@/presentation/onboarding/strings';

export function StepForm({
  applicationId,
  step,
  locale,
  initial,
}: {
  applicationId: string;
  step: string;
  locale: OnboardingLocale;
  initial: unknown;
}) {
  const layout = STEP_FIELDS[step];
  const [saved, setSaved] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const t = (key: OnboardingStringKey) => onboardingString(locale, key);
  const lt = (key: string) => t(labelKey(key));
  if (!layout) return <p>{t('common.unknownStep')}</p>;

  async function onSubmit(formData: FormData) {
    setError(null);
    setSaved(false);
    const inputs: Record<string, string | boolean> = {};
    for (const field of layout!.fields) {
      if (field.type === 'checkbox') inputs[field.name] = formData.get(field.name) === 'on';
      else inputs[field.name] = (formData.get(field.name) as string) ?? '';
    }
    let value: unknown;
    try {
      value = assembleStepValue(step, inputs);
    } catch {
      setError(t('common.invalidStep'));
      return;
    }
    const res = await fetch(`/api/onboarding/${applicationId}/step/${step}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', 'Idempotency-Key': `ik_${applicationId}_${step}` },
      body: JSON.stringify({ value }),
    });
    if (!res.ok) setError(t('common.saveFailed'));
    else setSaved(true);
  }

  const current = (initial ?? {}) as Record<string, unknown>;

  return (
    <form
      onSubmit={(e) => {
        e.preventDefault();
        void onSubmit(new FormData(e.currentTarget));
      }}
    >
      {layout.fields.map((field) => {
        if (field.type === 'info') return <p key={field.name}>{lt(field.labelKey)}</p>;
        if (field.type === 'checkbox') {
          return (
            <label key={field.name}>
              <input name={field.name} type="checkbox" defaultChecked={current[field.name] === true} /> {lt(field.labelKey)}
            </label>
          );
        }
        return (
          <label key={field.name}>
            {lt(field.labelKey)}
            <input
              name={field.name}
              type={field.type === 'number' ? 'number' : field.type === 'tel' ? 'tel' : 'text'}
              defaultValue={Array.isArray(current[field.name]) ? (current[field.name] as string[]).join(', ') : ((current[field.name] as string) ?? '')}
            />
          </label>
        );
      })}
      <button type="submit">{t('common.save')}</button>
      {saved ? <p role="status">{t('common.saved')}</p> : null}
      {error ? <p role="alert">{error}</p> : null}
    </form>
  );
}

import { headers } from 'next/headers';
import { onboardingActors } from '@/server/onboarding';
import { isReviewer, getReviewQueue } from '@/server/review';
import { DEFAULT_MARKET } from '@navdhya/identity/src/ports/actor-resolver';

export default async function ReviewQueuePage() {
  const auth = (await headers()).get('authorization') ?? '';
  const actor = await onboardingActors.resolve({ authorization: auth || undefined });
  if (!actor || !isReviewer(actor.roles)) {
    return (
      <main>
        <h1>Review queue</h1>
        <p>Sign in with a reviewer account to continue.</p>
      </main>
    );
  }
  const items = await getReviewQueue(actor.market ?? DEFAULT_MARKET, new Date().toISOString());
  return (
    <main>
      <h1>Review queue</h1>
      {items.length === 0 ? (
        <p>No applications awaiting review.</p>
      ) : (
        <ol>
          {items.map((item) => (
            <li key={item.id}>
              <a href={`/review/${item.id}`}>
                {item.id} · {item.track} · {item.status}
                {item.slaBreached ? ' · SLA breached' : ''}
              </a>
            </li>
          ))}
        </ol>
      )}
    </main>
  );
}

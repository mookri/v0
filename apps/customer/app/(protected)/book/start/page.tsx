import { resolveBookStart } from '@/application/bookings/resolve-book-start';

export default async function BookStartPage({
  searchParams,
}: {
  searchParams: Promise<{ intent?: string }>;
}) {
  const { intent } = await searchParams;
  const result = resolveBookStart(intent, new Date().toISOString());

  if (result.status === 'missing' || result.status === 'invalid') {
    return (
      <main>
        <h1>Start a booking</h1>
        <p>
          {result.status === 'missing'
            ? 'Choose a puja to begin — no booking link was provided.'
            : 'This booking link is no longer valid. Please start again.'}
        </p>
        <a href="/discover">Browse pujas</a>
      </main>
    );
  }

  if (result.status === 'incomplete') {
    return (
      <main>
        <h1>Almost there</h1>
        <p>To complete your booking we still need: {result.needs.join(', ')}.</p>
        <a href="/discover">Continue</a>
      </main>
    );
  }

  return (
    <main>
      <h1>Review your booking</h1>
      <dl>
        <dt>Puja</dt>
        <dd>{result.draft.pujaId}</dd>
        {result.draft.panditId ? (
          <>
            <dt>Pandit</dt>
            <dd>{result.draft.panditId}</dd>
          </>
        ) : null}
        {result.draft.slotRef ? (
          <>
            <dt>Slot</dt>
            <dd>{result.draft.slotRef}</dd>
          </>
        ) : null}
      </dl>
    </main>
  );
}

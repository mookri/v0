/**
 * QuickBook — B1 handoff semantics.
 *
 * Real catalog → the modal builds a signed book/start intent (puja id,
 * ISO slot, idempotency key) and hands off to the customer app, which
 * owns sign-in, quoting and POST /api/bookings — no pretend-local
 * confirmation. Sample catalog → the original demo path stays intact.
 */
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import QuickBook from '@/components/QuickBook';
import { buildBookStartUrl } from '@/lib/booking-handoff/handoff-url';
import { navigateTo } from '@/lib/navigation';

jest.mock('@/lib/booking-handoff/handoff-url', () => ({
  buildBookStartUrl: jest.fn(() => 'https://customer.test/book/start?intent=signed'),
}));
jest.mock('@/lib/navigation', () => ({
  navigateTo: jest.fn(),
}));

const buildMock = buildBookStartUrl as jest.Mock;
const navigateMock = navigateTo as jest.Mock;
const fetchMock = jest.fn();

beforeEach(() => {
  buildMock.mockClear();
  navigateMock.mockClear();
  fetchMock.mockReset();
  global.fetch = fetchMock as unknown as typeof fetch;
});

function catalogResponse(items: Array<Record<string, unknown>>) {
  return { ok: true, json: async () => ({ data: { items } }) };
}

test('falls back to the sample catalog when /api/pujas returns nothing', async () => {
  fetchMock.mockResolvedValue(catalogResponse([]));

  render(<QuickBook open onClose={jest.fn()} />);

  await waitFor(() => expect(fetchMock).toHaveBeenCalledWith('/api/pujas'));
  expect(await screen.findByText('Home Puja - Simple')).toBeInTheDocument();
  expect(buildMock).not.toHaveBeenCalled();
});

test('loads the real catalog and hands the pick to the customer app', async () => {
  fetchMock.mockResolvedValue(
    catalogResponse([
      {
        id: 'cat_1',
        sku: 'SKU1',
        name: 'Griha Pravesh Puja',
        description: 'Real catalog item',
        currency: 'INR',
        price_minor: 49900,
      },
    ]),
  );

  // Navigation seam: jsdom cannot navigate, so the destination is
  // asserted through the mocked navigateTo.
  render(<QuickBook open onClose={jest.fn()} />);

  expect(await screen.findByText('Griha Pravesh Puja')).toBeInTheDocument();

  fireEvent.click(screen.getByText('Continue')); // step 1 → 2 (date/time prefilled)
  fireEvent.click(screen.getByText('Continue')); // step 2 → 3

  expect(screen.getByText('Review & Continue')).toBeInTheDocument();
  fireEvent.click(screen.getByText('Continue to Book'));

  expect(navigateMock).toHaveBeenCalledTimes(1);
  expect(navigateMock).toHaveBeenCalledWith('https://customer.test/book/start?intent=signed');

  expect(buildMock).toHaveBeenCalledTimes(1);
  const [origin, fields] = buildMock.mock.calls[0] as [string, Record<string, string>];
  expect(typeof origin).toBe('string');
  expect(origin.length).toBeGreaterThan(0);
  expect(fields.pujaId).toBe('cat_1');
  expect(fields.slotRef).toMatch(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/);
  expect(fields.idempotencyKey).toBeTruthy();
  expect(fields.nowIso).toBeTruthy();
});

test('sample catalog keeps the demo confirmation path (no handoff)', async () => {
  fetchMock.mockResolvedValue(catalogResponse([]));

  render(<QuickBook open onClose={jest.fn()} />);
  await screen.findByText('Home Puja - Simple');

  fireEvent.click(screen.getByText('Continue')); // → schedule
  fireEvent.click(screen.getByText('Continue')); // → contact & payment

  expect(screen.getByText('Contact & Payment')).toBeInTheDocument();
  expect(screen.getByText(/Pay & Book/)).toBeInTheDocument();
  expect(buildMock).not.toHaveBeenCalled();
});

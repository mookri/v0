/**
 * OtpScreen — backend-truth attempt semantics (M6).
 *
 * `CustomerOtpService::MAX_ATTEMPTS = 3`: three wrong codes exhaust the
 * challenge row (delete), and no `locked_until` exists — so the UI must
 * show 3 attempts, must drop the challenge on exhaustion, and must never
 * fabricate a time-based lock (the old 15-minute `setLockedUntil` lie).
 * Real `Retry-After` waits from a backend 429 remain legitimate.
 */
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { OtpScreen } from '@/components/auth/OtpScreen';
import { authPost, getChallenge, clearChallenge } from '@/lib/auth/auth-client';

jest.mock('@/lib/auth/auth-client', () => ({
  authPost: jest.fn(),
  getChallenge: jest.fn(),
  setChallenge: jest.fn(),
  clearChallenge: jest.fn(),
  redeemBody: jest.fn((challengeId: string, proof: string) => ({ challengeId, proof })),
  toE164: jest.fn(() => '+919876543210'),
  newIdempotencyKey: jest.fn(() => 'idem-key'),
  AuthRequestError: class AuthRequestError extends Error {},
}));

const authPostMock = authPost as jest.Mock;
const getChallengeMock = getChallenge as jest.Mock;
const clearChallengeMock = clearChallenge as jest.Mock;

function typeOtp(value = '123456') {
  const inputs = screen.getAllByRole('textbox') as HTMLInputElement[];
  value.split('').forEach((digit, i) => {
    fireEvent.change(inputs[i], { target: { value: digit } });
  });
}

function submit() {
  const form = document.querySelector('form')!;
  fireEvent.submit(form);
}

function renderScreen() {
  return render(
    <OtpScreen
      phone="9876543210"
      countryCode="IN"
      otpSentReason="LOGIN"
      transitionTo={jest.fn()}
      animationVariants={{}}
    />,
  );
}

const unauthorized = {
  status: 401,
  code: 'UNAUTHENTICATED',
  message: 'Invalid or expired code.',
};

beforeEach(() => {
  jest.clearAllMocks();
  getChallengeMock.mockReturnValue({ id: 'chal_1', purpose: 'login' });
});

test('three failed verifies exhaust the challenge — no fabricated 15-minute lock', async () => {
  authPostMock.mockRejectedValue(unauthorized);
  renderScreen();
  typeOtp();

  // Failure 1 → 2 attempts remaining
  submit();
  await screen.findByText(/\(2 attempts remaining\)/);

  // Failure 2 → 1 attempt remaining
  submit();
  await screen.findByText(/\(1 attempts remaining\)/);

  // Failure 3 → challenge exhausted: local mirror matches backend truth
  submit();
  await screen.findByText('Too many failed attempts. Please request a new code.');

  // The challenge is dead server-side (row deleted) — drop it locally.
  expect(clearChallengeMock).toHaveBeenCalledTimes(1);

  // No fabricated time-based lock anywhere in the UI.
  expect(screen.queryByText(/Try again in/)).toBeNull();

  // Exhausted state blocks any further redeem — exactly 3 authPost calls.
  submit();
  expect(authPostMock).toHaveBeenCalledTimes(3);
  expect(screen.getByText('Too many failed attempts. Please request a new code.')).toBeInTheDocument();
});

test('backend 429 keeps a real Retry-After wait (genuine backend state)', async () => {
  authPostMock.mockRejectedValueOnce({
    status: 429,
    code: 'RATE_LIMITED',
    message: 'Too many requests.',
    retryAfter: 30,
  });
  renderScreen();
  typeOtp();

  submit();
  await screen.findByText('Too many requests.');

  // Second click inside the wait → countdown message from the real window.
  submit();
  await screen.findByText(/Try again in \d+s\./);
});

test('successful redeem clears the challenge and transitions (unchanged happy path)', async () => {
  authPostMock.mockResolvedValue({ data: {} });
  const transitionTo = jest.fn();
  render(
    <OtpScreen
      phone="9876543210"
      countryCode="IN"
      otpSentReason="LOGIN"
      transitionTo={transitionTo}
      animationVariants={{}}
    />,
  );
  typeOtp();
  submit();

  await waitFor(() => expect(transitionTo).toHaveBeenCalledWith('SUCCESS'));
  expect(clearChallengeMock).toHaveBeenCalledTimes(1);
});

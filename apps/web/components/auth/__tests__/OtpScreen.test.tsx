/**
 * OtpScreen — decoupled MSG91 widget OTP & backend-truth attempt semantics.
 */
import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { OtpScreen } from '@/components/auth/OtpScreen';
import { authPost, getChallenge, clearChallenge } from '@/lib/auth/auth-client';
import { verifyOtp, retryOtp, OtpWidgetError } from '@navdhya/otp/widget';

jest.mock('@navdhya/otp/client', () => ({
  createOtpClient: jest.fn(() => ({
    loadConfig: jest.fn().mockResolvedValue({
      widget_id: 'wid_test',
      token_auth: 'tok_test',
      resend: { delays_seconds: [60, 180, 540], max_resends: 3 },
    }),
  })),
}));

jest.mock('@navdhya/otp/widget', () => ({
  getWidget: jest.fn().mockResolvedValue(undefined),
  sendOtp: jest.fn().mockResolvedValue(undefined),
  verifyOtp: jest.fn().mockResolvedValue('fake.jwt.token'),
  retryOtp: jest.fn().mockResolvedValue(undefined),
  OtpWidgetError: class OtpWidgetError extends Error {
    kind: string;
    detail?: string;
    constructor(kind: string, message: string, detail?: string) {
      super(message);
      this.name = 'OtpWidgetError';
      this.kind = kind;
      this.detail = detail;
    }
  },
}));

jest.mock('@navdhya/otp/resend', () => ({
  useResendCountdown: jest.fn(() => ({
    secondsLeft: 0,
    canResend: true,
    exhausted: false,
  })),
  resendLabel: jest.fn((sec: number) => `${sec}s`),
}));

jest.mock('@/lib/auth/auth-client', () => ({
  authPost: jest.fn(),
  getChallenge: jest.fn(),
  setChallenge: jest.fn(),
  clearChallenge: jest.fn(),
  toE164: jest.fn(() => '+919876543210'),
  newIdempotencyKey: jest.fn(() => 'idem-key'),
  AuthRequestError: class AuthRequestError extends Error {},
}));

const authPostMock = authPost as jest.Mock;
const getChallengeMock = getChallenge as jest.Mock;
const clearChallengeMock = clearChallenge as jest.Mock;
const verifyOtpMock = verifyOtp as jest.Mock;
const retryOtpMock = retryOtp as jest.Mock;

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

function renderScreen(props: Partial<React.ComponentProps<typeof OtpScreen>> = {}) {
  return render(
    <OtpScreen
      phone="9876543210"
      countryCode="IN"
      otpSentReason="LOGIN"
      transitionTo={jest.fn()}
      animationVariants={{}}
      {...props}
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
  verifyOtpMock.mockResolvedValue('fake.jwt.token');
});

test('successful redeem exchanges widget JWT with BFF and transitions', async () => {
  authPostMock.mockResolvedValue({ data: {} });
  const transitionTo = jest.fn();
  renderScreen({ transitionTo });
  typeOtp('123456');
  submit();

  await waitFor(() => expect(verifyOtpMock).toHaveBeenCalledWith('123456'));
  await waitFor(() =>
    expect(authPostMock).toHaveBeenCalledWith('/api/auth/login', {
      challengeId: 'chal_1',
      token: 'fake.jwt.token',
    }),
  );
  await waitFor(() => expect(transitionTo).toHaveBeenCalledWith('SUCCESS'));
  expect(clearChallengeMock).toHaveBeenCalledTimes(1);
});

test('widget verification failure surfaces invalid code error and halts BFF call', async () => {
  verifyOtpMock.mockRejectedValue(new OtpWidgetError('verify_failed', 'widget error'));
  renderScreen();
  typeOtp('123456');
  submit();

  await screen.findByText('Invalid or expired code.');
  expect(authPostMock).not.toHaveBeenCalled();
});

test('202 mfa_required reveals TOTP input and second submit sends totpCode', async () => {
  authPostMock.mockResolvedValueOnce({ data: { data: { status: 'mfa_required' } } });
  const transitionTo = jest.fn();
  renderScreen({ transitionTo });
  typeOtp('123456');
  submit();

  await screen.findByText(/Enter the 6-digit code from your authenticator app/);

  authPostMock.mockResolvedValueOnce({ data: {} });
  typeOtp('654321');
  submit();

  await waitFor(() =>
    expect(authPostMock).toHaveBeenLastCalledWith('/api/auth/login', {
      challengeId: 'chal_1',
      totpCode: '654321',
    }),
  );
  await waitFor(() => expect(transitionTo).toHaveBeenCalledWith('SUCCESS'));
});

test('three failed verifies exhaust the challenge — no fabricated 15-minute lock', async () => {
  authPostMock.mockRejectedValue(unauthorized);
  renderScreen({ otpSentReason: 'SIGNUP' });
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
  await screen.findByText(/Too many attempts\. Try again in \d+s\./);
});

test('resend triggers BFF /api/auth/otp/resend and invokes widget retryOtp', async () => {
  authPostMock.mockResolvedValueOnce({ data: { data: { channel: '11', resends_left: 2 } } });
  renderScreen();

  const resendBtn = screen.getByRole('button', { name: /resend code/i });
  fireEvent.click(resendBtn);

  await waitFor(() =>
    expect(authPostMock).toHaveBeenCalledWith('/api/auth/otp/resend', {
      challengeId: 'chal_1',
    }),
  );
  await waitFor(() => expect(retryOtpMock).toHaveBeenCalledWith('11'));
});

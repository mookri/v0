// @vitest-environment jsdom
import { describe, it, expect, beforeEach, vi } from 'vitest';
import {
  getWidget,
  sendOtp,
  verifyOtp,
  retryOtp,
  getWidgetData,
  isCaptchaVerified,
  resetOtpWidgetForTests,
  OtpWidgetError,
  WIDGET_SCRIPT_SRC,
} from '@navdhya/otp/widget';

describe('MSG91 widget loader', () => {
  beforeEach(() => {
    resetOtpWidgetForTests();
    vi.restoreAllMocks();
  });

  const setupGlobals = (overrides?: Partial<typeof window>) => {
    window.sendOtp = vi.fn();
    window.retryOtp = vi.fn();
    window.verifyOtp = vi.fn();
    if (overrides) {
      Object.assign(window, overrides);
    }
  };

  it('injects script once and initializes widget singleton', async () => {
    let scriptEl: HTMLScriptElement | null = null;
    const createElementSpy = vi.spyOn(document, 'createElement');

    const widgetPromise = getWidget({ widgetId: 'wid_123', tokenAuth: 'tok_abc' });

    // Find injected script and simulate load
    const scripts = document.querySelectorAll(`script[src="${WIDGET_SCRIPT_SRC}"]`);
    expect(scripts.length).toBe(1);
    scriptEl = scripts[0] as HTMLScriptElement;

    // Simulate window.initSendOTP callback
    window.initSendOTP = vi.fn((cfg: any) => {
      expect(cfg.widgetId).toBe('wid_123');
      expect(cfg.tokenAuth).toBe('tok_abc');
      expect(cfg.exposeMethods).toBe(true);

      // simulate setting globals
      window.sendOtp = vi.fn();
      window.verifyOtp = vi.fn();
      window.retryOtp = vi.fn();
    });

    scriptEl.onload?.(new Event('load'));
    await widgetPromise;

    // Second call to getWidget should reuse singleton without adding new script
    await getWidget({ widgetId: 'wid_123', tokenAuth: 'tok_abc' });
    expect(document.querySelectorAll(`script[src="${WIDGET_SCRIPT_SRC}"]`).length).toBe(1);
  });

  it('strips leading + from phone identifiers in sendOtp', async () => {
    const mockSendOtp = vi.fn((phone: string, success: Function) => {
      success({ type: 'success', message: 'sent' });
    });
    setupGlobals({ sendOtp: mockSendOtp });

    // Mark widget ready
    const p = sendOtp('+919876543210');
    await expect(p).resolves.toBeUndefined();

    expect(mockSendOtp).toHaveBeenCalledWith(
      '919876543210',
      expect.any(Function),
      expect.any(Function),
    );
  });

  it('resolves verifyOtp promise with JWT token on success', async () => {
    const mockVerifyOtp = vi.fn((code: string, success: Function) => {
      success({ type: 'success', message: 'jwt.token.here' });
    });
    setupGlobals({ verifyOtp: mockVerifyOtp });

    const token = await verifyOtp('123456');
    expect(token).toBe('jwt.token.here');
  });

  it('rejects verifyOtp with typed OtpWidgetError on failure', async () => {
    const mockVerifyOtp = vi.fn((code: string, success: Function, failure: Function) => {
      failure({ type: 'error', message: 'invalid_code' });
    });
    setupGlobals({ verifyOtp: mockVerifyOtp });

    await expect(verifyOtp('000000')).rejects.toThrow(OtpWidgetError);
  });

  it('supports retryOtp and forwards channel parameter', async () => {
    const mockRetryOtp = vi.fn((channel: string, success: Function) => {
      success({ type: 'success', message: 'resent' });
    });
    setupGlobals({ retryOtp: mockRetryOtp });

    await retryOtp('11');
    expect(mockRetryOtp).toHaveBeenCalledWith('11', expect.any(Function), expect.any(Function));
  });

  it('forwards passthrough functions getWidgetData and isCaptchaVerified', () => {
    setupGlobals();
    window.getWidgetData = vi.fn(() => ({ sample: 'diagnostic_data' }));
    window.isCaptchaVerified = vi.fn(() => true);

    expect(getWidgetData()).toEqual({ sample: 'diagnostic_data' });
    expect(isCaptchaVerified()).toBe(true);
  });

  it('supports deterministic test number 9898989898 with code 123456', async () => {
    await sendOtp('+919898989898');
    const token = await verifyOtp('123456');
    expect(token).toBe('test-token-9898989898');

    await expect(verifyOtp('000000')).rejects.toThrow(OtpWidgetError);
  });

  it('never honours the dev shortcut in a production environment', async () => {
    // Re-import the loader with NODE_ENV=production so the compile-time
    // gate evaluates OFF, then drive the *real* widget path with distinct
    // mock globals: the shortcut token must not come back.
    vi.resetModules();
    vi.stubEnv('NODE_ENV', 'production');
    const realToken = 'real.jwt.from.provider';

    try {
      const prod = await import('@navdhya/otp/widget');

      window.sendOtp = vi.fn((_id: string, success: Function) =>
        success({ type: 'success', message: 'sent' }),
      );
      window.retryOtp = vi.fn((_ch: string, success: Function) =>
        success({ type: 'success', message: 'resent' }),
      );
      window.verifyOtp = vi.fn((_code: string, success: Function) =>
        success({ type: 'success', message: realToken }),
      );

      await prod.sendOtp('+919898989898');
      await expect(prod.verifyOtp('123456')).resolves.toBe(realToken);
    } finally {
      vi.unstubAllEnvs();
      vi.resetModules();
      resetOtpWidgetForTests();
    }
  });
});

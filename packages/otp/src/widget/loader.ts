import { otpConfigSchema, type OtpConfig } from '@navdhya/otp/schemas';

/**
 * MSG91 OTP widget loader, custom-SDK mode (`exposeMethods: true`):
 * MSG91's default popup never appears — our UI drives everything
 * through the globals the widget SDK installs (sendOtp / retryOtp /
 * verifyOtp) and redeems the JWT access token verifyOtp produces.
 *
 * The loader owns the awkward browser realities so screens stay
 * declarative: the script is injected exactly once; the captcha
 * container the SDK requires (`captchaRenderId`) is created offscreen
 * with a unique id; every method call is bridged into a promise via a
 * pending-registry so React StrictMode double-mounts share one
 * in-flight operation instead of racing two; and script/config
 * failures surface as one typed OtpWidgetUnavailableError the UI
 * renders as a clean retry arm.
 */

export const WIDGET_SCRIPT_SRC = 'https://verify.msg91.com/otp-provider.js';

/**
 * Deterministic dev shortcut (phone ending 9898989898 + code 123456) is
 * kept for local drills and the unit tests. It must never be reachable
 * from a production bundle: the bundler replaces `process.env.NODE_ENV`
 * statically, so this branch compiles out of production builds, and any
 * environment that leaves it unset keeps the shortcut OFF (fail closed —
 * only the literal `development`/`test` values turn it on). Never make
 * this runtime-configurable: a production toggle would be an OTP bypass.
 */
declare const process: { env?: { NODE_ENV?: string } } | undefined;

const DEV_SHORTCUT_ENABLED: boolean =
  typeof process !== 'undefined' &&
  (process.env?.NODE_ENV === 'development' || process.env?.NODE_ENV === 'test');

const DEV_SHORTCUT_SUFFIX = '9898989898';
const DEV_SHORTCUT_CODE = '123456';
const DEV_SHORTCUT_TOKEN = 'test-token-9898989898';

export type OtpWidgetErrorKind = 'unavailable' | 'send_failed' | 'retry_failed' | 'verify_failed' | 'captcha';

export class OtpWidgetError extends Error {
  constructor(
    readonly kind: OtpWidgetErrorKind,
    message: string,
    /** MSG91's symbolic failure head, when the widget provided one. */
    readonly detail?: string,
  ) {
    super(message);
    this.name = 'OtpWidgetError';
  }
}

type WidgetResult = { type?: string; message?: string };

type WidgetCallback = (result: WidgetResult) => void;

export interface WidgetGlobals {
  sendOtp: (identifier: string, success: WidgetCallback, failure: WidgetCallback) => void;
  retryOtp: (channel: string, success: WidgetCallback, failure: WidgetCallback) => void;
  verifyOtp: (code: string, success: WidgetCallback, failure: WidgetCallback) => void;
  getWidgetData?: () => unknown;
  isCaptchaVerified?: () => boolean;
}

declare global {
  interface Window {
    initSendOTP?: (config: Record<string, unknown>) => void;
    sendOtp?: WidgetGlobals['sendOtp'];
    retryOtp?: WidgetGlobals['retryOtp'];
    verifyOtp?: WidgetGlobals['verifyOtp'];
    getWidgetData?: WidgetGlobals['getWidgetData'];
    isCaptchaVerified?: WidgetGlobals['isCaptchaVerified'];
  }
}

export interface WidgetOptions {
  widgetId: string;
  tokenAuth: string;
}

const CAPTCHA_PREFIX = 'msg91-captcha';

let scriptPromise: Promise<void> | null = null;
let captchaElementId: string | null = null;
let widgetReady: Promise<void> | null = null;
let widgetKey: string | null = null;

/** Pending operation registry: reqId → settle bridge. */
const pending = new Map<string, { resolve: (value: unknown) => void; reject: (error: OtpWidgetError) => void; kind: OtpWidgetErrorKind }>();
let seq = 0;

/** Fetch + validate the public widget bootstrap from the BFF. */
export async function loadOtpConfig(client: { loadConfig(): Promise<unknown> }): Promise<OtpConfig> {
  return otpConfigSchema.parse(await client.loadConfig());
}

function isBrowser(): boolean {
  return typeof window !== 'undefined' && typeof document !== 'undefined';
}

function loadWidgetScript(): Promise<void> {
  if (scriptPromise !== null) {
    return scriptPromise;
  }

  scriptPromise = new Promise<void>((resolve, reject) => {
    const existing = document.querySelector<HTMLScriptElement>(`script[src="${WIDGET_SCRIPT_SRC}"]`);
    if (existing) {
      resolve();
      return;
    }

    const script = document.createElement('script');
    script.src = WIDGET_SCRIPT_SRC;
    script.async = true;
    script.onload = () => resolve();
    script.onerror = () => {
      scriptPromise = null;
      reject(new OtpWidgetError('unavailable', 'The OTP widget script failed to load.'));
    };
    document.head.appendChild(script);
  });

  return scriptPromise;
}

function ensureCaptchaContainer(): string {
  if (captchaElementId !== null && document.getElementById(captchaElementId) !== null) {
    return captchaElementId;
  }

  captchaElementId = `${CAPTCHA_PREFIX}-${Math.random().toString(36).slice(2, 10)}`;
  const container = document.createElement('div');
  container.id = captchaElementId;
  // Offscreen but rendered: the SDK measures/renders into this node,
  // it must never be visible (custom-SDK mode drives its own UI).
  container.setAttribute(
    'style',
    'position:fixed;left:-10000px;top:-10000px;width:1px;height:1px;overflow:hidden;',
  );
  document.body.appendChild(container);

  return captchaElementId;
}

function globals(): WidgetGlobals {
  const w = typeof window === 'undefined' ? undefined : window;
  if (!w || typeof w.sendOtp !== 'function' || typeof w.retryOtp !== 'function' || typeof w.verifyOtp !== 'function') {
    throw new OtpWidgetError('unavailable', 'The OTP widget methods are not available.');
  }

  return {
    sendOtp: w.sendOtp,
    retryOtp: w.retryOtp,
    verifyOtp: w.verifyOtp,
    getWidgetData: typeof w.getWidgetData === 'function' ? w.getWidgetData : undefined,
    isCaptchaVerified: typeof w.isCaptchaVerified === 'function' ? w.isCaptchaVerified : undefined,
  };
}

function settle(reqId: string, error: OtpWidgetError | null, value?: unknown): void {
  const entry = pending.get(reqId);
  if (!entry) {
    return;
  }
  pending.delete(reqId);
  if (error === null) {
    entry.resolve(value);
  } else {
    entry.reject(error);
  }
}

function failAll(error: OtpWidgetError): void {
  for (const [reqId, entry] of pending) {
    pending.delete(reqId);
    entry.reject(error);
  }
}

function widgetResultError(kind: OtpWidgetErrorKind, result: WidgetResult): OtpWidgetError {
  const detail = typeof result?.message === 'string' ? result.message : undefined;

  return new OtpWidgetError(kind, detail ?? 'The OTP widget rejected the request.', detail);
}

/**
 * Install the widget exactly once per (widgetId, tokenAuth) pair:
 * injects the SDK, creates the offscreen captcha container, and calls
 * initSendOTP with exposeMethods:true. Resolves when the globals are
 * live. Identifier-less: sendOtp carries the identifier per call.
 */
export function getWidget({ widgetId, tokenAuth }: WidgetOptions): Promise<void> {
  if (!isBrowser()) {
    return Promise.reject(new OtpWidgetError('unavailable', 'The OTP widget requires a browser.'));
  }

  const key = `${widgetId}:${tokenAuth}`;
  if (widgetReady !== null && widgetKey === key) {
    return widgetReady;
  }

  widgetKey = key;
  widgetReady = (async () => {
    await loadWidgetScript();

    if (typeof window.initSendOTP !== 'function') {
      throw new OtpWidgetError('unavailable', 'The OTP widget SDK did not expose initSendOTP.');
    }

    const captchaRenderId = ensureCaptchaContainer();

    window.initSendOTP({
      widgetId,
      tokenAuth,
      exposeMethods: true,
      captchaRenderId,
      success: (result: WidgetResult) => {
        // Widget-level success (popup-mode path): only meaningful as a
        // fallback settlement for an in-flight send.
        for (const [reqId, entry] of pending) {
          if (entry.kind === 'send_failed' || entry.kind === 'retry_failed') {
            settle(reqId, null);
            break;
          }
        }
        void result;
      },
      failure: (result: WidgetResult) => {
        failAll(widgetResultError('unavailable', result));
      },
    });

    // The SDK installs the globals synchronously inside initSendOTP in
    // exposeMethods mode; if a future SDK defers them, poll briefly.
    for (let waited = 0; waited < 2000; waited += 50) {
      if (typeof window.sendOtp === 'function') {
        return;
      }
      await new Promise((resolve) => setTimeout(resolve, 50));
    }

    throw new OtpWidgetError('unavailable', 'The OTP widget did not expose its methods.');
  })();

  widgetReady.catch(() => {
    // Allow a later retry after a failed install.
    if (widgetKey === key) {
      widgetReady = null;
      widgetKey = null;
    }
  });

  return widgetReady;
}

let lastIdentifier: string | null = null;

/** True only for the gated dev shortcut phone (see DEV_SHORTCUT_ENABLED). */
function isDevShortcutTarget(): boolean {
  return DEV_SHORTCUT_ENABLED && lastIdentifier !== null && lastIdentifier.endsWith(DEV_SHORTCUT_SUFFIX);
}

/** Send the code to one identifier (phone digits — leading + stripped). */
export function sendOtp(identifier: string): Promise<void> {
  const target = identifier.startsWith('+') ? identifier.slice(1) : identifier;
  lastIdentifier = target;

  if (DEV_SHORTCUT_ENABLED && target.endsWith(DEV_SHORTCUT_SUFFIX)) {
    return Promise.resolve();
  }

  const g = globals();

  return new Promise<void>((resolve, reject) => {
    seq += 1;
    const reqId = `send-${seq}`;
    pending.set(reqId, { resolve: resolve as (value: unknown) => void, reject, kind: 'send_failed' });
    g.sendOtp(
      target,
      () => settle(reqId, null),
      (result) => settle(reqId, widgetResultError('send_failed', result)),
    );
  });
}

/** Verify the entered code: resolves the JWT access token. */
export function verifyOtp(code: string): Promise<string> {
  if (isDevShortcutTarget()) {
    if (code === DEV_SHORTCUT_CODE) {
      return Promise.resolve(DEV_SHORTCUT_TOKEN);
    }
    return Promise.reject(new OtpWidgetError('verify_failed', 'Invalid verification code.'));
  }

  const g = globals();

  return new Promise<string>((resolve, reject) => {
    seq += 1;
    const reqId = `verify-${seq}`;
    pending.set(reqId, {
      resolve: resolve as (value: unknown) => void,
      reject,
      kind: 'verify_failed',
    });
    g.verifyOtp(
      code,
      (result) => {
        if (result?.type === 'success' && typeof result.message === 'string' && result.message !== '') {
          settle(reqId, null, result.message);
          return;
        }
        settle(reqId, widgetResultError('verify_failed', result));
      },
      (result) => settle(reqId, widgetResultError('verify_failed', result)),
    );
  });
}

/** Resend over the given channel (SMS '11', voice '4', email '3', WhatsApp '12'). */
export function retryOtp(channel: string): Promise<void> {
  if (isDevShortcutTarget()) {
    return Promise.resolve();
  }

  const g = globals();

  return new Promise<void>((resolve, reject) => {
    seq += 1;
    const reqId = `retry-${seq}`;
    pending.set(reqId, { resolve: resolve as (value: unknown) => void, reject, kind: 'retry_failed' });
    g.retryOtp(
      channel,
      () => settle(reqId, null),
      (result) => settle(reqId, widgetResultError('retry_failed', result)),
    );
  });
}

/** Passthrough: the widget's collected data blob (diagnostics). */
export function getWidgetData(): unknown {
  return globals().getWidgetData?.();
}

/** Passthrough: whether the widget's captcha challenge is solved. */
export function isCaptchaVerified(): boolean {
  const g = globals();

  return g.isCaptchaVerified ? g.isCaptchaVerified() : true;
}

/** Test seam: drop every cached handle so the next call re-installs. */
export function resetOtpWidgetForTests(): void {
  scriptPromise = null;
  captchaElementId = null;
  widgetReady = null;
  lastIdentifier = null;
  widgetKey = null;
  pending.clear();

  if (typeof window !== 'undefined') {
    delete window.initSendOTP;
    delete window.sendOtp;
    delete window.retryOtp;
    delete window.verifyOtp;
    delete window.getWidgetData;
    delete window.isCaptchaVerified;
    document.querySelectorAll(`[id^="${CAPTCHA_PREFIX}"]`).forEach((node) => node.remove());
  }
}

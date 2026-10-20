import { useEffect, useState } from 'react';

/**
 * Resend schedule math, mirroring the server's compounding policy
 * (60s → 180s → 540s, max 3, then a fresh challenge gated 10 min).
 * Pure here so UIs and tests pin the same numbers the OtpChallengeManager
 * enforces — the server stays authoritative; these only drive labels.
 */

export interface ResendState {
  /** Seconds until the next resend unlocks; null when no window is scheduled. */
  secondsLeft: number | null;
  canResend: boolean;
  /** true when the schedule is spent — the next code is a fresh challenge. */
  exhausted: boolean;
}

export function secondsUntilNext(nextAllowedAtIso: string | null | undefined, nowMs: number = Date.now()): number | null {
  if (!nextAllowedAtIso) {
    return null;
  }

  const target = Date.parse(nextAllowedAtIso);
  if (Number.isNaN(target)) {
    return null;
  }

  return Math.max(0, Math.ceil((target - nowMs) / 1000));
}

/**
 * Countdown state for one challenge's resend arm. Pass the 202's
 * `next_allowed_at`; `null`/`undefined` (or a spent schedule) yields
 * `exhausted: true` so the UI flips to the fresh-challenge arm, which
 * the server gates independently.
 */
export function useResendCountdown(nextAllowedAtIso: string | null | undefined): ResendState {
  const [state, setState] = useState<ResendState>(() => compute(nextAllowedAtIso));

  useEffect(() => {
    setState(compute(nextAllowedAtIso));

    if (!nextAllowedAtIso) {
      return;
    }

    const timer = window.setInterval(() => {
      setState(compute(nextAllowedAtIso));
    }, 1000);

    return () => {
      window.clearInterval(timer);
    };
  }, [nextAllowedAtIso]);

  return state;
}

function compute(nextAllowedAtIso: string | null | undefined): ResendState {
  if (nextAllowedAtIso === null || nextAllowedAtIso === undefined) {
    return { secondsLeft: null, canResend: false, exhausted: true };
  }

  const secondsLeft = secondsUntilNext(nextAllowedAtIso);

  if (secondsLeft === null) {
    return { secondsLeft: null, canResend: false, exhausted: true };
  }

  return { secondsLeft, canResend: secondsLeft === 0, exhausted: false };
}

/** Human label for the countdown ("Resend in 1:23") — null when ready. */
export function resendLabel(secondsLeft: number | null): string | null {
  if (secondsLeft === null || secondsLeft <= 0) {
    return null;
  }

  const minutes = Math.floor(secondsLeft / 60);
  const seconds = secondsLeft % 60;

  return minutes > 0
    ? `${minutes}:${String(seconds).padStart(2, '0')}`
    : `${seconds}s`;
}

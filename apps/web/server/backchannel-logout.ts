import { createBackchannelLogoutStore } from '@navdhya/auth-client';

const store = createBackchannelLogoutStore();

export const rememberLogoutJti = store.rememberLogoutJti;
export const markSidTerminated = store.markSidTerminated;
export const isSidTerminated = store.isSidTerminated;
export const payloadSidTerminated = store.payloadSidTerminated;
export const clearBackchannelLogoutState = store.clear;

/**
 * Termination check over the RAW cookie token — web's shape (the BFF
 * never verifies tokens locally, I18). The payload is decoded
 * UNTRUSTED purely to interrogate its `sid` claim: a forged token
 * can at most deny itself, never gain a session.
 */
export function tokenSidTerminated(token: string): boolean {
  const parts = token.split('.');
  if (parts.length !== 3) return false;

  let payload: Record<string, unknown>;
  try {
    payload = JSON.parse(Buffer.from(parts[1], 'base64url').toString('utf8')) as Record<string, unknown>;
  } catch {
    return false;
  }

  return payloadSidTerminated(payload);
}


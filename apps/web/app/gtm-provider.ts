"use client";

// GTM Web container bootstrap (plan B4). Thin adapter over the governed
// logic in @navdhya/analytics/src/adapters/ga4-data-layer:
//   - Consent Mode v2 DEFAULTS are pushed BEFORE the container loads —
//     everything stays denied until the real consent record decides;
//   - the loader is an external script from www.googletagmanager.com
//     (CSP-friendly — no inline bootstrap snippet);
//   - no container ID → renders nothing (privileged surfaces get no
//     marketing tags; web-only by B0 decision).
// Consent updates flow through setGtmConsent() — the consent UI (wired to
// the Postgres consent record when that API lands) calls it with the real
// decision; until then tags stay at the denied defaults.
//
// Written with createElement (no JSX) so the bare root `tsc --noEmit` —
// which has no --jsx for apps (known drift, plan known-debt 8) — stays
// error-free on this file; apps/web's own tsc is the package gate.

import { createElement, useEffect } from 'react';
import type { ReactElement } from 'react';
import {
  CONSENT_MODE_DEFAULTS,
  consentModeUpdate,
  gtmContainer,
} from '@navdhya/analytics/src/adapters/ga4-data-layer';
import type { ConsentStatus } from '@navdhya/analytics/src/contracts/consent';

declare global {
  interface Window {
    dataLayer?: unknown[];
  }
}

let pendingConsent: ConsentStatus | null = null;
const LOADED_MARKER = 'data-navdhya-gtm';

/** Push a real consent decision to Consent Mode (idempotent per change). */
export function setGtmConsent(status: ConsentStatus): void {
  pendingConsent = status;
  if (typeof window !== 'undefined' && window.dataLayer) {
    window.dataLayer.push(consentModeUpdate(status));
  }
}

export default function GtmProvider(): ReactElement | null {
  const containerId = process.env.NEXT_PUBLIC_GTM_CONTAINER_ID_WEB ?? '';
  const enabled = containerId.length > 0;

  useEffect(() => {
    if (!enabled) return;
    // StrictMode double-mount must not double-load the container.
    if (document.querySelector(`script[${LOADED_MARKER}]`)) return;

    window.dataLayer = window.dataLayer ?? [];
    // Consent initialization BEFORE all tags (plan B4).
    window.dataLayer.push({ ...CONSENT_MODE_DEFAULTS });
    if (pendingConsent) window.dataLayer.push(consentModeUpdate(pendingConsent));

    const script = document.createElement('script');
    script.setAttribute(LOADED_MARKER, 'true');
    script.async = true;
    script.src = gtmContainer(containerId).loaderUrl;
    document.head.appendChild(script);
  }, [containerId, enabled]);

  if (!enabled) return null;
  // No-JS fallback frame — SSR-rendered by the client component.
  return createElement(
    'noscript',
    null,
    createElement('iframe', {
      src: gtmContainer(containerId).noscriptUrl,
      height: '0',
      width: '0',
      title: 'Google Tag Manager',
      style: { display: 'none', visibility: 'hidden' },
    }),
  );
}

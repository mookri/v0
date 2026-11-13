'use client';

import * as React from 'react';
import { SACRED_EMBER_TOKENS } from './tokens';

export interface SafetyEscalationBannerProps {
  onReportSafetyConcern?: () => void;
}

export function SafetyEscalationBanner({
  onReportSafetyConcern,
}: SafetyEscalationBannerProps) {
  return (
    <aside
      role="alert"
      aria-label="Safety escalation and emergency assistance"
      data-testid="safety-escalation-banner"
      className="rounded-xl border-2 p-5"
      style={{
        backgroundColor: SACRED_EMBER_TOKENS.aubergineInk,
        borderColor: SACRED_EMBER_TOKENS.navdhyaFlame,
        color: SACRED_EMBER_TOKENS.warmIvory,
      }}
    >
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div className="space-y-1.5">
          <p
            className="text-xs font-bold uppercase tracking-wider"
            style={{ color: SACRED_EMBER_TOKENS.navdhyaFlame }}
          >
            Immediate Safety &amp; Confidential Escalation
          </p>
          <p className="text-sm font-medium">
            In immediate physical danger? Call India Emergency Response{' '}
            <a
              href="tel:112"
              data-testid="emergency-112-link"
              className="underline font-bold"
              style={{ color: SACRED_EMBER_TOKENS.warmIvory }}
            >
              112
            </a>{' '}
            immediately (Women Helpline: 1091 · Cybercrime Portal: cybercrime.gov.in).
          </p>
          <p className="text-xs" style={{ color: SACRED_EMBER_TOKENS.softSand }}>
            Confidential safety reports are stored in encrypted{' '}
            <code>REPORTER_PRIVATE</code> channels restricted to the Safety Lead and
            cannot be mediated away via store credit, apology, or reschedule.
          </p>
        </div>

        <button
          type="button"
          onClick={onReportSafetyConcern}
          className="rounded-md px-4 py-2 text-xs font-semibold"
          style={{
            backgroundColor: SACRED_EMBER_TOKENS.navdhyaFlame,
            color: SACRED_EMBER_TOKENS.warmIvory,
          }}
        >
          File Confidential Safety Report
        </button>
      </div>
    </aside>
  );
}


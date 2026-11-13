'use client';

import * as React from 'react';
import {
  EligibilityGateMode,
  IncidentSeverity,
  SACRED_EMBER_TOKENS,
} from './tokens';

export interface ReplacementCandidateOption {
  panditId: string;
  name: string;
  gateMode: EligibilityGateMode;
  traditionMatch: boolean;
}

export interface IncidentCommandWorkspaceProps {
  incidentReference: string;
  severity: IncidentSeverity;
  candidates: ReplacementCandidateOption[];
  fallbackAction?: 'NONE' | 'RESCHEDULE_OR_FULL_REFUND';
  onConfirmReplacement?: (panditId: string, devoteeConsented: boolean) => void;
}

const SEVERITY_STYLE: Record<IncidentSeverity, { bg: string; fg: string }> = {
  SEV_0: { bg: SACRED_EMBER_TOKENS.deepEmber, fg: SACRED_EMBER_TOKENS.warmIvory },
  SEV_1: { bg: SACRED_EMBER_TOKENS.navdhyaFlame, fg: SACRED_EMBER_TOKENS.warmIvory },
  SEV_2: { bg: SACRED_EMBER_TOKENS.aubergineInk, fg: SACRED_EMBER_TOKENS.warmIvory },
  SEV_3: { bg: SACRED_EMBER_TOKENS.softSand, fg: SACRED_EMBER_TOKENS.aubergineInk },
  SEV_4: { bg: SACRED_EMBER_TOKENS.softSand, fg: SACRED_EMBER_TOKENS.deepText },
};

export function IncidentCommandWorkspace({
  incidentReference,
  severity,
  candidates,
  fallbackAction = 'NONE',
  onConfirmReplacement,
}: IncidentCommandWorkspaceProps) {
  const [selectedPandit, setSelectedPandit] = React.useState<string>(
    candidates[0]?.panditId ?? '',
  );
  const [devoteeConsented, setDevoteeConsented] = React.useState<boolean>(false);
  const sevColors = SEVERITY_STYLE[severity];
  const exhausted = candidates.length === 0 || fallbackAction === 'RESCHEDULE_OR_FULL_REFUND';

  return (
    <section
      aria-label={`Incident workspace ${incidentReference}`}
      data-testid="incident-command-workspace"
      className="rounded-xl border p-5"
      style={{
        backgroundColor: SACRED_EMBER_TOKENS.warmIvory,
        borderColor: SACRED_EMBER_TOKENS.softSand,
        color: SACRED_EMBER_TOKENS.deepText,
      }}
    >
      <div className="mb-4 flex items-center justify-between">
        <h3
          className="text-base font-semibold"
          style={{ color: SACRED_EMBER_TOKENS.aubergineInk }}
        >
          Incident {incidentReference}
        </h3>
        <span
          className="rounded-full px-3 py-1 text-xs font-bold"
          style={{ backgroundColor: sevColors.bg, color: sevColors.fg }}
        >
          {severity}
        </span>
      </div>

      {exhausted ? (
        <div
          data-testid="replacement-exhausted-fallback"
          className="rounded-lg border p-4 text-sm"
          style={{
            backgroundColor: SACRED_EMBER_TOKENS.softSand,
            borderColor: SACRED_EMBER_TOKENS.deepEmber,
          }}
        >
          <p className="font-semibold" style={{ color: SACRED_EMBER_TOKENS.aubergineInk }}>
            Fallback Required: RESCHEDULE_OR_FULL_REFUND
          </p>
          <p className="mt-1 text-xs">
            No eligible verified pandit satisfies the tradition and slot gates. Offer
            an explicit reschedule or full monetary refund to the original payment
            method.
          </p>
        </div>
      ) : (
        <div className="space-y-3">
          <p className="text-xs font-medium">
            Gate-Verified Replacement Candidates (never auto-assigned silently):
          </p>
          <ul className="space-y-2">
            {candidates.map((c) => (
              <li
                key={c.panditId}
                className="flex items-center justify-between rounded border p-2 text-xs"
                style={{
                  borderColor: SACRED_EMBER_TOKENS.softSand,
                  backgroundColor:
                    selectedPandit === c.panditId
                      ? SACRED_EMBER_TOKENS.softSand
                      : SACRED_EMBER_TOKENS.warmIvory,
                }}
              >
                <label className="flex items-center gap-2 font-medium">
                  <input
                    type="radio"
                    name="replacementCandidate"
                    value={c.panditId}
                    checked={selectedPandit === c.panditId}
                    onChange={() => setSelectedPandit(c.panditId)}
                  />
                  {c.name}
                </label>
                <span
                  className="rounded px-2 py-0.5 text-[11px] font-semibold"
                  style={{
                    backgroundColor: SACRED_EMBER_TOKENS.aubergineInk,
                    color: SACRED_EMBER_TOKENS.warmIvory,
                  }}
                >
                  {c.gateMode}
                </span>
              </li>
            ))}
          </ul>

          <label className="flex items-center gap-2 text-xs font-medium">
            <input
              type="checkbox"
              data-testid="replacement-devotee-consent-checkbox"
              checked={devoteeConsented}
              onChange={(e) => setDevoteeConsented(e.target.checked)}
            />
            Devotee explicitly consented to this replacement pandit
          </label>

          <button
            type="button"
            data-testid="confirm-replacement-btn"
            disabled={!devoteeConsented || !selectedPandit}
            onClick={() => onConfirmReplacement?.(selectedPandit, devoteeConsented)}
            className="rounded-md px-4 py-2 text-xs font-semibold disabled:opacity-40"
            style={{
              backgroundColor: SACRED_EMBER_TOKENS.navdhyaFlame,
              color: SACRED_EMBER_TOKENS.warmIvory,
            }}
          >
            Confirm Consented Replacement
          </button>
        </div>
      )}
    </section>
  );
}


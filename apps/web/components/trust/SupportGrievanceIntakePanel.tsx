'use client';

import * as React from 'react';
import { SACRED_EMBER_TOKENS } from './tokens';

export type RefundPreference = 'MONETARY_ORIGINAL_METHOD' | 'VOLUNTARY_CREDIT';

export interface SupportGrievanceIntakePanelProps {
  grievanceReference?: string;
  acknowledgementDeadlineIso?: string;
  resolutionDeadlineIso?: string;
  complaintCopyDownloadUrl?: string;
  onSubmitRefundPreference?: (payload: {
    remedyInstrument: RefundPreference;
    freelyConsentedCredit: boolean;
    notes: string;
  }) => void;
}

export function SupportGrievanceIntakePanel({
  grievanceReference,
  acknowledgementDeadlineIso,
  resolutionDeadlineIso,
  complaintCopyDownloadUrl,
  onSubmitRefundPreference,
}: SupportGrievanceIntakePanelProps) {
  const [preference, setPreference] = React.useState<RefundPreference>(
    'MONETARY_ORIGINAL_METHOD',
  );
  const [freelyConsentedCredit, setFreelyConsentedCredit] =
    React.useState<boolean>(false);
  const [notes, setNotes] = React.useState<string>('');

  const creditNeedsConsent =
    preference === 'VOLUNTARY_CREDIT' && !freelyConsentedCredit;

  return (
    <section
      aria-label="Support and Grievance Intake"
      data-testid="support-grievance-panel"
      className="rounded-xl border p-5"
      style={{
        backgroundColor: SACRED_EMBER_TOKENS.warmIvory,
        borderColor: SACRED_EMBER_TOKENS.softSand,
        color: SACRED_EMBER_TOKENS.deepText,
      }}
    >
      <div className="mb-3 border-b pb-3" style={{ borderColor: SACRED_EMBER_TOKENS.softSand }}>
        <h3
          className="text-base font-semibold"
          style={{ color: SACRED_EMBER_TOKENS.aubergineInk }}
        >
          Formal Support &amp; Grievance Redressal
          {grievanceReference ? ` · ${grievanceReference}` : ''}
        </h3>
        <p className="mt-1 text-xs">
          Statutory SLA: Acknowledgement within <strong>48 hours</strong> · Final
          decision within <strong>1 calendar month</strong> · Unresolved disputes
          include National Consumer Helpline (NCH 1915) escalation.
        </p>
        {(acknowledgementDeadlineIso || resolutionDeadlineIso) && (
          <p className="mt-1 text-xs" style={{ color: SACRED_EMBER_TOKENS.deepEmber }}>
            {acknowledgementDeadlineIso ? `Ack due: ${acknowledgementDeadlineIso}` : ''}
            {acknowledgementDeadlineIso && resolutionDeadlineIso ? ' · ' : ''}
            {resolutionDeadlineIso ? `Resolution due: ${resolutionDeadlineIso}` : ''}
          </p>
        )}
        {complaintCopyDownloadUrl && (
          <a
            href={complaintCopyDownloadUrl}
            download
            data-testid="complaint-copy-download"
            className="mt-2 inline-block text-xs font-semibold underline"
            style={{ color: SACRED_EMBER_TOKENS.navdhyaFlame }}
          >
            Download Verbatim Complaint Copy
          </a>
        )}
      </div>

      <form
        onSubmit={(e) => {
          e.preventDefault();
          if (creditNeedsConsent) {
            return;
          }
          onSubmitRefundPreference?.({
            remedyInstrument: preference,
            freelyConsentedCredit,
            notes,
          });
        }}
        className="space-y-3"
      >
        <fieldset className="space-y-2">
          <legend
            className="text-xs font-semibold"
            style={{ color: SACRED_EMBER_TOKENS.aubergineInk }}
          >
            Refund Method Preference (Monetary refund to original method is always available)
          </legend>

          <label className="flex items-center gap-2 text-sm">
            <input
              type="radio"
              name="remedyInstrument"
              value="MONETARY_ORIGINAL_METHOD"
              checked={preference === 'MONETARY_ORIGINAL_METHOD'}
              onChange={() => {
                setPreference('MONETARY_ORIGINAL_METHOD');
                setFreelyConsentedCredit(false);
              }}
            />
            Refund to Original Payment Method (Recommended / Default)
          </label>

          <label className="flex items-center gap-2 text-sm">
            <input
              type="radio"
              name="remedyInstrument"
              value="VOLUNTARY_CREDIT"
              checked={preference === 'VOLUNTARY_CREDIT'}
              onChange={() => setPreference('VOLUNTARY_CREDIT')}
            />
            Opt into Navdhya Store Credit instead (Requires explicit consent)
          </label>
        </fieldset>

        {preference === 'VOLUNTARY_CREDIT' && (
          <label
            data-testid="explicit-credit-consent-row"
            className="flex items-start gap-2 rounded border p-3 text-xs"
            style={{
              backgroundColor: SACRED_EMBER_TOKENS.softSand,
              borderColor: SACRED_EMBER_TOKENS.deepEmber,
            }}
          >
            <input
              type="checkbox"
              checked={freelyConsentedCredit}
              onChange={(e) => setFreelyConsentedCredit(e.target.checked)}
            />
            <span>
              I freely choose Navdhya credit instead of a monetary refund to my
              original payment method.
            </span>
          </label>
        )}

        <div>
          <label htmlFor="grievance-notes" className="block text-xs font-medium">
            Details for the Case Officer
          </label>
          <textarea
            id="grievance-notes"
            value={notes}
            onChange={(e) => setNotes(e.target.value)}
            rows={2}
            className="mt-1 w-full rounded border p-2 text-sm"
            style={{ borderColor: SACRED_EMBER_TOKENS.aubergineInk }}
          />
        </div>

        <button
          type="submit"
          disabled={creditNeedsConsent}
          className="rounded-md px-4 py-2 text-xs font-semibold disabled:opacity-50"
          style={{
            backgroundColor: SACRED_EMBER_TOKENS.aubergineInk,
            color: SACRED_EMBER_TOKENS.warmIvory,
          }}
        >
          Submit Refund &amp; Support Request
        </button>
      </form>
    </section>
  );
}


import * as fs from 'node:fs';
import * as path from 'node:path';
import * as React from 'react';
import { fireEvent, render, screen } from '@testing-library/react';
import {
  CompletedCeremonyReviewCard,
  IncidentCommandWorkspace,
  SafetyEscalationBanner,
  SupportGrievanceIntakePanel,
  TrustConfidenceSummary,
} from '@/components/trust';

describe('Sacred Ember Trust UI & Dark-Pattern Prohibitions', () => {
  test('contains zero green palette tokens or green utility classes across trust components', () => {
    const trustDir = path.resolve(__dirname, '..');
    const files = fs
      .readdirSync(trustDir)
      .filter((f) => f.endsWith('.ts') || f.endsWith('.tsx'));

    const forbiddenGreenPattern =
      /(bg-green-|text-green-|border-green-|bg-emerald-|text-emerald-|bg-teal-|text-teal-|bg-lime-|#00ff00|#10b981|#16a34a|#22c55e|#059669)/i;

    for (const file of files) {
      const content = fs.readFileSync(path.join(trustDir, file), 'utf8');
      expect(content).not.toMatch(forbiddenGreenPattern);
    }
  });

  test('TrustConfidenceSummary suppresses 5.0 headline for NEW_PROVIDER and shows methodology popover', () => {
    render(
      <TrustConfidenceSummary
        subjectName="Acharya Sharma"
        bayesianRating={5.0}
        sampleCount={2}
        confidenceTier="NEW_PROVIDER"
        punctualityWilsonLower={0.78}
      />,
    );

    expect(screen.getByTestId('new-provider-no-5star-headline')).toHaveTextContent(
      'New provider · Limited reviews',
    );
    expect(screen.queryByTestId('bayesian-rating-headline')).not.toBeInTheDocument();

    fireEvent.click(screen.getByText('How Navdhya calculates this score'));
    expect(screen.getByTestId('score-methodology-popover')).toHaveTextContent(
      'Bayesian shrinkage',
    );
  });

  test('CompletedCeremonyReviewCard never blocks support behind ratings and redirects NO_SHOW bookings', () => {
    const onOpenSupport = jest.fn();

    render(
      <CompletedCeremonyReviewCard
        bookingReference="NV-BK-102"
        bookingStatus="NO_SHOW"
        onOpenSupportReport={onOpenSupport}
      />,
    );

    expect(screen.getByTestId('direct-support-link')).toBeInTheDocument();
    expect(screen.getByTestId('uncompleted-review-redirect')).toHaveTextContent(
      'NO_SHOW',
    );
    fireEvent.click(screen.getByText('Go to Support & Refund Report'));
    expect(onOpenSupport).toHaveBeenCalledWith('NV-BK-102');
  });

  test('SupportGrievanceIntakePanel defaults to monetary refund and never pre-ticks store credit', () => {
    const onSubmit = jest.fn();
    render(
      <SupportGrievanceIntakePanel
        grievanceReference="NV-GRV-2026-0001"
        complaintCopyDownloadUrl="/api/v1/support/grievances/1/complaint-copy"
        onSubmitRefundPreference={onSubmit}
      />,
    );

    const monetaryRadio = screen.getByLabelText(
      /Refund to Original Payment Method/i,
    ) as HTMLInputElement;
    const creditRadio = screen.getByLabelText(
      /Opt into Navdhya Store Credit instead/i,
    ) as HTMLInputElement;

    expect(monetaryRadio.checked).toBe(true);
    expect(creditRadio.checked).toBe(false);
    expect(screen.getByTestId('complaint-copy-download')).toBeInTheDocument();

    fireEvent.click(creditRadio);
    const submitBtn = screen.getByText(
      /Submit Refund & Support Request/i,
    ) as HTMLButtonElement;
    expect(submitBtn.disabled).toBe(true);
  });

  test('SafetyEscalationBanner shows 112 signposting, REPORTER_PRIVATE confidential notes, and no-mediation policy', () => {
    render(<SafetyEscalationBanner />);

    const banner = screen.getByTestId('safety-escalation-banner');
    expect(screen.getByTestId('emergency-112-link')).toHaveAttribute('href', 'tel:112');
    expect(banner).toHaveTextContent('REPORTER_PRIVATE');
    expect(banner).toHaveTextContent('cannot be mediated away');
  });

  test('IncidentCommandWorkspace requires devotee consent and exposes RESCHEDULE_OR_FULL_REFUND fallback', () => {
    const onConfirm = jest.fn();
    const { rerender } = render(
      <IncidentCommandWorkspace
        incidentReference="NV-INC-2026-01"
        severity="SEV_1"
        candidates={[
          {
            panditId: 'p-1',
            name: 'Pt. Raghav',
            gateMode: 'FULL_GATES',
            traditionMatch: true,
          },
        ]}
        onConfirmReplacement={onConfirm}
      />,
    );

    const confirmBtn = screen.getByTestId('confirm-replacement-btn') as HTMLButtonElement;
    expect(confirmBtn.disabled).toBe(true);
    fireEvent.click(screen.getByTestId('replacement-devotee-consent-checkbox'));
    expect(confirmBtn.disabled).toBe(false);
    fireEvent.click(confirmBtn);
    expect(onConfirm).toHaveBeenCalledWith('p-1', true);

    rerender(
      <IncidentCommandWorkspace
        incidentReference="NV-INC-2026-01"
        severity="SEV_0"
        candidates={[]}
        fallbackAction="RESCHEDULE_OR_FULL_REFUND"
      />,
    );
    expect(screen.getByTestId('replacement-exhausted-fallback')).toHaveTextContent(
      'RESCHEDULE_OR_FULL_REFUND',
    );
  });
});


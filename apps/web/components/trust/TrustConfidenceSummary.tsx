'use client';

import * as React from 'react';
import { ConfidenceTier, SACRED_EMBER_TOKENS } from './tokens';

export interface TrustConfidenceSummaryProps {
  subjectName: string;
  bayesianRating: number;
  sampleCount: number;
  confidenceTier: ConfidenceTier;
  confidenceLabel?: string;
  punctualityWilsonLower?: number;
}

const TIER_BADGES: Record<ConfidenceTier, { label: string; bg: string; fg: string }> = {
  NEW_PROVIDER: {
    label: 'New provider · Limited reviews',
    bg: SACRED_EMBER_TOKENS.softSand,
    fg: SACRED_EMBER_TOKENS.aubergineInk,
  },
  LOW_CONFIDENCE: {
    label: 'Early track record',
    bg: SACRED_EMBER_TOKENS.softSand,
    fg: SACRED_EMBER_TOKENS.deepText,
  },
  ESTABLISHED: {
    label: 'Established record',
    bg: SACRED_EMBER_TOKENS.aubergineInk,
    fg: SACRED_EMBER_TOKENS.warmIvory,
  },
  HIGH_CONFIDENCE: {
    label: 'High-confidence record',
    bg: SACRED_EMBER_TOKENS.navdhyaFlame,
    fg: SACRED_EMBER_TOKENS.warmIvory,
  },
};

export function TrustConfidenceSummary({
  subjectName,
  bayesianRating,
  sampleCount,
  confidenceTier,
  confidenceLabel,
  punctualityWilsonLower,
}: TrustConfidenceSummaryProps) {
  const [showMethodology, setShowMethodology] = React.useState(false);
  const isNew = confidenceTier === 'NEW_PROVIDER' || sampleCount < 5;
  const badge = TIER_BADGES[confidenceTier];
  const pct =
    punctualityWilsonLower !== undefined
      ? `${Math.round(punctualityWilsonLower * 100)}%`
      : null;

  return (
    <section
      aria-label={`Trust summary for ${subjectName}`}
      data-testid="trust-confidence-summary"
      className="rounded-xl border p-4"
      style={{
        backgroundColor: SACRED_EMBER_TOKENS.warmIvory,
        borderColor: SACRED_EMBER_TOKENS.softSand,
        color: SACRED_EMBER_TOKENS.deepText,
      }}
    >
      <div className="flex flex-wrap items-center justify-between gap-2">
        <div>
          {isNew ? (
            <p
              data-testid="new-provider-no-5star-headline"
              className="text-base font-semibold"
              style={{ color: SACRED_EMBER_TOKENS.aubergineInk }}
            >
              {confidenceLabel ?? 'New provider · Limited reviews'}
            </p>
          ) : (
            <p
              data-testid="bayesian-rating-headline"
              className="text-lg font-semibold"
              style={{ color: SACRED_EMBER_TOKENS.aubergineInk }}
            >
              {bayesianRating.toFixed(1)} / 5 ({sampleCount} verified reviews)
            </p>
          )}
          <p className="text-xs" style={{ color: SACRED_EMBER_TOKENS.deepText }}>
            {sampleCount === 0
              ? 'No completed ceremony reviews recorded yet.'
              : `${sampleCount} verified completed ceremony reviews included.`}
            {pct !== null ? ` · Punctuality lower bound: ${pct}` : ''}
          </p>
        </div>

        <span
          className="rounded-full px-3 py-1 text-xs font-medium"
          style={{ backgroundColor: badge.bg, color: badge.fg }}
        >
          {badge.label}
        </span>
      </div>

      <div className="mt-3">
        <button
          type="button"
          onClick={() => setShowMethodology((prev) => !prev)}
          aria-expanded={showMethodology}
          className="text-xs font-medium underline"
          style={{ color: SACRED_EMBER_TOKENS.deepEmber }}
        >
          How Navdhya calculates this score
        </button>

        {showMethodology && (
          <div
            data-testid="score-methodology-popover"
            role="region"
            aria-label="Scoring methodology disclosure"
            className="mt-2 rounded-lg border p-3 text-xs"
            style={{
              backgroundColor: SACRED_EMBER_TOKENS.softSand,
              borderColor: SACRED_EMBER_TOKENS.aubergineInk,
              color: SACRED_EMBER_TOKENS.deepText,
            }}
          >
            Scores combine completed-ceremony ratings using Bayesian shrinkage toward
            the platform prior (m=10), 180-day recency decay, and a 95% Wilson
            lower-bound for arrival punctuality backed by recorded arrival evidence.
            New providers (&lt;5 reviews) never display an inflated 5.0 headline.
          </div>
        )}
      </div>
    </section>
  );
}


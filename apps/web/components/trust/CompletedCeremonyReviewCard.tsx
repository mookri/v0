'use client';

import * as React from 'react';
import { SACRED_EMBER_TOKENS } from './tokens';

export type CeremonyStatusForFeedback =
  | 'COMPLETED'
  | 'CANCELLED'
  | 'NO_SHOW'
  | 'CONFIRMED';

export interface CompletedCeremonyReviewCardProps {
  bookingReference: string;
  bookingStatus: CeremonyStatusForFeedback;
  onSubmitReview?: (payload: { rating: number; comment: string; sharePublicly: boolean }) => void;
  onOpenSupportReport?: (bookingReference: string) => void;
}

export function CompletedCeremonyReviewCard({
  bookingReference,
  bookingStatus,
  onSubmitReview,
  onOpenSupportReport,
}: CompletedCeremonyReviewCardProps) {
  const [rating, setRating] = React.useState<number>(5);
  const [comment, setComment] = React.useState<string>('');
  const [sharePublicly, setSharePublicly] = React.useState<boolean>(true);

  const canLeaveReview = bookingStatus === 'COMPLETED';
  const requiresSupportRedirect =
    bookingStatus === 'CANCELLED' || bookingStatus === 'NO_SHOW';

  return (
    <section
      aria-label="Ceremony feedback and support options"
      data-testid="ceremony-review-card"
      className="rounded-xl border p-5"
      style={{
        backgroundColor: SACRED_EMBER_TOKENS.warmIvory,
        borderColor: SACRED_EMBER_TOKENS.softSand,
        color: SACRED_EMBER_TOKENS.deepText,
      }}
    >
      <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
        <h3
          className="text-base font-semibold"
          style={{ color: SACRED_EMBER_TOKENS.aubergineInk }}
        >
          Ceremony {bookingReference}
        </h3>
        <button
          type="button"
          data-testid="direct-support-link"
          onClick={() => onOpenSupportReport?.(bookingReference)}
          className="rounded-md border px-3 py-1 text-xs font-medium"
          style={{
            borderColor: SACRED_EMBER_TOKENS.aubergineInk,
            color: SACRED_EMBER_TOKENS.aubergineInk,
            backgroundColor: SACRED_EMBER_TOKENS.softSand,
          }}
        >
          Need help or refund instead? Open Support
        </button>
      </div>

      {!canLeaveReview ? (
        <div
          data-testid="uncompleted-review-redirect"
          className="rounded-lg border p-4 text-sm"
          style={{
            backgroundColor: SACRED_EMBER_TOKENS.softSand,
            borderColor: SACRED_EMBER_TOKENS.deepEmber,
            color: SACRED_EMBER_TOKENS.deepText,
          }}
        >
          {requiresSupportRedirect ? (
            <p>
              This ceremony was marked <strong>{bookingStatus}</strong>. Public
              star ratings are disabled for uncompleted bookings so you can report
              an arrival failure or request an original-method refund directly.
            </p>
          ) : (
            <p>
              Public ceremony reviews open once your ritual is completed. If you need
              assistance before or during your ceremony, open a support case directly
              without submitting a star rating.
            </p>
          )}
          <button
            type="button"
            onClick={() => onOpenSupportReport?.(bookingReference)}
            className="mt-3 rounded-md px-4 py-2 text-xs font-semibold"
            style={{
              backgroundColor: SACRED_EMBER_TOKENS.navdhyaFlame,
              color: SACRED_EMBER_TOKENS.warmIvory,
            }}
          >
            Go to Support &amp; Refund Report
          </button>
        </div>
      ) : (
        <form
          onSubmit={(e) => {
            e.preventDefault();
            onSubmitReview?.({ rating, comment, sharePublicly });
          }}
          className="space-y-3"
        >
          <p className="text-xs" style={{ color: SACRED_EMBER_TOKENS.deepText }}>
            Verified completed ceremony review · You can edit your feedback for{' '}
            <strong>48 hours</strong> after submission.
          </p>

          <div>
            <label
              htmlFor={`rating-${bookingReference}`}
              className="block text-xs font-medium"
            >
              Overall Ceremony Experience (1–5)
            </label>
            <select
              id={`rating-${bookingReference}`}
              value={rating}
              onChange={(e) => setRating(Number(e.target.value))}
              className="mt-1 rounded border px-2 py-1 text-sm"
              style={{
                borderColor: SACRED_EMBER_TOKENS.aubergineInk,
                backgroundColor: SACRED_EMBER_TOKENS.warmIvory,
              }}
            >
              {[5, 4, 3, 2, 1].map((v) => (
                <option key={v} value={v}>
                  {v} Star{v > 1 ? 's' : ''}
                </option>
              ))}
            </select>
          </div>

          <div>
            <label
              htmlFor={`comment-${bookingReference}`}
              className="block text-xs font-medium"
            >
              Honest Notes
            </label>
            <textarea
              id={`comment-${bookingReference}`}
              value={comment}
              onChange={(e) => setComment(e.target.value)}
              rows={3}
              className="mt-1 w-full rounded border p-2 text-sm"
              style={{ borderColor: SACRED_EMBER_TOKENS.aubergineInk }}
            />
          </div>

          <label className="flex items-center gap-2 text-xs">
            <input
              type="checkbox"
              checked={sharePublicly}
              onChange={(e) => setSharePublicly(e.target.checked)}
            />
            Share review comment publicly (uncheck to send private feedback only)
          </label>

          <button
            type="submit"
            className="rounded-md px-4 py-2 text-xs font-semibold"
            style={{
              backgroundColor: SACRED_EMBER_TOKENS.aubergineInk,
              color: SACRED_EMBER_TOKENS.warmIvory,
            }}
          >
            Submit Completed Ceremony Review
          </button>
        </form>
      )}
    </section>
  );
}


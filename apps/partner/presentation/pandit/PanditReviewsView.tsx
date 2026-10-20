interface PanditReview {
  id: string;
  rating?: number;
  comment?: string;
  customer_name?: string;
  created_at?: string;
  [key: string]: unknown;
}

export function PanditReviewsView({ reviews }: { reviews: PanditReview[] }) {
  if (reviews.length === 0) {
    return (
      <div className="rounded-lg border border-dashed border-gray-300 p-8 text-center text-gray-500">
        No reviews yet.
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {reviews.map((review) => (
        <div key={review.id} className="rounded-lg border border-gray-200 p-4">
          <div className="flex items-center justify-between mb-2">
            <span className="font-medium text-sm">{review.customer_name ?? 'Anonymous'}</span>
            <span className="text-sm text-amber-600">
              {'★'.repeat(review.rating ?? 0)}{'☆'.repeat(5 - (review.rating ?? 0))}
            </span>
          </div>
          {review.comment ? <p className="text-sm text-gray-600">{review.comment}</p> : null}
          {review.created_at ? (
            <p className="mt-2 text-xs text-gray-400">{new Date(review.created_at).toLocaleDateString()}</p>
          ) : null}
        </div>
      ))}
    </div>
  );
}

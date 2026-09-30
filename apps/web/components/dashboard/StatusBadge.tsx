type BadgeVariant = "success" | "warning" | "danger" | "info" | "neutral";
type BadgeSize = "sm" | "md";

/**
 * Curated set of business-status strings the dashboard renders. Adding
 * a new status? Add it here AND map it in VARIANT_MAP. Anything not in
 * this union falls back to the `neutral` variant.
 */
export type StatusBadgeStatus =
  | "confirmed"
  | "completed"
  | "active"
  | "approved"
  | "published"
  | "delivered"
  | "verified"
  | "paid"
  | "done"
  | "pending"
  | "shortlisted"
  | "packing"
  | "qa"
  | "review"
  | "open"
  | "new"
  | "queued"
  | "launching"
  | "dispatched"
  | "blocked"
  | "cancelled"
  | "rejected"
  | "failed"
  | "draft";

interface StatusBadgeProps {
  /**
   * Either a known StatusBadgeStatus (gets the curated variant) OR any
   * string (gets neutral). Use the union for type-safety at call sites.
   */
  status: StatusBadgeStatus | (string & {});
  /**
   * Override the variant derived from `status`. Useful for statuses that
   * could be rendered as either "info" or "warning" depending on context
   * (e.g. `pending` is usually warning, but in a success-trail it is info).
   */
  variant?: BadgeVariant;
  size?: BadgeSize;
  className?: string;
  /** Optional icon element rendered before the label. */
  icon?: React.ReactNode;
}

const VARIANT_MAP: Record<StatusBadgeStatus, BadgeVariant> = {
  confirmed: "success",
  completed: "success",
  active: "success",
  approved: "success",
  published: "success",
  delivered: "success",
  verified: "success",
  paid: "success",
  done: "success",
  pending: "warning",
  shortlisted: "warning",
  packing: "warning",
  qa: "warning",
  review: "warning",
  open: "warning",
  new: "info",
  queued: "info",
  launching: "info",
  dispatched: "info",
  blocked: "danger",
  cancelled: "danger",
  rejected: "danger",
  failed: "danger",
  draft: "neutral",
};

const VARIANT_CLASS: Record<BadgeVariant, string> = {
  success: "badge-success",
  warning: "badge-warning",
  danger: "badge-danger",
  info: "badge-info",
  neutral: "badge-neutral",
};

const SIZE_CLASS: Record<BadgeSize, string> = {
  sm: "text-[0.625rem] px-2 py-0.5",
  md: "text-xs px-2.5 py-0.5",
};

function isKnownStatus(status: string): status is StatusBadgeStatus {
  return status in VARIANT_MAP;
}

function deriveVariant(status: string): BadgeVariant {
  if (isKnownStatus(status)) return VARIANT_MAP[status];
  return "neutral";
}

function formatLabel(status: string): string {
  if (!status) return "";
  return status
    .replace(/[-_]+/g, " ")
    .split(" ")
    .filter(Boolean)
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(" ");
}

export function StatusBadge({
  status,
  variant,
  size = "md",
  className,
  icon,
}: StatusBadgeProps) {
  const resolved: BadgeVariant = variant ?? deriveVariant(status);
  const variantClass = VARIANT_CLASS[resolved];
  const sizeClass = SIZE_CLASS[size];
  const label = formatLabel(status);

  return (
    <span
      className={`inline-flex items-center gap-1 rounded-full font-semibold ${variantClass} ${sizeClass} ${className ?? ""}`}
      data-status={status}
      data-variant={resolved}
    >
      {icon ? <span aria-hidden="true">{icon}</span> : null}
      {label}
    </span>
  );
}

/** Helper exported for tests: the full list of curated statuses. */
export const KNOWN_STATUSES = Object.keys(VARIANT_MAP) as StatusBadgeStatus[];

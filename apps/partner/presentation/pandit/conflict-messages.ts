export const PARTNER_CONFLICT_MESSAGES: Record<string, string> = {
  PANDIT_ASSIGNMENT_BOOKING_CONFLICT:
    'Schedule conflict: you already have a confirmed ritual or travel buffer overlapping this ceremony slot.',
  PANDIT_BOOKING_WRONG_STATE:
    'This booking is no longer open for confirmation (it may have been reassigned or cancelled by operations).',
  PANDIT_ASSIGNMENT_WRONG_STATE:
    'Action cannot be completed in the assignment’s current status. Refresh to see the latest schedule.',
  CONFLICT:
    'This ritual slot was updated elsewhere. Refresh to load the latest assignment state.',
  DEPENDENCY_UNAVAILABLE:
    'Courtyard sync is temporarily offline. Connect to the network before confirming or completing rituals.',
  UNAUTHENTICATED: 'Your partner session expired. Please sign in again.',
  NOT_FOUND: 'This assignment is no longer assigned to your profile.',
};

export function describeAssignmentError(code?: string | null, fallback?: string | null): string {
  const normalized = (code ?? '').trim();
  if (normalized && PARTNER_CONFLICT_MESSAGES[normalized]) {
    return PARTNER_CONFLICT_MESSAGES[normalized];
  }
  if (fallback && fallback.trim()) {
    return fallback.trim();
  }
  return 'Could not update assignment right now. Check connectivity and try again.';
}


export interface HelpArticle {
  id: string;
  title: string;
  href: string;
}

// Static help surface: the support page is the customer's entry point
// even before the ticketing UI ships. Every href is a real customer route
// (or a mailto), never a dead link.
export const helpArticles: HelpArticle[] = [
  { id: 'manage-booking', title: 'Manage, reschedule, or cancel a booking', href: '/bookings' },
  { id: 'account-security', title: 'Account, security, and MFA settings', href: '/settings' },
  { id: 'saved-rituals', title: 'Saved rituals and temples', href: '/saved' },
  { id: 'contact-support', title: 'Contact the Navdhya support team', href: 'mailto:support@navdhya.com' },
];

export interface SupportRequestInput {
  bookingId?: string;
  topic: string;
  message: string;
}

export type SupportRequestResult =
  | { ok: true; input: SupportRequestInput }
  | { ok: false; reason: 'VALIDATION_ERROR'; fieldErrors: Record<string, string> };

export function validateSupportRequest(input: SupportRequestInput): SupportRequestResult {
  const fieldErrors: Record<string, string> = {};
  if (!input.topic.trim()) fieldErrors.topic = 'Choose a topic';
  if (input.message.trim().length < 10) fieldErrors.message = 'Describe the issue in a few more words';
  if (Object.keys(fieldErrors).length > 0) return { ok: false, reason: 'VALIDATION_ERROR', fieldErrors };
  return { ok: true, input };
}

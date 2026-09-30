export interface HelpArticle {
  id: string;
  title: string;
  href: string;
}

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

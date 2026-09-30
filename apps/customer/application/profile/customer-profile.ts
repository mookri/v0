import type { UserSummary } from '@navdhya/user/src/contracts/UserSummary';

export interface CustomerProfileViewModel {
  id: string;
  name: string;
  email: string;
}

export function toCustomerProfile(user: UserSummary): CustomerProfileViewModel {
  return { id: user.id, name: user.name, email: user.email };
}

export interface ProfileUpdateInput {
  name?: string;
  email?: string;
}

export type ProfileUpdateResult =
  | { ok: true; input: ProfileUpdateInput }
  | { ok: false; reason: 'VALIDATION_ERROR'; fieldErrors: Record<string, string> };

export function validateProfileUpdate(input: ProfileUpdateInput): ProfileUpdateResult {
  const fieldErrors: Record<string, string> = {};
  if (input.email !== undefined && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(input.email)) {
    fieldErrors.email = 'Please enter a valid email address';
  }
  if (input.name !== undefined && input.name.trim().length < 2) {
    fieldErrors.name = 'Please enter your name';
  }
  if (Object.keys(fieldErrors).length > 0) return { ok: false, reason: 'VALIDATION_ERROR', fieldErrors };
  return { ok: true, input };
}

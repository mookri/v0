import { z } from 'zod';
import { e164, challengeCode } from '@/application/auth-schemas';

/**
 * P1 transport schemas for session/state routes — `auth/p0-contract.md`
 * §8. Backend re-validates everything (hostile-BFF, §0); these exist to
 * reject garbage early and to define exactly which keys a route may
 * forward (no `{...body}` spreads anywhere).
 */

/** Saved item reference — restricted charset so ids can't smuggle path segments. */
export const savedAddSchema = z.object({
  ref_id: z
    .string()
    .trim()
    .min(1)
    .max(64)
    .regex(/^[A-Za-z0-9_-]+$/, 'ref_id must be alphanumeric with - or _'),
  title: z.string().trim().max(200).optional(),
});

/**
 * Settings update envelope. The *key set* stays closed by
 * `application/settings.validateSettingsUpdate` (BFF-owned allow-list);
 * this schema only guarantees a plain object (arrays/null rejected).
 */
export const settingsUpdateSchema = z.custom<Record<string, unknown>>(
  (value) => typeof value === 'object' && value !== null && !Array.isArray(value),
  { message: 'Settings must be an object' },
);

/**
 * Verification start — contact format must match its type, and phone
 * contacts are normalized to E.164 so "9876543210" and "+919876543210"
 * never become two identities (contract §1/I3).
 */
export const verificationCreateSchema = z.discriminatedUnion('type', [
  z.object({ type: z.literal('phone'), contact: e164 }),
  z.object({
    type: z.literal('email'),
    contact: z.string().trim().min(3).max(254).email('Must be a valid email'),
  }),
]);

/**
 * Verification confirm — `id` is interpolated into a backend path, so
 * the charset excludes `/`, `:`, `.` and whitespace.
 */
export const verificationConfirmSchema = z.object({
  id: z
    .string()
    .trim()
    .min(8)
    .max(128)
    .regex(/^[A-Za-z0-9_-]+$/, 'Invalid verification id'),
  code: challengeCode,
});

/** Bodyless command — unknown keys are never forwarded. */
export const revokeOthersSchema = z.object({});

export type SavedAdd = z.infer<typeof savedAddSchema>;
export type VerificationCreate = z.infer<typeof verificationCreateSchema>;
export type VerificationConfirm = z.infer<typeof verificationConfirmSchema>;

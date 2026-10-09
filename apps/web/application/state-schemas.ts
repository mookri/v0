import { z } from 'zod';
import { e164, challengeCode } from '@/application/auth-schemas';

/**
 * P1 transport schemas for session/state routes — `auth/p0-contract.md`
 * §8. Backend re-validates everything (hostile-BFF, §0); these exist to
 * reject garbage early and to define exactly which keys a route may
 * forward (no `{...body}` spreads anywhere).
 */

/**
 * Saved item reference — the wire mirrors `SavedItemController::store`
 * exactly: a typed reference (`ref_type` in puja|content|service) plus
 * a ULID-shaped `ref_id` (Crockford base32, 26 chars) so bookmarks are
 * deterministic keys. Backend re-validates (hostile-BFF); rejecting
 * here surfaces the field error with the BFF's own wording (§8).
 */
export const savedAddSchema = z.object({
  ref_type: z.enum(['puja', 'content', 'service']),
  ref_id: z
    .string()
    .trim()
    .min(1)
    .max(64)
    .regex(/^[0-9A-HJKMNP-TV-Z]{26}$/i, 'ref_id must be a 26-character ULID'),
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
 * Verification start — wire keys are `channel` + `value` because that is
 * exactly what `CustomerVerificationController` validates (`{channel:
 * phone|email, value}`); the previous `{type, contact}` shape 422'd every
 * call (mismatch M1). Phone values are normalized to E.164 so "9876543210"
 * and "+919876543210" never become two identities (contract §1/I3).
 */
export const verificationCreateSchema = z.discriminatedUnion('channel', [
  z.object({ channel: z.literal('phone'), value: e164 }),
  z.object({
    channel: z.literal('email'),
    value: z.string().trim().min(3).max(254).email('Must be a valid email'),
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
export const emptyCommandSchema = z.object({});
/** Alias kept for the sessions revoke-others call site. */
export const revokeOthersSchema = emptyCommandSchema;

export type SavedAdd = z.infer<typeof savedAddSchema>;
export type VerificationCreate = z.infer<typeof verificationCreateSchema>;
export type VerificationConfirm = z.infer<typeof verificationConfirmSchema>;

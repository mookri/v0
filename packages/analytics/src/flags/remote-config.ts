// Remote Config guardrails (plan B6 bullet 6): the ONLY approved client-
// behavior/rollout flags, validated server-side. Recorded postures:
//   - client-delivered config is never trusted for authorization or business
//     truth; backend boundaries re-validate every consequential action
//     against registry rules and authoritative state regardless of flags;
//   - no secrets, credentials, or endpoint overrides may ever ride a flag;
//   - unknown flags are REJECTED (an allowlist, not a blocklist).

export type FlagKind = 'boolean' | 'number' | 'string';

export interface FlagSpec {
  name: string;
  kind: FlagKind;
  min?: number;
  max?: number;
  permitted?: string[];
}

/** Approved client behavior + rollout flags only (plan B6 bullet 6). */
export const APPROVED_FLAGS: readonly FlagSpec[] = [
  { name: 'rollout_percentage', kind: 'number', min: 0, max: 100 },
  { name: 'rollout_percentage_checkout_v2', kind: 'number', min: 0, max: 100 },
  { name: 'flag_checkout_flow_variant', kind: 'string', permitted: ['control', 'compact'] },
  { name: 'flag_home_hero_variant', kind: 'string', permitted: ['a', 'b'] },
  { name: 'flag_enable_search_v2', kind: 'boolean' },
];

/** Names that must never be flags — matched before the allowlist. */
const SECRET_NAME = /(secret|token|password|credential|api[-_]?key|private|endpoint|url)/i;

export interface FlagRejection {
  name: string;
  reason: string;
}

export interface ConfigValidation {
  accepted: Record<string, boolean | number | string>;
  rejected: FlagRejection[];
}

/** Validate a client-delivered config patch against the approved flag
 *  allowlist. Returns accepted values (typed) + every rejection with its
 *  reason — nothing is silently dropped. */
export function validateClientConfigPatch(patch: Record<string, unknown>): ConfigValidation {
  const accepted: ConfigValidation['accepted'] = {};
  const rejected: FlagRejection[] = [];

  for (const [name, value] of Object.entries(patch)) {
    if (SECRET_NAME.test(name)) {
      rejected.push({ name, reason: 'secret-like name — flags never carry credentials or endpoints' });
      continue;
    }
    const spec = APPROVED_FLAGS.find((flag) => flag.name === name);
    if (!spec) {
      rejected.push({ name, reason: 'not on the approved flag allowlist' });
      continue;
    }
    if (typeof value !== spec.kind) {
      rejected.push({ name, reason: `type mismatch: expected ${spec.kind}, got ${typeof value}` });
      continue;
    }
    if (spec.kind === 'number') {
      const asNumber = value as number;
      if ((spec.min !== undefined && asNumber < spec.min) || (spec.max !== undefined && asNumber > spec.max)) {
        rejected.push({ name, reason: `number outside [${spec.min ?? '-∞'}, ${spec.max ?? '+∞'}]` });
        continue;
      }
    }
    if (spec.permitted !== undefined && !spec.permitted.includes(value as string)) {
      rejected.push({ name, reason: `value not in permitted set [${spec.permitted.join(', ')}]` });
      continue;
    }
    accepted[name] = value as boolean | number | string;
  }

  return { accepted, rejected };
}

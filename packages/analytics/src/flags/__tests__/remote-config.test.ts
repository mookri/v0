// Remote Config guardrails (plan B6 bullet 6): allowlisted flags only,
// secret-like names rejected before the allowlist, typed values enforced.

import { describe, expect, it } from 'vitest';
import {
  APPROVED_FLAGS,
  validateClientConfigPatch,
} from '@navdhya/analytics/src/flags/remote-config';

describe('remote config validation (B6)', () => {
  it('accepts approved flags with policy-conformant values', () => {
    const result = validateClientConfigPatch({
      rollout_percentage: 25,
      flag_checkout_flow_variant: 'compact',
      flag_enable_search_v2: true,
    });
    expect(result.rejected).toEqual([]);
    expect(result.accepted).toEqual({
      rollout_percentage: 25,
      flag_checkout_flow_variant: 'compact',
      flag_enable_search_v2: true,
    });
  });

  it('REJECTS: unknown flag (allowlist, not blocklist)', () => {
    const result = validateClientConfigPatch({ flag_totally_new_thing: true });
    expect(result.accepted).toEqual({});
    expect(result.rejected[0]).toMatchObject({ name: 'flag_totally_new_thing' });
    expect(result.rejected[0]!.reason).toContain('allowlist');
  });

  it('REJECTS: secret-like names before the allowlist', () => {
    for (const name of ['api_key_override', 'secret_flag', 'payment_token', 'endpoint_url']) {
      const result = validateClientConfigPatch({ [name]: 'x' });
      expect(result.accepted, name).toEqual({});
      expect(result.rejected[0]!.reason, name).toContain('secret-like');
    }
  });

  it('REJECTS: type mismatches and out-of-policy values', () => {
    const result = validateClientConfigPatch({
      rollout_percentage: 'twenty', // wrong kind
      flag_home_hero_variant: 'z', // not in permitted set
      flag_checkout_flow_variant: 3, // wrong kind
    });
    expect(result.accepted).toEqual({});
    expect(result.rejected.map((r) => r.reason)).toEqual([
      expect.stringContaining('type mismatch'),
      expect.stringContaining('permitted set'),
      expect.stringContaining('type mismatch'),
    ]);
  });

  it('REJECTS: numbers outside the declared band', () => {
    const result = validateClientConfigPatch({ rollout_percentage: 101 });
    expect(result.rejected[0]!.reason).toContain('outside');
  });

  it('every approved flag is behavior/rollout shaped — no credentials, no endpoints', () => {
    for (const flag of APPROVED_FLAGS) {
      expect(flag.name, flag.name).toMatch(/^(rollout_|flag_)/);
    }
  });
});

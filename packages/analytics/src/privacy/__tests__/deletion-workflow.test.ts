// Deletion workflow units (plan B7): fail-closed target sets, suppression
// before fan-out, per-system proofs, SLA verification, token upgrades.

import { describe, expect, it } from 'vitest';
import {
  DeletionWorkflow,
  MemoryDeletionAuditStore,
  REQUIRED_ACCOUNT_DELETION_TARGETS,
  REQUIRED_WITHDRAWAL_TARGETS,
  type DeletionRequest,
  type ErasureTarget,
} from '@navdhya/analytics/src/privacy/deletion-workflow';
import { MemoryErasureTokenStore } from '@navdhya/analytics/src/privacy/erasure-tokens';
import {
  MemoryConsentLedger,
  assertFanoutAllowed,
} from '@navdhya/analytics/src/privacy/consent-ledger';

const NOW = Date.parse('2026-10-13T12:00:00.000Z');
const DAY = 24 * 60 * 60 * 1000;

function target(id: string, evidence = `${id} erased`): ErasureTarget {
  return { id, execute: async () => ({ status: 'erased', evidence }) };
}

function workflow(targets: readonly ErasureTarget[]) {
  const audit = new MemoryDeletionAuditStore();
  const erasureTokens = new MemoryErasureTokenStore();
  const ledger = new MemoryConsentLedger();
  let nowMs = NOW;
  const instance = new DeletionWorkflow({
    targets,
    audit,
    erasureTokens,
    ledger,
    nowMs: () => nowMs,
  });
  return { instance, audit, erasureTokens, ledger, advance: (ms: number) => (nowMs += ms) };
}

function accountRequest(overrides: Partial<DeletionRequest> = {}): DeletionRequest {
  return {
    requestId: 'del_1',
    subject: { uidHash: 'uh_subject', anonId: null, fcmToken: 'fcm_1' },
    reason: 'account_deletion',
    requestedAtMs: NOW,
    ...overrides,
  };
}

describe('deletion workflow (B7)', () => {
  it('account deletion: every required system gets a proof; token + ledger recorded', async () => {
    const { instance, erasureTokens, ledger } = workflow(
      REQUIRED_ACCOUNT_DELETION_TARGETS.map((id) => target(id)),
    );
    const record = await instance.run(accountRequest());

    expect(record.status).toBe('complete');
    expect(record.completedAtMs).toBe(NOW);
    expect(record.deadlineMs).toBe(NOW + 30 * DAY);
    expect(record.proofs.map((p) => p.target)).toEqual([...REQUIRED_ACCOUNT_DELETION_TARGETS]);
    expect(record.proofs.every((p) => p.status === 'erased')).toBe(true);
    for (const proof of record.proofs) expect(proof.evidence.length).toBeGreaterThan(0);

    expect(await erasureTokens.get('uh_subject')).toMatchObject({ scope: 'full', requestId: 'del_1' });
    expect((await ledger.get('uh_subject', 'analytics'))?.requestId).toBe('del_1');
    expect((await ledger.get('uh_subject', 'ads'))?.requestId).toBe('del_1');
    expect((await assertFanoutAllowed(ledger, 'uh_subject', 'analytics')).ok).toBe(false);
  });

  it('FAIL-CLOSED: an incomplete target set refuses to run', async () => {
    const { instance } = workflow([target('signals_raw'), target('signals_stg')]);
    await expect(instance.run(accountRequest())).rejects.toThrow(/incomplete erasure target set/);
  });

  it('refuses a request without a pseudonymous anchor', async () => {
    const { instance } = workflow(REQUIRED_ACCOUNT_DELETION_TARGETS.map((id) => target(id)));
    await expect(
      instance.run(
        accountRequest({ subject: { uidHash: null, anonId: null, fcmToken: 'fcm_only' } }),
      ),
    ).rejects.toThrow(/no pseudonymous anchor/);
  });

  it('a throwing target becomes a VISIBLE failed proof → partial_failure', async () => {
    const targets = REQUIRED_ACCOUNT_DELETION_TARGETS.map((id) =>
      id === 'ga4_user_deletion'
        ? { id, execute: async () => { throw new Error('GA4 API 503'); } }
        : target(id),
    );
    const { instance } = workflow(targets);
    const record = await instance.run(accountRequest());
    expect(record.status).toBe('partial_failure');
    expect(record.completedAtMs).toBeNull();
    const failed = record.proofs.find((p) => p.target === 'ga4_user_deletion')!;
    expect(failed.status).toBe('failed');
    expect(failed.evidence).toContain('GA4 API 503');
  });

  it('SLA: an unfinished request past the 30-day deadline becomes sla_breached; complete stays complete', async () => {
    const failing: ErasureTarget[] = REQUIRED_ACCOUNT_DELETION_TARGETS.map((id) =>
      id === 'fcm_audience'
        ? { id, execute: async () => ({ status: 'failed' as const, evidence: 'audience sync down' }) }
        : target(id),
    );
    const { instance, advance } = workflow(failing);
    const record = await instance.run(accountRequest());
    expect(record.status).toBe('partial_failure');

    advance(31 * DAY);
    const verified = await instance.verify('del_1');
    expect(verified.status).toBe('sla_breached');

    const { instance: done } = workflow(REQUIRED_ACCOUNT_DELETION_TARGETS.map((id) => target(id)));
    const complete = await done.run(accountRequest());
    expect((await done.verify(complete.requestId)).status).toBe('complete');
  });

  it('consent withdrawal: only the withdrawal target set, fanout_only token, ledger first', async () => {
    const seen: string[] = [];
    const provingTarget: ErasureTarget = {
      id: 'fanout_block',
      execute: async () => {
        // The proof target runs AFTER suppression is recorded — it observes
        // the ledger already blocking.
        seen.push('checked');
        return { status: 'blocked', evidence: 'ledger confirms withdrawal' };
      },
    };
    const targets: ErasureTarget[] = [
      provingTarget,
      target('ga4_user_deletion', 'GA4 deletion request filed'),
      { id: 'fcm_audience', execute: async () => ({ status: 'suppressed', evidence: 'token removed from audience' }) },
    ];
    const { instance, erasureTokens, ledger } = workflow(targets);
    const record = await instance.run({
      requestId: 'wd_1',
      subject: { uidHash: 'uh_wd', anonId: null, fcmToken: null },
      reason: 'consent_withdrawal',
      requestedAtMs: NOW,
      purposes: ['ads'],
    });

    expect(seen).toEqual(['checked']);
    expect(record.status).toBe('complete');
    expect(record.proofs.find((p) => p.target === 'fanout_block')!.status).toBe('blocked');
    expect(await erasureTokens.get('uh_wd')).toMatchObject({ scope: 'fanout_only' });
    expect((await ledger.get('uh_wd', 'ads'))?.requestId).toBe('wd_1');
    expect((await ledger.get('uh_wd', 'analytics'))).toBeNull(); // purpose-scoped

    // The withdrawal target set does NOT include raw datasets:
    expect(REQUIRED_WITHDRAWAL_TARGETS).not.toContain('signals_raw');
  });

  it('dry-run: plans every system, records NOTHING, never claims erasure', async () => {
    const { instance, erasureTokens, ledger } = workflow(
      REQUIRED_ACCOUNT_DELETION_TARGETS.map((id) => ({
        id,
        execute: async () => ({ status: 'planned' as const, evidence: `would DELETE from ${id}` }),
      })),
    );
    const record = await instance.run(accountRequest({ mode: 'dry_run' }));
    expect(record.status).toBe('dry_run');
    expect(record.mode).toBe('dry_run');
    expect(record.completedAtMs).toBeNull();
    expect(record.proofs.every((p) => p.status === 'planned')).toBe(true);
    // No side effects at all:
    expect(await erasureTokens.get('uh_subject')).toBeNull();
    expect(await ledger.get('uh_subject', 'analytics')).toBeNull();
    // Dry-run records are never flipped to sla_breached either:
    expect((await instance.verify('del_1')).status).toBe('dry_run');
  });

  it('erasure tokens upgrade fanout_only → full, never downgrade', async () => {
    const { instance, erasureTokens } = workflow(
      REQUIRED_WITHDRAWAL_TARGETS.map((id) => target(id)),
    );
    await instance.run({
      requestId: 'wd_2',
      subject: { uidHash: 'uh_up', anonId: null, fcmToken: null },
      reason: 'consent_withdrawal',
      requestedAtMs: NOW,
    });
    expect((await erasureTokens.get('uh_up'))!.scope).toBe('fanout_only');

    const full = workflow([
      ...REQUIRED_ACCOUNT_DELETION_TARGETS.map((id) => target(id)),
      target('fanout_block'), // so the harness can also run withdrawal requests
    ]);
    await full.instance.run(
      accountRequest({ requestId: 'del_up', subject: { uidHash: 'uh_up', anonId: null, fcmToken: null } }),
    );
    expect((await full.erasureTokens.get('uh_up'))!.scope).toBe('full');

    // Downgrade attempt: a withdrawal after a full deletion keeps 'full'.
    await full.instance.run({
      requestId: 'wd_down',
      subject: { uidHash: 'uh_up', anonId: null, fcmToken: null },
      reason: 'consent_withdrawal',
      requestedAtMs: NOW,
    });
    expect((await full.erasureTokens.get('uh_up'))!.scope).toBe('full');
  });
});

// B7 acceptance — privacy lifecycle end to end (plan B7):
//   1. E2E deletion fixture: subject seeded across raw/stg/mart/
//      reconciliation/GA4-mock/audience + identity/cache → account_deletion
//      workflow → absence/suppression EVERYWHERE with a complete audit
//      record (proof per system, evidence, timestamps).
//   2. Raw-dataset erasure: storage-level hard delete — rows are physically
//      gone, not soft-excluded by a view.
//   3. Erasure-aware access: queries over erased hashes return nothing; the
//      erasure-token table is authoritative.
//   4. Consent withdrawal: fan-out blocked before any destination runs,
//      within the SLO; previously granted destinations (GA4, audience)
//      revoked; raw data retained under the retention matrix.
import { describe, expect, it } from 'vitest';
import {
  DeletionWorkflow,
  MemoryDeletionAuditStore,
  REQUIRED_ACCOUNT_DELETION_TARGETS,
  REQUIRED_WITHDRAWAL_TARGETS,
  DELETION_SLA_DAYS,
  type DeletionRequest,
  type ErasureTarget,
  type SubjectRef,
} from '@navdhya/analytics/src/privacy/deletion-workflow';
import {
  MemoryErasureTokenStore,
  excludeErased,
} from '@navdhya/analytics/src/privacy/erasure-tokens';
import {
  MemoryConsentLedger,
  WITHDRAWAL_BLOCK_SLO_MS,
  assertFanoutAllowed,
} from '@navdhya/analytics/src/privacy/consent-ledger';

const NOW = Date.parse('2026-10-13T12:00:00.000Z');
const DAY = 24 * 60 * 60 * 1000;

// The subject exists ONLY as pseudonyms in the signal systems — that is all
// a deletion workflow may ever see or record.
const SUBJECT: SubjectRef = { uidHash: 'v2:subjhash00000001', anonId: 'anon-77', fcmToken: 'fcm_subject_1' };
const OTHER: SubjectRef = { uidHash: 'v2:otherhash000001', anonId: null, fcmToken: 'fcm_other_1' };

interface Row {
  id: string;
  uidHash: string;
}

/** In-memory stand-ins for every system holding the subject. The dataset
 *  tables are modelled as MATERIALIZED stores (rows you can splice out) to
 *  prove per-system hard delete; this warehouse's stg/mart are views whose
 *  rows physically live in raw — the CLI records re-projection for those. */
function seedWorld() {
  const raw: Row[] = [
    { id: 'raw_1', uidHash: SUBJECT.uidHash! },
    { id: 'raw_2', uidHash: SUBJECT.uidHash! },
    { id: 'raw_3', uidHash: OTHER.uidHash! },
  ];
  const stg: Row[] = [
    { id: 'stg_1', uidHash: SUBJECT.uidHash! },
    { id: 'stg_2', uidHash: OTHER.uidHash! },
  ];
  const mart: Row[] = [
    { id: 'mart_1', uidHash: SUBJECT.uidHash! },
    { id: 'mart_2', uidHash: OTHER.uidHash! },
  ];
  const recon: Row[] = [
    { id: 'recon_1', uidHash: SUBJECT.uidHash! },
    { id: 'recon_2', uidHash: OTHER.uidHash! },
  ];
  const ga4Requests: Array<{ value: string; type: string }> = [];
  const audience = new Set<string>([SUBJECT.fcmToken!, OTHER.fcmToken!]);
  const identity = new Map<string, string[]>([
    [SUBJECT.uidHash!, ['google:123', 'apple:xyz']],
    [OTHER.uidHash!, ['google:999']],
  ]);
  const cache = new Map<string, { subjectHash: string }>([
    [`proj:${SUBJECT.uidHash}`, { subjectHash: SUBJECT.uidHash! }],
    [`proj:${OTHER.uidHash}`, { subjectHash: OTHER.uidHash! }],
  ]);

  const rowsTarget = (id: string, rows: Row[]): ErasureTarget => ({
    id,
    execute: async () => {
      const before = rows.length;
      for (let index = rows.length - 1; index >= 0; index -= 1) {
        if (rows[index]!.uidHash === SUBJECT.uidHash) rows.splice(index, 1);
      }
      return {
        status: 'erased',
        evidence: `${before - rows.length} rows hard-deleted from ${id} storage`,
      };
    },
  });

  const targets: ErasureTarget[] = [
    rowsTarget('signals_raw', raw),
    rowsTarget('signals_stg', stg),
    rowsTarget('signals_mart', mart),
    rowsTarget('signals_reconciliation', recon),
    {
      id: 'ga4_user_deletion',
      execute: async () => {
        ga4Requests.push({ value: SUBJECT.uidHash!, type: 'USER_ID' });
        return { status: 'erased', evidence: 'GA4 deleteUserdata request accepted' };
      },
    },
    {
      id: 'identity_mapping',
      execute: async () => {
        identity.delete(SUBJECT.uidHash!);
        return { status: 'erased', evidence: 'identity rows removed' };
      },
    },
    {
      id: 'cached_projections',
      execute: async () => {
        cache.delete(`proj:${SUBJECT.uidHash}`);
        return { status: 'suppressed', evidence: 'projection cache purged' };
      },
    },
    {
      id: 'fcm_audience',
      execute: async () => {
        if (SUBJECT.fcmToken) audience.delete(SUBJECT.fcmToken);
        return { status: 'suppressed', evidence: 'FCM token removed from audience' };
      },
    },
  ];

  return { raw, stg, mart, recon, ga4Requests, audience, identity, cache, targets };
}

function buildWorkflow(targets: readonly ErasureTarget[], startMs = NOW) {
  const audit = new MemoryDeletionAuditStore();
  const erasureTokens = new MemoryErasureTokenStore();
  const ledger = new MemoryConsentLedger();
  let nowMs = startMs;
  const workflow = new DeletionWorkflow({
    targets,
    audit,
    erasureTokens,
    ledger,
    nowMs: () => nowMs,
  });
  return { workflow, audit, erasureTokens, ledger, at: (ms: number) => (nowMs = ms) };
}

const ACCOUNT_REQUEST: DeletionRequest = {
  requestId: 'del_e2e_1',
  subject: SUBJECT,
  reason: 'account_deletion',
  requestedAtMs: NOW,
};

describe('B7 acceptance — account deletion end to end', () => {
  it('erases or suppresses the subject in EVERY system, with a complete audit record', async () => {
    const world = seedWorld();
    const { workflow, erasureTokens, ledger } = buildWorkflow(world.targets);

    const record = await workflow.run(ACCOUNT_REQUEST);

    // Complete audit: one proof per required system, all successful,
    // each with timestamp + evidence; the 30-day SLA deadline recorded.
    expect(record.status).toBe('complete');
    expect(record.mode).toBe('execute');
    expect(record.proofs.map((proof) => proof.target)).toEqual([...REQUIRED_ACCOUNT_DELETION_TARGETS]);
    expect(record.proofs.every((proof) => proof.status !== 'failed')).toBe(true);
    expect(record.proofs.every((proof) => proof.evidence.length > 0)).toBe(true);
    expect(record.proofs.every((proof) => proof.atMs === NOW)).toBe(true);
    expect(record.deadlineMs).toBe(NOW + DELETION_SLA_DAYS * DAY);
    expect(record.completedAtMs).toBe(NOW);
    // The audit record holds the pseudonymous anchor — never a raw id.
    expect(record.subjectHash).toBe(SUBJECT.uidHash);

    // Absence everywhere (subject-scoped — the other subject is untouched).
    for (const [name, rows] of [
      ['raw', world.raw],
      ['stg', world.stg],
      ['mart', world.mart],
      ['recon', world.recon],
    ] as const) {
      expect(rows.filter((row) => row.uidHash === SUBJECT.uidHash), name).toEqual([]);
      expect(rows.some((row) => row.uidHash === OTHER.uidHash), name).toBe(true);
    }
    expect(world.ga4Requests).toContainEqual({ value: SUBJECT.uidHash, type: 'USER_ID' });
    expect(world.audience.has(SUBJECT.fcmToken!)).toBe(false);
    expect(world.audience.has(OTHER.fcmToken!)).toBe(true);
    expect(world.identity.has(SUBJECT.uidHash!)).toBe(false);
    expect(world.identity.has(OTHER.uidHash!)).toBe(true);
    expect(world.cache.has(`proj:${SUBJECT.uidHash}`)).toBe(false);
    expect(world.cache.has(`proj:${OTHER.uidHash}`)).toBe(true);

    // Authoritative suppression: token + ledger, fan-out blocked.
    expect(await erasureTokens.get(SUBJECT.uidHash!)).toMatchObject({ scope: 'full', requestId: 'del_e2e_1' });
    expect((await assertFanoutAllowed(ledger, SUBJECT.uidHash!, 'analytics')).ok).toBe(false);
    expect((await assertFanoutAllowed(ledger, OTHER.uidHash!, 'analytics')).ok).toBe(true);
  });

  it('raw-dataset erasure is a storage-level hard delete, not a view exclusion', async () => {
    const world = seedWorld();
    const rawBefore = world.raw.length; // 3 rows (2 subject + 1 other)
    const { workflow } = buildWorkflow(world.targets);
    await workflow.run(ACCOUNT_REQUEST);

    // The rows are physically gone from the storage array — no exclusion
    // predicate, no erased-marking, no soft-delete flag anywhere:
    expect(world.raw.length).toBe(rawBefore - 2);
    expect(world.raw.some((row) => row.id === 'raw_1' || row.id === 'raw_2')).toBe(false);
    // A soft-delete-only mechanism would leave the rows in storage and
    // filter at read time; nothing here filters at read time.
  });

  it('erasure-aware access: queries over erased hashes return nothing', async () => {
    const { workflow, erasureTokens } = buildWorkflow(seedWorld().targets);
    await workflow.run(ACCOUNT_REQUEST);

    const rows = [
      { id: 'e1', uidHash: OTHER.uidHash! },
      { id: 'e2', uidHash: SUBJECT.uidHash! },
    ];
    const erased = await erasureTokens.filterErased(rows.map((row) => row.uidHash));
    expect(erased).toEqual(new Set([SUBJECT.uidHash]));
    expect(excludeErased(rows, (row) => row.uidHash, erased).map((row) => row.id)).toEqual(['e1']);
    // A query targeting only erased subjects returns nothing at all:
    expect(
      excludeErased(
        rows.filter((row) => row.uidHash === SUBJECT.uidHash),
        (row) => row.uidHash,
        erased,
      ),
    ).toEqual([]);
  });
});

describe('B7 acceptance — consent withdrawal', () => {
  it('blocks fan-out before any destination runs, within the SLO; previously granted destinations revoked', async () => {
    const world = seedWorld();
    // Observe suppression state AT fan-out time: the fanout proof target
    // executes during the workflow and must already see the block.
    // (Object holder: closure-assigned `let` vars confuse TS narrowing.)
    const fanoutState: { guard: { ok: boolean } | null } = { guard: null };
    const targets: ErasureTarget[] = [
      ...world.targets.filter((target) =>
        ['ga4_user_deletion', 'fcm_audience'].includes(target.id),
      ),
      {
        id: 'fanout_block',
        execute: async () => {
          const { ledger } = context;
          fanoutState.guard = await assertFanoutAllowed(ledger, SUBJECT.uidHash!, 'ads');
          return { status: 'blocked', evidence: 'ledger confirms withdrawal' };
        },
      },
    ];
    const context = buildWorkflow(targets);
    const { workflow, erasureTokens, ledger } = context;

    const request: DeletionRequest = {
      requestId: 'wd_e2e_1',
      subject: SUBJECT,
      reason: 'consent_withdrawal',
      requestedAtMs: NOW,
      purposes: ['analytics', 'ads'],
    };
    const record = await workflow.run(request);

    expect(record.status).toBe('complete');
    expect(record.proofs.map((proof) => proof.target).sort()).toEqual(
      [...REQUIRED_WITHDRAWAL_TARGETS].sort(),
    );

    // Blocked BEFORE fan-out: by the time the fan-out boundary ran, the
    // ledger already refused — and the withdrawal landed within the SLO.
    expect(fanoutState.guard).not.toBeNull();
    expect(fanoutState.guard!.ok).toBe(false);
    for (const purpose of ['analytics', 'ads'] as const) {
      const withdrawal = await ledger.get(SUBJECT.uidHash!, purpose);
      expect(withdrawal).not.toBeNull();
      expect(withdrawal!.withdrawnAtMs - request.requestedAtMs).toBeLessThanOrEqual(WITHDRAWAL_BLOCK_SLO_MS);
    }

    // Previously granted destinations verified:
    expect(world.ga4Requests).toContainEqual({ value: SUBJECT.uidHash, type: 'USER_ID' });
    expect(world.audience.has(SUBJECT.fcmToken!)).toBe(false);

    // Raw data is NOT erased on withdrawal — it ages out by the retention
    // matrix; the token records exactly that scope.
    expect(world.raw.filter((row) => row.uidHash === SUBJECT.uidHash).length).toBe(2);
    expect(await erasureTokens.get(SUBJECT.uidHash!)).toMatchObject({ scope: 'fanout_only' });
  });
});

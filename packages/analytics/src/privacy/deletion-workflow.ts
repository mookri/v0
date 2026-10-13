// Deletion workflow (plan B7, implementation bullet 2): account deletion /
// consent withdrawal → execute EVERY system holding the subject → record a
// proof per system → verified completion. 30-day SLA.
//
// Design postures (all evaluated):
//   - FAIL-CLOSED target sets: the workflow refuses to run unless every
//     required system for the request reason is present. "Verifiably
//     removes in every system" means an incomplete wiring is an ERROR,
//     not a silent partial erasure.
//   - Suppression lands BEFORE fan-out: the consent ledger withdrawal and
//     the erasure token are recorded before any target executes, so a
//     racing send path already sees the block.
//   - Proofs are per-system, timestamped, evidence-carrying — the audit
//     record is the proof a regulator asks for.
//   - Hard delete where required: `signals_raw/stg/mart/reconciliation`,
//     identity mapping, cached projections ERASE (a view filter is not
//     enforcement); GA4 gets a user-deletion API request; FCM/audience
//     gets removal; fan-out gets blocked via the ledger.

import type { ConsentLedgerStore, ConsentPurpose } from './consent-ledger';
import type { ErasureScope, ErasureTokenStore } from './erasure-tokens';

export const DELETION_SLA_DAYS = 30;

export interface SubjectRef {
  /** HMAC-pseudonymous anchor — the erasure key. At least one field set. */
  uidHash: string | null;
  anonId: string | null;
  fcmToken: string | null;
}

export type DeletionReason = 'account_deletion' | 'consent_withdrawal';

export type WorkflowMode = 'execute' | 'dry_run';

export interface DeletionRequest {
  requestId: string;
  subject: SubjectRef;
  reason: DeletionReason;
  requestedAtMs: number;
  /** Purposes withdrawn on consent_withdrawal (default: both). */
  purposes?: ConsentPurpose[];
  /** 'dry_run' (the CLI default) records PLANNED operations and never
   *  claims erasure happened. Default: 'execute'. */
  mode?: WorkflowMode;
}

export type ErasureStatus = 'erased' | 'suppressed' | 'blocked' | 'not_applicable' | 'failed' | 'planned';

export interface ErasureProof {
  target: string;
  status: ErasureStatus;
  atMs: number;
  evidence: string;
}

export interface ErasureTarget {
  id: string;
  execute(
    subject: SubjectRef,
    request: DeletionRequest,
  ): Promise<{ status: ErasureStatus; evidence: string }>;
}

export type AuditStatus = 'in_progress' | 'complete' | 'partial_failure' | 'sla_breached' | 'dry_run';

export interface DeletionAuditRecord {
  requestId: string;
  /** Pseudonymous anchor only — audit records never hold raw identifiers. */
  subjectHash: string;
  reason: DeletionReason;
  mode: WorkflowMode;
  requestedAtMs: number;
  deadlineMs: number;
  proofs: ErasureProof[];
  completedAtMs: number | null;
  status: AuditStatus;
}

export interface DeletionAuditStore {
  put(record: DeletionAuditRecord): Promise<void>;
  get(requestId: string): Promise<DeletionAuditRecord | null>;
}

export class MemoryDeletionAuditStore implements DeletionAuditStore {
  private readonly rows = new Map<string, DeletionAuditRecord>();

  async put(record: DeletionAuditRecord): Promise<void> {
    this.rows.set(record.requestId, JSON.parse(JSON.stringify(record)) as DeletionAuditRecord);
  }

  async get(requestId: string): Promise<DeletionAuditRecord | null> {
    const record = this.rows.get(requestId);
    return record ? (JSON.parse(JSON.stringify(record)) as DeletionAuditRecord) : null;
  }
}

/** Every system an account deletion must reach — the workflow fails closed
 *  if any of these has no wired target. */
export const REQUIRED_ACCOUNT_DELETION_TARGETS: readonly string[] = [
  'signals_raw',
  'signals_stg',
  'signals_mart',
  'signals_reconciliation',
  'ga4_user_deletion',
  'identity_mapping',
  'cached_projections',
  'fcm_audience',
];

/** Every system a consent withdrawal must reach: stop fan-out, revoke the
 *  previously granted destinations (GA4), drop messaging identifiers.
 *  Raw data is NOT erased on withdrawal — it ages out by the retention
 *  matrix (scope 'fanout_only' token records exactly that). */
export const REQUIRED_WITHDRAWAL_TARGETS: readonly string[] = [
  'fanout_block',
  'ga4_user_deletion',
  'fcm_audience',
];

export interface WorkflowDeps {
  targets: readonly ErasureTarget[];
  audit: DeletionAuditStore;
  erasureTokens: ErasureTokenStore;
  ledger: ConsentLedgerStore;
  nowMs: () => number;
}

export class DeletionWorkflow {
  constructor(private readonly deps: WorkflowDeps) {}

  async run(request: DeletionRequest): Promise<DeletionAuditRecord> {
    const subjectHash = request.subject.uidHash ?? request.subject.anonId;
    if (subjectHash === null) {
      throw new Error('deletion request has no pseudonymous anchor (uidHash/anonId) — refusing to run');
    }

    const required =
      request.reason === 'account_deletion' ? REQUIRED_ACCOUNT_DELETION_TARGETS : REQUIRED_WITHDRAWAL_TARGETS;
    const byId = new Map(this.deps.targets.map((target) => [target.id, target]));
    const missing = required.filter((id) => !byId.has(id));
    if (missing.length > 0) {
      throw new Error(
        `incomplete erasure target set for ${request.reason}: missing [${missing.join(', ')}] — refusing to run a partial erasure`,
      );
    }

    // Suppression first: ledger + erasure token land BEFORE any target runs,
    // so a racing fan-out path is already blocked when external systems
    // start processing. Dry-run records NOTHING — it is side-effect free.
    const mode: WorkflowMode = request.mode ?? 'execute';
    const nowMs = this.deps.nowMs();
    if (mode === 'execute') {
      const purposes: ConsentPurpose[] =
        request.reason === 'consent_withdrawal'
          ? (request.purposes ?? ['analytics', 'ads'])
          : ['analytics', 'ads'];
      for (const purpose of purposes) {
        await this.deps.ledger.withdraw({
          subjectHash,
          purpose,
          withdrawnAtMs: nowMs,
          requestId: request.requestId,
        });
      }
      const scope: ErasureScope = request.reason === 'account_deletion' ? 'full' : 'fanout_only';
      await this.deps.erasureTokens.record({
        subjectHash,
        scope,
        erasedAtMs: nowMs,
        requestId: request.requestId,
      });
    }

    const proofs: ErasureProof[] = [];
    for (const targetId of required) {
      const target = byId.get(targetId)!;
      try {
        const result = await target.execute(request.subject, request);
        proofs.push({ target: targetId, status: result.status, atMs: this.deps.nowMs(), evidence: result.evidence });
      } catch (error) {
        // A target error is a VISIBLE failure, never a silent skip.
        proofs.push({
          target: targetId,
          status: 'failed',
          atMs: this.deps.nowMs(),
          evidence: `target threw: ${error instanceof Error ? error.message : String(error)}`,
        });
      }
    }

    const failed = proofs.filter((proof) => proof.status === 'failed');
    const planned = proofs.filter((proof) => proof.status === 'planned');
    const record: DeletionAuditRecord = {
      requestId: request.requestId,
      subjectHash,
      reason: request.reason,
      mode,
      requestedAtMs: request.requestedAtMs,
      deadlineMs: request.requestedAtMs + DELETION_SLA_DAYS * 24 * 60 * 60 * 1000,
      proofs,
      completedAtMs:
        mode === 'execute' && failed.length === 0 && planned.length === 0 ? this.deps.nowMs() : null,
      status:
        mode === 'dry_run'
          ? 'dry_run'
          : failed.length === 0 && planned.length === 0
            ? 'complete'
            : 'partial_failure',
    };
    await this.deps.audit.put(record);
    return record;
  }

  /** Verify completion against the SLA: an unfinished request past its
   *  deadline becomes `sla_breached` (never quietly forgotten). Dry-run
   *  records are never SLA-tracked — they never claimed completion. */
  async verify(requestId: string): Promise<DeletionAuditRecord> {
    const record = await this.deps.audit.get(requestId);
    if (!record) throw new Error(`no deletion audit record for ${requestId}`);
    const open = record.status === 'in_progress' || record.status === 'partial_failure';
    if (open && record.completedAtMs === null && this.deps.nowMs() > record.deadlineMs) {
      const breached: DeletionAuditRecord = { ...record, status: 'sla_breached' };
      await this.deps.audit.put(breached);
      return breached;
    }
    return record;
  }
}

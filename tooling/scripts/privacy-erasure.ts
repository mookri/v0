// Privacy erasure CLI (plan B7): runs the deletion workflow across every
// system holding a subject (account deletion / consent withdrawal).
//
// DRY-RUN by default: prints the exact per-system operation and records a
// `dry_run` audit record that claims NOTHING was erased.
//
//   npx tsx tooling/scripts/privacy-erasure.ts --request request.json [--live]
//
// request.json:
//   {
//     "requestId": "del_2026_10_13_001",         // required, explicit for SLA tracking
//     "reason": "account_deletion" | "consent_withdrawal",
//     "subject": { "uidHash": "...", "anonId": "...", "fcmToken": "..." },
//     "requestedAtMs": 1760000000000,             // optional (default: now)
//     "purposes": ["analytics", "ads"]            // consent_withdrawal only
//   }
//
// --live FAILS CLOSED:
//   - every credential the request's target set needs is checked up front;
//     a missing credential aborts BEFORE any system is touched;
//   - audit record + erasure tokens + consent ledger persist to Postgres
//     (DATABASE_URL) — the erasure-token row is the authoritative
//     suppression record, so a live run that cannot write it must not run;
//   - a failed target is a visible `failed` proof and a red exit.
//
//   SIGNALS_BQ_PROJECT              BigQuery signals_raw hard DELETEs via
//                                   the bq CLI, capped by
//                                   SIGNALS_BQ_MAX_BYTES_BILLED (default 10 GiB)
//   GA4_PROPERTY_ID, GA4_ACCESS_TOKEN   GA4 Data Deletion API
//                                   (properties.deleteUserdata)
//   DATABASE_URL                    psql: identity-mapping purge +
//                                   erasure tokens/audit/ledger writes
//   SIGNALS_CACHE_PURGE_URL         cached-projection purge endpoint
//   SIGNALS_AUDIENCE_URL[,SIGNALS_AUDIENCE_TOKEN]
//                                   FCM/audience removal (only when the
//                                   subject has an fcmToken)
//
// Honesty notes:
//   - signals_stg / signals_mart are VIEWS in this warehouse — their rows
//     live in signals_raw, so the plan records re-projection rather than a
//     fake DELETE. Deployments that materialise marts wire their own target.
//   - The GA4 uuid type must match the identifier GA4 received at
//     collection time (uidHash-as-user_id vs anonId-as-app_instance_id);
//     adjust here if collection ever changes.
//   - Subprocesses run WITHOUT a shell; a Windows `.cmd` fallback only
//     engages when direct spawn fails, and any argument containing `"` or
//     `%` is refused outright rather than interpolated.
import { spawnSync } from 'node:child_process';
import * as fs from 'node:fs';
import {
  DeletionWorkflow,
  MemoryDeletionAuditStore,
  REQUIRED_ACCOUNT_DELETION_TARGETS,
  REQUIRED_WITHDRAWAL_TARGETS,
  type DeletionAuditRecord,
  type DeletionAuditStore,
  type DeletionRequest,
  type ErasureStatus,
  type ErasureTarget,
  type SubjectRef,
} from '@navdhya/analytics/src/privacy/deletion-workflow';
import {
  MemoryErasureTokenStore,
  type ErasureToken,
  type ErasureTokenStore,
} from '@navdhya/analytics/src/privacy/erasure-tokens';
import {
  MemoryConsentLedger,
  type ConsentLedgerStore,
  type ConsentPurpose,
  type ConsentWithdrawal,
} from '@navdhya/analytics/src/privacy/consent-ledger';

const BQ_MAX_BYTES_BILLED = process.env.SIGNALS_BQ_MAX_BYTES_BILLED ?? '10737418240'; // 10 GiB cap
const SAFE_IDENT = /^[A-Za-z0-9_:.-]+$/; // subject hashes are pseudonyms — reject anything SQL-hostile

function arg(name: string): string | undefined {
  const index = process.argv.indexOf(`--${name}`);
  return index >= 0 ? process.argv[index + 1] : undefined;
}

function hasFlag(name: string): boolean {
  return process.argv.includes(`--${name}`);
}

function readJson<T>(file: string): T {
  const text = fs.readFileSync(file, 'utf8'); // strip BOM if a Windows tool added one
  return JSON.parse(text.charCodeAt(0) === 0xfeff ? text.slice(1) : text) as T;
}

function missingEnv(...names: string[]): string[] {
  return names.filter((name) => !process.env[name]);
}

function sqlLit(value: string): string {
  return value.replace(/'/g, "''");
}

interface CmdResult {
  ok: boolean;
  out: string;
  err: string;
}

/** Run a CLI without a shell. Windows `.cmd` shims (bq) get a cmd.exe
 *  fallback; any argument with `"` or `%` is refused rather than
 *  interpolated into a command string. */
function runCli(cmd: string, args: string[], extraEnv?: Record<string, string>): CmdResult {
  for (const value of args) {
    if (/["%]/.test(value)) {
      return { ok: false, out: '', err: `refusing shell-unsafe argument for ${cmd} (contains " or %)` };
    }
  }
  const env = extraEnv ? { ...process.env, ...extraEnv } : process.env;
  let result = spawnSync(cmd, args, { encoding: 'utf8', env });
  if (result.error && (result.error as NodeJS.ErrnoException).code === 'ENOENT') {
    const command = [cmd, ...args].map((part) => (part.includes(' ') ? `"${part}"` : part)).join(' ');
    result = spawnSync(process.env.ComSpec ?? 'cmd.exe', ['/d', '/s', '/c', command], {
      encoding: 'utf8',
      env,
    });
  }
  if (result.error) {
    return { ok: false, out: '', err: `${cmd} unavailable: ${result.error.message}` };
  }
  return {
    ok: result.status === 0,
    out: result.stdout ?? '',
    err: (result.stderr ?? '').trim().slice(0, 400),
  };
}

function runBq(sql: string): { status: ErasureStatus; evidence: string } {
  const project = process.env.SIGNALS_BQ_PROJECT;
  if (!project) return { status: 'failed', evidence: 'SIGNALS_BQ_PROJECT not set' };
  const result = runCli('bq', [
    'query',
    '--nouse_legacy_sql',
    '--format=quiet',
    `--max_bytes_billed=${BQ_MAX_BYTES_BILLED}`,
    sql,
  ]);
  return result.ok
    ? { status: 'erased', evidence: `bq DELETE exited 0 under ${BQ_MAX_BYTES_BILLED}-byte cap` }
    : { status: 'failed', evidence: `bq failed: ${result.err || result.out.slice(0, 400)}` };
}

/** libpq env mapping keeps DATABASE_URL (password, %-encoded) OUT of argv. */
function pgEnv(): Record<string, string> | null {
  const raw = process.env.DATABASE_URL;
  if (!raw) return null;
  try {
    const url = new URL(raw);
    return {
      PGHOST: url.hostname,
      PGPORT: url.port || '5432',
      PGDATABASE: decodeURIComponent(url.pathname.replace(/^\//, '')),
      PGUSER: decodeURIComponent(url.username),
      PGPASSWORD: decodeURIComponent(url.password),
    };
  } catch {
    return null;
  }
}

/** psql returning a single JSON cell (row_to_json / json_agg). Throws on
 *  any psql failure — persistence errors must fail the run, not degrade it. */
function psqlJson<T>(sql: string): T {
  const env = pgEnv();
  if (!env) throw new Error('DATABASE_URL missing or unparseable');
  const result = runCli('psql', ['-X', '-v', 'ON_ERROR_STOP=1', '-t', '-A', '-c', sql], env);
  if (!result.ok) throw new Error(`psql failed: ${result.err || result.out.slice(0, 400)}`);
  const cell = result.out.trim();
  if (!cell) throw new Error(`psql returned no cell for: ${sql.slice(0, 120)}`);
  return JSON.parse(cell) as T;
}

/** psql write with no result parsing — throws on any failure so a live run
 *  that cannot persist the authoritative rows fails loudly. */
function psqlExec(sql: string): void {
  const env = pgEnv();
  if (!env) throw new Error('DATABASE_URL missing or unparseable');
  const result = runCli('psql', ['-X', '-v', 'ON_ERROR_STOP=1', '-q', '-c', sql], env);
  if (!result.ok) throw new Error(`psql failed: ${result.err || result.out.slice(0, 400)}`);
}

function runPsql(sql: string): { status: ErasureStatus; evidence: string } {
  const env = pgEnv();
  if (!env) return { status: 'failed', evidence: 'DATABASE_URL missing or unparseable' };
  const result = runCli('psql', ['-X', '-v', 'ON_ERROR_STOP=1', '-c', sql], env);
  return result.ok
    ? { status: 'erased', evidence: `psql statement committed: ${sql.slice(0, 300)}` }
    : { status: 'failed', evidence: `psql failed: ${result.err || result.out.slice(0, 400)}` };
}

async function postJson(
  urlEnv: string,
  body: Record<string, unknown>,
  tokenEnv?: string,
): Promise<{ status: ErasureStatus; evidence: string }> {
  const url = process.env[urlEnv];
  if (!url) return { status: 'failed', evidence: `${urlEnv} not set` };
  const headers: Record<string, string> = { 'content-type': 'application/json' };
  if (tokenEnv && process.env[tokenEnv]) headers.authorization = `Bearer ${process.env[tokenEnv]}`;
  try {
    const response = await fetch(url, { method: 'POST', headers, body: JSON.stringify(body) });
    const text = (await response.text()).slice(0, 300);
    return response.ok
      ? { status: 'suppressed', evidence: `${urlEnv} responded ${response.status}: ${text}` }
      : { status: 'failed', evidence: `${urlEnv} responded ${response.status}: ${text}` };
  } catch (error) {
    return {
      status: 'failed',
      evidence: `${urlEnv} request failed: ${error instanceof Error ? error.message : String(error)}`,
    };
  }
}

interface PlanContext {
  requestId: string;
  purposes: ConsentPurpose[];
}

interface Plan {
  label: string;
  /** Env names --live requires before anything is touched. */
  requiredEnv: string[];
  /** Nothing to execute (view / aggregate / no token). */
  skip?: { status: ErasureStatus; evidence: string };
  execute?: () => Promise<{ status: ErasureStatus; evidence: string }>;
}

function anchorOf(subject: SubjectRef): string {
  const anchor = subject.uidHash ?? subject.anonId;
  if (!anchor || !SAFE_IDENT.test(anchor)) {
    throw new Error('subject anchor missing or contains unsafe characters — refusing SQL');
  }
  return anchor;
}

function planFor(targetId: string, subject: SubjectRef, ctx: PlanContext): Plan {
  switch (targetId) {
    case 'signals_raw':
      return {
        label: 'signals_raw hard DELETE (events + quarantined) by uid_hash/anon_id',
        requiredEnv: missingEnv('SIGNALS_BQ_PROJECT'),
        execute: async () => {
          const h = anchorOf(subject);
          const events = runBq(
            `DELETE FROM \`${process.env.SIGNALS_BQ_PROJECT}.signals_raw.events\` WHERE uid_hash = '${h}' OR anon_id = '${h}'`,
          );
          if (events.status === 'failed') return events;
          const quarantined = runBq(
            `DELETE FROM \`${process.env.SIGNALS_BQ_PROJECT}.signals_raw.quarantined\` WHERE uid_hash = '${h}' OR anon_id = '${h}'`,
          );
          return quarantined.status === 'failed'
            ? quarantined
            : { status: 'erased', evidence: `${events.evidence} | ${quarantined.evidence}` };
        },
      };
    case 'signals_stg':
      return {
        label: 'signals_stg.events — view over signals_raw',
        requiredEnv: [],
        skip: {
          status: 'not_applicable',
          evidence: 'view (stg_events.sql) stores no rows — erased by the signals_raw hard delete',
        },
      };
    case 'signals_mart':
      return {
        label: 'signals_mart.* — views over signals_raw',
        requiredEnv: [],
        skip: {
          status: 'not_applicable',
          evidence: 'mart models are views — no stored copy; re-projection after raw erasure removes subject rows',
        },
      };
    case 'signals_reconciliation':
      return {
        label: 'signals_reconciliation nightly/parity rows',
        requiredEnv: [],
        skip: {
          status: 'not_applicable',
          evidence: 'nightly_checks / level2_parity hold aggregate check results with no subject column',
        },
      };
    case 'ga4_user_deletion':
      return {
        label: 'GA4 Data Deletion API (properties.deleteUserdata)',
        requiredEnv: missingEnv('GA4_PROPERTY_ID', 'GA4_ACCESS_TOKEN'),
        execute: async () => {
          const value = subject.uidHash ?? subject.anonId;
          if (!value) return { status: 'failed', evidence: 'GA4 deletion needs uidHash or anonId' };
          try {
            const response = await fetch(
              `https://analyticsadmin.googleapis.com/v1beta/properties/${process.env.GA4_PROPERTY_ID}:deleteUserdata`,
              {
                method: 'POST',
                headers: {
                  authorization: `Bearer ${process.env.GA4_ACCESS_TOKEN}`,
                  'content-type': 'application/json',
                },
                body: JSON.stringify({
                  uuid: { value, type: subject.uidHash ? 'USER_ID' : 'APP_INSTANCE_ID' },
                }),
              },
            );
            const text = (await response.text()).slice(0, 300);
            return response.ok
              ? { status: 'erased', evidence: `GA4 deleteUserdata ${response.status}: ${text}` }
              : { status: 'failed', evidence: `GA4 deleteUserdata ${response.status}: ${text}` };
          } catch (error) {
            return {
              status: 'failed',
              evidence: `GA4 deleteUserdata failed: ${error instanceof Error ? error.message : String(error)}`,
            };
          }
        },
      };
    case 'identity_mapping':
      return {
        label: 'Postgres identity-mapping purge',
        requiredEnv: missingEnv('DATABASE_URL'),
        execute: async () => {
          const h = anchorOf(subject);
          return runPsql(`DELETE FROM identity_mapping WHERE uid_hash = '${h}' OR anon_id = '${h}';`);
        },
      };
    case 'cached_projections':
      return {
        label: 'cached-projection purge endpoint',
        requiredEnv: missingEnv('SIGNALS_CACHE_PURGE_URL'),
        execute: async () =>
          postJson('SIGNALS_CACHE_PURGE_URL', { subjectHash: subject.uidHash ?? subject.anonId }),
      };
    case 'fcm_audience':
      if (!subject.fcmToken) {
        return {
          label: 'FCM/audience removal',
          requiredEnv: [],
          skip: { status: 'not_applicable', evidence: 'subject has no fcmToken — nothing to remove' },
        };
      }
      return {
        label: 'FCM/audience removal',
        requiredEnv: missingEnv('SIGNALS_AUDIENCE_URL'),
        execute: async () =>
          postJson(
            'SIGNALS_AUDIENCE_URL',
            { fcmToken: subject.fcmToken, subjectHash: subject.uidHash ?? subject.anonId },
            'SIGNALS_AUDIENCE_TOKEN',
          ),
      };
    case 'fanout_block':
      return {
        label: 'fan-out suppression: verify consent-ledger withdrawal rows',
        requiredEnv: missingEnv('DATABASE_URL'),
        execute: async () => {
          // The workflow records the withdrawal BEFORE targets run — this
          // target proves the authoritative row is present for every
          // requested purpose (the guard every fan-out boundary consults).
          const h = anchorOf(subject);
          const wanted = ctx.purposes.map((p) => `'${p}'`).join(', ');
          const rows = psqlJson<Array<{ purpose: string }>>(
            `SELECT COALESCE(json_agg(purpose), '[]'::json) FROM consent_withdrawals WHERE subject_hash = '${h}' AND request_id = '${sqlLit(ctx.requestId)}' AND purpose IN (${wanted});`,
          );
          const found = new Set(rows.map((row) => row.purpose));
          const absent = ctx.purposes.filter((purpose) => !found.has(purpose));
          return absent.length === 0
            ? {
                status: 'blocked',
                evidence: `ledger rows present for ${ctx.purposes.join(', ')} — fan-out guard refuses further sends`,
              }
            : { status: 'failed', evidence: `ledger missing withdrawal rows for: ${absent.join(', ')}` };
        },
      };
    default:
      throw new Error(`unknown erasure target ${targetId}`);
  }
}

function targetFor(targetId: string, subject: SubjectRef, ctx: PlanContext, dryRun: boolean): ErasureTarget {
  const plan = planFor(targetId, subject, ctx);
  return {
    id: targetId,
    execute: async () => {
      if (plan.skip) return { status: plan.skip.status, evidence: plan.skip.evidence };
      if (dryRun) return { status: 'planned', evidence: `[dry-run] ${plan.label}` };
      if (!plan.execute) return { status: 'failed', evidence: 'plan has no execute step' };
      return plan.execute();
    },
  };
}

// ---------------------------------------------------------------------------
// Live-mode persistence: the authoritative Postgres tables from migration
// 2026_10_14_000001. Dry-run uses in-memory stores (zero side effects).
// ---------------------------------------------------------------------------

function isoToMs(value: string | null): number | null {
  return value === null ? null : Date.parse(value);
}

class PsqlErasureTokenStore implements ErasureTokenStore {
  async record(token: ErasureToken): Promise<void> {
    psqlExec(
      `INSERT INTO erasure_tokens (subject_hash, scope, erased_at, request_id)
       VALUES ('${sqlLit(token.subjectHash)}', '${token.scope}', to_timestamp(${token.erasedAtMs} / 1000.0), '${sqlLit(token.requestId)}')
       ON CONFLICT (subject_hash) DO UPDATE SET scope = 'full', erased_at = EXCLUDED.erased_at, request_id = EXCLUDED.request_id
       WHERE erasure_tokens.scope = 'fanout_only' AND EXCLUDED.scope = 'full';`,
    );
  }

  async get(subjectHash: string): Promise<ErasureToken | null> {
    const row = psqlJson<{ subject_hash: string; scope: string; erased_at: string; request_id: string } | null>(
      `SELECT COALESCE((SELECT row_to_json(t) FROM (SELECT subject_hash, scope, erased_at, request_id FROM erasure_tokens WHERE subject_hash = '${sqlLit(subjectHash)}') t), 'null');`,
    );
    if (!row) return null;
    return {
      subjectHash: row.subject_hash,
      scope: row.scope as ErasureToken['scope'],
      erasedAtMs: Date.parse(row.erased_at),
      requestId: row.request_id,
    };
  }

  async filterErased(subjectHashes: readonly string[]): Promise<Set<string>> {
    const listed = subjectHashes.filter((hash) => SAFE_IDENT.test(hash));
    if (listed.length !== subjectHashes.length) {
      throw new Error('erasure lookup contains unsafe identifiers — refusing');
    }
    if (listed.length === 0) return new Set();
    const inList = listed.map((hash) => `'${hash}'`).join(', ');
    const rows = psqlJson<Array<{ subject_hash: string }>>(
      `SELECT COALESCE(json_agg(subject_hash), '[]'::json) FROM erasure_tokens WHERE subject_hash IN (${inList});`,
    );
    return new Set(rows.map((row) => row.subject_hash));
  }
}

class PsqlConsentLedger implements ConsentLedgerStore {
  async withdraw(withdrawal: ConsentWithdrawal): Promise<void> {
    psqlExec(
      `INSERT INTO consent_withdrawals (subject_hash, purpose, withdrawn_at, request_id)
       VALUES ('${sqlLit(withdrawal.subjectHash)}', '${withdrawal.purpose}', to_timestamp(${withdrawal.withdrawnAtMs} / 1000.0), '${sqlLit(withdrawal.requestId)}')
       ON CONFLICT DO NOTHING;`,
    );
  }

  async get(subjectHash: string, purpose: ConsentPurpose): Promise<ConsentWithdrawal | null> {
    const row = psqlJson<{
      subject_hash: string;
      purpose: string;
      withdrawn_at: string;
      request_id: string;
    } | null>(
      `SELECT COALESCE((SELECT row_to_json(t) FROM (SELECT subject_hash, purpose, withdrawn_at, request_id FROM consent_withdrawals WHERE subject_hash = '${sqlLit(subjectHash)}' AND purpose = '${purpose}') t), 'null');`,
    );
    if (!row) return null;
    return {
      subjectHash: row.subject_hash,
      purpose: row.purpose as ConsentPurpose,
      withdrawnAtMs: Date.parse(row.withdrawn_at),
      requestId: row.request_id,
    };
  }

  async list(subjectHash: string): Promise<ConsentWithdrawal[]> {
    const rows = psqlJson<Array<{ subject_hash: string; purpose: string; withdrawn_at: string; request_id: string }>>(
      `SELECT COALESCE(json_agg(t), '[]'::json) FROM (SELECT subject_hash, purpose, withdrawn_at, request_id FROM consent_withdrawals WHERE subject_hash = '${sqlLit(subjectHash)}') t;`,
    );
    return rows.map((row) => ({
      subjectHash: row.subject_hash,
      purpose: row.purpose as ConsentPurpose,
      withdrawnAtMs: Date.parse(row.withdrawn_at),
      requestId: row.request_id,
    }));
  }
}

class PsqlDeletionAuditStore implements DeletionAuditStore {
  async put(record: DeletionAuditRecord): Promise<void> {
    psqlExec(
      `INSERT INTO deletion_audit (request_id, subject_hash, reason, mode, requested_at, deadline_at, completed_at, status, proofs)
       VALUES (
         '${sqlLit(record.requestId)}', '${sqlLit(record.subjectHash)}', '${record.reason}', '${record.mode}',
         to_timestamp(${record.requestedAtMs} / 1000.0), to_timestamp(${record.deadlineMs} / 1000.0),
         ${record.completedAtMs === null ? 'NULL' : `to_timestamp(${record.completedAtMs} / 1000.0)`},
         '${record.status}', '${sqlLit(JSON.stringify(record.proofs))}'::jsonb
       )
       ON CONFLICT (request_id) DO UPDATE SET
         status = EXCLUDED.status, completed_at = EXCLUDED.completed_at, proofs = EXCLUDED.proofs, mode = EXCLUDED.mode;`,
    );
  }

  async get(requestId: string): Promise<DeletionAuditRecord | null> {
    const row = psqlJson<{
      request_id: string;
      subject_hash: string;
      reason: string;
      mode: string;
      requested_at: string;
      deadline_at: string;
      completed_at: string | null;
      status: string;
      proofs: DeletionAuditRecord['proofs'];
    } | null>(
      `SELECT COALESCE((SELECT row_to_json(t) FROM (SELECT request_id, subject_hash, reason, mode, requested_at, deadline_at, completed_at, status, proofs FROM deletion_audit WHERE request_id = '${sqlLit(requestId)}') t), 'null');`,
    );
    if (!row) return null;
    return {
      requestId: row.request_id,
      subjectHash: row.subject_hash,
      reason: row.reason as DeletionAuditRecord['reason'],
      mode: row.mode as DeletionAuditRecord['mode'],
      requestedAtMs: isoToMs(row.requested_at)!,
      deadlineMs: isoToMs(row.deadline_at)!,
      completedAtMs: isoToMs(row.completed_at),
      status: row.status as DeletionAuditRecord['status'],
      proofs: row.proofs,
    };
  }
}

function main(): void {
  const requestFile = arg('request');
  if (!requestFile) {
    console.error('Usage: privacy-erasure.ts --request <request.json> [--live]');
    process.exit(1);
  }
  const dryRun = !hasFlag('live');
  const parsed = readJson<Partial<DeletionRequest>>(requestFile);

  if (!parsed.requestId) {
    console.error('requestId is required — explicit ids are how the 30-day SLA is tracked');
    process.exit(1);
  }
  if (parsed.reason !== 'account_deletion' && parsed.reason !== 'consent_withdrawal') {
    console.error(`reason must be account_deletion | consent_withdrawal, got: ${String(parsed.reason)}`);
    process.exit(1);
  }
  const subject: SubjectRef = {
    uidHash: parsed.subject?.uidHash ?? null,
    anonId: parsed.subject?.anonId ?? null,
    fcmToken: parsed.subject?.fcmToken ?? null,
  };
  if (!subject.uidHash && !subject.anonId) {
    console.error('subject needs at least uidHash or anonId (the pseudonymous anchor)');
    process.exit(1);
  }

  const purposes: ConsentPurpose[] =
    parsed.reason === 'consent_withdrawal'
      ? (parsed.purposes ?? ['analytics', 'ads'])
      : ['analytics', 'ads'];
  const request: DeletionRequest = {
    requestId: parsed.requestId,
    subject,
    reason: parsed.reason,
    requestedAtMs: parsed.requestedAtMs ?? Date.now(),
    purposes,
    mode: dryRun ? 'dry_run' : 'execute',
  };
  const ctx: PlanContext = { requestId: request.requestId, purposes };

  const required =
    request.reason === 'account_deletion' ? REQUIRED_ACCOUNT_DELETION_TARGETS : REQUIRED_WITHDRAWAL_TARGETS;

  // Fail-closed pre-flight: in --live mode every credential the target set
  // needs is verified BEFORE any system is touched.
  if (!dryRun) {
    const missing = new Map<string, string[]>();
    for (const targetId of required) {
      for (const name of planFor(targetId, subject, ctx).requiredEnv) {
        missing.set(name, [...(missing.get(name) ?? []), targetId]);
      }
    }
    if (missing.size > 0) {
      console.error('--live refuses to run: missing credentials (no partial erasure):');
      for (const [name, targets] of missing) console.error(`  ${name} (needed by ${targets.join(', ')})`);
      process.exit(1);
    }
    try {
      anchorOf(subject);
    } catch (error) {
      console.error(error instanceof Error ? error.message : String(error));
      process.exit(1);
    }
  }

  const workflow = new DeletionWorkflow({
    targets: required.map((targetId) => targetFor(targetId, subject, ctx, dryRun)),
    audit: dryRun ? new MemoryDeletionAuditStore() : new PsqlDeletionAuditStore(),
    erasureTokens: dryRun ? new MemoryErasureTokenStore() : new PsqlErasureTokenStore(),
    ledger: dryRun ? new MemoryConsentLedger() : new PsqlConsentLedger(),
    nowMs: Date.now,
  });

  workflow
    .run(request)
    .then(async (record) => {
      const verified = await workflow.verify(record.requestId);
      console.log(`mode: ${record.mode} — status: ${verified.status}`);
      console.log(`deadline (30-day SLA): ${new Date(record.deadlineMs).toISOString()}`);
      console.log('| target | status | evidence |');
      console.log('| --- | --- | --- |');
      for (const proof of record.proofs) {
        console.log(`| ${proof.target} | ${proof.status} | ${proof.evidence.replace(/\|/g, '\\|')} |`);
      }
      if (verified.status === 'dry_run') {
        console.log('\nprivacy-erasure: DRY-RUN only — nothing was erased. Re-run with --live to execute.');
        process.exit(0);
      }
      if (verified.status === 'complete') {
        console.log(`\nprivacy-erasure: all ${record.proofs.length} systems evidenced complete`);
        process.exit(0);
      }
      const failed = verified.proofs.filter((proof) => proof.status === 'failed');
      console.error(
        `\nprivacy-erasure: NOT complete (${verified.status}); failed: ${failed.map((p) => p.target).join(', ') || 'none'}`,
      );
      process.exit(1);
    })
    .catch((error: unknown) => {
      console.error(`privacy-erasure: ${error instanceof Error ? error.message : String(error)}`);
      process.exit(1);
    });
}

main();

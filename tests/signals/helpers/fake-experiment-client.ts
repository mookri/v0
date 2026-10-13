// Statement-level fake for the B8 experiment ledger path. It understands
// EXACTLY the SQL shapes PostgresExperimentLedgerStore issues (same posture
// as fake-rule-actions-client): insert-on-conflict (first-write-wins) and
// the four select shapes. If the real SQL drifts, the shape assertions in
// the tests fail here first. Anything unrecognized throws.

import type { DbClient } from '@infra/database/postgres/client';

interface AssignmentRow {
  experiment_id: string;
  subject_id_hash: string;
  variant: string;
  allocation_version: number;
  assigned_at_ms: number;
}

interface ExposureRow {
  experiment_id: string;
  subject_id_hash: string;
  variant: string;
  exposed_at_ms: number;
}

export class FakeExperimentClient implements DbClient {
  readonly assignments = new Map<string, AssignmentRow>();
  readonly exposures = new Map<string, ExposureRow>();

  private key(experimentId: string, subjectIdHash: string): string {
    return `${experimentId}::${subjectIdHash}`;
  }

  async query<T>(text: string, params: unknown[] = []): Promise<T[]> {
    const sql = text.replace(/\s+/g, ' ').trim();

    if (sql.startsWith('INSERT INTO experiment_assignments')) {
      const [experimentId, subjectIdHash, variant, allocationVersion, assignedAtMs] = params as [
        string,
        string,
        string,
        number,
        number,
      ];
      const key = this.key(experimentId, subjectIdHash);
      if (this.assignments.has(key)) return [] as T[]; // ON CONFLICT DO NOTHING
      this.assignments.set(key, {
        experiment_id: experimentId,
        subject_id_hash: subjectIdHash,
        variant,
        allocation_version: allocationVersion,
        assigned_at_ms: assignedAtMs,
      });
      return [{ experiment_id: experimentId }] as T[];
    }

    if (sql.startsWith('INSERT INTO experiment_exposures')) {
      const [experimentId, subjectIdHash, variant, exposedAtMs] = params as [
        string,
        string,
        string,
        number,
      ];
      const key = this.key(experimentId, subjectIdHash);
      if (this.exposures.has(key)) return [] as T[];
      this.exposures.set(key, {
        experiment_id: experimentId,
        subject_id_hash: subjectIdHash,
        variant,
        exposed_at_ms: exposedAtMs,
      });
      return [{ experiment_id: experimentId }] as T[];
    }

    if (sql.startsWith('SELECT') && sql.includes('FROM experiment_assignments WHERE experiment_id = $1 AND subject_id_hash = $2')) {
      const row = this.assignments.get(this.key(params[0] as string, params[1] as string));
      return (row ? [row] : []) as T[];
    }

    if (sql.startsWith('SELECT') && sql.includes('FROM experiment_assignments WHERE experiment_id = $1')) {
      const experimentId = params[0] as string;
      const rows = [...this.assignments.values()]
        .filter((row) => row.experiment_id === experimentId)
        .sort((a, b) => a.subject_id_hash.localeCompare(b.subject_id_hash));
      return rows as T[];
    }

    if (sql.startsWith('SELECT') && sql.includes('FROM experiment_exposures WHERE experiment_id = $1')) {
      const experimentId = params[0] as string;
      const rows = [...this.exposures.values()]
        .filter((row) => row.experiment_id === experimentId)
        .sort((a, b) => a.subject_id_hash.localeCompare(b.subject_id_hash));
      return rows as T[];
    }

    throw new Error(`FakeExperimentClient: unrecognized SQL shape: ${sql.slice(0, 120)}`);
  }
}

// Postgres ExperimentLedgerStore (plan B8): production persistence for the
// assignment ledger, implementing the same contract as the in-memory
// reference. Statement-level tested against a fake DbClient
// (tests/signals/postgres-experiment-ledger.test.ts) — the B2 outbox
// posture: no database credential in this repo, driver wiring lands with
// DATABASE_URL.

import type {
  ExperimentAssignment,
  ExperimentExposure,
  ExperimentLedgerStore,
} from '@navdhya/analytics/src/experiments/ledger';
import type { DbClient } from '@infra/database/postgres/client';

interface AssignmentRow {
  experiment_id: string;
  subject_id_hash: string;
  variant: string;
  allocation_version: number;
  assigned_at_ms: string | number;
}

interface ExposureRow {
  experiment_id: string;
  subject_id_hash: string;
  variant: string;
  exposed_at_ms: string | number;
}

const ASSIGNMENT_COLUMNS = `experiment_id, subject_id_hash, variant,
       allocation_version, assigned_at_ms`;
const EXPOSURE_COLUMNS = `experiment_id, subject_id_hash, variant, exposed_at_ms`;

function mapAssignment(row: AssignmentRow): ExperimentAssignment {
  return {
    experimentId: row.experiment_id,
    subjectIdHash: row.subject_id_hash,
    variant: row.variant,
    allocationVersion: row.allocation_version,
    assignedAtMs: Number(row.assigned_at_ms),
  };
}

function mapExposure(row: ExposureRow): ExperimentExposure {
  return {
    experimentId: row.experiment_id,
    subjectIdHash: row.subject_id_hash,
    variant: row.variant,
    exposedAtMs: Number(row.exposed_at_ms),
  };
}

export class PostgresExperimentLedgerStore implements ExperimentLedgerStore {
  constructor(private readonly db: DbClient) {}

  async getAssignment(experimentId: string, subjectIdHash: string): Promise<ExperimentAssignment | null> {
    const rows = await this.db.query<AssignmentRow>(
      `SELECT ${ASSIGNMENT_COLUMNS}
       FROM experiment_assignments
       WHERE experiment_id = $1 AND subject_id_hash = $2`,
      [experimentId, subjectIdHash],
    );
    return rows.length > 0 ? mapAssignment(rows[0]!) : null;
  }

  async putAssignment(assignment: ExperimentAssignment): Promise<'stored' | 'already_exists'> {
    const rows = await this.db.query(
      `INSERT INTO experiment_assignments (${ASSIGNMENT_COLUMNS})
       VALUES ($1, $2, $3, $4, $5)
       ON CONFLICT (experiment_id, subject_id_hash) DO NOTHING
       RETURNING experiment_id`,
      [
        assignment.experimentId,
        assignment.subjectIdHash,
        assignment.variant,
        assignment.allocationVersion,
        assignment.assignedAtMs,
      ],
    );
    return rows.length > 0 ? 'stored' : 'already_exists';
  }

  async listAssignments(experimentId: string): Promise<ExperimentAssignment[]> {
    const rows = await this.db.query<AssignmentRow>(
      `SELECT ${ASSIGNMENT_COLUMNS}
       FROM experiment_assignments
       WHERE experiment_id = $1
       ORDER BY subject_id_hash ASC`,
      [experimentId],
    );
    return rows.map(mapAssignment);
  }

  async recordExposure(exposure: ExperimentExposure): Promise<'stored' | 'already_exists'> {
    const rows = await this.db.query(
      `INSERT INTO experiment_exposures (${EXPOSURE_COLUMNS})
       VALUES ($1, $2, $3, $4)
       ON CONFLICT (experiment_id, subject_id_hash) DO NOTHING
       RETURNING experiment_id`,
      [exposure.experimentId, exposure.subjectIdHash, exposure.variant, exposure.exposedAtMs],
    );
    return rows.length > 0 ? 'stored' : 'already_exists';
  }

  async listExposures(experimentId: string): Promise<ExperimentExposure[]> {
    const rows = await this.db.query<ExposureRow>(
      `SELECT ${EXPOSURE_COLUMNS}
       FROM experiment_exposures
       WHERE experiment_id = $1
       ORDER BY subject_id_hash ASC`,
      [experimentId],
    );
    return rows.map(mapExposure);
  }
}

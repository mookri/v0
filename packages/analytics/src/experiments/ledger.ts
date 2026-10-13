// Experiment ledger (plan B8 bullet 1): assignment AUTHORITY in storage.
// Remote Config delivers configuration; this ledger answers "which variant
// is this subject in, under which allocation_version, since when".
// Exposures are a SEPARATE record and are written only when the experience
// is actually displayed (service.logExposure) — an assignment alone must
// never produce an exposure row (acceptance item 1).

export interface ExperimentAssignment {
  experimentId: string;
  /** HMAC pseudonym — the ledger never holds raw user ids (B7 posture). */
  subjectIdHash: string;
  variant: string;
  allocationVersion: number;
  assignedAtMs: number;
}

export interface ExperimentExposure {
  experimentId: string;
  subjectIdHash: string;
  variant: string;
  exposedAtMs: number;
}

export interface ExperimentLedgerStore {
  /** null when the subject has no assignment yet. */
  getAssignment(experimentId: string, subjectIdHash: string): Promise<ExperimentAssignment | null>;
  /** First-write-wins: an existing assignment is never overwritten (the
   *  ledger's authority depends on assignments being immutable facts). */
  putAssignment(assignment: ExperimentAssignment): Promise<'stored' | 'already_exists'>;
  listAssignments(experimentId: string): Promise<ExperimentAssignment[]>;
  /** First-write-wins per (experiment, subject): re-displaying the
   *  experience does not duplicate the exposure. */
  recordExposure(exposure: ExperimentExposure): Promise<'stored' | 'already_exists'>;
  listExposures(experimentId: string): Promise<ExperimentExposure[]>;
}

export class MemoryExperimentLedger implements ExperimentLedgerStore {
  private readonly assignments = new Map<string, ExperimentAssignment>();
  private readonly exposures = new Map<string, ExperimentExposure>();

  private assignmentKey(experimentId: string, subjectIdHash: string): string {
    return `${experimentId}::${subjectIdHash}`;
  }

  async getAssignment(experimentId: string, subjectIdHash: string): Promise<ExperimentAssignment | null> {
    return this.assignments.get(this.assignmentKey(experimentId, subjectIdHash)) ?? null;
  }

  async putAssignment(assignment: ExperimentAssignment): Promise<'stored' | 'already_exists'> {
    const key = this.assignmentKey(assignment.experimentId, assignment.subjectIdHash);
    if (this.assignments.has(key)) return 'already_exists';
    this.assignments.set(key, { ...assignment });
    return 'stored';
  }

  async listAssignments(experimentId: string): Promise<ExperimentAssignment[]> {
    return [...this.assignments.values()]
      .filter((row) => row.experimentId === experimentId)
      .sort((a, b) => a.subjectIdHash.localeCompare(b.subjectIdHash));
  }

  async recordExposure(exposure: ExperimentExposure): Promise<'stored' | 'already_exists'> {
    const key = this.assignmentKey(exposure.experimentId, exposure.subjectIdHash);
    if (this.exposures.has(key)) return 'already_exists';
    this.exposures.set(key, { ...exposure });
    return 'stored';
  }

  async listExposures(experimentId: string): Promise<ExperimentExposure[]> {
    return [...this.exposures.values()]
      .filter((row) => row.experimentId === experimentId)
      .sort((a, b) => a.subjectIdHash.localeCompare(b.subjectIdHash));
  }
}

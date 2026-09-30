export interface ExperimentAssignment {
  experimentId: string;
  variant: string;
}

export function assignmentsRecord(list: ExperimentAssignment[]): Record<string, string> {
  const out: Record<string, string> = {};
  for (const a of list) out[a.experimentId] = a.variant;
  return out;
}

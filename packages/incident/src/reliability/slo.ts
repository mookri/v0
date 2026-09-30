export interface ServiceLevelIndicator {
  id: string;
  serviceId: string;
  name: string;
  unit: string;
  window: string;
}

export interface ServiceLevelObjective {
  id: string;
  sliId: string;
  target: number;
  window: string;
  policyVersion: string;
}

export function errorBudgetRemaining(slo: ServiceLevelObjective, actual: number): number {
  return slo.target - actual;
}

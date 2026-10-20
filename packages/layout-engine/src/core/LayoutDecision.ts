export interface RegionDecision {
  id: string;
}

export interface LayoutDecision {
  shell: string;
  layout: string;
  regions: RegionDecision[];
  variant?: string;
  policyId: string;
  policyVersion: string;
}

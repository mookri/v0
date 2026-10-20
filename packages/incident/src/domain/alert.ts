export interface AlertFingerprint {
  source: string;
  ruleId: string;
  resource: string;
  environment: string;
}

export function fingerprintKey(f: AlertFingerprint): string {
  return `${f.source}:${f.ruleId}:${f.resource}:${f.environment}`;
}

export interface Alert {
  id: string;
  fingerprint: AlertFingerprint;
  severity: string;
  status: 'OPEN' | 'ACKNOWLEDGED' | 'RESOLVED';
  detectedAt: string;
  incidentId?: string;
}

export function groupAlerts(alerts: Alert[]): Map<string, Alert[]> {
  const groups = new Map<string, Alert[]>();
  for (const alert of alerts) {
    const key = fingerprintKey(alert.fingerprint);
    const list = groups.get(key) ?? [];
    list.push(alert);
    groups.set(key, list);
  }
  return groups;
}

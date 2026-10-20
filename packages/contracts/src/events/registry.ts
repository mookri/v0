// Event registry (§21): every integration/business event is registered with
// producer + version before any second consumer exists. Additive-only within
// a major version: consumers declare minVersion, producers never rename or
// remove fields without a major bump.

export interface EventRegistration {
  eventType: string;
  version: number;
  producer: string;
  // Hash of the canonical schema (kept alongside the event contract).
  schemaHash: string;
  consumers: string[];
}

const registry = new Map<string, EventRegistration[]>();

export function registerEvent(reg: EventRegistration): void {
  if (!reg.eventType || reg.version < 1 || !reg.producer || !reg.schemaHash) {
    throw new Error('EVENT_REGISTRATION_INVALID');
  }
  const versions = registry.get(reg.eventType) ?? [];
  if (versions.some((v) => v.version === reg.version)) throw new Error('EVENT_VERSION_CONFLICT');
  registry.set(reg.eventType, [...versions, reg].sort((a, b) => a.version - b.version));
}

export function getEventVersions(eventType: string): EventRegistration[] {
  return [...(registry.get(eventType) ?? [])];
}

export function latestEventVersion(eventType: string): EventRegistration | null {
  const versions = registry.get(eventType) ?? [];
  return versions.length > 0 ? versions[versions.length - 1]! : null;
}

export function assertConsumerCompatible(eventType: string, minVersion: number): void {
  const latest = latestEventVersion(eventType);
  if (!latest) throw new Error(`EVENT_UNREGISTERED:${eventType}`);
  if (latest.version < minVersion) throw new Error(`EVENT_VERSION_TOO_OLD:${eventType}`);
}

export function __clearRegistryForTests(): void {
  registry.clear();
}

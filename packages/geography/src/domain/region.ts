export type RegionType = 'COUNTRY' | 'STATE' | 'DISTRICT' | 'CITY' | 'METRO' | 'SUB_REGION' | 'CUSTOM';

export interface Region {
  id: string;
  type: RegionType;
  name: string;
  code?: string;
  parentId?: string;
  status: 'ACTIVE' | 'INACTIVE';
}

export interface RegionAlias {
  alias: string;
  locale?: string;
  regionId: string;
  source: string;
}

export function canonicalRegionId(aliases: RegionAlias[], name: string, locale?: string): string | null {
  const key = name.trim().toLowerCase();
  const exact = aliases.find((a) => a.alias.toLowerCase() === key && (a.locale ?? locale) === (locale ?? a.locale));
  if (exact) return exact.regionId;
  return aliases.find((a) => a.alias.toLowerCase() === key)?.regionId ?? null;
}

export function ancestors(regions: Region[], regionId: string): Region[] {
  const byId = new Map(regions.map((r) => [r.id, r]));
  const out: Region[] = [];
  let parentId = byId.get(regionId)?.parentId;
  while (parentId) {
    const parent = byId.get(parentId);
    if (!parent) break;
    out.push(parent);
    parentId = parent.parentId;
  }
  return out;
}

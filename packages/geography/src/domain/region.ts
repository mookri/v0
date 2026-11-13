export type RegionType = 'COUNTRY' | 'STATE' | 'DISTRICT' | 'CITY' | 'METRO' | 'SUB_REGION' | 'CUSTOM';

export interface Region {
  id: string;
  type: RegionType;
  name: string;
  code?: string;
  parentId?: string;
  status: 'ACTIVE' | 'INACTIVE';
  nameDev?: string;
}

export interface RegionAlias {
  alias: string;
  locale?: string;
  regionId: string;
  source: string;
}

export const CANONICAL_REGIONS: Region[] = [
  { id: 'reg_varanasi', type: 'CITY', name: 'Varanasi', nameDev: 'वाराणसी', status: 'ACTIVE' },
  { id: 'reg_haridwar', type: 'CITY', name: 'Haridwar', nameDev: 'हरिद्वार', status: 'ACTIVE' },
  { id: 'reg_rishikesh', type: 'CITY', name: 'Rishikesh', nameDev: 'ऋषिकेश', status: 'ACTIVE' },
  { id: 'reg_ayodhya', type: 'CITY', name: 'Ayodhya', nameDev: 'अयोध्या', status: 'ACTIVE' },
  { id: 'reg_ujjain', type: 'CITY', name: 'Ujjain', nameDev: 'उज्जैन', status: 'ACTIVE' },
  { id: 'reg_mathura', type: 'CITY', name: 'Mathura', nameDev: 'मथुरा', status: 'ACTIVE' },
  { id: 'reg_vrindavan', type: 'CITY', name: 'Vrindavan', nameDev: 'वृन्दावन', status: 'ACTIVE' },
  { id: 'reg_prayagraj', type: 'CITY', name: 'Prayagraj', nameDev: 'प्रयागराज', status: 'ACTIVE' },
  { id: 'reg_bengaluru', type: 'CITY', name: 'Bengaluru', nameDev: 'बेंगलुरु', status: 'ACTIVE' },
  { id: 'reg_mumbai', type: 'CITY', name: 'Mumbai', nameDev: 'मुंबई', status: 'ACTIVE' },
  { id: 'reg_delhi_ncr', type: 'METRO', name: 'Delhi-NCR', nameDev: 'दिल्ली-एनसीआर', status: 'ACTIVE' },
];

export const CANONICAL_ALIASES: RegionAlias[] = [
  { alias: 'Banaras', regionId: 'reg_varanasi', source: 'vernacular' },
  { alias: 'Benares', regionId: 'reg_varanasi', source: 'colonial' },
  { alias: 'Kashi', regionId: 'reg_varanasi', source: 'spiritual' },
  { alias: 'काशी', regionId: 'reg_varanasi', source: 'devanagari' },
  { alias: 'बनारस', regionId: 'reg_varanasi', source: 'devanagari' },
  { alias: 'Hardwar', regionId: 'reg_haridwar', source: 'phonetic' },
  { alias: 'Mayapuri', regionId: 'reg_haridwar', source: 'spiritual' },
  { alias: 'Hrishikesh', regionId: 'reg_rishikesh', source: 'phonetic' },
  { alias: 'Saket', regionId: 'reg_ayodhya', source: 'ancient' },
  { alias: 'साकेत', regionId: 'reg_ayodhya', source: 'devanagari' },
  { alias: 'Avantika', regionId: 'reg_ujjain', source: 'ancient' },
  { alias: 'Braj', regionId: 'reg_mathura', source: 'spiritual' },
  { alias: 'Brindavan', regionId: 'reg_vrindavan', source: 'phonetic' },
  { alias: 'Allahabad', regionId: 'reg_prayagraj', source: 'legacy' },
  { alias: 'इलाहाबाद', regionId: 'reg_prayagraj', source: 'devanagari' },
  { alias: 'Bangalore', regionId: 'reg_bengaluru', source: 'legacy' },
  { alias: 'बैंगलोर', regionId: 'reg_bengaluru', source: 'devanagari' },
  { alias: 'Bombay', regionId: 'reg_mumbai', source: 'legacy' },
  { alias: 'बम्बई', regionId: 'reg_mumbai', source: 'devanagari' },
  { alias: 'Delhi', regionId: 'reg_delhi_ncr', source: 'metro' },
  { alias: 'New Delhi', regionId: 'reg_delhi_ncr', source: 'metro' },
  { alias: 'Gurgaon', regionId: 'reg_delhi_ncr', source: 'metro' },
  { alias: 'Noida', regionId: 'reg_delhi_ncr', source: 'metro' },
];

export function resolveCanonicalRegion(raw: string): Region | null {
  const norm = raw.trim().toLowerCase();
  const direct = CANONICAL_REGIONS.find((r) => r.name.toLowerCase() === norm || r.nameDev?.toLowerCase() === norm);
  if (direct) return direct;

  const id = canonicalRegionId(CANONICAL_ALIASES, raw);
  if (id) {
    return CANONICAL_REGIONS.find((r) => r.id === id) ?? null;
  }
  return null;
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

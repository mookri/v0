const fs = require('fs');
const path = require('path');

const dir = 'packages/puja/src';

const files = {
  'domain/PujaId.ts': `export type PujaId = string;\n`,
  'domain/PujaCategory.ts': `export type PujaCategory = 'FESTIVAL' | 'CEREMONY' | 'DAILY' | 'SPECIAL';\n`,
  'domain/PujaType.ts': `export type PujaType = 'IN_PERSON' | 'ONLINE' | 'REMOTE_SANKALP';\n`,
  'domain/Ritual.ts': `export interface Ritual {\n  id: string;\n  name: string;\n  durationMinutes: number;\n}\n`,
  'domain/Puja.ts': `import { PujaId } from './PujaId';\nimport { PujaCategory } from './PujaCategory';\nimport { PujaType } from './PujaType';\nimport { Ritual } from './Ritual';\n\nexport interface Puja {\n  id: PujaId;\n  name: string;\n  description: string;\n  category: PujaCategory;\n  type: PujaType;\n  rituals: Ritual[];\n  baseDurationMinutes: number;\n  isActive: boolean;\n}\n`,
  'domain/puja-rules.ts': `import { Puja } from './Puja';\n\nexport function isPujaAvailable(puja: Puja): boolean {\n  return puja.isActive;\n}\n`,
  'contracts/PujaSummary.ts': `import { PujaId } from '../domain/PujaId';\nimport { PujaCategory } from '../domain/PujaCategory';\n\nexport interface PujaSummary {\n  id: PujaId;\n  name: string;\n  category: PujaCategory;\n  duration: number;\n}\n`,
  'contracts/PujaDetails.ts': `import { Puja } from '../domain/Puja';\n\nexport interface PujaDetails {\n  puja: Puja;\n}\n`,
  'ports/PujaRepository.ts': `import { Puja } from '../domain/Puja';\nimport { PujaId } from '../domain/PujaId';\nimport { PujaCategory } from '../domain/PujaCategory';\n\nexport interface PujaRepository {\n  findById(id: PujaId): Promise<Puja | null>;\n  findAll(): Promise<Puja[]>;\n  findByCategory(category: PujaCategory): Promise<Puja[]>;\n}\n`,
  'application/get-puja.ts': `import { PujaRepository } from '../ports/PujaRepository';\nimport { PujaId } from '../domain/PujaId';\nimport { Puja } from '../domain/Puja';\n\nexport async function getPuja(repo: PujaRepository, id: PujaId): Promise<Puja | null> {\n  return repo.findById(id);\n}\n`,
  'application/list-pujas.ts': `import { PujaRepository } from '../ports/PujaRepository';\nimport { Puja } from '../domain/Puja';\n\nexport async function listPujas(repo: PujaRepository): Promise<Puja[]> {\n  return repo.findAll();\n}\n`,
  'application/get-puja-categories.ts': `import { PujaCategory } from '../domain/PujaCategory';\n\nexport async function getPujaCategories(): Promise<PujaCategory[]> {\n  return ['FESTIVAL', 'CEREMONY', 'DAILY', 'SPECIAL'];\n}\n`,
  'application/get-puja-details.ts': `import { PujaRepository } from '../ports/PujaRepository';\nimport { PujaId } from '../domain/PujaId';\nimport { PujaDetails } from '../contracts/PujaDetails';\n\nexport async function getPujaDetails(repo: PujaRepository, id: PujaId): Promise<PujaDetails | null> {\n  const puja = await repo.findById(id);\n  return puja ? { puja } : null;\n}\n`,
  'index.ts': `export * from './domain/Puja';\nexport * from './domain/PujaId';\nexport * from './domain/PujaCategory';\nexport * from './domain/PujaType';\nexport * from './domain/Ritual';\nexport * from './domain/puja-rules';\n\nexport * from './contracts/PujaSummary';\nexport * from './contracts/PujaDetails';\n\nexport * from './ports/PujaRepository';\n\nexport * from './application/get-puja';\nexport * from './application/list-pujas';\nexport * from './application/get-puja-categories';\nexport * from './application/get-puja-details';\n`
};

for (const [file, content] of Object.entries(files)) {
  const fullPath = path.join(dir, file);
  fs.writeFileSync(fullPath, content);
}
console.log('Puja capability files generated');

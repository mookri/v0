const fs = require('fs');
const path = require('path');

const dir = 'packages/pandit/src';

const files = {
  'domain/PanditId.ts': `export type PanditId = string;\n`,
  'domain/VerificationStatus.ts': `export type VerificationStatus = 'PENDING' | 'VERIFIED' | 'REJECTED' | 'SUSPENDED';\n`,
  'domain/Expertise.ts': `export interface Expertise {\n  domain: string;\n  yearsOfExperience: number;\n}\n`,
  'domain/PanditProfile.ts': `import { Expertise } from './Expertise';\n\nexport interface PanditProfile {\n  bio: string;\n  languages: string[];\n  expertise: Expertise[];\n  location: string;\n}\n`,
  'domain/Pandit.ts': `import { PanditId } from './PanditId';\nimport { PanditProfile } from './PanditProfile';\nimport { VerificationStatus } from './VerificationStatus';\n\nexport interface Pandit {\n  id: PanditId;\n  userId: string; // Reference to User Capability\n  profile: PanditProfile;\n  status: VerificationStatus;\n  rating: number;\n  totalBookings: number;\n}\n`,
  'domain/pandit-rules.ts': `import { Pandit } from './Pandit';\n\nexport function canAcceptBookings(pandit: Pandit): boolean {\n  return pandit.status === 'VERIFIED';\n}\n`,
  'contracts/PanditSummary.ts': `import { PanditId } from '../domain/PanditId';\nimport { VerificationStatus } from '../domain/VerificationStatus';\n\nexport interface PanditSummary {\n  id: PanditId;\n  name: string; // Joined from User profile via app layer\n  status: VerificationStatus;\n  rating: number;\n  expertise: string[];\n}\n`,
  'contracts/PanditDetails.ts': `import { Pandit } from '../domain/Pandit';\n\nexport interface PanditDetails {\n  pandit: Pandit;\n  // Aggregated data can be here\n}\n`,
  'ports/PanditRepository.ts': `import { Pandit } from '../domain/Pandit';\nimport { PanditId } from '../domain/PanditId';\n\nexport interface PanditRepository {\n  findById(id: PanditId): Promise<Pandit | null>;\n  findByUserId(userId: string): Promise<Pandit | null>;\n  search(query: string): Promise<Pandit[]>;\n  update(pandit: Pandit): Promise<void>;\n}\n`,
  'application/get-pandit.ts': `import { PanditRepository } from '../ports/PanditRepository';\nimport { PanditId } from '../domain/PanditId';\nimport { Pandit } from '../domain/Pandit';\n\nexport async function getPandit(repo: PanditRepository, id: PanditId): Promise<Pandit | null> {\n  return repo.findById(id);\n}\n`,
  'application/search-pandits.ts': `import { PanditRepository } from '../ports/PanditRepository';\nimport { Pandit } from '../domain/Pandit';\n\nexport async function searchPandits(repo: PanditRepository, query: string): Promise<Pandit[]> {\n  return repo.search(query);\n}\n`,
  'application/verify-pandit.ts': `import { PanditRepository } from '../ports/PanditRepository';\nimport { Pandit } from '../domain/Pandit';\n\nexport async function verifyPandit(repo: PanditRepository, pandit: Pandit): Promise<void> {\n  pandit.status = 'VERIFIED';\n  await repo.update(pandit);\n}\n`,
  'application/get-pandit-profile.ts': `import { PanditRepository } from '../ports/PanditRepository';\nimport { PanditId } from '../domain/PanditId';\nimport { PanditDetails } from '../contracts/PanditDetails';\n\nexport async function getPanditProfile(repo: PanditRepository, id: PanditId): Promise<PanditDetails | null> {\n  const pandit = await repo.findById(id);\n  return pandit ? { pandit } : null;\n}\n`,
  'index.ts': `export * from './domain/Pandit';\nexport * from './domain/PanditId';\nexport * from './domain/PanditProfile';\nexport * from './domain/VerificationStatus';\nexport * from './domain/Expertise';\nexport * from './domain/pandit-rules';\n\nexport * from './contracts/PanditSummary';\nexport * from './contracts/PanditDetails';\n\nexport * from './ports/PanditRepository';\n\nexport * from './application/get-pandit';\nexport * from './application/search-pandits';\nexport * from './application/verify-pandit';\nexport * from './application/get-pandit-profile';\n`
};

for (const [file, content] of Object.entries(files)) {
  const fullPath = path.join(dir, file);
  fs.writeFileSync(fullPath, content);
}
console.log('Pandit capability files generated');

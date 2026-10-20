const fs = require('fs');
const path = require('path');

const userDir = 'packages/user/src';

const files = {
  'domain/UserRole.ts': `export type UserRole = 'CUSTOMER' | 'PANDIT' | 'VENDOR' | 'ADMIN';\n`,
  'domain/UserProfile.ts': `export interface UserProfile {\n  firstName: string;\n  lastName: string;\n  phone?: string;\n  preferences?: Record<string, any>;\n}\n`,
  'domain/User.ts': `import { UserId } from './UserId';\nimport { UserRole } from './UserRole';\nimport { UserProfile } from './UserProfile';\n\nexport interface User {\n  id: UserId;\n  email: string;\n  role: UserRole;\n  profile: UserProfile;\n  createdAt: Date;\n  updatedAt: Date;\n  isActive: boolean;\n}\n`,
  'domain/user-rules.ts': `import { User } from './User';\n\nexport function canDeactivateUser(user: User): boolean {\n  return user.isActive;\n}\n`,
  'contracts/UserSummary.ts': `import { UserId } from '../domain/UserId';\nimport { UserRole } from '../domain/UserRole';\n\nexport interface UserSummary {\n  id: UserId;\n  email: string;\n  role: UserRole;\n  name: string;\n}\n`,
  'contracts/UserProfileResponse.ts': `import { UserProfile } from '../domain/UserProfile';\n\nexport interface UserProfileResponse {\n  profile: UserProfile;\n}\n`,
  'ports/UserRepository.ts': `import { User } from '../domain/User';\nimport { UserId } from '../domain/UserId';\n\nexport interface UserRepository {\n  findById(id: UserId): Promise<User | null>;\n  findByEmail(email: string): Promise<User | null>;\n  save(user: User): Promise<void>;\n  update(user: User): Promise<void>;\n}\n`,
  'application/get-user.ts': `import { UserRepository } from '../ports/UserRepository';\nimport { UserId } from '../domain/UserId';\nimport { User } from '../domain/User';\n\nexport async function getUser(repo: UserRepository, id: UserId): Promise<User | null> {\n  return repo.findById(id);\n}\n`,
  'application/update-profile.ts': `import { UserRepository } from '../ports/UserRepository';\nimport { User } from '../domain/User';\nimport { UserProfile } from '../domain/UserProfile';\n\nexport async function updateProfile(repo: UserRepository, user: User, profile: Partial<UserProfile>): Promise<void> {\n  user.profile = { ...user.profile, ...profile };\n  user.updatedAt = new Date();\n  await repo.update(user);\n}\n`,
  'application/deactivate-user.ts': `import { UserRepository } from '../ports/UserRepository';\nimport { User } from '../domain/User';\nimport { canDeactivateUser } from '../domain/user-rules';\n\nexport async function deactivateUser(repo: UserRepository, user: User): Promise<void> {\n  if (!canDeactivateUser(user)) throw new Error('User cannot be deactivated');\n  user.isActive = false;\n  user.updatedAt = new Date();\n  await repo.update(user);\n}\n`,
  'application/complete-profile.ts': `import { UserRepository } from '../ports/UserRepository';\nimport { User } from '../domain/User';\n\nexport async function completeProfile(repo: UserRepository, user: User): Promise<void> {\n  // Implementation here\n  await repo.update(user);\n}\n`
};

for (const [file, content] of Object.entries(files)) {
  const fullPath = path.join(userDir, file);
  fs.writeFileSync(fullPath, content);
}
console.log('User capability files generated');

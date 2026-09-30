import { UserRepository } from '../ports/UserRepository';
import { User } from '../domain/User';

export async function completeProfile(repo: UserRepository, user: User): Promise<void> {
  // Implementation here
  await repo.update(user);
}

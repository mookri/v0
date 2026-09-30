import { UserRepository } from '@navdhya/user/src/ports/UserRepository';
import { User } from '@navdhya/user/src/domain/User';

export async function completeProfile(repo: UserRepository, user: User): Promise<void> {
  // Implementation here
  await repo.update(user);
}

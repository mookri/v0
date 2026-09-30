import { UserRepository } from '@navdhya/user/src/ports/UserRepository';
import { User } from '@navdhya/user/src/domain/User';
import { canDeactivateUser } from '@navdhya/user/src/domain/user-rules';

export async function deactivateUser(repo: UserRepository, user: User): Promise<void> {
  if (!canDeactivateUser(user)) throw new Error('User cannot be deactivated');
  user.isActive = false;
  user.updatedAt = new Date();
  await repo.update(user);
}

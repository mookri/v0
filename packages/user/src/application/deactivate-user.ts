import { UserRepository } from '../ports/UserRepository';
import { User } from '../domain/User';
import { canDeactivateUser } from '../domain/user-rules';

export async function deactivateUser(repo: UserRepository, user: User): Promise<void> {
  if (!canDeactivateUser(user)) throw new Error('User cannot be deactivated');
  user.isActive = false;
  user.updatedAt = new Date();
  await repo.update(user);
}

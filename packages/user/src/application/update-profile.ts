import { UserRepository } from '@navdhya/user/src/ports/UserRepository';
import { User } from '@navdhya/user/src/domain/User';
import { UserProfile } from '@navdhya/user/src/domain/UserProfile';

export async function updateProfile(repo: UserRepository, user: User, profile: Partial<UserProfile>): Promise<void> {
  user.profile = { ...user.profile, ...profile };
  user.updatedAt = new Date();
  await repo.update(user);
}

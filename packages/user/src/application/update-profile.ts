import { UserRepository } from '../ports/UserRepository';
import { User } from '../domain/User';
import { UserProfile } from '../domain/UserProfile';

export async function updateProfile(repo: UserRepository, user: User, profile: Partial<UserProfile>): Promise<void> {
  user.profile = { ...user.profile, ...profile };
  user.updatedAt = new Date();
  await repo.update(user);
}

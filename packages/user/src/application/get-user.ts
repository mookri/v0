import { UserRepository } from '../ports/UserRepository';
import { UserId } from '../domain/UserId';
import { User } from '../domain/User';

export async function getUser(repo: UserRepository, id: UserId): Promise<User | null> {
  return repo.findById(id);
}

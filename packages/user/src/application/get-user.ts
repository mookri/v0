import { UserRepository } from '@navdhya/user/src/ports/UserRepository';
import { UserId } from '@navdhya/user/src/domain/UserId';
import { User } from '@navdhya/user/src/domain/User';

export async function getUser(repo: UserRepository, id: UserId): Promise<User | null> {
  return repo.findById(id);
}

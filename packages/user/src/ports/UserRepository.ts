import { User } from '@navdhya/user/src/domain/User';
import { UserId } from '@navdhya/user/src/domain/UserId';

export interface UserRepository {
  findById(id: UserId): Promise<User | null>;
  findByEmail(email: string): Promise<User | null>;
  save(user: User): Promise<void>;
  update(user: User): Promise<void>;
}

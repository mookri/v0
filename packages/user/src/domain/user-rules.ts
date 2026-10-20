import { User } from './User';

export function canDeactivateUser(user: User): boolean {
  return user.isActive;
}

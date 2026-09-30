import { UserId } from '../domain/UserId';
import { UserRole } from '../domain/UserRole';

export interface UserSummary {
  id: UserId;
  email: string;
  role: UserRole;
  name: string;
}

import { UserId } from '@navdhya/user/src/domain/UserId';
import { UserRole } from '@navdhya/user/src/domain/UserRole';

export interface UserSummary {
  id: UserId;
  email: string;
  role: UserRole;
  name: string;
}

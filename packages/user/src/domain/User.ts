import { UserId } from './UserId';
import { UserRole } from './UserRole';
import { UserProfile } from './UserProfile';

export interface User {
  id: UserId;
  email: string;
  role: UserRole;
  profile: UserProfile;
  createdAt: Date;
  updatedAt: Date;
  isActive: boolean;
}

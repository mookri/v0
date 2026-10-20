import { PanditId } from './PanditId';
import { PanditProfile } from './PanditProfile';
import { VerificationStatus } from './VerificationStatus';

export interface Pandit {
  id: PanditId;
  userId: string; // Reference to User Capability
  profile: PanditProfile;
  status: VerificationStatus;
  rating: number;
  totalBookings: number;
}

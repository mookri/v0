import { PanditId } from '../domain/PanditId';
import { VerificationStatus } from '../domain/VerificationStatus';

export interface PanditSummary {
  id: PanditId;
  name: string; // Joined from User profile via app layer
  status: VerificationStatus;
  rating: number;
  expertise: string[];
}

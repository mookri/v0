import { PanditId } from '@navdhya/pandit/src/domain/PanditId';
import { VerificationStatus } from '@navdhya/pandit/src/domain/VerificationStatus';

export interface PanditSummary {
  id: PanditId;
  name: string; // Joined from User profile via app layer
  status: VerificationStatus;
  rating: number;
  expertise: string[];
}

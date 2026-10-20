import { Expertise } from './Expertise';

export interface PanditProfile {
  bio: string;
  languages: string[];
  expertise: Expertise[];
  location: string;
}

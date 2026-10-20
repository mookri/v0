export interface UserProfile {
  firstName: string;
  lastName: string;
  phone?: string;
  preferences?: Record<string, any>;
}

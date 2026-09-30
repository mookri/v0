import { CustomerProfile } from '@navdhya/customer-profile/src/domain/profile';
import { Preference, InferredPreference } from '@navdhya/customer-profile/src/domain/preferences';
import { SavedItem } from '@navdhya/customer-profile/src/domain/saved';

export interface ProfileRepository {
  get(customerId: string): Promise<CustomerProfile | null>;
  save(profile: CustomerProfile, expectedVersion: number): Promise<boolean>;
}

export interface PreferenceRepository {
  list(customerId: string): Promise<{ explicit: Preference[]; inferred: InferredPreference[] }>;
  upsert(preference: Preference, expectedVersion: number): Promise<boolean>;
}

export interface SavedRepository {
  list(customerId: string): Promise<SavedItem[]>;
  save(item: SavedItem): Promise<boolean>;
  unsave(customerId: string, entityType: string, entityId: string, listId?: string): Promise<boolean>;
}

export type Customer360Audience = 'CUSTOMER_SELF' | 'OPERATIONS' | 'SUPPORT' | 'PARTNER_CONTEXT';

export interface CustomerSelfView {
  customerId: string;
  profile: CustomerProfile | null;
  preferences: Preference[];
  savedCount: { pujas: number; pandits: number };
  lifecycle: string;
  projectionVersion: string;
  generatedAt: string;
}

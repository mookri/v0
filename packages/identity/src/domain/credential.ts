export type CredentialType = 'otp' | 'password' | 'passkey' | 'oauth';

export interface CredentialRef {
  identityId: string;
  type: CredentialType;
  verified: boolean;
}

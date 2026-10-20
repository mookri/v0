import { Identity } from '@navdhya/identity/src/domain/identity';
import { Session, SessionAudience } from '@navdhya/identity/src/domain/session';

export interface IdentityRepository {
  findById(id: string): Promise<Identity | null>;
  save(identity: Identity): Promise<void>;
}

export interface SessionRecord extends Session {}

export interface SessionStore {
  create(session: Session): Promise<void>;
  get(id: string): Promise<Session | null>;
  revoke(id: string, revokedAt: string): Promise<void>;
  rotate(oldId: string, next: Session): Promise<void>;
}

export interface CredentialVerifier {
  verify(identityId: string, proof: unknown): Promise<boolean>;
}

export interface HandoffStore {
  save(record: {
    id: string;
    subject: string;
    source: SessionAudience;
    audience: SessionAudience;
    codeHash: string;
    expiresAt: string;
    returnPath?: string;
    nonce: string;
  }): Promise<void>;
  consume(codeHash: string, usedAt: string): Promise<{
    id: string;
    subject: string;
    source: SessionAudience;
    audience: SessionAudience;
    codeHash: string;
    expiresAt: string;
    usedAt?: string;
    returnPath?: string;
    nonce: string;
  } | null>;
}

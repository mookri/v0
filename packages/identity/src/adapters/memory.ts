import { Session, SessionAudience } from '@navdhya/identity/src/domain/session';
import { Identity } from '@navdhya/identity/src/domain/identity';
import { IdentityRepository, SessionStore } from '@navdhya/identity/src/ports/stores';

export class MemoryIdentityRepository implements IdentityRepository {
  private readonly map = new Map<string, Identity>();

  async findById(id: string): Promise<Identity | null> {
    return this.map.get(id) ?? null;
  }

  async save(identity: Identity): Promise<void> {
    this.map.set(identity.id, identity);
  }
}

export class MemorySessionStore implements SessionStore {
  private readonly map = new Map<string, Session>();

  async create(session: Session): Promise<void> {
    this.map.set(session.id, session);
  }

  async get(id: string): Promise<Session | null> {
    return this.map.get(id) ?? null;
  }

  async revoke(id: string, revokedAt: string): Promise<void> {
    const s = this.map.get(id);
    if (s) this.map.set(id, { ...s, revokedAt });
  }

  async rotate(oldId: string, next: Session): Promise<void> {
    const s = this.map.get(oldId);
    if (s) this.map.set(oldId, { ...s, revokedAt: next.issuedAt });
    this.map.set(next.id, next);
  }
}

export interface HandoffRecord {
  codeHash: string;
  subject: string;
  source: SessionAudience;
  audience: SessionAudience;
  expiresAt: string;
  usedAt?: string;
  returnPath?: string;
  nonce: string;
  [key: string]: unknown;
}

export class MemoryHandoffStore {
  private readonly map = new Map<string, HandoffRecord>();

  async save(record: HandoffRecord): Promise<void> {
    this.map.set(record.codeHash, record);
  }

  async consume(codeHash: string, usedAt: string): Promise<HandoffRecord | null> {
    const record = this.map.get(codeHash);
    if (!record || record.usedAt || record.expiresAt <= usedAt) return null;
    const next: HandoffRecord = { ...record, usedAt };
    this.map.set(codeHash, next);
    return next;
  }
}

export function audienceFor(audience: SessionAudience): SessionAudience {
  return audience;
}
